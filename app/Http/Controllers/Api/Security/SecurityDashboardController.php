<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class SecurityDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        $totalToday = AuditLog::whereDate('created_at', $today)->count();

        $criticalToday = AuditLog::whereDate('created_at', $today)
            ->where('severity', 'critical')
            ->count();

        $bySeverity = AuditLog::whereDate('created_at', $today)
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $byModule = AuditLog::whereDate('created_at', $today)
            ->select('module', DB::raw('COUNT(*) as count'))
            ->groupBy('module')
            ->pluck('count', 'module')
            ->toArray();

        $lastEvents = AuditLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topIps = AuditLog::whereDate('created_at', $today)
            ->select('ip_address', DB::raw('COUNT(*) as count'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'ip' => $item->ip_address,
                'count' => (int) $item->count,
            ])
            ->toArray();

        $topUsers = AuditLog::whereDate('created_at', $today)
            ->select('user_id', 'user_email', DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'user_email')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'user_id' => (int) $item->user_id,
                'email' => $item->user_email,
                'count' => (int) $item->count,
            ])
            ->toArray();

        return response()->json([
            'total_today' => $totalToday,
            'critical_today' => $criticalToday,
            'by_severity' => $bySeverity,
            'by_module' => $byModule,
            'last_events' => $lastEvents,
            'top_ips' => $topIps,
            'top_users' => $topUsers,
        ]);
    }

    public function realtime(): JsonResponse
    {
        $since = Carbon::now()->subMinutes(5);

        $events = AuditLog::where('severity', 'critical')
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'events' => AuditLogResource::collection($events),
            'total' => $events->count(),
        ]);
    }
}
