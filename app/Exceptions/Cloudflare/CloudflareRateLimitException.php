<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada cuando Cloudflare responde con HTTP 429 (Rate Limit).
 * Incluye el tiempo de espera sugerido si está disponible en los headers.
 */
final class CloudflareRateLimitException extends Exception
{
    public readonly ?int $retryAfter;

    public function __construct(?int $retryAfter = null, string $message = 'Límite de tasa de Cloudflare excedido. Intente nuevamente.', int $code = 429)
    {
        $this->retryAfter = $retryAfter;

        parent::__construct($message, $code);
    }
}
