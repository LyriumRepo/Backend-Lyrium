<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada cuando la API de Cloudflare rechaza las credenciales (HTTP 401/403).
 *
 * Nunca incluye el token en el mensaje por seguridad.
 */
final class CloudflareAuthenticationException extends Exception
{
    public function __construct(string $message = 'Error de autenticación con Cloudflare. Verifique el API Token.', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
