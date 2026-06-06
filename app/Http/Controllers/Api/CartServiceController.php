<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceSlotHold;
use App\Models\Specialist;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService,
    ) {}

    /**
     * Add a service slot hold to the cart.
     * POST /api/cart/add-service
     */
    public function addServiceHold(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'specialist_id' => ['required', 'exists:specialists,id'],
            'schedule_id' => ['nullable', 'exists:service_schedules,id'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'service_address' => ['nullable', 'string', 'max:500'],
            'cart_token' => ['required', 'string', 'min:8'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $specialist = Specialist::findOrFail($data['specialist_id']);

        // Verify the specialist belongs to this service
        if (! $service->specialists()->where('specialist_id', $specialist->id)->exists()) {
            return response()->json(['message' => 'El especialista no pertenece a este servicio.'], 422);
        }

        // Check for existing active booking on this slot
        $appointmentDateTime = \Carbon\Carbon::parse(
            $data['appointment_date'].' '.$data['start_time']
        );
        $existingBooking = ServiceBooking::where('service_id', $service->id)
            ->where('specialist_id', $specialist->id)
            ->where('appointment_date', $appointmentDateTime)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();

        if ($existingBooking) {
            return response()->json(['message' => 'Este horario ya está reservado.'], 409);
        }

        // Check for existing active hold (by other carts)
        $existingHold = ServiceSlotHold::where('service_id', $service->id)
            ->where('specialist_id', $specialist->id)
            ->where('appointment_date', $data['appointment_date'])
            ->where('start_time', $data['start_time'])
            ->where('cart_token', '!=', $data['cart_token'])
            ->active()
            ->exists();

        if ($existingHold) {
            return response()->json(['message' => 'Este horario está siendo reservado por otro usuario.'], 409);
        }

        // Release any existing hold for this cart on this same slot (idempotency)
        ServiceSlotHold::where('cart_token', $data['cart_token'])
            ->where('service_id', $service->id)
            ->where('specialist_id', $specialist->id)
            ->where('appointment_date', $data['appointment_date'])
            ->where('start_time', $data['start_time'])
            ->delete();

        // Create hold (15 minutes)
        $hold = ServiceSlotHold::create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'schedule_id' => $data['schedule_id'] ?? null,
            'appointment_date' => $data['appointment_date'],
            'start_time' => $data['start_time'],
            'customer_notes' => $data['customer_notes'] ?? null,
            'service_address' => $data['service_address'] ?? null,
            'cart_token' => $data['cart_token'],
            'expires_at' => now()->addMinutes(15),
        ]);

        return response()->json([
            'hold' => [
                'id' => $hold->id,
                'service_id' => $hold->service_id,
                'service_name' => $service->name,
                'service_price' => $service->finalPrice(),
                'service_image' => $service->image,
                'specialist_id' => $specialist->id,
                'specialist_name' => trim($specialist->nombres.' '.$specialist->apellidos),
                'schedule_id' => $hold->schedule_id,
                'appointment_date' => $hold->appointment_date->format('Y-m-d'),
                'start_time' => $hold->start_time,
                'customer_notes' => $hold->customer_notes,
                'service_address' => $hold->service_address,
                'expires_at' => $hold->expires_at->toIso8601String(),
                'seconds_remaining' => now()->diffInSeconds($hold->expires_at, false),
            ],
        ], 201);
    }

    /**
     * Verify all active holds for a cart token.
     * GET /api/cart/service-holds?cart_token=xxx
     */
    public function verifyHolds(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string', 'min:8'],
        ]);

        $holds = ServiceSlotHold::with(['service', 'specialist'])
            ->forCart($data['cart_token'])
            ->get();

        return response()->json([
            'holds' => $holds->map(fn (ServiceSlotHold $h) => [
                'id' => $h->id,
                'service_id' => $h->service_id,
                'service_name' => $h->service?->name ?? '',
                'service_price' => (float) ($h->service?->finalPrice() ?? 0),
                'service_image' => $h->service?->image,
                'schedule_id' => $h->schedule_id,
                'specialist_id' => $h->specialist_id,
                'specialist_name' => $h->specialist ? trim($h->specialist->nombres.' '.$h->specialist->apellidos) : '',
                'appointment_date' => $h->appointment_date->format('Y-m-d'),
                'start_time' => $h->start_time,
                'customer_notes' => $h->customer_notes,
                'service_address' => $h->service_address,
                'expires_at' => $h->expires_at->toIso8601String(),
                'seconds_remaining' => max(0, now()->diffInSeconds($h->expires_at, false)),
            ]),
        ]);
    }

    /**
     * Remove a specific service hold.
     * DELETE /api/cart/service-holds/{holdId}?cart_token=xxx
     */
    public function removeServiceHold(Request $request, int $holdId): JsonResponse
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string', 'min:8'],
        ]);

        $hold = ServiceSlotHold::where('id', $holdId)
            ->where('cart_token', $data['cart_token'])
            ->first();

        if (! $hold) {
            return response()->json(['message' => 'Hold no encontrado.'], 404);
        }

        $hold->delete();

        return response()->json(['message' => 'Hold liberado.']);
    }

    public function updateServiceHold(Request $request, int $holdId): JsonResponse
    {
        $data = $request->validate([
            'cart_token' => ['required', 'string', 'min:8'],
            'service_address' => ['nullable', 'string', 'max:500'],
        ]);

        $hold = ServiceSlotHold::where('id', $holdId)
            ->where('cart_token', $data['cart_token'])
            ->first();

        if (! $hold) {
            return response()->json(['message' => 'Hold no encontrado.'], 404);
        }

        if (array_key_exists('service_address', $data)) {
            $hold->service_address = $data['service_address'];
        }
        $hold->save();

        return response()->json(['hold' => [
            'id' => $hold->id,
            'service_address' => $hold->service_address,
        ]]);
    }
}
