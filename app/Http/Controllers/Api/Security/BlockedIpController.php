<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreBlockedIpRequest;
use App\Http\Requests\Security\UpdateBlockedIpRequest;
use App\Http\Resources\Security\BlockedIpResource;
use App\Models\BlockedIp;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BlockedIpController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = BlockedIp::query()
            ->with(['blocker', 'unblocker']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('ip_address', 'like', "%{$search}%");
        }

        if ($request->filled('sort')) {
            [$field, $dir] = explode(',', $request->input('sort', 'created_at,desc'));
            $query->orderBy($field, $dir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $ips = $query->paginate($perPage);

        return $this->success([
            'items' => BlockedIpResource::collection($ips),
            'pagination' => [
                'current_page' => $ips->currentPage(),
                'last_page' => $ips->lastPage(),
                'per_page' => $ips->perPage(),
                'total' => $ips->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $ip = BlockedIp::with(['blocker', 'unblocker'])->findOrFail($id);

        return $this->success(new BlockedIpResource($ip));
    }

    public function store(StoreBlockedIpRequest $request): JsonResponse
    {
        $data = DB::transaction(function () use ($request): BlockedIp {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $record = BlockedIp::updateOrCreate(
                ['ip_address' => $request->input('ip_address')],
                [
                    'reason' => $request->input('reason'),
                    'blocked_by' => $user->id,
                    'blocked_at' => now(),
                    'expires_at' => $request->input('expires_at'),
                    'unblocked_at' => null,
                    'unblocked_by' => null,
                    'status' => $request->input('status', BlockedIp::STATUS_BLOCKED),
                ],
            );

            $this->auditService->record(
                event: $record->status === BlockedIp::STATUS_WHITELISTED
                    ? 'security.ip.whitelisted'
                    : 'security.ip.blocked',
                module: 'security',
                description: match ($record->status) {
                    BlockedIp::STATUS_WHITELISTED => "IP {$record->ip_address} añadida a lista blanca",
                    BlockedIp::STATUS_FLAGGED => "IP {$record->ip_address} marcada como sospechosa",
                    default => "IP {$record->ip_address} bloqueada manualmente",
                },
                severity: $record->status === BlockedIp::STATUS_WHITELISTED ? 'info' : 'critical',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'blocked_ip_id' => $record->id,
                    'ip_address' => $record->ip_address,
                    'reason' => $record->reason,
                    'status' => $record->status,
                    'blocked_by' => $user->id,
                    'expires_at' => $record->expires_at?->toISOString(),
                ],
            );

            $request->attributes->set('_audit_recorded', true);

            return $record;
        });

        return $this->created(new BlockedIpResource($data), 'IP registrada correctamente.');
    }

    public function update(UpdateBlockedIpRequest $request, int $id): JsonResponse
    {
        $record = BlockedIp::findOrFail($id);

        $data = DB::transaction(function () use ($request, $record): BlockedIp {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $oldStatus = $record->status;

            $record->update($request->validated());

            if ($request->input('status') === BlockedIp::STATUS_UNBLOCKED) {
                $record->update([
                    'unblocked_at' => now(),
                    'unblocked_by' => $user->id,
                ]);

                $this->auditService->record(
                    event: 'security.ip.unblocked',
                    module: 'security',
                    description: "IP {$record->ip_address} desbloqueada manualmente",
                    severity: 'warning',
                    success: true,
                    source: AuditService::SOURCE_WEB,
                    metadata: [
                        'blocked_ip_id' => $record->id,
                        'ip_address' => $record->ip_address,
                        'unblocked_by' => $user->id,
                        'previous_status' => $oldStatus,
                    ],
                );
            } else {
                $this->auditService->record(
                    event: $request->input('status') === BlockedIp::STATUS_WHITELISTED
                        ? 'security.ip.whitelisted'
                        : 'security.ip.updated',
                    module: 'security',
                    description: "IP {$record->ip_address} actualizada: {$oldStatus} → {$record->status}",
                    severity: 'info',
                    success: true,
                    source: AuditService::SOURCE_WEB,
                    metadata: [
                        'blocked_ip_id' => $record->id,
                        'ip_address' => $record->ip_address,
                        'previous_status' => $oldStatus,
                        'new_status' => $record->status,
                    ],
                );
            }

            $request->attributes->set('_audit_recorded', true);

            return $record;
        });

        return $this->success(new BlockedIpResource($data), 'IP actualizada correctamente.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $record = BlockedIp::findOrFail($id);

        DB::transaction(function () use ($request, $record): void {
            $ip = $record->ip_address;
            $status = $record->status;

            $record->delete();

            $this->auditService->record(
                event: 'security.ip.deleted',
                module: 'security',
                description: "Registro de IP {$ip} eliminado (estado: {$status})",
                severity: 'warning',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'ip_address' => $ip,
                    'previous_status' => $status,
                ],
            );

            $request->attributes->set('_audit_recorded', true);
        });

        return $this->success(null, 'Registro de IP eliminado correctamente.');
    }
}
