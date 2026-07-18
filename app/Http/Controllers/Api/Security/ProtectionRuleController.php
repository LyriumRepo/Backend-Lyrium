<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreProtectionRuleRequest;
use App\Http\Requests\Security\UpdateProtectionRuleRequest;
use App\Http\Resources\Security\ProtectionRuleResource;
use App\Models\ProtectionRule;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProtectionRuleController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProtectionRule::query()
            ->with('creator');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('priority')->orderBy('name');

        $rules = $query->get();

        $activeCount = ProtectionRule::where('status', ProtectionRule::STATUS_ACTIVE)->count();

        return $this->success([
            'items' => ProtectionRuleResource::collection($rules),
            'active_count' => $activeCount,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $rule = ProtectionRule::with('creator')->findOrFail($id);

        return $this->success(new ProtectionRuleResource($rule));
    }

    public function store(StoreProtectionRuleRequest $request): JsonResponse
    {
        $rule = DB::transaction(function () use ($request): ProtectionRule {
            $rule = ProtectionRule::create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            $this->auditService->record(
                event: 'security.protection.rule.created',
                module: 'security',
                description: "Regla de protección creada: {$rule->name} ({$rule->type})",
                severity: 'info',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'type' => $rule->type,
                    'created_by' => $request->user()->id,
                ],
            );

            $request->attributes->set('_audit_recorded', true);

            return $rule;
        });

        return $this->created(new ProtectionRuleResource($rule), 'Regla de protección creada correctamente.');
    }

    public function update(UpdateProtectionRuleRequest $request, int $id): JsonResponse
    {
        $rule = ProtectionRule::findOrFail($id);

        $data = DB::transaction(function () use ($request, $rule): ProtectionRule {
            $oldStatus = $rule->status;
            $rule->update($request->validated());

            $this->auditService->record(
                event: 'security.protection.rule.updated',
                module: 'security',
                description: "Regla de protección actualizada: {$rule->name}",
                severity: 'warning',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'previous_status' => $oldStatus,
                    'new_status' => $rule->status,
                ],
            );

            $request->attributes->set('_audit_recorded', true);

            return $rule;
        });

        return $this->success(new ProtectionRuleResource($data), 'Regla de protección actualizada.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $rule = ProtectionRule::findOrFail($id);

        DB::transaction(function () use ($request, $rule): void {
            $name = $rule->name;
            $rule->delete();

            $this->auditService->record(
                event: 'security.protection.rule.deleted',
                module: 'security',
                description: "Regla de protección eliminada: {$name}",
                severity: 'warning',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'name' => $name,
                    'rule_id' => $rule->id,
                ],
            );

            $request->attributes->set('_audit_recorded', true);
        });

        return $this->success(null, 'Regla de protección eliminada.');
    }

    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $rule = ProtectionRule::findOrFail($id);

        $newStatus = $rule->status === ProtectionRule::STATUS_ACTIVE
            ? ProtectionRule::STATUS_INACTIVE
            : ProtectionRule::STATUS_ACTIVE;

        $rule = DB::transaction(function () use ($rule, $newStatus, $request): ProtectionRule {
            $rule->update(['status' => $newStatus]);

            $this->auditService->record(
                event: 'security.protection.rule.updated',
                module: 'security',
                description: "Regla de protección {$rule->name}: {$newStatus}",
                severity: 'info',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'status' => $newStatus,
                ],
            );

            $request->attributes->set('_audit_recorded', true);

            return $rule;
        });

        return $this->success(new ProtectionRuleResource($rule), "Regla {$newStatus} correctamente.");
    }
}
