<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ProtectionRule;
use App\Services\AuditService;
use App\Support\ClientIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProtectionRuleMiddleware
{
    private const CIDR_REGEX = '/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/';

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $rules = ProtectionRule::active()->orderBy('priority')->get();

        if ($rules->isEmpty()) {
            return $next($request);
        }

        foreach ($rules as $rule) {
            $matched = $this->evaluateRule($request, $rule);

            if (! $matched) {
                continue;
            }

            $rule->increment('trigger_count');
            $rule->update([
                'triggered_at' => now(),
                'status' => ProtectionRule::STATUS_TRIGGERED,
            ]);

            $this->auditService->record(
                event: 'security.protection.rule.triggered',
                module: 'security',
                description: "Regla de protección '{$rule->name}' activada para IP ".ClientIp::resolve($request),
                severity: 'critical',
                success: false,
                source: AuditService::SOURCE_SYSTEM,
                metadata: [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->type,
                    'pattern' => $rule->pattern,
                    'ip' => ClientIp::resolve($request),
                    'url' => $request->fullUrl(),
                ],
            );

            return response()->json([
                'success' => false,
                'error' => 'Acceso denegado por regla de protección.',
                'code' => 'PROTECTION_RULE',
            ], 403);
        }

        return $next($request);
    }

    private function evaluateRule(Request $request, ProtectionRule $rule): bool
    {
        return match ($rule->type) {
            ProtectionRule::TYPE_IP_BLOCK => $this->matchIpBlock($request, $rule),
            ProtectionRule::TYPE_RATE_LIMIT => false,
            ProtectionRule::TYPE_GEO => false, // TODO: GeoIP service required
            ProtectionRule::TYPE_DEVICE => $this->matchDevice($request, $rule),
            ProtectionRule::TYPE_CUSTOM => false,
            default => false,
        };
    }

    private function matchIpBlock(Request $request, ProtectionRule $rule): bool
    {
        $ip = (string) ClientIp::resolve($request);
        $pattern = $rule->pattern;

        if ($pattern === null) {
            return false;
        }

        if ($ip === $pattern) {
            return true;
        }

        if (preg_match(self::CIDR_REGEX, $pattern)) {
            return $this->cidrMatch($ip, $pattern);
        }

        if (str_contains($pattern, '*')) {
            return $this->wildcardMatch($ip, $pattern);
        }

        return false;
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr);
        $range = $parts[0];
        $prefix = (int) $parts[1];

        $ipLong = ip2long($ip);
        $rangeLong = ip2long($range);
        $mask = -1 << (32 - $prefix);

        return $ipLong !== false && $rangeLong !== false
            && ($ipLong & $mask) === ($rangeLong & $mask);
    }

    private function wildcardMatch(string $ip, string $pattern): bool
    {
        $escaped = preg_quote($pattern, '/');
        $regex = '/^'.str_replace('\*', '\d{1,3}', $escaped).'$/';

        return (bool) preg_match($regex, $ip);
    }

    private function matchDevice(Request $request, ProtectionRule $rule): bool
    {
        $userAgent = $request->userAgent() ?? '';
        $pattern = $rule->pattern ?? '';
        return preg_match('/' . $pattern . '/i', $userAgent) === 1;
    }
}
