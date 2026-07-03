<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     * Log de auditoría técnica — sólo lectura, sólo administradores.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        // Sorting
        $sortField = $request->query('sort');
        $sortOrder = $request->query('order', 'desc');

        if ($sortField) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        // Filtro por módulo: suppliers | expenses | operational_roles | users
        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        // Filtro por tipo de evento: created | updated | deleted | login | ban
        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        // Filtro por severidad
        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        // Filtro por dirección IP
        if ($ip = $request->query('ip')) {
            $query->where('ip_address', $ip);
        }

        // Filtro por session_id
        if ($sessionId = $request->query('session_id')) {
            $query->where('session_id', $sessionId);
        }

        // Filtro por source
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        // Filtro por success (bool)
        $successFilter = $request->query('success');
        if ($successFilter !== null) {
            $query->where('success', filter_var($successFilter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
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
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => AuditLogResource::collection($logs),
            'pagination' => [
                'page' => $logs->currentPage(),
                'perPage' => $logs->perPage(),
                'total' => $logs->total(),
                'totalPages' => $logs->lastPage(),
                'hasMore' => $logs->hasMorePages(),
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
            ->select('module', DB::raw('COUNT(DISTINCT event) as events_count'))
            ->groupBy('module')
            ->orderBy('module')
            ->get()
            ->map(fn ($item) => [
                'module' => $item->module,
                'events_count' => (int) $item->events_count,
            ]);

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

    /**
     * GET /api/audit-logs/stats
     */
    public function stats(): JsonResponse
    {
        $total = AuditLog::count();

        $today = AuditLog::whereDate('created_at', today())->count();

        $bySeverity = AuditLog::query()
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $byModule = AuditLog::query()
            ->select('module', DB::raw('COUNT(*) as count'))
            ->groupBy('module')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->pluck('count', 'module');

        $uniqueIpsToday = AuditLog::whereDate('created_at', today())
            ->distinct('ip_address')
            ->count('ip_address');

        return response()->json([
            'total' => $total,
            'today' => $today,
            'unique_ips_today' => $uniqueIpsToday,
            'by_severity' => $bySeverity,
            'by_module' => $byModule,
        ]);
    }

    /**
     * GET /api/audit-logs/export
     */
    public function export(Request $request): Response
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->limit(10000)->get();
        $format = $request->query('format', 'json');

        if ($format === 'csv') {
            $headers = ['id', 'event', 'module', 'severity', 'description', 'user_id', 'user_email', 'ip_address', 'source', 'correlation_id', 'created_at'];
            $callback = function () use ($logs, $headers) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->event,
                        $log->module,
                        $log->severity,
                        $log->description,
                        $log->user_id,
                        $log->user_email,
                        $log->ip_address,
                        $log->source,
                        $log->correlation_id,
                        $log->created_at?->toISOString(),
                    ]);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-logs-export.csv"',
            ]);
        }

        return response()->json(['data' => AuditLogResource::collection($logs)]);
    }
}
