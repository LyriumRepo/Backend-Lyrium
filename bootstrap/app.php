<?php

use App\Http\Middleware\EnsureRpaAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJson::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'store.approved' => \App\Http\Middleware\EnsureStoreApproved::class,
            'verified' => \App\Http\Middleware\EnsureEmailVerified::class,
            'contract.active' => \App\Http\Middleware\EnsureContractActive::class,
            'track.session' => \App\Http\Middleware\TrackAdminSession::class,
            'auth.rpa' => EnsureRpaAuth::class,
        ]);

        // ── Cloudflare Trusted Proxies ──────────────────────────────────
        // Laravel recibe conexiones a través de Cloudflare Tunnel (cloudflared),
        // lo que hace que $request->ip() devuelva 127.0.0.1.
        // Al confiar en los proxies de Cloudflare, Laravel usará los headers
        // CF-Connecting-IP o X-Forwarded-For para obtener la IP real del visitante.
        //
        // Cloudflare publica sus rangos de IP en:
        //   https://www.cloudflare.com/ips-v4
        //   https://www.cloudflare.com/ips-v6
        //
        // Usamos '*' porque Cloudflare Tunnel puede originarse desde cualquier IP
        // (cloudflared corre localmente y se conecta via 127.0.0.1).
        // Laravel internamente prioriza CF-Connecting-IP sobre X-Forwarded-For
        // cuando detecta headers de Cloudflare.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
