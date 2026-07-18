<?php

use App\Catalogs\SystemEvents;
use App\Http\Middleware\AuditAuthMiddleware;
use App\Http\Middleware\AuditSecurityMiddleware;
use App\Http\Middleware\EnsureContractActive;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsurePlanModule;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureRpaAuth;
use App\Http\Middleware\EnsureStoreApproved;
use App\Http\Middleware\EnsureUserNotBanned;
use App\Http\Middleware\ForceJson;
use App\Http\Middleware\ProtectionRuleMiddleware;
use App\Http\Middleware\SecurityAccessMiddleware;
use App\Http\Middleware\TrackAdminSession;
use App\Http\Middleware\TrackCustomerPanelVisit;
use App\Services\AuditService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            ForceJson::class,
            TrackCustomerPanelVisit::class,
            SecurityAccessMiddleware::class,
            ProtectionRuleMiddleware::class,
            EnsureUserNotBanned::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'store.approved' => EnsureStoreApproved::class,
            'verified' => EnsureEmailVerified::class,
            'contract.active' => EnsureContractActive::class,
            'track.session' => TrackAdminSession::class,
            'plan.module' => EnsurePlanModule::class,
            'audit.auth' => AuditAuthMiddleware::class,
            'audit.security' => AuditSecurityMiddleware::class,
            'auth.rpa' => EnsureRpaAuth::class,
            'security.access' => SecurityAccessMiddleware::class,
            'protection.rule' => ProtectionRuleMiddleware::class,
            'user.notbanned' => EnsureUserNotBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            if ($statusCode >= 500) {
                try {
                    app(AuditService::class)->record(
                        event: SystemEvents::EXCEPTION,
                        module: 'system',
                        description: 'Excepción no controlada: '.$e->getMessage(),
                        severity: 'critical',
                        source: AuditService::SOURCE_SYSTEM,
                        metadata: [
                            'exception_class' => $e::class,
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ],
                    );
                } catch (Throwable) {
                    // Silent fail — never interrupt the main flow
                }
            }
        });
    })->create();
