@extends('emails.layout')

@section('email_title', 'Nuevo pedido recibido - Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="greeting">¡Hola, {{ $name }}!</div>
                <p style="margin-top: 12px;">Has recibido un nuevo pedido en tu tienda <strong>"{{ $storeName }}"</strong>.</p>

                <div class="status-badge status-confirmed">🆕 Pedido Confirmado</div>

                <div class="info-card">
                    <div class="info-card-title">Resumen del Pedido</div>
                    <div class="info-row">
                        <span class="info-label">N° Pedido</span>
                        <span class="info-value" style="font-family: monospace;">#{{ $orderNumber }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cliente</span>
                        <span class="info-value">{{ $customerName }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Productos</span>
                        <span class="info-value">{{ $itemsCount }} producto(s)</span>
                    </div>
                    <div class="info-row" style="border-bottom: none; padding-bottom: 0;">
                        <span class="info-label">Total</span>
                        <span class="info-value" style="font-size: 18px; color: #1a3a2a;">S/ {{ number_format($total, 2) }}</span>
                    </div>
                </div>

                @if(count($items) > 0)
                <div class="info-card">
                    <div class="info-card-title">Detalle de Productos</div>
                    @foreach($items as $item)
                    <div class="info-row">
                        <span class="info-label">{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                        <span class="info-value">S/ {{ number_format($item['line_total'], 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($shippingAddress)
                <div class="info-card">
                    <div class="info-card-title">Dirección de Envío</div>
                    <p style="margin: 0; color: #1E293B; font-weight: 500;">{{ $shippingAddress }}</p>
                </div>
                @endif

                <a href="{{ $actionUrl }}" class="cta-button">Ver Pedido</a>

                <p style="text-align: center; font-size: 13px; color: #94A3B8;">
                    Revisa y confirma el pedido lo antes posible para mantener satisfecho a tu cliente.
                </p>
            </td>
        </tr>
    </table>
@endsection
