@extends('emails.layout')

@section('email_title', 'Gracias por tu compra en Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="text-align: center; padding-bottom: 24px;">
                <img src="cid:gracias-compra" alt="Gracias por tu compra" style="max-width: 100%; height: auto; border-radius: 12px;" />
            </td>
        </tr>
        <tr>
            <td>
                <div class="greeting">¡Hola, {{ $customerName }}!</div>

                <p style="margin-top: 12px;">
                    Gracias por tu compra en <strong>Lyrium</strong>.
                </p>

                <p>
                    Hemos recibido correctamente tu pedido y comenzaremos a procesarlo.
                </p>

                <p>
                    Puedes consultar el estado de tu compra en cualquier momento desde la sección
                    <strong>"Mis Pedidos"</strong> de tu cuenta.
                </p>

                <div class="info-card" style="margin-top: 24px;">
                    <div class="info-card-title">Resumen de tu Compra</div>
                    <div class="info-row">
                        <span class="info-label">N° Pedido</span>
                        <span class="info-value" style="font-family: monospace;">#{{ $orderNumber }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha de compra</span>
                        <span class="info-value">{{ $orderDate }}</span>
                    </div>
                    <div class="info-row" style="border-bottom: none; padding-bottom: 0;">
                        <span class="info-label">Total pagado</span>
                        <span class="info-value" style="font-size: 18px; color: #1a3a2a;">S/ {{ $total }}</span>
                    </div>
                </div>

                <a href="{{ $actionUrl }}" class="cta-button">Ver mis pedidos</a>

                <p style="text-align: center; font-size: 13px; color: #94A3B8;">
                    Gracias por confiar en Lyrium.
                </p>
            </td>
        </tr>
    </table>
@endsection
