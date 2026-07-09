<?php

declare(strict_types=1);

namespace App\Exceptions\Cloudflare;

use Exception;

/**
 * Lanzada cuando Cloudflare rechaza la petición por datos inválidos (HTTP 422).
 * Expone los errores de validación devueltos por la API.
 */
final class CloudflareValidationException extends Exception
{
    public readonly array $errors;

    public function __construct(array $errors = [], string $message = 'Datos inválidos enviados a Cloudflare.', int $code = 422)
    {
        $this->errors = $errors;

        parent::__construct($message, $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
