<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TrackAdminSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $now = now()->timestamp;

            DB::table('sessions')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => $now,
                    'payload' => '[]',
                ]
            );
        }

        return $next($request);
    }
}
