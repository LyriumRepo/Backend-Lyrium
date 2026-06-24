<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Controller;

class SecurityDashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'operational',
            'role' => 'security_admin',
            'modules' => ['audit', 'sessions', 'protection', 'ips', 'alerts', 'cloudflare', 'settings'],
        ]);
    }
}
