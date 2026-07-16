@extends('emails.layout-clean')

@section('email_title', 'Confirmación de suscripción — Lyrium')

@section('email_top')
<img src="https://fv5-5.files.fm/thumb_show.php?i=msu7t9u4py&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a"
     width="100%" alt="Confirmación de suscripción — Lyrium"
     style="display:block;width:100%;height:auto;border:0;" />
@endsection

@section('email_content')

{{-- ===== SALUDO ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 14px;">
    <tr>
        <td>
            <p style="margin:0 0 6px;font-size:18px;font-weight:800;color:#1a3a2a;">¡Hola, {{ $name }}!</p>
            <p style="margin:0;font-size:13.5px;color:#4b5563;line-height:1.7;">
                Tu suscripción al plan <strong>{{ $planName }}</strong> para la tienda <strong>{{ $storeName }}</strong> ha sido activada exitosamente.
                @if($isTrial) Este plan fue activado como período de prueba sin cargo. @else A continuación encontrarás el comprobante de tu pago. @endif
            </p>
        </td>
    </tr>
</table>

{{-- ===== DETALLE DE LA SUSCRIPCIÓN ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 4px;">
    <tr>
        <td style="padding:0;">

            {{-- Header teal --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#2BBFBF;border-radius:0;">
                <tr>
                    <th colspan="2" style="padding:12px 16px;font-size:13px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:1.5px;text-align:left;">
                        Detalle de la suscripción
                    </th>
                </tr>
            </table>

            {{-- Filas --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid #d5f0ef;border-top:none;">
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Plan</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:800;color:#2BBFBF;text-align:right;">{{ $planName }}</td>
                </tr>
                <tr style="background:#f9fffe;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Tienda</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:700;color:#1a3a2a;text-align:right;">{{ $storeName }}</td>
                </tr>
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Duración</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:600;color:#1a3a2a;text-align:right;">{{ $months }} {{ $months === 1 ? 'mes' : 'meses' }}</td>
                </tr>
                <tr style="background:#f9fffe;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Fecha de inicio</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:600;color:#1a3a2a;text-align:right;">{{ $startsAt }}</td>
                </tr>
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Válido hasta</th>
                    <td style="padding:11px 14px;font-size:15px;font-weight:900;color:#2BBFBF;text-align:right;">{{ $endsAt }}</td>
                </tr>
                <tr style="background:#f9fffe;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Comisión por venta</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:700;color:#1a3a2a;text-align:right;">{{ $commission }}</td>
                </tr>
            </table>

        </td>
    </tr>
</table>

{{-- ===== COMPROBANTE DE PAGO ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 4px;">
    <tr>
        <td style="padding:0;">

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#2BBFBF;">
                <tr>
                    <th colspan="2" style="padding:12px 16px;font-size:13px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:1.5px;text-align:left;">
                        Comprobante de pago
                    </th>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid #d5f0ef;border-top:none;">
                @if($isTrial)
                <tr style="background:#ffffff;">
                    <td colspan="2" style="padding:14px;font-size:13px;font-weight:700;color:#15803d;text-align:center;">
                        ✅ Período de prueba — sin cargo
                    </td>
                </tr>
                @else
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">N° de referencia</th>
                    <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#1a3a2a;text-align:right;font-family:monospace;">{{ $referenceId }}</td>
                </tr>
                <tr style="background:#f9fffe;border-bottom:1px solid #d5f0ef;">
                    <th scope="row" style="padding:11px 14px;font-size:13px;color:#555;font-weight:normal;text-align:left;">Método de pago</th>
                    <td style="padding:11px 14px;font-size:13px;font-weight:600;color:#1a3a2a;text-align:right;">{{ $paymentMethod === 'izipay' ? 'Izipay' : ucfirst($paymentMethod) }}</td>
                </tr>
                @endif
            </table>

            {{-- Total pagado --}}
            @if(!$isTrial)
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0;">
                <tr style="background:#2BBFBF;">
                    <td style="padding:13px 14px;font-size:13px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Total pagado</td>
                    <td style="padding:13px 14px;font-size:17px;font-weight:900;color:#ffffff;text-align:right;white-space:nowrap;">S/ {{ number_format((float)$totalAmount, 2) }}</td>
                </tr>
            </table>
            @endif

        </td>
    </tr>
</table>

{{-- ===== CTA ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 8px;">
    <tr>
        <td style="text-align:center;">
            <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 44px;background:#2BBFBF;color:#ffffff;font-size:13.5px;font-weight:800;text-decoration:none;border-radius:9px;letter-spacing:0.3px;">Ver mi plan &rarr;</a>
        </td>
    </tr>
</table>

{{-- ===== REDES SOCIALES ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1.5px solid #d1f0eb;margin:8px 0 0;padding:20px 0 16px;">
    <tr>
        <td style="text-align:center;padding:20px 16px 16px;">
            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/" target="_blank" style="text-decoration:none;">
                            <img src="https://fv5-4.files.fm/thumb_show.php?i=726g592gj8&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a" width="44" height="44" alt="Facebook" style="display:block;border-radius:50%;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.instagram.com/lyrium_biomarketplace/" target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/4138/4138124.png" width="44" height="44" alt="Instagram" style="display:block;border-radius:10px;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://wa.me/51937093420" target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/15713/15713434.png" width="44" height="44" alt="WhatsApp" style="display:block;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.youtube.com/@LyriumBiomarketplace" target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/1384/1384060.png" width="44" height="44" alt="YouTube" style="display:block;" />
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


@endsection

@section('email_bottom')
<img src="https://fv5-4.files.fm/thumb_show.php?i=67vwf5vakf&view&v=1&PHPSESSID=b6862738416edfc1629012e3aecc89734866bb64"
     width="100%" alt="Lyrium Biomarketplace 2025"
     style="display:block;width:100%;height:auto;border:0;" />
@endsection
