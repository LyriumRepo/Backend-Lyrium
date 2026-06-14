@extends('emails.layout')

@section('email_title', $level === 'out' ? 'Producto agotado — Lyrium' : 'Stock crítico — Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="greeting">Hola, {{ $sellerName }}</div>

                @if($level === 'out')
                    <p style="margin-top: 12px;">
                        Tu producto se ha <strong>agotado</strong>. Los clientes no podrán comprarlo hasta que reponga el stock.
                    </p>

                    <div class="status-badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:12px 18px;margin:18px 0;font-size:14px;">
                        ⚠️ Sin stock disponible
                    </div>
                @else
                    <p style="margin-top: 12px;">
                        Tu producto está llegando a un nivel de stock <strong>crítico</strong>. Te recomendamos reabastecer pronto para evitar quedarte sin existencias.
                    </p>

                    <div class="status-badge" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:8px;padding:12px 18px;margin:18px 0;font-size:14px;">
                        🔔 Stock en nivel crítico
                    </div>
                @endif

                <div class="highlight-box">
                    <p><strong>Producto:</strong> {{ $productName }}</p>
                    <p><strong>Unidades disponibles:</strong> {{ $stock }}</p>
                </div>

                <p>
                    Ingresa a tu panel de inventario para actualizar el stock y mantener tu catálogo activo.
                </p>

                <a href="{{ $actionUrl }}" class="cta-button">Ir al inventario</a>

                <p style="text-align: center; font-size: 13px; color: #94A3B8; margin-top: 24px;">
                    Este correo se generó automáticamente. No respondas a este mensaje.
                </p>
            </td>
        </tr>
    </table>
@endsection
