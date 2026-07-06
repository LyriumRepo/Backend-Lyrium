@extends('emails.layout')

@section('email_title', 'Pedido entregado — Lyrium BioMarketplace')

@section('email_content')
<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#14532d;">Hola, {{ $name }}</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#15803d;">✅ Pedido entregado y confirmado</p>
      <p style="margin:6px 0 0;font-size:14px;color:#374151;">
        El cliente <strong>{{ $customerName }}</strong> confirmó la recepción del pedido <strong>#{{ $orderNumber }}</strong>.
      </p>
    </td>
  </tr>
</table>

<p style="margin:0 0 16px;font-size:14px;color:#4b5563;line-height:1.6;">
  Resumen de los productos entregados en <strong>{{ $storeName }}</strong>:
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;">
  <tr>
    <th style="text-align:left;font-size:12px;color:#6b7280;font-weight:600;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Producto</th>
    <th style="text-align:center;font-size:12px;color:#6b7280;font-weight:600;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Cant.</th>
    <th style="text-align:right;font-size:12px;color:#6b7280;font-weight:600;padding:6px 8px;border-bottom:1px solid #e5e7eb;">Subtotal</th>
  </tr>
  @foreach($items as $item)
  <tr>
    <td style="font-size:13px;color:#374151;padding:8px;border-bottom:1px solid #f3f4f6;">{{ $item['name'] }}</td>
    <td style="font-size:13px;color:#374151;padding:8px;text-align:center;border-bottom:1px solid #f3f4f6;">{{ $item['quantity'] }}</td>
    <td style="font-size:13px;color:#374151;padding:8px;text-align:right;border-bottom:1px solid #f3f4f6;">S/ {{ number_format($item['line_total'], 2) }}</td>
  </tr>
  @endforeach
  <tr>
    <td colspan="2" style="font-size:14px;font-weight:700;color:#14532d;padding:10px 8px 4px;text-align:right;">Total:</td>
    <td style="font-size:14px;font-weight:700;color:#14532d;padding:10px 8px 4px;text-align:right;">S/ {{ number_format($total, 2) }}</td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
        Ver mis pedidos
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
  Gracias por vender en Lyrium BioMarketplace 🌿
</p>
@endsection
