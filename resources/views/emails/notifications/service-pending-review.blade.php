@extends('emails.layout')

@section('email_title', 'Nuevo servicio pendiente de revisión — Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="greeting">Hola, {{ $adminName }}</div>

                <p style="margin-top: 12px;">
                    Un vendedor ha registrado un nuevo servicio que requiere tu revisión y aprobación.
                </p>

                <div class="status-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:8px;padding:12px 18px;margin:18px 0;font-size:14px;">
                    🆕 Servicio pendiente de aprobación
                </div>

                <div class="highlight-box">
                    <p><strong>Servicio:</strong> {{ $serviceName }}</p>
                    <p><strong>Tienda:</strong> {{ $storeName }}</p>
                    @if($price)
                    <p><strong>Precio:</strong> S/ {{ number_format($price, 2) }}</p>
                    @endif
                </div>

                <p>
                    Ingresa al panel de administración para revisar los detalles del servicio y decidir si aprobarlo o rechazarlo.
                </p>

                <a href="{{ $actionUrl }}" class="cta-button">Revisar servicio</a>

                <p style="text-align: center; font-size: 13px; color: #94A3B8; margin-top: 24px;">
                    Este correo se generó automáticamente. No respondas a este mensaje.
                </p>
            </td>
        </tr>
    </table>
@endsection
