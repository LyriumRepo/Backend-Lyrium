<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\AuditLogCreated;
use App\Events\CriticalSecurityEvent;
use App\Events\RepeatedFailedLoginEvent;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AuditService
{
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_QUEUE = 'queue';
    public const SOURCE_SCHEDULER = 'scheduler';
    public const SOURCE_SYSTEM = 'system';

    public function record(
        string $event,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $severity = null,
        ?bool $success = null,
        string $source = self::SOURCE_WEB,
        ?string $correlationId = null,
        array $metadata = [],
    ): AuditLog {
        $this->validateEvent($event);

        $resolvedSeverity = $this->resolveSeverity($event, $severity);
        $resolvedCorrelationId = $this->resolveCorrelationId($correlationId);

        /** @var Request|null $request */
        $request = request();

        try {
            $log = AuditLog::create([
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_role' => auth()->user() !== null
                    ? (string) (auth()->user()->getRoleNames()->first() ?? '')
                    : null,
                'session_id' => $this->resolveSessionId($request),
                'correlation_id' => $resolvedCorrelationId,
                'event' => $event,
                'module' => $module,
                'severity' => $resolvedSeverity,
                'description' => $description,
                'success' => $success,
                'source' => $source,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'old_values' => !empty($oldValues) ? $oldValues : null,
                'new_values' => !empty($newValues) ? $newValues : null,
                'metadata' => !empty($metadata) ? $metadata : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_method' => $request?->method(),
                'request_url' => $this->resolveRequestUrl($request),
                'response_code' => null,
                'created_at' => now(),
            ]);

            $this->dispatchEvents($log);

            return $log;
        } catch (\Throwable $e) {
            Log::error('AuditService: fallo al registrar evento de auditoría', [
                'event' => $event,
                'module' => $module,
                'description' => $description,
                'severity' => $resolvedSeverity,
                'source' => $source,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function validateEvent(string $event): void
    {
        if (app()->isProduction()) {
            return;
        }

        $validEvents = $this->getValidEvents();

        if (!in_array($event, $validEvents, true)) {
            throw new \InvalidArgumentException(
                "Evento de auditoría no registrado en catálogo: [{$event}]. " .
                "Debe agregarse al catálogo correspondiente en app/Catalogs/."
            );
        }
    }

    private function getValidEvents(): array
    {
        static $events = null;

        if ($events !== null) {
            return $events;
        }

        $events = [];
        $files = glob(app_path('Catalogs/*.php'));

        foreach ($files as $file) {
            $className = 'App\\Catalogs\\' . basename($file, '.php');

            if (!class_exists($className)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($className);
                $events = array_merge($events, array_values($reflection->getConstants()));
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $events;
    }

    private function resolveSeverity(string $event, ?string $override): string
    {
        if ($override !== null) {
            return $override;
        }

        $configSeverity = config('audit.severity', []);

        if (is_array($configSeverity) && array_key_exists($event, $configSeverity)) {
            return $configSeverity[$event];
        }

        return 'info';
    }

    private function resolveCorrelationId(?string $correlationId): string
    {
        if ($correlationId !== null) {
            return $correlationId;
        }

        return (string) Str::uuid();
    }

    private function resolveSessionId(?Request $request): ?string
    {
        if ($request === null || !$request->hasSession()) {
            return null;
        }

        $session = $request->session();
        if ($session !== null && method_exists($session, 'getId')) {
            return $session->getId();
        }

        return $request->header('X-Session-Id');
    }

    private function resolveRequestUrl(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $url = $request->fullUrl();

        $sensitiveParams = ['password', 'token', 'secret', 'api_key', 'otp'];
        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            foreach ($sensitiveParams as $param) {
                if (isset($queryParams[$param])) {
                    $queryParams[$param] = '[REDACTED]';
                }
            }
            $parsedUrl['query'] = http_build_query($queryParams);
            $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host']
                . (isset($parsedUrl['path']) ? $parsedUrl['path'] : '')
                . '?' . $parsedUrl['query'];
        }

        return $url;
    }

    private function dispatchEvents(AuditLog $log): void
    {
        AuditLogCreated::dispatch($log);

        if ($log->severity === 'critical') {
            CriticalSecurityEvent::dispatch($log);
        }

        if ($log->event === 'auth.login.failed') {
            $this->checkRepeatedFailedLogin($log);
        }
    }

    private function checkRepeatedFailedLogin(AuditLog $log): void
    {
        $threshold = (int) config('audit.patterns.failed_login.threshold', 5);
        $windowMinutes = (int) config('audit.patterns.failed_login.window_minutes', 5);

        $ip = $log->ip_address;

        if ($ip === null) {
            return;
        }

        $count = AuditLog::query()
            ->where('event', 'auth.login.failed')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($count >= $threshold) {
            RepeatedFailedLoginEvent::dispatch(
                $log,
                $ip,
                $count,
            );
        }
    }
}
