@extends('emails.layout')

@section('email_title', $level === 'out' ? 'Producto agotado — Lyrium' : 'Stock crítico — Lyrium')

@section('email_content')
<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#14532d;">Hola, {{ $sellerName }}</p>

@if($level === 'out')
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#b91c1c;">⚠️ Producto agotado</p>
      <p style="margin:6px 0 0;font-size:14px;color:#374151;">
        Tu producto <strong>{{ $productName }}</strong> se ha agotado (0 unidades). Los clientes no podrán comprarlo hasta que reponga el stock.
      </p>
    </td>
  </tr>
</table>
@else
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#fff7ed;border-left:4px solid #ea580c;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#c2410c;">🔔 Stock en nivel crítico</p>
      <p style="margin:6px 0 0;font-size:14px;color:#374151;">
        Tu producto <strong>{{ $productName }}</strong> tiene solo <strong>{{ $stock }} unidad(es)</strong> disponibles. Considera reabastecer pronto.
      </p>
    </td>
  </tr>
</table>
@endif

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <th scope="row" style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;text-align:left;">Producto</th>
    <td style="padding:10px 16px;font-size:13px;color:#14532d;font-weight:700;border-bottom:1px solid #e5e7eb;">{{ $productName }}</td>
  </tr>
  <tr>
    <th scope="row" style="padding:10px 16px;font-size:13px;color:#6b7280;font-weight:600;text-align:left;">Unidades disponibles</th>
    <td style="padding:10px 16px;font-size:13px;color:#{{ $level === 'out' ? 'b91c1c' : 'c2410c' }};font-weight:700;">{{ $stock }}</td>
  </tr>
</table>

<p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
  Ingresa a tu panel de inventario para actualizar el stock y mantener tu catálogo activo.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
        Ir al inventario
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
  Este correo se generó automáticamente.
</p>
@endsection
