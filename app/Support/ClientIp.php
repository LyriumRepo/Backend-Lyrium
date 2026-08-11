<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resuelve la IP real del visitante cuando la app esta detras de Cloudflare.
 *
 * `Request::ip()` depende de `trustProxies()` + la cadena `X-Forwarded-For`,
 * la cual se vuelve ambigua cuando hay mas de un salto de proxy (Cloudflare +
 * el Load Balancer de Oracle Cloud) y se configura `trustProxies(at: '*')`:
 * Symfony no tiene forma de distinguir "IP de proxy" de "IP de cliente" y
 * termina devolviendo el salto mas cercano al servidor (el borde de
 * Cloudflare) en vez del cliente original.
 *
 * El header `CF-Connecting-IP` lo agrega Cloudflare siempre con la IP real
 * del visitante, sin ambiguedad -- es la misma fuente que ya se usa para
 * `CF-IPCountry`. Se usa como fuente principal, con fallback a `Request::ip()`
 * para entornos sin Cloudflare (local, tests).
 */
final class ClientIp
{
    public static function resolve(Request $request): ?string
    {
        return $request->header('CF-Connecting-IP') ?: $request->ip();
    }
}
