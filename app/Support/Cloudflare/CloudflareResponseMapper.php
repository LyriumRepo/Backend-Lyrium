<?php

declare(strict_types=1);

namespace App\Support\Cloudflare;

use App\Exceptions\Cloudflare\CloudflareApiException;
use App\Exceptions\Cloudflare\CloudflareRateLimitException;
use Illuminate\Http\Client\Response;

/**
 * Mapea y valida las respuestas de la API de Cloudflare.
 *
 * Responsabilidad: extraer datos, manejar errores, paginación,
 * y lanzar excepciones específicas según el código HTTP.
 */
final readonly class CloudflareResponseMapper
{
    /**
     * Valida la respuesta HTTP y extrae el cuerpo.
     *
     * @throws CloudflareApiException
     * @throws CloudflareRateLimitException
     */
    public function validateAndExtract(Response $httpResponse): array
    {
        $status = $httpResponse->status();

        if ($httpResponse->clientError() || $httpResponse->serverError()) {
            $this->handleHttpError($httpResponse);
        }

        $body = $httpResponse->json();

        if (! is_array($body)) {
            throw new CloudflareApiException(
                'Respuesta inválida de Cloudflare: formato inesperado.',
                502
            );
        }

        $success = (bool) ($body['success'] ?? false);
        $errors = $body['errors'] ?? [];

        if (! $success) {
            $this->handleApiError($errors, $status);
        }

        return $body;
    }

    /**
     * Extrae el array de resultados de una respuesta paginada.
     */
    public function extractResult(Response $httpResponse): array
    {
        $body = $this->validateAndExtract($httpResponse);

        return $body['result'] ?? [];
    }

    /**
     * Extrae un resultado único (objeto individual).
     */
    public function extractSingleResult(Response $httpResponse): array
    {
        $result = $this->extractResult($httpResponse);

        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Extrae metadatos de paginación.
     */
    public function extractPagination(Response $httpResponse): array
    {
        $body = $this->validateAndExtract($httpResponse);

        return $body['result_info'] ?? [];
    }

    /**
     * Maneja errores HTTP (4xx/5xx) y lanza la excepción correspondiente.
     */
    private function handleHttpError(Response $response): never
    {
        $status = $response->status();

        if ($status === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?? 60);

            throw new CloudflareRateLimitException($retryAfter);
        }

        $body = $response->json();
        $errors = is_array($body) ? ($body['errors'] ?? []) : [];

        throw new CloudflareApiException(
            message: "Cloudflare respondió con HTTP {$status}.",
            code: $status,
            cloudflareErrorCode: $errors[0]['code'] ?? null,
            cloudflareErrorChain: json_encode($errors),
        );
    }

    /**
     * Maneja errores de la API (success=false).
     */
    private function handleApiError(array $errors, int $httpStatus): never
    {
        $errorMessages = array_map(
            fn (array $err) => $err['message'] ?? 'Error desconocido',
            $errors
        );

        $message = implode('; ', $errorMessages);

        throw new CloudflareApiException(
            message: "Cloudflare API error: {$message}",
            code: $httpStatus,
            cloudflareErrorCode: $errors[0]['code'] ?? null,
            cloudflareErrorChain: json_encode($errors),
        );
    }
}
