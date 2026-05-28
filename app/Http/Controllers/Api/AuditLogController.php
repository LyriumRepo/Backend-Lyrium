<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     * Log de auditoría técnica — sólo lectura, sólo administradores.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        // Filtro por módulo: suppliers | expenses | operational_roles | users
        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        // Filtro por tipo de evento: created | updated | deleted | login | ban
        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        // Filtro por usuario (ID)
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        // Rango de fechas
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Búsqueda en descripción
        if ($search = $request->query('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $logs    = $query->paginate($perPage);

        return response()->json([
            'data'       => AuditLogResource::collection($logs),
            'pagination' => [
                'page'       => $logs->currentPage(),
                'perPage'    => $logs->perPage(),
                'total'      => $logs->total(),
                'totalPages' => $logs->lastPage(),
                'hasMore'    => $logs->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/audit-logs/modules
     * Lista los módulos disponibles para el selector del filtro.
     */
    public function modules(): JsonResponse
    {
        $modules = AuditLog::query()
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return response()->json($modules);
    }

    /**
     * GET /api/audit-logs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::findOrFail($id);

        return response()->json(new AuditLogResource($log));
    }
}
