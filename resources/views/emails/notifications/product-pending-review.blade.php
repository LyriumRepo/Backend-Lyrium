@extends('emails.layout')

@section('email_title', 'Nuevo producto pendiente de revisión — Lyrium')

@section('email_content')
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="greeting">Hola, {{ $adminName }}</div>

                <p style="margin-top: 12px;">
                    Un vendedor ha registrado un nuevo producto que requiere tu revisión y aprobación.
                </p>

                <div class="status-badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;padding:12px 18px;margin:18px 0;font-size:14px;">
                    🆕 Producto pendiente de aprobación
                </div>

                <div class="highlight-box">
                    <p><strong>Producto:</strong> {{ $productName }}</p>
                    <p><strong>Tienda:</strong> {{ $storeName }}</p>
                    <p><strong>Tipo:</strong> {{ ucfirst($productType) }}</p>
                    <p><strong>Precio:</strong> S/ {{ number_format($price, 2) }}</p>
                </div>

                <p>
                    Ingresa al panel de administración para revisar los detalles del producto y tomar una decisión de aprobación o rechazo.
                </p>

                <a href="{{ $actionUrl }}" class="cta-button">Revisar producto</a>

                <p style="text-align: center; font-size: 13px; color: #94A3B8; margin-top: 24px;">
                    Este correo se generó automáticamente. No respondas a este mensaje.
                </p>
            </td>
        </tr>
    </table>
@endsection
