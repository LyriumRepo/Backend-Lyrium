<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\NubefactException;
use App\Services\NubefactProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class NubefactProviderTest extends TestCase
{
    private function makeProvider(): NubefactProvider
    {
        return new NubefactProvider(
            apiUrl: 'https://api.nubefact.com/api/v1/fake-uuid',
            apiToken: 'fake-token',
        );
    }

    public function test_demo_account_limit_is_classified_as_demo_limit_exceeded(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => 'No puedes enviar mas de 50 documentos en en una cuenta DEMO, ingresa a tu cuenta y elimina tus comprobantes.',
                'codigo' => 21,
            ], 400),
        ]);

        $provider = $this->makeProvider();

        try {
            $provider->emitInvoice(['operacion' => 'generar_comprobante']);
            $this->fail('Expected NubefactException was not thrown.');
        } catch (NubefactException $e) {
            $this->assertSame(NubefactException::DEMO_LIMIT_EXCEEDED, $e->getNubefactCode());
        }
    }

    public function test_duplicate_document_is_still_classified_as_duplicate_document(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => 'Este documento ya existe.',
                'codigo' => 23,
            ], 400),
        ]);

        $provider = $this->makeProvider();

        try {
            $provider->emitInvoice(['operacion' => 'generar_comprobante']);
            $this->fail('Expected NubefactException was not thrown.');
        } catch (NubefactException $e) {
            $this->assertSame(NubefactException::DUPLICATE_DOCUMENT, $e->getNubefactCode());
        }
    }

    public function test_other_400_errors_are_still_classified_as_validation_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => 'Dato inválido.',
                'codigo' => 99,
            ], 400),
        ]);

        $provider = $this->makeProvider();

        try {
            $provider->emitInvoice(['operacion' => 'generar_comprobante']);
            $this->fail('Expected NubefactException was not thrown.');
        } catch (NubefactException $e) {
            $this->assertSame(NubefactException::VALIDATION_ERROR, $e->getNubefactCode());
        }
    }

    /**
     * Regression guard: NubeFact reuses código 21 as a generic "bad request" bucket —
     * confirmed in production where "RUC incorrecto, el último dígito debería ser 2"
     * also came back with codigo 21. Only the exact demo-limit wording should map to
     * DEMO_LIMIT_EXCEEDED; everything else with codigo 21 must stay VALIDATION_ERROR
     * so it's marked REJECTED (needs a real data fix) instead of retried forever.
     */
    public function test_codigo_21_with_ruc_error_is_classified_as_validation_error_not_demo_limit(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => 'RUC incorrecto, el último dígito debería ser 2',
                'codigo' => 21,
            ], 400),
        ]);

        $provider = $this->makeProvider();

        try {
            $provider->emitInvoice(['operacion' => 'generar_comprobante']);
            $this->fail('Expected NubefactException was not thrown.');
        } catch (NubefactException $e) {
            $this->assertSame(NubefactException::VALIDATION_ERROR, $e->getNubefactCode());
        }
    }

    public function test_codigo_21_with_invalid_series_is_classified_as_validation_error_not_demo_limit(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => "Serie No puedes emitir comprobantes con esta serie'",
                'codigo' => 21,
            ], 400),
        ]);

        $provider = $this->makeProvider();

        try {
            $provider->emitInvoice(['operacion' => 'generar_comprobante']);
            $this->fail('Expected NubefactException was not thrown.');
        } catch (NubefactException $e) {
            $this->assertSame(NubefactException::VALIDATION_ERROR, $e->getNubefactCode());
        }
    }
}
