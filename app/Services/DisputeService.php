<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dispute;
use App\Models\DisputeAttachment;
use App\Models\DisputeMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

final class DisputeService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function create(array $data): Dispute
    {
        $dispute = Dispute::create([
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'store_id' => $data['store_id'],
            'dispute_number' => Dispute::generateDisputeNumber(),
            'type' => $data['type'],
            'status' => Dispute::STATUS_OPEN,
            'priority' => $data['priority'] ?? Dispute::PRIORITY_MEDIUM,
            'description' => $data['description'],
            'opened_at' => now(),
        ]);

        if (! empty($data['message'])) {
            $this->addMessage($dispute->id, $data['user_id'], $data['message']);
        }

        return $dispute->fresh(['order', 'store', 'user']);
    }

    public function findOrFail(int $id): Dispute
    {
        return Dispute::query()
            ->with(['messages.user', 'attachments', 'order', 'store', 'user', 'assignedTo'])
            ->findOrFail($id);
    }

    public function addMessage(int $disputeId, int $userId, string $message, bool $isInternal = false): DisputeMessage
    {
        $dispute = $this->findOrFail($disputeId);

        if (! $dispute->canAddMessage()) {
            throw new \InvalidArgumentException('No se puede agregar mensajes a esta disputa');
        }

        return DisputeMessage::create([
            'dispute_id' => $disputeId,
            'user_id' => $userId,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);
    }

    public function addAttachment(
        int $disputeId,
        int $messageId,
        UploadedFile $file
    ): DisputeAttachment {
        $path = $file->store('disputes/attachments', 'public');

        return DisputeAttachment::create([
            'dispute_id' => $disputeId,
            'message_id' => $messageId,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    public function assign(int $disputeId, int $adminUserId): Dispute
    {
        $dispute = $this->findOrFail($disputeId);

        $dispute->update([
            'assigned_to' => $adminUserId,
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'reviewed_at' => now(),
        ]);

        return $dispute->fresh();
    }

    public function updateStatus(int $disputeId, string $status): Dispute
    {
        $dispute = $this->findOrFail($disputeId);

        $updateData = ['status' => $status];

        if ($status === Dispute::STATUS_UNDER_REVIEW) {
            $updateData['reviewed_at'] = now();
        } elseif ($status === Dispute::STATUS_RESOLVED) {
            $updateData['resolved_at'] = now();
        } elseif ($status === Dispute::STATUS_CLOSED) {
            $updateData['closed_at'] = now();
        }

        $dispute->update($updateData);

        return $dispute->fresh();
    }

    public function resolve(
        int $disputeId,
        string $resolution,
        ?string $notes = null,
        ?float $refundAmount = null
    ): Dispute {
        $dispute = $this->findOrFail($disputeId);

        $dispute->update([
            'status' => Dispute::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolution_notes' => $notes,
            'refund_amount' => $refundAmount,
            'resolved_at' => now(),
        ]);

        return $dispute->fresh();
    }

    public function close(int $disputeId): Dispute
    {
        $dispute = $this->findOrFail($disputeId);

        if (! $dispute->canBeClosed()) {
            throw new \InvalidArgumentException('Esta disputa no puede ser cerrada');
        }

        $dispute->update([
            'status' => Dispute::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return $dispute->fresh();
    }

    public function cancel(int $disputeId): Dispute
    {
        $dispute = $this->findOrFail($disputeId);

        $dispute->update([
            'status' => Dispute::STATUS_CANCELLED,
            'closed_at' => now(),
        ]);

        return $dispute->fresh();
    }

    public function getUserDisputes(int $userId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Dispute::query()
            ->where('user_id', $userId)
            ->with(['order', 'store'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStoreDisputes(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Dispute::query()
            ->where('store_id', $storeId)
            ->with(['order', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getOrderDisputes(int $orderId): Collection
    {
        return Dispute::query()
            ->where('order_id', $orderId)
            ->with(['user', 'store'])
            ->get();
    }

    public function getAllDisputes(
        ?string $status = null,
        ?string $priority = null,
        ?int $assignedTo = null,
        int $perPage = self::DEFAULT_PER_PAGE
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Dispute::query()
            ->with(['order', 'store', 'user', 'assignedTo']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        return $query->latest()->paginate($perPage);
    }
}
