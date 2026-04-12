<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReturnRequest;
use App\Http\Resources\ReturnResource;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnService $returnService,
    ) {}

    public function myReturns(Request $request): JsonResponse
    {
        $returns = $this->returnService->getUserReturns(
            userId: $request->user()->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function store(CreateReturnRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();
        $data['user_id'] = $user->id;

        $order = \App\Models\Order::with('items')->findOrFail($data['order_id']);

        $store = $order->items()->first()?->store;

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar la tienda',
            ], 422);
        }

        $data['store_id'] = $store->id;

        $return = $this->returnService->create($data);

        return response()->json(
            new ReturnResource($return),
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $return = $this->returnService->findOrFail($id);

        return response()->json(new ReturnResource($return));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $return = $this->returnService->findOrFail($id);

        if ($return->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta devolución',
            ], 403);
        }

        $return = $this->returnService->cancel($id);

        return response()->json(new ReturnResource($return));
    }

    public function sellerReturns(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = $user->stores()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda',
            ], 403);
        }

        $returns = $this->returnService->getStoreReturns(
            storeId: $store->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $return = $this->returnService->findOrFail($id);

        if ($return->store_id !== $user->stores()->first()?->id && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta devolución',
            ], 403);
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $return = $this->returnService->approve(
            id: $id,
            notes: $request->input('notes'),
            refundAmount: $request->input('refund_amount')
        );

        return response()->json(new ReturnResource($return));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $return = $this->returnService->findOrFail($id);

        if ($return->store_id !== $user->stores()->first()?->id && ! $user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta devolución',
            ], 403);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $return = $this->returnService->reject(
            id: $id,
            reason: $request->input('reason')
        );

        return response()->json(new ReturnResource($return));
    }

    public function markReceived(int $id): JsonResponse
    {
        $return = $this->returnService->markReceived($id);

        return response()->json(new ReturnResource($return));
    }

    public function refund(int $id): JsonResponse
    {
        $return = $this->returnService->refund($id);

        return response()->json(new ReturnResource($return));
    }

    public function updateTracking(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'carrier' => ['required', 'string', 'max:50'],
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        $return = $this->returnService->updateTracking(
            id: $id,
            carrier: $request->input('carrier'),
            trackingNumber: $request->input('tracking_number')
        );

        return response()->json(new ReturnResource($return));
    }

    public function orderReturns(int $orderId): JsonResponse
    {
        $returns = $this->returnService->getOrderReturns($orderId);

        return response()->json([
            'data' => ReturnResource::collection($returns),
        ]);
    }
}
