@extends('emails.layout')

@section('email_title', 'Nuevo servicio pendiente de revisión — Lyrium')
@section('email_tagline', 'Panel de Administración')

@section('email_content')
<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#14532d;">Hola, {{ $adminName }}</p>

<p style="margin:0 0 16px;font-size:14px;color:#4b5563;line-height:1.6;">
  Un vendedor ha registrado un nuevo servicio que requiere tu revisión y aprobación.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#15803d;">🆕 Servicio pendiente de aprobación</p>
    </td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <td style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;">Servicio</td>
    <td style="padding:10px 16px;font-size:13px;color:#14532d;font-weight:700;border-bottom:1px solid #e5e7eb;">{{ $serviceName }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;">Tienda</td>
    <td style="padding:10px 16px;font-size:13px;color:#374151;border-bottom:1px solid #e5e7eb;">{{ $storeName }}</td>
  </tr>
  @if($price)
  <tr>
    <td style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;">Precio</td>
    <td style="padding:10px 16px;font-size:13px;color:#374151;font-weight:700;">S/ {{ number_format($price, 2) }}</td>
  </tr>
  @endif
</table>

<p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
  Ingresa al panel de administración para revisar los detalles del servicio y decidir si aprobarlo o rechazarlo.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
        Revisar servicio
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
  Este correo se generó automáticamente. No respondas a este mensaje.
</p>
@endsection
