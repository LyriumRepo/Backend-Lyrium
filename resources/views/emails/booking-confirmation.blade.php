@extends('emails.layout')

@section('email_title', 'Confirmación de Cita — ' . $serviceName)

@section('email_content')

{{-- ══════════════════════════════════════════════════════════════
     IMAGEN HERO + BADGE DE VALIDACIÓN
══════════════════════════════════════════════════════════════ --}}
<img src="https://fv5-3.files.fm/thumb_show.php?i=yubvn64bfn&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a"
    alt="Confirmación de Cita - Lyrium"
    style="display:block;width:100%;max-width:600px;height:auto;margin:0 auto;" />

{{-- Badge: Validado por centro de salud --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
    <tr>
        <td style="padding:0 16px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background:#f0fdf9;border:1.5px solid #2db8b0;border-radius:0 0 10px 10px;">
                <tr>
                    <td style="padding:10px 18px;text-align:center;">
                        <span style="font-size:13px;font-weight:800;color:#0F766E;
                            letter-spacing:0.5px;">
                            🏥 Validado por centro de salud certificado
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════════
     SALUDO PERSONALIZADO POR ROL
══════════════════════════════════════════════════════════════ --}}
<p class="greeting" style="margin-top:20px;">
    @if($role === 'client')
    Hola, {{ $recipientName }} 👋
    @elseif($role === 'specialist')
    Hola, {{ $recipientName }} 🩺
    @else
    Hola, equipo de {{ $recipientName }} 🏪
    @endif
</p>

{{-- ══════════════════════════════════════════════════════════════
     TÍTULO Y BADGE DE ESTADO
══════════════════════════════════════════════════════════════ --}}
<h1 style="margin-top: 6px; margin-bottom: 16px;">
    @if($role === 'client')
    Tu cita ha sido reservada
    @elseif($role === 'specialist')
    Nueva cita asignada
    @else
    Nueva reserva en tu tienda
    @endif
</h1>

@php
$badgeClass = $status === 'confirmed' ? 'status-confirmed' : 'status-pending';
$badgeLabel = $status === 'confirmed' ? '✓ Confirmada' : '⏳ Pendiente de confirmación';
@endphp
<span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>

{{-- ══════════════════════════════════════════════════════════════
     AVISO: GOOGLE CALENDAR SINCRONIZADO
══════════════════════════════════════════════════════════════ --}}
@if($gcalOk)
<div class="highlight-box">
    <p>
        📅 <strong>Evento agregado a Google Calendar.</strong>
        Revisa tu calendario — la invitación ya aparece con todos los detalles.
        También recibirás un recordatorio 24 h antes de la cita.
    </p>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     AVISO: ICS ADJUNTO (solo si Google Calendar falló)
══════════════════════════════════════════════════════════════ --}}
@if($hasIcs)
<div class="ics-notice">
    <p>
        📎 <strong>Archivo de cita adjunto (cita-lyrium.ics).</strong>
        Abre el archivo adjunto para agregar esta cita a Google Calendar,
        Apple Calendar u Outlook con un solo clic.
    </p>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TABLA: DETALLES DE SU SERVICIO (NUEVO DISEÑO)
══════════════════════════════════════════════════════════════ --}}
<div style="font-family: Arial, sans-serif; margin-top: 20px; margin-bottom: 20px;">
    {{-- Pestaña superior --}}
    <div style="display: inline-block; background-color: #5EEAD4; color: white; padding: 10px 20px; font-weight: bold; text-transform: uppercase; border-top-left-radius: 4px; border-top-right-radius: 4px; font-size: 14px;">
        DETALLE DE SU SERVICIO
    </div>

    {{-- Contenedor principal de la tabla --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 2px solid #5EEAD4; border-collapse: collapse;">
        {{-- Encabezado de la tabla --}}
        <thead>
            <tr>
                <th style="background-color: #14B8A6; color: white; padding: 12px; text-align: left; text-transform: uppercase; font-size: 14px; width: 50%;">SERVICIO</th>
                <th style="background-color: #14B8A6; color: white; padding: 12px; text-align: center; text-transform: uppercase; font-size: 14px; width: 25%;">CANT.</th>
                <th style="background-color: #14B8A6; color: white; padding: 12px; text-align: center; text-transform: uppercase; font-size: 14px; width: 25%;">TOTAL</th>
            </tr>
        </thead>

        {{-- Cuerpo de la tabla --}}
        <tbody>
            <tr>
                <td style="padding: 15px; border-bottom: 1px solid #E2E8F0; vertical-align: top;">
                    <div style="color: #5EEAD4; font-weight: bold; font-size: 16px; margin-bottom: 10px;">
                        {{ $serviceName }}
                    </div>
                    <ul style="margin: 0; padding-left: 20px; color: #334155; font-size: 14px; line-height: 1.6;">
                        <li>Especialista : {{ $specialistName }}</li>
                        <li>Fecha : {{ $appointmentDate }}</li>
                        <li>Hora : {{ $appointmentTime }}</li>
                        @if($role !== 'client')
                        <li>Cliente : {{ $clientName }}</li>
                        @endif
                        @if($role !== 'seller')
                        <li>Tienda : {{ $storeName }}</li>
                        @endif
                        @if($isHomeService && isset($serviceAddress))
                        <li>Dirección : {{ $serviceAddress }}</li>
                        @elseif(!$isHomeService && isset($storeAddress))
                        <li>Dirección : {{ $storeAddress }}</li>
                        @endif
                    </ul>
                </td>
                <td style="padding: 15px; border-bottom: 1px solid #E2E8F0; text-align: center; vertical-align: middle; font-weight: bold; color: #0F172A;">
                    1
                </td>
                <td style="padding: 15px; border-bottom: 1px solid #E2E8F0; text-align: center; vertical-align: middle; font-weight: bold; color: #5EEAD4;">
                    S/ {{ number_format($price, 2) }}
                </td>
            </tr>

            {{-- Subtotales y Total --}}
            @if($role === 'client')
            <tr>
                <td style="padding: 15px; text-align: right; font-weight: normal; color: #0F172A;" colspan="2">
                    Subtotal
                </td>
                <td style="padding: 15px; text-align: center; font-weight: normal; color: #0F172A;">
                    S/ {{ number_format($price, 2) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 15px; text-align: right; font-weight: normal; color: #0F172A; padding-bottom: 25px;" colspan="2">
                    Envió
                </td>
                <td style="padding: 15px; text-align: center; font-weight: normal; color: #0F172A; padding-bottom: 25px;">
                    S/ {{ isset($shippingCost) ? number_format($shippingCost, 2) : '10.00' }}
                </td>
            </tr>
            <tr>
                <th style="background-color: #14B8A6; color: white; padding: 15px; text-align: center; font-size: 16px;" colspan="2">
                    Total a pagar
                </th>
                <th style="background-color: #14B8A6; color: white; padding: 15px; text-align: center; font-size: 16px;">
                    S/ {{ number_format($price + (isset($shippingCost) ? $shippingCost : 10), 2) }}
                </th>
            </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════════
         INFORMACIÓN ADICIONAL SEGÚN ROL
    ══════════════════════════════════════════════════════════════ --}}
@if($role === 'client')
<hr class="divider" />
<h2>¿Qué sigue?</h2>
<p>
    El equipo de <strong>{{ $storeName }}</strong> revisará tu reserva y la confirmará
    en breve. Te avisaremos por email cuando esté confirmada.
</p>
<p style="font-size: 13px; color: #94A3B8; margin-bottom: 0;">
    Si necesitas cancelar o reagendar, hazlo con al menos
    <strong>24 horas de anticipación</strong> desde tu panel en Lyrium.
</p>

@elseif($role === 'specialist')
<hr class="divider" />
<h2>Información de la consulta</h2>
<p>
    Se ha asignado una nueva cita a tu agenda. Revisa tu panel para
    ver el historial completo del paciente y los detalles adicionales.
</p>
@if(!$gcalOk)
<div class="warning-box">
    <p>
        ⚠ <strong>Nota:</strong> La sincronización automática con Google Calendar
        no pudo completarse. Abre el archivo <em>cita-lyrium.ics</em> adjunto para
        agregar la cita manualmente a tu calendario.
    </p>
</div>
@endif

@else {{-- seller --}}
<hr class="divider" />
<h2>Acción requerida</h2>
<p>
    Tienes una nueva reserva pendiente de confirmación. Ingresa a tu panel
    para confirmarla o gestionarla.
</p>
<a href="{{ config('app.frontend_url', 'https://lyrium.pe') }}/seller/bookings"
    class="cta-button">
    Ver reserva en mi panel →
</a>
@if(!$gcalOk)
<div class="warning-box">
    <p>
        ⚠ <strong>Nota:</strong> La sincronización con Google Calendar no pudo completarse.
        El archivo <em>cita-lyrium.ics</em> adjunto te permite agregar la cita manualmente.
    </p>
</div>
@endif
@endif

{{-- ══════════════════════════════════════════════════════════════
     REDES SOCIALES
══════════════════════════════════════════════════════════════ --}}
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
                            <img src="https://cdn-icons-png.flaticon.com/128/4138/4138124.png"
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

<img src="https://fv5-4.files.fm/thumb_show.php?i=67vwf5vakf&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a" alt="Lyrium"
    style="display:block;width:100%;max-width:600px;height:auto;margin:4px 0 0;" />

@endsection