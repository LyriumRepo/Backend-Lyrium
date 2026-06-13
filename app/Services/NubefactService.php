<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NubefactService
{
    private string $route;

    private string $token;

    public function __construct()
    {
        $this->route = (string) config('services.nubefact.route');
        $this->token = (string) config('services.nubefact.token');
    }

    public function emitInvoice(array $data): array
    {
        $payload = $this->buildPayload($data);

        Log::info('Nubefact: enviando comprobante', [
            'serie' => $data['serie'],
            'numero' => $data['numero'],
            'cliente' => $data['cliente_denominacion'],
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Token token="'.$this->token.'"',
            'Content-Type' => 'application/json',
        ])->post($this->route, $payload);

        if ($response->failed()) {
            $body = $response->body();
            Log::error('Nubefact: error HTTP', [
                'status' => $response->status(),
                'body' => $body,
            ]);
            throw new \RuntimeException('Error al comunicarse con Nubefact: '.$body);
        }

        return $this->parseResponse($response->json() ?? []);
    }

    private function buildPayload(array $data): array
    {
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'unidad_de_medida' => $item['unidad_de_medida'],
                'codigo' => $item['codigo'] ?? '',
                'descripcion' => $item['descripcion'],
                'cantidad' => (string) $item['cantidad'],
                'valor_unitario' => (string) $item['valor_unitario'],
                'precio_unitario' => (string) $item['precio_unitario'],
                'descuento' => $item['descuento'] ?? '',
                'subtotal' => (string) $item['subtotal'],
                'tipo_de_igv' => $item['tipo_de_igv'],
                'igv' => (string) $item['igv'],
                'total' => (string) $item['total'],
                'anticipo_regularizacion' => 'false',
                'anticipo_documento_serie' => '',
                'anticipo_documento_numero' => '',
            ];
        }

        return [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $data['tipo_de_comprobante'],
            'serie' => $data['serie'],
            'numero' => $data['numero'],
            'sunat_transaction' => $data['sunat_transaction'] ?? '1',
            'cliente_tipo_de_documento' => $data['cliente_tipo_de_documento'],
            'cliente_numero_de_documento' => $data['cliente_numero_de_documento'],
            'cliente_denominacion' => $data['cliente_denominacion'],
            'cliente_direccion' => $data['cliente_direccion'] ?? '',
            'cliente_email' => $data['cliente_email'] ?? '',
            'cliente_email_1' => '',
            'cliente_email_2' => '',
            'fecha_de_emision' => $data['fecha_de_emision'] ?? now('America/Lima')->format('d-m-Y'),
            'fecha_de_vencimiento' => '',
            'moneda' => $data['moneda'] ?? '1',
            'tipo_de_cambio' => '',
            'porcentaje_de_igv' => '18.00',
            'descuento_global' => '',
            'total_descuento' => '',
            'total_anticipo' => '',
            'total_gravada' => (string) $data['total_gravada'],
            'total_inafecta' => '0',
            'total_exonerada' => '0',
            'total_igv' => (string) $data['total_igv'],
            'total_gratuita' => '0',
            'total_otros_cargos' => '0',
            'total' => (string) $data['total'],
            'percepcion_tipo' => '',
            'percepcion_base_imponible' => '',
            'total_percepcion' => '',
            'total_incluido_percepcion' => '',
            'detraccion' => 'false',
            'observaciones' => $data['observaciones'] ?? '',
            'documento_que_se_modifica_tipo' => '',
            'documento_que_se_modifica_serie' => '',
            'documento_que_se_modifica_numero' => '',
            'tipo_de_nota_de_credito' => '',
            'tipo_de_nota_de_debito' => '',
            'enviar_automaticamente_a_la_sunat' => 'true',
            'enviar_automaticamente_al_cliente' => 'false',
            'codigo_unico' => (string) str()->uuid(),
            'condiciones_de_pago' => '',
            'medio_de_pago' => '',
            'placa_vehiculo' => '',
            'orden_compra_servicio' => '',
            'tabla_personalizada_codigo' => '',
            'formato_de_pdf' => '',
            'items' => $items,
        ];
    }

    private function parseResponse(array $response): array
    {
        if (isset($response['errors'])) {
            throw new \RuntimeException($response['errors']);
        }

        $aceptada = $response['aceptada_por_sunat'] ?? false;
        $sunatResponseCode = $response['sunat_responsecode'] ?? '';

        $status = match (true) {
            $aceptada === true || $aceptada === 'true' || $aceptada === 1 => 'ACCEPTED',
            $sunatResponseCode === '98' || $sunatResponseCode === 98 => 'OBSERVED',
            ! empty($sunatResponseCode) => 'REJECTED',
            default => 'SENT_WAIT_CDR',
        };

        return [
            'success' => true,
            'status' => $status,
            'provider_invoice_id' => $response['key'] ?? $response['codigo_unico'] ?? null,
            'authorization_code' => $response['codigo_hash'] ?? null,
            'qr_data' => $response['cadena_para_codigo_qr'] ?? null,
            'pdf_url' => $response['enlace_del_pdf'] ?? $response['enlace'] ?? null,
            'xml_url' => $response['enlace_del_xml'] ?? null,
            'cdr_url' => $response['enlace_del_cdr'] ?? null,
            'pdf_base64' => $response['pdf_zip_base64'] ?? null,
            'xml_base64' => $response['xml_zip_base64'] ?? null,
            'cdr_base64' => $response['cdr_zip_base64'] ?? null,
            'sunat_aceptada' => $aceptada,
            'sunat_description' => $response['sunat_description'] ?? '',
            'sunat_note' => $response['sunat_note'] ?? '',
            'sunat_responsecode' => $sunatResponseCode,
            'sunat_soap_error' => $response['sunat_soap_error'] ?? '',
            'raw' => $response,
        ];
    }
}
