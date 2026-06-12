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
            <td style="text-align: center; padding-bottom: 16px;">
                <h1 style="font-size: 22px; font-weight: 800; color: #00BFC1; margin: 0; line-height: 1.3;">
                    {{ $trackingTitle }}
                </h1>
            </td>
        </tr>

        <tr>
            <td style="height: 4px; font-size: 0;">&nbsp;</td>
        </tr>

        {{-- DETALLE DE SU PEDIDO --}}
        <tr>
            <td style="padding-bottom: 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #4CD3D5; border-radius: 6px; overflow: hidden;">

                    {{-- Pestaña superior "DETALLE DE SU PEDIDO" --}}
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="background-color: #4CD3D5; padding: 10px 18px 8px 18px; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px; border-radius: 0 0 6px 6px; white-space: nowrap;">Detalle de tu pedido</td>
                                    <td style="border-bottom: 1px solid #4CD3D5;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Encabezados de tabla --}}
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #00BFC1;">
                                        <th style="padding: 10px 16px; text-align: left; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Producto</th>
                                        <th width="60" style="padding: 10px 16px; text-align: center; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Cant.</th>
                                        <th width="100" style="padding: 10px 16px; text-align: right; font-size: 10px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($items as $item)
                                    <tr style="border-bottom: 1px solid #4CD3D5;">
                                        <td style="padding: 12px 16px; font-size: 13px; color: #000000; font-weight: 500;">{{ $item['name'] }}</td>
                                        <td style="padding: 12px 16px; text-align: center; font-size: 13px; color: #000000; font-weight: 700;">{{ $item['quantity'] }}</td>
                                        <td style="padding: 12px 16px; text-align: right; font-size: 13px; color: #4CD3D5; font-weight: 700;">S/ {{ $item['line_total'] }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" style="padding: 16px; text-align: center; color: #94A3B8; font-size: 13px;">Sin productos</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="height: 20px; font-size: 0;">&nbsp;</td>
        </tr>

        {{-- DATOS DEL ENVÍO (operador logístico) --}}
        @if($carrierName)
        <tr>
            <td style="padding-bottom: 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #4CD3D5; border-radius: 6px; overflow: hidden;">
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="background-color: #4CD3D5; padding: 10px 18px 8px 18px; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px; border-radius: 0 0 6px 6px; white-space: nowrap;">Datos de tu env\u00edo</td>
                                    <td style="border-bottom: 1px solid #4CD3D5;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 18px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 6px 0; font-size: 13px; color: #000000; border-bottom: 1px solid #e2e8f0;">Operador</td>
                                    <td style="padding: 6px 0; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right;">{{ $carrierName }}</td>
                                </tr>
                                @if($trackingCode)
                                <tr>
                                    <td style="padding: 6px 0; font-size: 13px; color: #000000; border-bottom: 1px solid #e2e8f0;">C\u00f3digo de seguimiento</td>
                                    <td style="padding: 6px 0; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right;">{{ $trackingCode }}</td>
                                </tr>
                                @endif
                                @foreach($carrierFields as $field)
                                <tr>
                                    <td style="padding: 6px 0; font-size: 13px; color: #000000; border-bottom: 1px solid #e2e8f0;">{{ $field['label'] }}</td>
                                    <td style="padding: 6px 0; font-size: 13px; color: #00BFC1; font-weight: 700; text-align: right;">{{ $field['value'] }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @if($trackingUrl)
                    <tr>
                        <td style="text-align: center; padding: 0 18px 16px;">
                            <a href="{{ $trackingUrl }}" target="_blank" style="display: inline-block; padding: 10px 24px; background-color: #00BFC1; color: #ffffff !important; font-size: 13px; font-weight: 700; text-decoration: none; border-radius: 6px; letter-spacing: 0.2px;">
                                Rastrear mi pedido
                            </a>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>

        <tr>
            <td style="height: 20px; font-size: 0;">&nbsp;</td>
        </tr>
        @endif

        {{-- RESUMEN ECONÓMICO --}}
        <tr>
            <td style="padding-bottom: 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #4CD3D5; border-radius: 6px; overflow: hidden;">

                    {{-- Pestaña superior "RESUMEN" --}}
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="background-color: #4CD3D5; padding: 10px 18px 8px 18px; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px; border-radius: 0 0 6px 6px; white-space: nowrap;">Resumen</td>
                                    <td style="border-bottom: 1px solid #4CD3D5;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Summary rows --}}
                    <tr>
                        <td style="padding: 4px 16px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 10px 0; font-size: 13px; color: #000000; border-bottom: 1px solid #4CD3D5;">Subtotal</td>
                                    <td style="padding: 10px 0; font-size: 13px; color: #4CD3D5; font-weight: 700; text-align: right; border-bottom: 1px solid #4CD3D5;">S/ {{ $subtotal }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; font-size: 13px; color: #000000; border-bottom: 1px solid #4CD3D5;">Env\u00edo</td>
                                    <td style="padding: 10px 0; font-size: 13px; color: #4CD3D5; font-weight: 700; text-align: right; border-bottom: 1px solid #4CD3D5;">S/ {{ $shippingCost }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #ffffff; font-weight: 800; background-color: #00BFC1;">TOTAL A PAGAR</td>
                                    <td style="padding: 12px 16px; font-size: 17px; color: #ffffff; font-weight: 800; text-align: right; background-color: #00BFC1;">S/ {{ $total }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="height: 20px; font-size: 0;">&nbsp;</td>
        </tr>

        {{-- REDES SOCIALES --}}
        <tr>
            <td style="text-align: center; padding: 8px 0 24px;">
                <p style="font-size: 12px; color: #94A3B8; font-weight: 600; margin: 0 0 16px; text-transform: uppercase; letter-spacing: 1px;">
                    S\u00edguenos en redes sociales
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        {{-- Facebook --}}
                        <td width="25%" style="text-align: center; padding: 0 6px;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 50%; background-color: #1877F2; width: 44px; height: 44px; text-align: center; vertical-align: middle;">
                                        <a href="https://facebook.com" target="_blank" style="text-decoration: none; display: block; line-height: 44px;">
                                            <span style="font-size: 20px; color: #ffffff; font-weight: bold;">f</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        {{-- Instagram --}}
                        <td width="25%" style="text-align: center; padding: 0 6px;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 50%; background: radial-gradient(circle at 30% 30%, #fdf497, #fd5949, #d6249f, #285AEB); width: 44px; height: 44px; text-align: center; vertical-align: middle;">
                                        <a href="https://instagram.com" target="_blank" style="text-decoration: none; display: block; line-height: 44px;">
                                            <span style="font-size: 22px; color: #ffffff; font-weight: bold;">&#9679;</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        {{-- YouTube --}}
                        <td width="25%" style="text-align: center; padding: 0 6px;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 50%; background-color: #FF0000; width: 44px; height: 44px; text-align: center; vertical-align: middle;">
                                        <a href="https://youtube.com" target="_blank" style="text-decoration: none; display: block; line-height: 44px;">
                                            <span style="font-size: 20px; color: #ffffff; font-weight: bold;">&#9654;</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        {{-- WhatsApp --}}
                        <td width="25%" style="text-align: center; padding: 0 6px;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 50%; background-color: #25D366; width: 44px; height: 44px; text-align: center; vertical-align: middle;">
                                        <a href="https://wa.me/51999999999" target="_blank" style="text-decoration: none; display: block; line-height: 44px;">
                                            <span style="font-size: 22px; color: #ffffff; font-weight: bold;">&#9990;</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
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
