<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperationalRoleRequest;
use App\Http\Requests\UpdateOperationalRoleRequest;
use App\Http\Resources\OperationalRoleResource;
use App\Models\AuditLog;
use App\Models\OperationalRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OperationalRoleController extends Controller
{
    /**
     * GET /api/operational-roles
     */
    public function index(Request $request): JsonResponse
    {
        $query = OperationalRole::withCount('users')->orderBy('name');

        if ($active = $request->query('active')) {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        $roles = $query->get();

        return response()->json(OperationalRoleResource::collection($roles));
    }

    /**
     * GET /api/operational-roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $role = OperationalRole::withCount('users')->findOrFail($id);

        return response()->json(new OperationalRoleResource($role));
    }

    /**
     * POST /api/operational-roles
     */
    public function store(StoreOperationalRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = OperationalRole::create([
            ...$data,
            'code' => $data['code'] ?? Str::snake($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        AuditLog::record(
            event: 'created',
            module: 'operational_roles',
            description: "Creó rol operativo «{$role->name}»",
            auditable: $role,
            newValues: $role->toArray(),
        );

        return response()->json(new OperationalRoleResource($role->loadCount('users')), 201);
    }

    /**
     * PUT /api/operational-roles/{id}
     */
    public function update(UpdateOperationalRoleRequest $request, int $id): JsonResponse
    {
        $role = OperationalRole::findOrFail($id);
        $oldData = $role->toArray();

        $role->update($request->validated());

        AuditLog::record(
            event: 'updated',
            module: 'operational_roles',
            description: "Actualizó rol operativo «{$role->name}»",
            auditable: $role,
            oldValues: $oldData,
            newValues: $role->fresh()->toArray(),
        );

        return response()->json(new OperationalRoleResource($role->fresh()->loadCount('users')));
    }

    /**
     * PUT /api/operational-roles/{id}/toggle
     * Activa / desactiva el rol sin eliminarlo.
     */
    public function toggle(int $id): JsonResponse
    {
        $role = OperationalRole::findOrFail($id);
        $role->update(['is_active' => ! $role->is_active]);

        $action = $role->is_active ? 'activó' : 'desactivó';

        AuditLog::record(
            event: 'updated',
            module: 'operational_roles',
            description: "Se {$action} el rol operativo «{$role->name}»",
            auditable: $role,
        );

        return response()->json(new OperationalRoleResource($role->loadCount('users')));
    }

    /**
     * POST /api/operational-roles/{id}/users
     * Asigna un usuario al rol operativo.
     */
    public function assignUser(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $role = OperationalRole::findOrFail($id);
        $role->users()->syncWithoutDetaching([$validated['user_id']]);

        AuditLog::record(
            event: 'assigned',
            module: 'operational_roles',
            description: "Asignó usuario #{$validated['user_id']} al rol «{$role->name}»",
            auditable: $role,
        );

        return response()->json(new OperationalRoleResource($role->loadCount('users')));
    }

    /**
     * DELETE /api/operational-roles/{id}/users/{userId}
     * Remueve un usuario del rol operativo.
     */
    public function removeUser(int $id, int $userId): JsonResponse
    {
        $role = OperationalRole::findOrFail($id);
        $role->users()->detach($userId);

        AuditLog::record(
            event: 'removed',
            module: 'operational_roles',
            description: "Removió usuario #{$userId} del rol «{$role->name}»",
            auditable: $role,
        );

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/operational-roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $role = OperationalRole::findOrFail($id);

        // Seguridad: no borrar si tiene usuarios asignados
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un rol con usuarios asignados. Desactívelo primero.',
            ], 422);
        }

        AuditLog::record(
            event: 'deleted',
            module: 'operational_roles',
            description: "Eliminó rol operativo «{$role->name}»",
            auditable: $role,
        );

        $role->delete();

        return response()->json(['success' => true]);
    }
}
