<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceHold;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceHoldController extends Controller
{
    private const HOLD_MINUTES = 15;

    /**
     * GET /api/cart/service-holds?cart_token=...
     * Returns active (non-expired) holds for the given cart token.
     */
    public function index(Request $request): JsonResponse
    {
        $cartToken = $request->query('cart_token', '');

        if (! $cartToken) {
            return response()->json(['holds' => []]);
        }

        // Clean up expired holds for this token while we're here
        ServiceHold::where('cart_token', $cartToken)
            ->where('expires_at', '<=', now())
            ->delete();

        $holds = ServiceHold::where('cart_token', $cartToken)->get();

        return response()->json([
            'holds' => $holds->map(fn ($h) => $this->formatHold($h)),
        ]);
    }

    /**
     * POST /api/cart/add-service
     * Creates a temporary hold for a service booking slot.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id'       => 'required|integer|exists:services,id',
            'specialist_id'    => 'required|integer|exists:specialists,id',
            'schedule_id'      => 'nullable|integer|exists:service_schedules,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'start_time'       => 'required|string|max:10',
            'customer_notes'   => 'nullable|string|max:1000',
            'cart_token'       => 'required|string|max:128',
            'service_address'  => 'nullable|string|max:500',
        ]);

        $service    = Service::findOrFail($data['service_id']);
        $specialist = Specialist::findOrFail($data['specialist_id']);

        $hold = ServiceHold::create([
            'cart_token'       => $data['cart_token'],
            'service_id'       => $service->id,
            'service_name'     => $service->name,
            'service_price'    => $service->price,
            'service_image'    => $service->image,
            'specialist_id'    => $specialist->id,
            'specialist_name'  => trim("{$specialist->nombres} {$specialist->apellidos}"),
            'schedule_id'      => $data['schedule_id'] ?? null,
            'appointment_date' => $data['appointment_date'],
            'start_time'       => $data['start_time'],
            'customer_notes'   => $data['customer_notes'] ?? null,
            'service_address'  => $data['service_address'] ?? null,
            'expires_at'       => now()->addMinutes(self::HOLD_MINUTES),
        ]);

        return response()->json(['hold' => $this->formatHold($hold)], 201);
    }

    /**
     * PATCH /api/cart/service-holds/{id}
     * Update the service_address on an existing hold.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'cart_token'      => 'required|string|max:128',
            'service_address' => 'required|string|max:500',
        ]);

        $hold = ServiceHold::where('id', $id)
            ->where('cart_token', $data['cart_token'])
            ->firstOrFail();

        $hold->update(['service_address' => $data['service_address']]);

        return response()->json([
            'hold' => ['id' => $hold->id, 'service_address' => $hold->service_address],
        ]);
    }

    /**
     * DELETE /api/cart/service-holds/{id}?cart_token=...
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cartToken = $request->query('cart_token', '');

        ServiceHold::where('id', $id)
            ->where('cart_token', $cartToken)
            ->delete();

        return response()->json(['success' => true]);
    }

    private function formatHold(ServiceHold $hold): array
    {
        return [
            'id'               => $hold->id,
            'service_id'       => $hold->service_id,
            'service_name'     => $hold->service_name,
            'service_price'    => (float) $hold->service_price,
            'service_image'    => $hold->service_image,
            'specialist_id'    => $hold->specialist_id,
            'specialist_name'  => $hold->specialist_name,
            'schedule_id'      => $hold->schedule_id,
            'appointment_date' => $hold->appointment_date?->format('Y-m-d'),
            'start_time'       => $hold->start_time,
            'customer_notes'   => $hold->customer_notes,
            'service_address'  => $hold->service_address,
            'expires_at'       => $hold->expires_at->toIso8601String(),
            'seconds_remaining' => $hold->secondsRemaining(),
        ];
    }
}
