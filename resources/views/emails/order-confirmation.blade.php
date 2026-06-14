@extends('emails.layout')

@section('email_title', 'Compra confirmada — Pedido #' . $orderNumber)

@section('email_content')

<style>
    .content-table {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    }

    @media only screen and (max-width: 480px) {
        .row-product td {
            padding: 10px 6px !important;
            font-size: 11px !important;
        }

        .total-bar td {
            font-size: 17px !important;
        }
    }
</style>

<img src="https://fv5-5.files.fm/thumb_show.php?i=337x2ej3b6" alt="Gracias por tu compra - Lyrium"
    style="display:block;width:100%;max-width:600px;height:auto;margin:0 auto;" />

{{-- ===== DETALLE DEL PEDIDO ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 0;">
    <tr>
        <td style="padding:0 16px;">

            {{-- Título turquesa + encabezados de columnas --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background:#2db8b0;border-radius:10px 10px 0 0;">
                <tr>
                    <td style="padding:12px 16px 10px;">
                        <span style="font-size:13px;font-weight:900;color:#ffffff;
                            text-transform:uppercase;letter-spacing:1.5px;">
                            📋 Detalle de su pedido
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 0;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="background:#1a9d96;">
                            <tr>
                                <td width="55%" style="padding:9px 10px;font-size:11px;
                                    font-weight:800;color:#e0faf8;text-transform:uppercase;
                                    letter-spacing:0.8px;text-align:left;">
                                    Producto
                                </td>
                                <td width="15%" style="padding:9px 4px;font-size:11px;
                                    font-weight:800;color:#e0faf8;text-transform:uppercase;
                                    letter-spacing:0.8px;text-align:center;">
                                    Cant.
                                </td>
                                <td width="30%" style="padding:9px 10px;font-size:11px;
                                    font-weight:800;color:#e0faf8;text-transform:uppercase;
                                    letter-spacing:0.8px;text-align:right;">
                                    Total (S/)
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Filas de productos --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="border:1.5px solid #2db8b0;border-top:none;">
                @foreach($items as $index => $item)
                <tr class="row-product"
                    style="background:{{ ($index % 2 === 1) ? '#f4fdfb' : '#ffffff' }};
                           border-bottom:1px solid #d1f0eb;">
                    <td width="55%" style="padding:12px 10px;font-size:13px;font-weight:700;
                        color:#1a7a74;text-align:left;line-height:1.4;word-break:break-word;">
                        {{ $item->name }}
                    </td>
                    <td width="15%" style="padding:12px 4px;font-size:13px;font-weight:800;
                        color:#1a3a3a;text-align:center;">
                        {{ $item->quantity }}
                    </td>
                    <td width="30%" style="padding:12px 10px;font-size:13px;font-weight:800;
                        color:#2db8b0;text-align:right;white-space:nowrap;">
                        S/ {{ number_format($item->line_total, 2) }}
                    </td>
                </tr>
                @endforeach
            </table>

        </td>
    </tr>
</table>

{{-- ===== SUBTOTAL / ENVÍO / DESCUENTO ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 0;">
    <tr>
        <td style="padding:0 16px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="border:1.5px solid #2db8b0;border-top:none;padding:12px 16px 4px;">

                {{-- Subtotal --}}
                <tr>
                    <td style="padding:5px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:13px;font-weight:700;color:#1a4a4a;
                                    text-align:right;padding-right:16px;">
                                    Subtotal 🛒
                                </td>
                                <td width="110" style="font-size:13px;font-weight:700;
                                    color:#1a3a3a;text-align:right;white-space:nowrap;">
                                    S/ {{ number_format($subtotal, 2) }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Envío --}}
                <tr>
                    <td style="padding:5px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:13px;font-weight:700;color:#1a4a4a;
                                    text-align:right;padding-right:16px;">
                                    Envío 🚚
                                </td>
                                <td width="110" style="font-size:13px;font-weight:700;
                                    text-align:right;white-space:nowrap;
                                    color:{{ (float)$shippingCost > 0 ? '#1a3a3a' : '#2db8b0' }};">
                                    @if((float)$shippingCost > 0)
                                    S/ {{ number_format($shippingCost, 2) }}
                                    @else
                                    Gratis
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if((float)$discount > 0)
                {{-- Descuento --}}
                <tr>
                    <td style="padding:5px 0 8px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:13px;font-weight:700;color:#1a4a4a;
                                    text-align:right;padding-right:16px;">
                                    Descuento 🏷️
                                </td>
                                <td width="110" style="font-size:13px;font-weight:700;
                                    color:#16A34A;text-align:right;white-space:nowrap;">
                                    -S/ {{ number_format($discount, 2) }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

            </table>
        </td>
    </tr>
</table>

{{-- ===== TOTAL A PAGAR ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 24px;">
    <tr>
        <td style="padding:0 16px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background:#1a8a8a;border-radius:0 0 10px 10px;">
                <tr class="total-bar">
                    <td style="padding:14px 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:15px;font-weight:700;color:#ffffff;">
                                    Total a pagar
                                </td>
                                <td style="text-align:right;font-size:22px;font-weight:900;
                                    color:#ffffff;white-space:nowrap;">
                                    S/ {{ number_format($total, 2) }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== DIRECCIÓN DE ENVÍO ===== --}}
@if($shippingAddress)
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
    <tr>
        <td style="padding:0 16px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="border:1.5px solid #2db8b0;border-radius:10px;background:#f4fdfb;">
                <tr>
                    <td style="padding:14px 18px;">
                        <p style="margin:0 0 6px;font-size:12px;font-weight:800;color:#0F766E;
                            text-transform:uppercase;letter-spacing:1px;">
                            📍 Dirección de envío
                        </p>
                        <p style="margin:0;font-size:13px;color:#334155;line-height:1.6;">
                            {{ $shippingAddress }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endif

{{-- ===== REDES SOCIALES ===== --}}
<table width="100%" cellpadding="0" cellspacing="0"
    style="border-top:1.5px solid #d1f0eb;margin:24px 16px 4px;padding:24px 0 16px;">
    <tr>
        <td style="text-align:center;padding:0 16px;">
            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                    {{-- Facebook --}}
                    <td style="padding:0 14px;text-align:center;vertical-align:top;">
                        <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/" target="_blank"
                            style="text-decoration:none;display:inline-block;">
                            <img src="https://fv5-4.files.fm/thumb_show.php?i=726g592gj8&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a"
                                width="48" height="48" alt="Facebook"
                                style="display:block;margin:0 auto;" />
                        </a>
                    </td>

                    <td style="padding:0;">
                        <div style="width:1px;height:54px;background:#c8e8e4;"></div>
                    </td>

                    {{-- Instagram --}}
                    <td style="padding:0 14px;text-align:center;vertical-align:top;">
                        <a href="https://www.instagram.com/lyrium_biomarketplace/" target="_blank"
                            style="text-decoration:none;display:inline-block;">
                            <img src="https://cdn-icons-png.flaticon.com/128/4138/4138124.png" ;
                                width="48" height="48" alt="Instagram"
                                style="display:block;margin:0 auto;border-radius:10px;" />
                        </a>
                    </td>

                    <td style="padding:0;">
                        <div style="width:1px;height:54px;background:#c8e8e4;"></div>
                    </td>

                    {{-- WhatsApp --}}
                    <td style="padding:0 14px;text-align:center;vertical-align:top;">
                        <a href="https://wa.me/51937093420{{ config('lyrium.whatsapp', '999999999') }}"
                            target="_blank" style="text-decoration:none;display:inline-block;">
                            <img src="https://cdn-icons-png.flaticon.com/128/15713/15713434.png"
                                width="48" height="48" alt="WhatsApp"
                                style="display:block;margin:0 auto;" />
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<img src="https://fv5-5.files.fm/thumb_show.php?i=aabbernwqc" alt="Lyrium"
    style="display:block;width:100%;max-width:600px;height:auto;margin:4px 0 0;" />

@endsection