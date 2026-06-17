<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets panel_visited_at on first customer panel or profile-update API call.
 * Once set, the reminder command skips this user permanently.
 */
final class TrackCustomerPanelVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (!$user || !is_null($user->panel_visited_at) || !$user->hasRole('customer')) {
            return $response;
        }

        $path = $request->path();
        $method = $request->method();

        $isCustomerPanel = str_starts_with($path, 'api/customer/');
        $isProfileUpdate = in_array($method, ['PUT', 'PATCH'], true) && str_contains($path, 'profile');

        if ($isCustomerPanel || $isProfileUpdate) {
            $user->update(['panel_visited_at' => now()]);
        }

        return $response;
    }
}
