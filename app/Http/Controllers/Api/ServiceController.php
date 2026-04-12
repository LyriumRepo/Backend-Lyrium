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

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $user = $request->user();

        $store = $user->stores()->where('status', 'approved')->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda aprobada para crear servicios',
            ], 403);
        }

        $service = $this->serviceService->createForStore(
            storeId: $store->id,
            data: $request->validated()
        );

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

    public function update(UpdateServiceRequest $request, int $id): JsonResponse
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

        $service = $this->serviceService->update($id, $request->validated());

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

    public function availableSlots(Request $request, int $serviceId): JsonResponse
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
    }

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
        $hasAccess = $user->stores()->where('id', $service->store_id)->exists();

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

        if ($booking->user_id !== $user->id &&
            ! $user->stores()->where('id', $booking->service->store_id)->exists() &&
            ! $user->hasRole('administrator')) {
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
}
