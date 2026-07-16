@extends('emails.layout')

@section('email_title', 'Seguimiento de tu pedido - Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">

        {{-- BANNER SUPERIOR --}}
        @if($bannerTopCid)
        <tr>
            <td style="text-align: center; line-height: 0; font-size: 0;">
                <img src="cid:{{ $bannerTopCid }}" alt="Lyrium" style="display: block; width: 100%; max-width: 600px; height: auto; border: 0;" />
            </td>
        </tr>
        <tr>
            <td style="height: 24px; font-size: 0;">&nbsp;</td>
        </tr>
        @endif

        {{-- IMAGEN DE SEGUIMIENTO --}}
        @if($imageCid)
        <tr>
            <td style="text-align: center; padding-bottom: 24px;">
                <img src="cid:{{ $imageCid }}" alt="Seguimiento" style="display: block; max-width: 100%; height: auto; border-radius: 12px; margin: 0 auto;" />
            </td>
        </tr>
        @endif

        {{-- TÍTULO PRINCIPAL --}}
        <tr>
            <td style="text-align: center; padding-bottom: 20px;">
                <h1 style="font-size: 22px; font-weight: 800; color: #00BFC1; margin: 0; line-height: 1.3;">
                    {{ $trackingTitle }}
                </h1>
            </td>
        </tr>

        {{-- DETALLE DEL PEDIDO --}}
        <tr>
            <td style="padding-bottom: 20px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #4CD3D5; border-radius: 6px; overflow: hidden;">

                    {{-- Header full-ancho --}}
                    <tr>
                        <td colspan="3" style="background-color: #4CD3D5; padding: 10px 16px; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px;">
                            Detalle de su pedido
                        </td>
                    </tr>

                    {{-- Encabezados de columnas --}}
                    <tr style="background-color: #00BFC1;">
                        <th style="padding: 10px 16px; text-align: left; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Producto / Servicio</th>
                        <th width="50" style="padding: 10px 8px; text-align: center; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Cant.</th>
                        <th width="130" style="padding: 10px 16px; text-align: right; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">Total</th>
                    </tr>

                    {{-- Filas de productos --}}
                    @forelse($items as $item)
                    <tr style="border-bottom: 1px solid #e0f7f7;">
                        <td style="padding: 12px 16px; font-size: 13px; color: #00BFC1; font-weight: 500;">{{ $item['name'] }}</td>
                        <td style="padding: 12px 8px; text-align: center; font-size: 13px; color: #333333; font-weight: 700;">{{ $item['quantity'] }}</td>
                        <td style="padding: 12px 16px; text-align: right; font-size: 13px; color: #00BFC1; font-weight: 700; white-space: nowrap;">S/ {{ $item['line_total'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="padding: 16px; text-align: center; color: #94A3B8; font-size: 13px;">Sin productos</td>
                    </tr>
                    @endforelse

                    {{-- Línea divisoria entre productos y resumen --}}
                    <tr>
                        <td colspan="3" style="padding: 0; border-bottom: 2px solid #4CD3D5; font-size: 0;">&nbsp;</td>
                    </tr>

                    {{-- Spacer antes del resumen --}}
                    <tr>
                        <td colspan="3" style="height: 8px; font-size: 0;">&nbsp;</td>
                    </tr>

                    {{-- Subtotal --}}
                    <tr>
                        <td style="padding: 10px 16px; font-size: 13px; color: #333333;"></td>
                        <th scope="row" style="padding: 10px 16px; font-size: 13px; color: #555555; text-align: right; font-weight: normal;">Subtotal</th>
                        <td style="padding: 10px 16px; font-size: 13px; color: #333333; text-align: right;">S/ {{ $subtotal }}</td>
                    </tr>

                    {{-- Envío --}}
                    <tr>
                        <td style="padding: 6px 16px; font-size: 13px; color: #333333;"></td>
                        <th scope="row" style="padding: 6px 16px; font-size: 13px; color: #555555; text-align: right; font-weight: normal;">Envío</th>
                        <td style="padding: 6px 16px; font-size: 13px; color: #333333; text-align: right;">S/ {{ $shippingCost }}</td>
                    </tr>

                    {{-- Spacer --}}
                    <tr>
                        <td colspan="3" style="height: 6px; font-size: 0;">&nbsp;</td>
                    </tr>

                    {{-- Total a pagar --}}
                    <tr style="background-color: #00BFC1;">
                        <td style="padding: 12px 16px; font-size: 13px; color: #ffffff; font-weight: 700;"></td>
                        <th scope="row" style="padding: 12px 8px; font-size: 13px; color: #ffffff; font-weight: 700; text-align: right; white-space: nowrap;">Total a pagar</th>
                        <td style="padding: 12px 16px; font-size: 14px; color: #ffffff; font-weight: 800; text-align: right; white-space: nowrap;">S/ {{ $total }}</td>
                    </tr>

                </table>
            </td>
        </tr>

        {{-- DATOS DEL ENVÍO --}}
        @if($carrierName)
        <tr>
            <td style="padding-bottom: 20px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #4CD3D5; border-radius: 6px; overflow: hidden;">

                    <tr>
                        <td colspan="2" style="background-color: #4CD3D5; padding: 10px 16px; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px;">
                            Datos de tu envío
                        </td>
                    </tr>

                    <tr>
                        <th scope="row" style="padding: 10px 16px; font-size: 13px; color: #555555; border-bottom: 1px solid #e0f7f7; font-weight: normal; text-align: left;">Operador</th>
                        <td style="padding: 10px 16px; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right; border-bottom: 1px solid #e0f7f7;">{{ $carrierName }}</td>
                    </tr>

                    @if($trackingCode)
                    <tr>
                        <th scope="row" style="padding: 10px 16px; font-size: 13px; color: #555555; border-bottom: 1px solid #e0f7f7; font-weight: normal; text-align: left;">Código de seguimiento</th>
                        <td style="padding: 10px 16px; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right; border-bottom: 1px solid #e0f7f7;">{{ $trackingCode }}</td>
                    </tr>
                    @endif

                    @foreach($carrierFields as $field)
                    <tr>
                        <th scope="row" style="padding: 10px 16px; font-size: 13px; color: #555555; border-bottom: 1px solid #e0f7f7; font-weight: normal; text-align: left;">{{ $field['label'] }}</th>
                        <td style="padding: 10px 16px; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right; border-bottom: 1px solid #e0f7f7;">{{ $field['value'] }}</td>
                    </tr>
                    @endforeach

                    @if($trackingUrl)
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 14px 16px;">
                            <a href="{{ $trackingUrl }}" target="_blank" style="display: inline-block; padding: 10px 24px; background-color: #00BFC1; color: #ffffff !important; font-size: 13px; font-weight: 700; text-decoration: none; border-radius: 6px;">
                                Rastrear mi pedido
                            </a>
                        </td>
                    </tr>
                    @endif

                </table>
            </td>
        </tr>
        @endif

        {{-- REDES SOCIALES --}}
        <tr>
            <td style="text-align: center; padding: 8px 0 28px;">
                <p style="font-size: 11px; color: #94A3B8; font-weight: 600; margin: 0 0 14px; text-transform: uppercase; letter-spacing: 1px;">
                    Síguenos en redes sociales
                </p>
                <table cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 auto;">
                    <tr>
                        {{-- Facebook --}}
                        <td style="padding: 0 8px;">
                            <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/" target="_blank" style="text-decoration: none; display: block;">
                                <table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                    <tr>
                                        <td width="56" height="56" style="text-align: center; vertical-align: middle;">
                                            <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" width="56" height="56" alt="Facebook" style="display: block; margin: 0 auto;" />
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                        {{-- Instagram --}}
                        <td style="padding: 0 8px;">
                            <a href="https://www.instagram.com/lyrium_biomarketplace/" target="_blank" style="text-decoration: none; display: block;">
                                <table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                    <tr>
                                        <td width="56" height="56" style="text-align: center; vertical-align: middle;">
                                            <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" width="56" height="56" alt="Instagram" style="display: block; margin: 0 auto;" />
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                        {{-- YouTube --}}
                        <td style="padding: 0 8px;">
                            <a href="https://www.youtube.com/@LyriumBioMarketplace" target="_blank" style="text-decoration: none; display: block;">
                                <table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                    <tr>
                                        <td width="56" height="56" style="text-align: center; vertical-align: middle;">
                                            <img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png" width="56" height="56" alt="YouTube" style="display: block; margin: 0 auto;" />
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                        {{-- WhatsApp --}}
                        <td style="padding: 0 8px;">
                            <a href="https://wa.me/51999999999" target="_blank" style="text-decoration: none; display: block;">
                                <table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                    <tr>
                                        <td width="56" height="56" style="text-align: center; vertical-align: middle;">
                                            <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" width="56" height="56" alt="WhatsApp" style="display: block; margin: 0 auto;" />
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- BANNER INFERIOR --}}
        @if($bannerBottomCid)
        <tr>
            <td style="text-align: center; line-height: 0; font-size: 0; padding-top: 8px;">
                <img src="cid:{{ $bannerBottomCid }}" alt="Lyrium" style="display: block; width: 100%; max-width: 600px; height: auto; border: 0;" />
            </td>
        </tr>
        @endif

        {{-- CTA --}}
        <tr>
            <td style="text-align: center; padding: 28px 0 8px;">
                <a href="{{ $actionUrl }}" style="display: inline-block; padding: 14px 36px; background-color: #00BFC1; color: #ffffff !important; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 8px; letter-spacing: 0.2px;">
                    Ver mis pedidos
                </a>
            </td>
        </tr>

        <tr>
            <td style="text-align: center; padding-top: 8px;">
                <p style="font-size: 13px; color: #94A3B8; margin: 0;">
                    Gracias por confiar en Lyrium.
                </p>
            </td>
        </tr>

    </table>
@endsection