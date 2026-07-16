@extends('emails.layout')

@section('email_title', 'Reserva cancelada — Lyrium')

@section('email_content')
<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#14532d;">Hola, {{ $name }}</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#b91c1c;">❌ Reserva cancelada</p>
      <p style="margin:6px 0 0;font-size:14px;color:#374151;">
        @if($role === 'seller')
          La reserva del servicio <strong>{{ $serviceName }}</strong> ha sido cancelada por el cliente.
        @else
          Tu reserva del servicio <strong>{{ $serviceName }}</strong> ha sido cancelada.
        @endif
      </p>
    </td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <th scope="row" style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;text-align:left;">Servicio</th>
    <td style="padding:10px 16px;font-size:13px;color:#14532d;font-weight:700;border-bottom:1px solid #e5e7eb;">{{ $serviceName }}</td>
  </tr>
  <tr>
    <th scope="row" style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;text-align:left;">Proveedor</th>
    <td style="padding:10px 16px;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">{{ $storeName }}</td>
  </tr>
  <tr>
    <th scope="row" style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;text-align:left;">Fecha</th>
    <td style="padding:10px 16px;font-size:13px;color:#374151;">{{ $date }}</td>
  </tr>
</table>

@if($role === 'client')
<p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
  Si tienes dudas sobre la cancelación, puedes contactar al soporte de Lyrium desde tu panel de cliente.
</p>
@else
<p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
  El espacio queda libre en tu agenda. Puedes revisar tus próximas reservas desde el panel de vendedor.
</p>
@endif

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
        @if($role === 'seller') Ver mis reservas @else Ver mis pedidos @endif
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
  Lyrium BioMarketplace 🌿
</p>
@endsection
