<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada cuando no se puede establecer conexión con la API de Cloudflare.
 * Incluye detalles de red pero nunca expone credenciales.
 */
final class CloudflareConnectionException extends Exception
{
    public function __construct(string $message = 'No se pudo conectar con la API de Cloudflare.', int $code = 502, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
