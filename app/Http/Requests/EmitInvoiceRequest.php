<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EmitInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'nullable|integer|exists:orders,id',
            'tipo_de_comprobante' => 'required|string|in:1,2,3,4',
            'serie' => 'required|string|max:4',
            'numero' => 'required|string|max:10',
            'sunat_transaction' => 'nullable|string|in:1,2,3,4,5,6,7,8',

            'cliente_tipo_de_documento' => 'required|string|in:1,6,4,7,0',
            'cliente_numero_de_documento' => 'required|string|max:20',
            'cliente_denominacion' => 'required|string|max:255',
            'cliente_direccion' => 'nullable|string|max:500',
            'cliente_email' => 'nullable|email|max:255',

            'fecha_de_emision' => 'nullable|date_format:d-m-Y',
            'moneda' => 'nullable|string|in:1,2',

            'total_gravada' => 'required|numeric|min:0',
            'total_igv' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',

            'observaciones' => 'nullable|string|max:500',

            'items' => 'required|array|min:1',
            'items.*.unidad_de_medida' => 'required|string|max:10',
            'items.*.codigo' => 'nullable|string|max:50',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.valor_unitario' => 'required|numeric|min:0',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.descuento' => 'nullable|numeric',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.tipo_de_igv' => 'required|string|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17',
            'items.*.igv' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_de_comprobante.required' => 'El tipo de comprobante es obligatorio.',
            'tipo_de_comprobante.in' => 'Debe ser: 1 (Factura), 2 (Boleta), 3 (Nota de Credito), 4 (Nota de Debito).',
            'serie.required' => 'La serie es obligatoria.',
            'numero.required' => 'El numero es obligatorio.',
            'cliente_tipo_de_documento.required' => 'El tipo de documento del cliente es obligatorio.',
            'cliente_tipo_de_documento.in' => 'Debe ser: 1 (DNI), 6 (RUC), 4 (CE), 7 (Pasaporte), 0 (Otros).',
            'cliente_numero_de_documento.required' => 'El numero de documento del cliente es obligatorio.',
            'cliente_denominacion.required' => 'El nombre o razon social del cliente es obligatorio.',
            'items.required' => 'Debe incluir al menos un item.',
            'items.*.descripcion.required' => 'La descripcion del item es obligatoria.',
            'items.*.tipo_de_igv.required' => 'El tipo de IGV del item es obligatorio.',
        ];
    }
}
