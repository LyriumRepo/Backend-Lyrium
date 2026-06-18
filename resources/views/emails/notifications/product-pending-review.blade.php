@extends('emails.layout')

@section('email_title', 'Nuevo producto pendiente de revisión — Lyrium')

@section('email_tagline', 'Panel de Administración')

@section('email_content')

  {{-- Tarjeta de evento --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;margin-bottom:20px;">
    <tr>
      <td style="width:52px;padding:12px 0 12px 14px;vertical-align:middle;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#15803D,#064e3b);border-radius:10px;text-align:center;line-height:42px;font-size:20px;">🔍</div>
      </td>
      <td style="padding:12px 14px;vertical-align:middle;">
        <div style="font-size:9px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:1.5px;">Revisión Requerida</div>
        <div style="font-size:13px;font-weight:600;color:#14532d;margin-top:3px;">Nuevo producto enviado por {{ $storeName }}</div>
      </td>
    </tr>
  </table>

  <h1 style="font-size:18px;color:#111827;margin:0 0 8px;font-weight:700;">Hola, {{ $adminName }}</h1>
  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 18px;">Un vendedor ha publicado un nuevo producto que requiere tu revisión y aprobación antes de aparecer en el marketplace.</p>

  {{-- Detalle del producto --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d1fae5;border-radius:10px;overflow:hidden;margin-bottom:20px;">
    <tr>
      <td style="background:#f0fdf4;padding:8px 16px;border-bottom:1px solid #d1fae5;">
        <span style="font-size:9px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:1.5px;">Detalle del Producto</span>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #f9fafb;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Producto</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ $productName }}</td>
        </tr></table>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #f9fafb;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Tienda</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ $storeName }}</td>
        </tr></table>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #f9fafb;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Tipo</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ ucfirst($productType) }}</td>
        </tr></table>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Precio</td>
          <td style="font-size:16px;font-weight:800;color:#14532d;text-align:right;">S/ {{ number_format($price, 2) }}</td>
        </tr></table>
      </td>
    </tr>
  </table>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="text-align:center;padding:6px 0 10px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 40px;background:linear-gradient(135deg,#15803D,#166534);color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;border-radius:9px;">Revisar Producto &rarr;</a>
      </td>
    </tr>
  </table>

  <p style="text-align:center;font-size:11px;color:#9ca3af;margin:6px 0 0;">Correo generado automáticamente. No respondas a este mensaje.</p>

@endsection
