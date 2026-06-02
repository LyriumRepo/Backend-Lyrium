<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

final class NubefactException extends \RuntimeException
{
    public const AUTH_ERROR = 'AUTH_ERROR';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const CONNECTION_ERROR = 'CONNECTION_ERROR';
    public const UNKNOWN_ERROR = 'UNKNOWN_ERROR';
    public const SUNAT_REJECTED = 'SUNAT_REJECTED';
    public const SUNAT_OBSERVED = 'SUNAT_OBSERVED';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?string $nubefactCode = null,
        private readonly ?array $context = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getNubefactCode(): ?string
    {
        return $this->nubefactCode;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public static function authError(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Error de autenticación: {$message}",
            code: 401,
            nubefactCode: self::AUTH_ERROR,
            context: $context,
        );
    }

    public static function validationError(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Datos inválidos: {$message}",
            code: 422,
            nubefactCode: self::VALIDATION_ERROR,
            context: $context,
        );
    }

    public static function serverError(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Error del servidor: {$message}",
            code: 502,
            nubefactCode: self::SERVER_ERROR,
            context: $context,
        );
    }

    public static function connectionError(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Error de conexión: {$message}",
            code: 503,
            nubefactCode: self::CONNECTION_ERROR,
            context: $context,
        );
    }

    public static function sunatRejected(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Comprobante rechazado por SUNAT: {$message}",
            code: 422,
            nubefactCode: self::SUNAT_REJECTED,
            context: $context,
        );
    }

    public static function sunatObserved(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Comprobante observado por SUNAT: {$message}",
            code: 422,
            nubefactCode: self::SUNAT_OBSERVED,
            context: $context,
        );
    }

    public static function unknown(string $message, ?array $context = null): self
    {
        return new self(
            message: "NubeFact — Error inesperado: {$message}",
            code: 500,
            nubefactCode: self::UNKNOWN_ERROR,
            context: $context,
        );
    }
}
