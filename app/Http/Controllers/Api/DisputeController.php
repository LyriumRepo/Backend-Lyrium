<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddDisputeMessageRequest;
use App\Http\Requests\CreateDisputeRequest;
use App\Http\Requests\ResolveDisputeRequest;
use App\Http\Resources\DisputeMessageResource;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

final class DisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService $disputeService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $disputes = $this->disputeService->getAllDisputes(
            status: $request->query('status'),
            priority: $request->query('priority'),
            assignedTo: $request->query('assigned_to'),
            perPage: (int) $request->query('per_page', 15)
        );

        return DisputeResource::collection($disputes);
    }

    public function store(CreateDisputeRequest $request): JsonResponse
    {
        $dispute = $this->disputeService->create($request->validated());

        return (new DisputeResource($dispute))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): DisputeResource
    {
        $dispute = $this->disputeService->findOrFail($id);

        return new DisputeResource($dispute);
    }

    public function userDisputes(Request $request): AnonymousResourceCollection
    {
        $disputes = $this->disputeService->getUserDisputes(
            userId: $request->user()->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return DisputeResource::collection($disputes);
    }

    public function storeDisputes(Request $request): AnonymousResourceCollection
    {
        $store = $request->user()->stores()->firstOrFail();

        $disputes = $this->disputeService->getStoreDisputes(
            storeId: $store->id,
            perPage: (int) $request->query('per_page', 15)
        );

        return DisputeResource::collection($disputes);
    }

    public function orderDisputes(int $orderId): AnonymousResourceCollection
    {
        $disputes = $this->disputeService->getOrderDisputes($orderId);

        return DisputeResource::collection($disputes);
    }

    public function addMessage(int $id, AddDisputeMessageRequest $request): DisputeMessageResource
    {
        $message = $this->disputeService->addMessage(
            disputeId: $id,
            userId: $request->user()->id,
            message: $request->validated('message')
        );

        return new DisputeMessageResource($message->load('user', 'attachments'));
    }

    public function assign(int $id, Request $request): DisputeResource
    {
        $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $dispute = $this->disputeService->assign(
            disputeId: $id,
            adminUserId: $request->validated('assigned_to')
        );

        return new DisputeResource($dispute);
    }

    public function updateStatus(int $id, Request $request): DisputeResource
    {
        $request->validate([
            'status' => ['required', 'string', Rule::in(Dispute::STATUSES)],
        ]);

        $dispute = $this->disputeService->updateStatus(
            disputeId: $id,
            status: $request->validated('status')
        );

        return new DisputeResource($dispute);
    }

    public function resolve(int $id, ResolveDisputeRequest $request): DisputeResource
    {
        $dispute = $this->disputeService->resolve(
            disputeId: $id,
            resolution: $request->validated('resolution'),
            notes: $request->validated('resolution_notes'),
            refundAmount: $request->validated('refund_amount')
        );

        return new DisputeResource($dispute);
    }

    public function close(int $id): DisputeResource
    {
        $dispute = $this->disputeService->close($id);

        return new DisputeResource($dispute);
    }

    public function cancel(int $id): DisputeResource
    {
        $dispute = $this->disputeService->cancel($id);

        return new DisputeResource($dispute);
    }
}
