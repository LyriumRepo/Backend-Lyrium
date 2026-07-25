<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\NewBookingReceived;
use App\Events\ServiceStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceBookingResource;
use App\Http\Resources\ServiceResource;
use App\Models\OrderServiceItem;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use App\Notifications\ServicePendingReviewNotification;
use App\Notifications\ServiceStatusNotification;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function sellerServices(Request $request): JsonResponse
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
            'data' => ServiceResource::collection($services->items()),
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

        $data = $request->validated();
        $data['status'] = Service::STATUS_PENDING_REVIEW;

        $service = $this->serviceService->createForStore(
            storeId: $store->id,
            data: $data
        );
        $service->load(['schedules', 'category.parent', 'store', 'specialists']);

        // Notificar a administradores: servicio pendiente de revisión
        $admins = User::role('administrator')->get();
        $notification = new ServicePendingReviewNotification($service);
        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

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
        $hasAccess = $user->ownedStores()->where('stores.id', $service->store_id)->exists()
            || $user->stores()->where('stores.id', $service->store_id)->exists();

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

        $hasAccess = $user->ownedStores()->where('stores.id', $service->store_id)->exists()
            || $user->stores()->where('stores.id', $service->store_id)->exists();

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este servicio',
            ], 403);
        }

        $service = $this->serviceService->update($id, $request->validated());

        $service->load(['schedules', 'category.parent', 'store', 'specialists']);

        return response()->json(new ServiceResource($service));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = Service::with(['store', 'category']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $services = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'price' => (string) $service->price,
                'status' => $service->status,
                'rejection_reason' => $service->rejection_reason,
                'reviewed_at' => $service->reviewed_at?->toIso8601String(),
                'created_at' => $service->created_at?->toIso8601String(),
                'store' => $service->relationLoaded('store') && $service->store
                    ? [
                        'id' => $service->store->id,
                        'name' => $service->store->store_name ?? $service->store->trade_name ?? '',
                        'slug' => $service->store->slug ?? '',
                    ]
                    : null,
                'category' => $service->relationLoaded('category') && $service->category
                    ? [
                        'id' => $service->category->id,
                        'name' => $service->category->name,
                    ]
                    : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $services->currentPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
                'total_pages' => $services->lastPage(),
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:approved,rejected,pending_review',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validated['status'] === 'rejected' && empty($validated['reason'])) {
            return response()->json([
                'message' => 'El motivo de rechazo es obligatorio.',
                'errors' => ['reason' => ['Debes indicar el motivo del rechazo.']],
            ], 422);
        }

        $storedStatus = $validated['status'] === 'approved' ? Service::STATUS_ACTIVE : $validated['status'];

        $service->update([
            'status' => $storedStatus,
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['reason'] : null,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        broadcast(new ServiceStatusChanged($service));

        $service->load('store.owner');

        if ($service->store && $service->store->owner) {
            try {
                $service->store->owner->notify(new ServiceStatusNotification(
                    $service,
                    $validated['status'],
                    $validated['reason'] ?? null,
                ));
            } catch (\Throwable $e) {
                Log::error('[Service] Error notificando ServiceStatus al vendedor', [
                    'service_id' => $service->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        $service->load(['schedules', 'category.parent', 'store', 'specialists']);

        return response()->json(new ServiceResource($service));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $service = $this->serviceService->findOrFail($id);

        $hasAccess = $user->ownedStores()->where('stores.id', $service->store_id)->exists()
            || $user->stores()->where('stores.id', $service->store_id)->exists();

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
        try {
            broadcast(new NewBookingReceived($booking));
        } catch (\Throwable) {
            // Real-time broadcast unavailable; data was saved successfully
        }

        return response()->json(new ServiceBookingResource($booking), 201);
    }

    public function pendingReview(Request $request, int $serviceId): JsonResponse
    {
        $booking = ServiceBooking::where('service_id', $serviceId)
            ->where('user_id', $request->user()->id)
            ->where('status', ServiceBooking::STATUS_COMPLETED)
            ->whereDoesntHave('review')
            ->latest('updated_at')
            ->first();

        return response()->json([
            'data' => $booking ? new ServiceBookingResource($booking) : null,
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
        \Log::debug('[confirmBooking] ENTRY', [
            'bookingId' => $bookingId,
            'userId' => $request->user()?->id,
            'userRoles' => $request->user()?->getRoleNames(),
        ]);

        $user = $request->user();
        $booking = $this->serviceService->confirmBooking($bookingId);

        \Log::debug('[confirmBooking] after serviceService.confirmBooking', [
            'bookingId' => $bookingId,
            'bookingStatus' => $booking->status,
            'bookingUserId' => $booking->user_id,
            'serviceId' => $booking->service_id,
            'serviceStoreId' => $booking->service?->store_id,
        ]);

        $bookingStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');

        $storeIds = $user->ownedStores()->pluck('id')
            ->concat($user->stores()->pluck('stores.id'))
            ->unique();
        $hasAccess = $storeIds->contains($bookingStoreId);

        \Log::debug('[confirmBooking] access check', [
            'hasAccess' => $hasAccess,
            'storeIds' => $storeIds->toArray(),
            'bookingStoreId' => $bookingStoreId,
            'isAdmin' => $user->hasRole('administrator'),
        ]);

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            \Log::warning('[confirmBooking] ACCESS DENIED', [
                'bookingId' => $bookingId,
                'userId' => $user->id,
                'bookingStoreId' => $bookingStoreId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta reserva',
            ], 403);
        }

        \Log::debug('[confirmBooking] SUCCESS');

        return response()->json(new ServiceBookingResource($booking));
    }

    public function cancelBooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = ServiceBooking::with('service')
            ->findOrFail($bookingId);

        $serviceStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');

        if (
            $booking->user_id !== $user->id &&
            ! $user->stores()->where('id', $serviceStoreId)->exists() &&
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
        $booking = ServiceBooking::with('service')
            ->findOrFail($bookingId);

        $serviceStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');
        $hasAccess = $user->stores()->where('id', $serviceStoreId)->exists();

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
        $store = $user->store ?? $user->stores()->where('status', 'approved')->first();

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
        \Log::debug('[completeBooking] ENTRY', [
            'bookingId' => $bookingId,
            'userId' => $request->user()?->id,
            'userRoles' => $request->user()?->getRoleNames(),
        ]);

        $user = $request->user();
        $booking = ServiceBooking::with('service.store')->findOrFail($bookingId);

        \Log::debug('[completeBooking] booking found', [
            'bookingId' => $booking->id,
            'bookingStatus' => $booking->status,
            'serviceStoreId' => $booking->service?->store_id,
        ]);

        $storeIds = $user->ownedStores()->pluck('id')
            ->concat($user->stores()->pluck('stores.id'))
            ->unique();
        $serviceStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');
        $hasAccess = $storeIds->contains($serviceStoreId);
        \Log::debug('[completeBooking] access check', [
            'hasAccess' => $hasAccess,
            'storeIds' => $storeIds->toArray(),
            'serviceStoreId' => $serviceStoreId,
            'isAdmin' => $user->hasRole('administrator'),
        ]);

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            \Log::warning('[completeBooking] ACCESS DENIED', [
                'bookingId' => $bookingId,
                'userId' => $user->id,
            ]);

            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->completeBooking($bookingId);

        \Log::debug('[completeBooking] SUCCESS', [
            'bookingId' => $booking->id,
            'newStatus' => $booking->status,
        ]);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function markOnTheWay(Request $request, int $bookingId): JsonResponse
    {
        \Log::debug('[markOnTheWay] ENTRY', [
            'bookingId' => $bookingId,
            'userId' => $request->user()?->id,
            'userRoles' => $request->user()?->getRoleNames(),
        ]);

        $user = $request->user();
        $booking = ServiceBooking::with('service.store')->findOrFail($bookingId);

        \Log::debug('[markOnTheWay] booking found', [
            'bookingId' => $booking->id,
            'bookingStatus' => $booking->status,
            'serviceStoreId' => $booking->service?->store_id,
        ]);

        $storeIds = $user->ownedStores()->pluck('id')
            ->concat($user->stores()->pluck('stores.id'))
            ->unique();
        $serviceStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');
        $hasAccess = $storeIds->contains($serviceStoreId);
        \Log::debug('[markOnTheWay] access check', [
            'hasAccess' => $hasAccess,
            'storeIds' => $storeIds->toArray(),
            'serviceStoreId' => $serviceStoreId,
            'isAdmin' => $user->hasRole('administrator'),
        ]);

        if (! $hasAccess && ! $user->hasRole('administrator')) {
            \Log::warning('[markOnTheWay] ACCESS DENIED', [
                'bookingId' => $bookingId,
                'userId' => $user->id,
                'serviceStoreId' => $booking->service?->store_id,
            ]);

            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->markAsOnTheWay($bookingId);

        \Log::debug('[markOnTheWay] SUCCESS', [
            'bookingId' => $booking->id,
            'newStatus' => $booking->status,
        ]);

        return response()->json(new ServiceBookingResource($booking));
    }

    public function confirmCompletion(Request $request, int $bookingId): JsonResponse
    {
        $booking = ServiceBooking::with('service')->findOrFail($bookingId);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = $this->serviceService->confirmCompletion($bookingId);

        return response()->json(new ServiceBookingResource($booking));
    }

    /**
     * El cliente valida la finalización de su reserva (capa aditiva:
     * no modifica el status, solo customer_validated_at + validation_source).
     */
    public function validateReceipt(
        Request $request,
        int $bookingId,
        \App\Services\ReceiptValidationService $receiptValidationService,
    ): JsonResponse {
        $booking = ServiceBooking::with('service')->findOrFail($bookingId);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($booking->status !== ServiceBooking::STATUS_COMPLETED) {
            return response()->json(['message' => 'Solo puedes validar reservas que ya fueron completadas.'], 422);
        }

        $result = $receiptValidationService->validateBooking(
            $booking,
            \App\Services\ReceiptValidationService::SOURCE_MANUAL,
        );

        if (! $result['validated']) {
            $message = $result['validation_source'] === \App\Services\ReceiptValidationService::SOURCE_AUTO_EXPIRED
                ? 'Esta reserva ya fue cerrada automáticamente por inacción.'
                : 'Esta reserva ya fue validada anteriormente.';

            return response()->json(['message' => $message], 409);
        }

        return response()->json([
            'booking' => new ServiceBookingResource($booking->fresh()->load('service')),
            'lirios_bonus' => \App\Services\LiriosService::VALIDATION_BONUS_LIRIOS,
        ]);
    }

    public function rateBooking(Request $request, int $bookingId): JsonResponse
    {
        $booking = ServiceBooking::with('service')->findOrFail($bookingId);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($booking->status !== ServiceBooking::STATUS_COMPLETED) {
            return response()->json(['message' => 'Solo se puede calificar reservas completadas'], 422);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Review::where('service_booking_id', $bookingId)->first();

        if ($existing) {
            return response()->json(['message' => 'Ya calificaste esta reserva'], 422);
        }

        $review = Review::create([
            'service_booking_id' => $bookingId,
            'specialist_id' => $booking->specialist_id,
            'user_id' => $request->user()->id,
            'product_id' => $booking->service?->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => true,
        ]);

        return response()->json($review, 201);
    }

    public function addNotes(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = ServiceBooking::with('service')
            ->findOrFail($bookingId);

        $serviceStoreId = $booking->service?->store_id
            ?? OrderServiceItem::where('service_booking_id', $bookingId)->value('store_id');
        $hasAccess = $user->stores()->where('id', $serviceStoreId)->exists();

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
    public function availableSlots(Request $request, int $id): JsonResponse
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
            Log::error('Error cargando slots: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la agenda disponible.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
