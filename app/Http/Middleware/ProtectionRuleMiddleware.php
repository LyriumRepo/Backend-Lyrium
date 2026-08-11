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

        if ($pattern === null || $ip === '') {
            return false;
        }

        if ($ip === $pattern) {
            return true;
        }

        if (str_contains($pattern, '/')) {
            return $this->cidrMatch($ip, $pattern);
        }

        if (str_contains($pattern, '*')) {
            return $this->wildcardMatch($ip, $pattern);
        }

        return false;
    }

    /**
     * Compara IP contra un rango CIDR de forma agnóstica a la versión
     * (IPv4 de 32 bits o IPv6 de 128 bits), usando inet_pton para obtener
     * la representación binaria de cada dirección en vez de ip2long()
     * (que solo entiende IPv4 y devuelve false — sin match — para IPv6).
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$range, $prefixRaw] = array_pad(explode('/', $cidr, 2), 2, null);

        if ($range === null || $prefixRaw === null || ! ctype_digit($prefixRaw)) {
            return false;
        }

        $prefix = (int) $prefixRaw;
        $ipBinary = @inet_pton($ip);
        $rangeBinary = @inet_pton($range);

        // Deben ser de la misma familia (ambas IPv4 de 4 bytes o ambas IPv6
        // de 16 bytes); si alguna no es una IP valida, inet_pton da false.
        if ($ipBinary === false || $rangeBinary === false || strlen($ipBinary) !== strlen($rangeBinary)) {
            return false;
        }

        $maxBits = strlen($ipBinary) * 8;
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainderBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($rangeBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainderBits)) & 0xFF;

        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($rangeBinary[$fullBytes]) & $mask);
    }

    private function wildcardMatch(string $ip, string $pattern): bool
    {
        $escaped = preg_quote($pattern, '/');
        // '.+' en vez de '\d{1,3}' para que tambien funcione con segmentos
        // hexadecimales de IPv6 (p.ej. "2001:1388:*"), no solo octetos IPv4.
        $regex = '/^'.str_replace('\*', '.+', $escaped).'$/i';

        return (bool) preg_match($regex, $ip);
    }

    private function matchDevice(Request $request, ProtectionRule $rule): bool
    {
        $userAgent = $request->userAgent() ?? '';
        $pattern = $rule->pattern ?? '';
        return preg_match('/' . $pattern . '/i', $userAgent) === 1;
    }
}
