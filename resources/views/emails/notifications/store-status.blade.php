@extends('emails.layout')

@section('email_title', 'Estado de tienda actualizado - Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="greeting">{!! $greeting !!}</div>

                @if($status === 'approved')
                    <p style="margin-top: 12px;">¡Felicitaciones! Tu tienda <strong>"{{ $storeName }}"</strong> ha sido <strong>aprobada</strong>.</p>
                    <p>Ya puedes iniciar sesión y comenzar a gestionar tus productos y servicios.</p>

                    <div class="status-badge status-confirmed">✅ Tienda Aprobada</div>

                    <div class="highlight-box">
                        <p>🎉 ¡Bienvenido a Lyrium BioMarketplace! Estamos emocionados de tenerte a bordo.</p>
                    </div>

                    <a href="{{ $actionUrl }}" class="cta-button">Ir a Mi Tienda</a>

                @elseif($status === 'rejected')
                    <p style="margin-top: 12px;">Lamentablemente, tu tienda <strong>"{{ $storeName }}"</strong> no fue aprobada.</p>

                    @if($reason)
                    <div class="warning-box">
                        <p><strong>Motivo:</strong> {{ $reason }}</p>
                    </div>
                    @endif

                    <p>Puedes contactarnos si tienes alguna consulta o deseas más información.</p>

                @elseif($status === 'banned')
                    <p style="margin-top: 12px;">Tu tienda <strong>"{{ $storeName }}"</strong> ha sido suspendida.</p>

                    @if($reason)
                    <div class="warning-box">
                        <p><strong>Motivo:</strong> {{ $reason }}</p>
                    </div>
                    @endif

                    <p>Si crees que esto es un error, puedes contactarnos para resolver cualquier inconveniente.</p>

                @else
                    <p style="margin-top: 12px;">El estado de tu tienda <strong>"{{ $storeName }}"</strong> ha cambiado a: <strong>{{ $status }}</strong>.</p>
                @endif

                <p style="text-align: center; font-size: 13px; color: #94A3B8; margin-top: 24px;">
                    Si tienes preguntas, responde a este correo o contáctanos en soporte@lyrium.pe
                </p>
            </td>
        </tr>
    </table>
@endsection
