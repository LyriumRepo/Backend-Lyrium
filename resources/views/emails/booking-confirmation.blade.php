@extends('emails.layout')

@section('email_title', 'Confirmación de Cita — ' . $serviceName)

@section('email_content')

{{-- ══════════════════════════════════════════════════════════════
     SALUDO PERSONALIZADO POR ROL
══════════════════════════════════════════════════════════════ --}}
<p class="greeting">
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
     TARJETA: DETALLES DE LA CITA
══════════════════════════════════════════════════════════════ --}}
<div class="info-card">
    <div class="info-card-title">📋 Detalles de la cita</div>

    <div class="info-row">
        <span class="info-label">Servicio</span>
        <span class="info-value">{{ $serviceName }}</span>
    </div>

    <div class="info-row">
        <span class="info-label">Especialista</span>
        <span class="info-value">{{ $specialistName }}</span>
    </div>

    {{-- El cliente no necesita ver su propio nombre, pero el especialista y vendedor sí --}}
    @if($role !== 'client')
    <div class="info-row">
        <span class="info-label">Cliente</span>
        <span class="info-value">{{ $clientName }}</span>
    </div>
    @endif

    @if($role !== 'seller')
    <div class="info-row">
        <span class="info-label">Tienda</span>
        <span class="info-value">{{ $storeName }}</span>
    </div>
    @endif

    <div class="info-row">
        <span class="info-label">📅 Fecha</span>
        <span class="info-value">{{ $appointmentDate }}</span>
    </div>

    <div class="info-row">
        <span class="info-label">🕐 Hora</span>
        <span class="info-value">{{ $appointmentTime }} ({{ $duration }} min)</span>
    </div>

    @if($role === 'client')
    <div class="info-row">
        <span class="info-label">💳 Total pagado</span>
        <span class="info-value" style="color: #15803D;">S/ {{ $price }}</span>
    </div>
    @endif

    @if($role === 'seller')
    <div class="info-row">
        <span class="info-label">💰 Monto</span>
        <span class="info-value">S/ {{ $price }}</span>
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     NOTAS DEL CLIENTE (si existen)
══════════════════════════════════════════════════════════════ --}}
@if($customerNotes)
<div class="info-card" style="border-left: 4px solid #2563EB;">
    <div class="info-card-title">💬 Notas del cliente</div>
    <p style="margin: 0; font-size: 14px; color: #334155;">{{ $customerNotes }}</p>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     MENSAJE CONTEXTUAL POR ROL
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

@endsection