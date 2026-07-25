<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminSessionResource;
use App\Http\Resources\Admin\LoginAttemptResource;
use App\Http\Resources\Admin\SecurityEventResource;
use App\Models\AdminSession;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

final class SecurityController extends Controller
{
    public function stats(): JsonResponse
    {
        $activeThreshold = now()->subMinutes(15)->timestamp;

        $activeUsers = AdminSession::query()
            ->withUser()
            ->where('last_activity', '>=', $activeThreshold)
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = User::count();

        $totalSessions = AdminSession::withUser()->count();

        $failedLoginsToday = LoginAttempt::query()
            ->where('status', 'failed')
            ->whereDate('created_at', today())
            ->count();

        $successLoginsToday = LoginAttempt::query()
            ->where('status', 'success')
            ->whereDate('created_at', today())
            ->count();

        $bannedUsers = User::where('is_banned', true)->count();

        $eventsToday = SecurityEvent::query()
            ->whereDate('created_at', today())
            ->count();

        $blockedIps = SecurityEvent::query()
            ->where('event_type', 'blocked_ip')
            ->whereDate('created_at', today())
            ->count();

        return $this->success([
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'active_sessions' => $totalSessions,
            'failed_logins_today' => $failedLoginsToday,
            'success_logins_today' => $successLoginsToday,
            'banned_users' => $bannedUsers,
            'events_today' => $eventsToday,
            'blocked_ips' => $blockedIps,
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $query = AdminSession::query()
            ->withUser()
            ->with('user')
            ->orderByDesc('last_activity');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($request->boolean('active')) {
            $query->where('last_activity', '>=', now()->subMinutes(15)->timestamp);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $sessions = $query->paginate($perPage);

        return response()->json([
            'data' => AdminSessionResource::collection($sessions),
            'pagination' => [
                'page' => $sessions->currentPage(),
                'perPage' => $sessions->perPage(),
                'total' => $sessions->total(),
                'totalPages' => $sessions->lastPage(),
                'hasMore' => $sessions->hasMorePages(),
            ],
        ]);
    }

    public function revokeSession(string $id): JsonResponse
    {
        $session = AdminSession::findOrFail($id);

        if ($session->token_id) {
            PersonalAccessToken::where('id', $session->token_id)->delete();
        }

        $session->delete();

        SecurityEvent::create([
            'event_type' => 'session_revoked',
            'description' => "Sesión {$id} revocada por administrador.",
            'ip_address' => request()->ip(),
            'user_id' => auth()->id(),
            'metadata' => [
                'revoked_session_id' => $id,
                'revoked_user_id' => $session->user_id,
                'revoked_token_id' => $session->token_id,
            ],
        ]);

        return $this->success(null, 'Sesión revocada correctamente.');
    }

    public function activity(Request $request): JsonResponse
    {
        $query = SecurityEvent::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($eventType = $request->query('event_type')) {
            $query->where('event_type', $eventType);
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

        $perPage = min((int) $request->query('per_page', 25), 100);
        $events = $query->paginate($perPage);

        return response()->json([
            'data' => SecurityEventResource::collection($events),
            'pagination' => [
                'page' => $events->currentPage(),
                'perPage' => $events->perPage(),
                'total' => $events->total(),
                'totalPages' => $events->lastPage(),
                'hasMore' => $events->hasMorePages(),
            ],
        ]);
    }

    public function chartData(): JsonResponse
    {
        $days = 30;

        $loginAttempts = LoginAttempt::query()
            ->select(DB::raw('DATE(created_at) as date'), 'status', DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'), 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($items, $date) => [
                'date' => $date,
                'success' => (int) $items->where('status', 'success')->sum('total'),
                'failed' => (int) $items->where('status', 'failed')->sum('total'),
            ])
            ->values();

        $eventsByType = SecurityEvent::query()
            ->select('event_type', DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get();

        $dailyActiveUsers = AdminSession::query()
            ->select(DB::raw('DATE(from_unixtime(last_activity)) as date'), DB::raw('count(distinct user_id) as active_users'))
            ->withUser()
            ->where('last_activity', '>=', now()->subDays($days)->timestamp)
            ->groupBy(DB::raw('DATE(from_unixtime(last_activity))'))
            ->orderBy('date')
            ->get();

        $sessionsByDay = AdminSession::query()
            ->select(DB::raw('DATE(from_unixtime(last_activity)) as date'), DB::raw('count(*) as total'))
            ->withUser()
            ->where('last_activity', '>=', now()->subDays($days)->timestamp)
            ->groupBy(DB::raw('DATE(from_unixtime(last_activity))'))
            ->orderBy('date')
            ->get();

        return $this->success([
            'login_attempts' => $loginAttempts,
            'events_by_type' => $eventsByType,
            'daily_active_users' => $dailyActiveUsers,
            'sessions_by_day' => $sessionsByDay,
        ]);
    }

    public function loginAttempts(Request $request): JsonResponse
    {
        $query = LoginAttempt::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($email = $request->query('email')) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $attempts = $query->paginate($perPage);

        return response()->json([
            'data' => LoginAttemptResource::collection($attempts),
            'pagination' => [
                'page' => $attempts->currentPage(),
                'perPage' => $attempts->perPage(),
                'total' => $attempts->total(),
                'totalPages' => $attempts->lastPage(),
                'hasMore' => $attempts->hasMorePages(),
            ],
        ]);
    }
}
