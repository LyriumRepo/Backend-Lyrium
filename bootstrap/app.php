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
            \App\Http\Middleware\TrackCustomerPanelVisit::class,
        ]);

        $middleware->alias([
            'role'              => \App\Http\Middleware\EnsureRole::class,
            'store.approved'    => \App\Http\Middleware\EnsureStoreApproved::class,
            'verified'          => \App\Http\Middleware\EnsureEmailVerified::class,
            'contract.active'   => \App\Http\Middleware\EnsureContractActive::class,
            'plan.module'       => \App\Http\Middleware\EnsurePlanModule::class,
            'audit.auth'        => \App\Http\Middleware\AuditAuthMiddleware::class,
            'audit.security'    => \App\Http\Middleware\AuditSecurityMiddleware::class,
            'track.session'     => \App\Http\Middleware\TrackAdminSession::class,
            'auth.rpa'          => EnsureRpaAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            if ($statusCode >= 500) {
                try {
                    app(\App\Services\AuditService::class)->record(
                        event: \App\Catalogs\SystemEvents::EXCEPTION,
                        module: 'system',
                        description: 'Excepción no controlada: ' . $e->getMessage(),
                        severity: 'critical',
                        source: \App\Services\AuditService::SOURCE_SYSTEM,
                        metadata: [
                            'exception_class' => $e::class,
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ],
                    );
                } catch (\Throwable) {
                    // Silent fail — never interrupt the main flow
                }
            }
        });
    })->create();
