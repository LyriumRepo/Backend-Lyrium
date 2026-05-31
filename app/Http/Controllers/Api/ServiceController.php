<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\NewBookingReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceBookingResource;
use App\Http\Resources\ServiceResource;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        // Seller/admin context: filter by their own store
        if ($request->query('store_id')) {
            $services = $this->serviceService->paginateForStore(
                storeId: (int) $request->query('store_id'),
                perPage: $perPage
            );
        } else {
            // Public listing: only active services, supports category/search filters
            $services = $this->serviceService->paginatePublic(
                filters: [
                    'category_id' => $request->query('category_id'),
                    'category_slug' => $request->query('category_slug'),
                    'search' => $request->query('search'),
                ],
                perPage: $perPage
            );
        }

        return response()->json([
            'data' => ServiceResource::collection($services->items()),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function sellerServices(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        // 1. Obtener el usuario autenticado a través del Token de Sanctum
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Debe iniciar sesión.',
            ], 401);
        }

        // 2. Resolver la tienda del vendedor utilizando las relaciones del modelo User
        // Primero intentamos con la relación directa "store" y luego con la relación N:N "stores"
        $store = $user->store ?? $user->stores()->where('status', 'approved')->first();

        // 3. Validar que el vendedor realmente posea una tienda activa
        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda registrada o aprobada en el sistema.',
            ], 403);
        }

        // 4. Capturar el parámetro de paginación de la URL (por defecto 15 ítems)
        $perPage = (int) $request->query('per_page', 15);

        // 5. Consultar a tu ServiceService la lista paginada filtrando por el ID de la tienda
        $services = $this->serviceService->paginateForStore(
            storeId: $store->id,
            perPage: $perPage
        );

        // 6. Retornar la colección de recursos estructurada bajo el formato estándar de tu API
        return response()->json([
            'data' => \App\Http\Resources\ServiceResource::collection($services->items()),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $user = $request->user();

        $store = $user->store;

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada.'], 403);
        }

        $service = $this->serviceService->createForStore(
            storeId: $store->id,
            data: $request->validated()
        );
        $service->load(['schedules', 'category', 'store']);

        return response()->json(
            new ServiceResource($service),
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $service = $this->serviceService->findOrFail($id);

        return response()->json(new ServiceResource($service));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $service = $this->serviceService->findBySlug($slug);

        return response()->json(new ServiceResource($service));
    }

    public function showMyService(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $serviceId = (int) $id;

        $service = $this->serviceService->findOrFail($serviceId);

        // Validamos si la tienda del vendedor autenticado es dueña de este servicio
        $hasAccess = $user->stores()->where('stores.id', $service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Este servicio no pertenece a tu tienda.',
            ], 403);
        }

        return response()->json(new ServiceResource($service));
    }

    public function update(UpdateServiceRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $service = $this->serviceService->findOrFail($id);

        $hasAccess = $user->stores()->where('stores.id', $service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este servicio',
            ], 403);
        }

        $service = $this->serviceService->update($id, $request->validated());

        $service->load(['schedules', 'category', 'store']);

        return response()->json(new ServiceResource($service));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $service = $this->serviceService->findOrFail($id);

        $hasAccess = $user->stores()->where('id', $service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este servicio',
            ], 403);
        }

        $this->serviceService->delete($id);

        return response()->json(null, 204);
    }

    /**public function availableSlots(Request $request, int $serviceId): JsonResponse
    {
        $date = $request->query('date');

        if (! $date) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha es requerida',
            ], 422);
        }

        $slots = $this->serviceService->getAvailableSlots($serviceId, $date);

        return response()->json([
            'data' => $slots,
            'date' => $date,
        ]);
    } */

    public function book(BookServiceRequest $request, int $serviceId): JsonResponse
    {
        $booking = $this->serviceService->book(
            serviceId: $serviceId,
            userId: $request->user()->id,
            data: $request->validated()
        );

        $booking->load(['service', 'schedule', 'user']);
        broadcast(new NewBookingReceived($booking));

        return response()->json(new ServiceBookingResource($booking), 201);
    }

    public function pendingReview(Request $request, int $serviceId): JsonResponse
    {
        $booking = \App\Models\ServiceBooking::where('service_id', $serviceId)
            ->where('user_id', $request->user()->id)
            ->where('status', \App\Models\ServiceBooking::STATUS_COMPLETED)
            ->whereDoesntHave('review')
            ->latest('updated_at')
            ->first();

        return response()->json([
            'data' => $booking ? new \App\Http\Resources\ServiceBookingResource($booking) : null,
        ]);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->serviceService->getUserBookings(
            userId: $request->user()->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ServiceBookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function confirmBooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = $this->serviceService->confirmBooking($bookingId);

        $service = $booking->service;
        $hasAccess = $user->stores()->where('stores.id', $service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta reserva',
            ], 403);
        }

        return response()->json(new ServiceBookingResource($booking));
    }

    public function cancelBooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = \App\Models\ServiceBooking::with('service')
            ->findOrFail($bookingId);

        if (
            $booking->user_id !== $user->id &&
            ! $user->stores()->where('id', $booking->service->store_id)->exists() &&
            ! $user->hasRole('administrator')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta reserva',
            ], 403);
        }

        $booking = $this->serviceService->cancelBooking($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function markNoShow(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = \App\Models\ServiceBooking::with('service')
            ->findOrFail($bookingId);

        $hasAccess = $user->stores()->where('id', $booking->service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta reserva',
            ], 403);
        }

        $booking = $this->serviceService->markAsNoShow($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function reschedule(Request $request, int $bookingId): JsonResponse
    {
        $request->validate([
            'new_datetime' => ['required', 'date', 'after:now'],
            'token' => ['required', 'string'],
        ]);

        $booking = $this->serviceService->reschedule(
            bookingId: $bookingId,
            newDateTime: $request->input('new_datetime'),
            token: $request->input('token')
        );

        return response()->json(new ServiceBookingResource($booking));
    }

    public function sellerBookings(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->stores()->where('status', 'approved')->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda aprobada',
            ], 403);
        }

        $bookings = $this->serviceService->getStoreBookings(
            storeId: $store->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ServiceBookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function completeBooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = \App\Models\ServiceBooking::with('service.store')->findOrFail($bookingId);

        $hasAccess = $user->stores()->where('id', $booking->service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->completeBooking($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function markOnTheWay(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = \App\Models\ServiceBooking::with('service.store')->findOrFail($bookingId);

        $hasAccess = $user->stores()->where('id', $booking->service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->markAsOnTheWay($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function confirmCompletion(Request $request, int $bookingId): JsonResponse
    {
        $booking = \App\Models\ServiceBooking::with('service')->findOrFail($bookingId);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->confirmCompletion($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function rateBooking(Request $request, int $bookingId): JsonResponse
    {
        $booking = \App\Models\ServiceBooking::with('service')->findOrFail($bookingId);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($booking->status !== \App\Models\ServiceBooking::STATUS_COMPLETED) {
            return response()->json(['message' => 'Solo se puede calificar reservas completadas'], 422);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = \App\Models\Review::where('service_booking_id', $bookingId)->first();

        if ($existing) {
            return response()->json(['message' => 'Ya calificaste esta reserva'], 422);
        }

        $review = \App\Models\Review::create([
            'service_booking_id' => $bookingId,
            'specialist_id' => $booking->specialist_id,
            'user_id' => $request->user()->id,
            'product_id' => $booking->service->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => true,
        ]);

        return response()->json($review, 201);
    }

    public function addNotes(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = \App\Models\ServiceBooking::with('service')
            ->findOrFail($bookingId);

        $hasAccess = $user->stores()->where('id', $booking->service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta reserva',
            ], 403);
        }

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $booking = $this->serviceService->addSellerNotes(
            bookingId: $bookingId,
            notes: $request->input('notes')
        );

        return response()->json(new ServiceBookingResource($booking));
    }

    /**
     * Obtener los intervalos disponibles reales de un especialista para un servicio y fecha.
     * GET /api/services/{id}/slots
     */
    public function availableSlots(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'specialist_id' => ['required', 'integer', 'exists:specialists,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $specialistId = (int) $request->input('specialist_id');
        $dateString = (string) $request->input('appointment_date');

        try {
            $slots = $this->serviceService->getAvailableSlots(
                serviceId: $id,
                specialistId: $specialistId,
                dateString: $dateString
            );

            // Retornamos un listado limpio de strings de horas en formato JSON
            return response()->json([
                'success' => true,
                'date' => $dateString,
                'slots' => $slots,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error cargando slots: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la agenda disponible.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
