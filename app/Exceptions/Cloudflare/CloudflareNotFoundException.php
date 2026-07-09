<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada cuando un recurso solicitado no existe en Cloudflare (HTTP 404).
 */
final class CloudflareNotFoundException extends Exception
{
    public function __construct(string $resource = 'Recurso', int $code = 404)
    {
        parent::__construct("{$resource} no encontrado en Cloudflare.", $code);
    }
}
