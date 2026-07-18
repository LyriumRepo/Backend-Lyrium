<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\AlertActionRequest;
use App\Http\Resources\Security\SecurityAlertResource;
use App\Models\SecurityAlert;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SecurityAlertController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SecurityAlert::query()
            ->with('resolver');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('sort')) {
            [$field, $dir] = explode(',', $request->input('sort', 'created_at,desc'));
            $query->orderBy($field, $dir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $alerts = $query->paginate($perPage);

        $activeCount = SecurityAlert::where('status', SecurityAlert::STATUS_OPEN)->count();

        return $this->success([
            'items' => SecurityAlertResource::collection($alerts),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
            'active_count' => $activeCount,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $alert = SecurityAlert::with('resolver', 'auditLog')->findOrFail($id);

        return $this->success(new SecurityAlertResource($alert));
    }

    public function dismiss(AlertActionRequest $request, int $id): JsonResponse
    {
        $alert = SecurityAlert::where('status', SecurityAlert::STATUS_OPEN)->findOrFail($id);

        $alert->update([
            'status' => SecurityAlert::STATUS_DISMISSED,
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        $this->auditService->record(
            event: 'security.alert.dismissed',
            module: 'security',
            description: "Alerta #{$id} descartada: {$alert->title}",
            severity: 'info',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'alert_id' => $alert->id,
                'alert_type' => $alert->type,
                'resolved_by' => $request->user()->id,
            ],
        );

        $request->attributes->set('_audit_recorded', true);

        return $this->success(new SecurityAlertResource($alert), 'Alerta descartada correctamente.');
    }

    public function resolve(AlertActionRequest $request, int $id): JsonResponse
    {
        $alert = SecurityAlert::whereIn('status', [
            SecurityAlert::STATUS_OPEN,
            SecurityAlert::STATUS_DISMISSED,
        ])->findOrFail($id);

        $alert->update([
            'status' => SecurityAlert::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        $this->auditService->record(
            event: 'security.alert.resolved',
            module: 'security',
            description: "Alerta #{$id} resuelta: {$alert->title}",
            severity: 'info',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'alert_id' => $alert->id,
                'alert_type' => $alert->type,
                'resolved_by' => $request->user()->id,
            ],
        );

        $request->attributes->set('_audit_recorded', true);

        return $this->success(new SecurityAlertResource($alert), 'Alerta resuelta correctamente.');
    }
}
