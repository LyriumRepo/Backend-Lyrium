<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada para errores de API no clasificados (HTTP 500, respuestas inesperadas).
 * Nunca expone datos sensibles en el mensaje público.
 */
final class CloudflareApiException extends Exception
{
    public readonly ?int $cloudflareErrorCode;

    public readonly ?string $cloudflareErrorChain;

    public function __construct(
        string $message = 'Error inesperado de la API de Cloudflare.',
        int $code = 500,
        ?int $cloudflareErrorCode = null,
        ?string $cloudflareErrorChain = null,
    ) {
        $this->cloudflareErrorCode = $cloudflareErrorCode;
        $this->cloudflareErrorChain = $cloudflareErrorChain;

        parent::__construct($message, $code);
    }
}
