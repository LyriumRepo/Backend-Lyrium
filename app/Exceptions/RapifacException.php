<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

final class RapifacException extends \RuntimeException
{
    public const AUTH_ERROR = 'AUTH_ERROR';

    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public const SERVER_ERROR = 'SERVER_ERROR';

    public const CONNECTION_ERROR = 'CONNECTION_ERROR';

    public const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?string $rapifacCode = null,
        private readonly ?array $context = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRapifacCode(): ?string
    {
        return $this->rapifacCode;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public static function authError(string $message, ?array $context = null): self
    {
        return new self(
            message: "Rapifac — Error de autenticación: {$message}",
            code: 401,
            rapifacCode: self::AUTH_ERROR,
            context: $context,
        );
    }

    public static function validationError(string $message, ?array $context = null): self
    {
        return new self(
            message: "Rapifac — Datos inválidos: {$message}",
            code: 422,
            rapifacCode: self::VALIDATION_ERROR,
            context: $context,
        );
    }

    public static function serverError(string $message, ?array $context = null): self
    {
        return new self(
            message: "Rapifac — Error del servidor: {$message}",
            code: 502,
            rapifacCode: self::SERVER_ERROR,
            context: $context,
        );
    }

    public static function connectionError(string $message, ?array $context = null): self
    {
        return new self(
            message: "Rapifac — Error de conexión: {$message}",
            code: 503,
            rapifacCode: self::CONNECTION_ERROR,
            context: $context,
        );
    }

    public static function unknown(string $message, ?array $context = null): self
    {
        return new self(
            message: "Rapifac — Error inesperado: {$message}",
            code: 500,
            rapifacCode: self::UNKNOWN_ERROR,
            context: $context,
        );
    }
}
