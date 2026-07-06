<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 30px 35px; }
    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 9px;
      color: #1a1a1a;
      margin: 0;
      padding: 0;
      line-height: 1.5;
    }

    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-left { width: 50%; }
    .header-right { width: 50%; text-align: right; }
    .logo-img { width: 120px; }
    .issuer-name { font-size: 12px; font-weight: bold; color: #1a1a1a; }
    .issuer-ruc { font-size: 8px; color: #666; margin-top: 2px; }

    .confirmation-badge {
      display: inline-block;
      background-color: #15803D;
      color: #fff;
      font-size: 10px;
      font-weight: bold;
      padding: 5px 16px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      border-radius: 3px;
    }

    .divider { border: none; border-top: 2px solid #15803D; margin: 0 0 14px 0; }

    .section-title {
      font-size: 7.5px; font-weight: bold;
      background-color: #15803D; color: #fff;
      padding: 4px 8px; text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 0;
    }

    .info-table { width: 100%; border-collapse: collapse; border: 1px solid #ccc; border-top: none; margin-bottom: 12px; }
    .info-table td { padding: 4px 8px; font-size: 8px; }
    .info-table .label { font-weight: bold; color: #555; width: 140px; }
    .info-table .value { color: #1a1a1a; }
    .info-table tr:nth-child(even) td { background-color: #f8f8f8; }

    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .items-table th {
      background-color: #15803D; color: #fff;
      font-size: 7px; text-transform: uppercase;
      padding: 4px 6px; text-align: center;
      border: 1px solid #15803D;
      letter-spacing: 0.3px;
    }
    .items-table td {
      padding: 4px 6px; font-size: 8px;
      border: 1px solid #ccc;
    }
    .items-table .right { text-align: right; }
    .items-table .center { text-align: center; }
    .items-table .left { text-align: left; }
    .items-table tr:nth-child(even) td { background-color: #f8f8f8; }

    .totals { width: 50%; margin-left: auto; border-collapse: collapse; margin-bottom: 14px; }
    .totals td { padding: 3px 8px; font-size: 8px; }
    .totals .label { text-align: right; color: #555; }
    .totals .value { text-align: right; font-weight: bold; color: #1a1a1a; }
    .totals .grand td { border-top: 2px solid #15803D; padding-top: 5px; }
    .totals .grand .label { font-size: 10px; font-weight: bold; color: #1a1a1a; }
    .totals .grand .value { font-size: 11px; font-weight: bold; color: #15803D; }

    .status-box {
      text-align: center;
      border: 2px solid #15803D;
      border-radius: 4px;
      padding: 10px 0;
      margin-bottom: 14px;
    }
    .status-box .status-text {
      font-size: 14px;
      font-weight: bold;
      color: #15803D;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .status-box .status-sub {
      font-size: 7.5px;
      color: #888;
      margin-top: 3px;
    }

    .footer { text-align: center; font-size: 6.5px; color: #999; padding-top: 10px; }
    .footer-divider { border: none; border-top: 0.5px dashed #aaa; margin: 8px 0; }
  </style>
</head>
<body>

@php
  $iconPath = public_path('images/iconologo.png');
  $iconBase64 = file_exists($iconPath) ? base64_encode(file_get_contents($iconPath)) : '';
@endphp

  <table class="header-table">
    <tr>
      <td class="header-left">
        @if($iconBase64)
          <img src="data:image/png;base64,{{ $iconBase64 }}" class="logo-img" alt="Lyrium">
        @endif
        <div class="issuer-name">LYRIUM EIRL</div>
        <div class="issuer-ruc">RUC 20612731838</div>
      </td>
      <td class="header-right">
        <div class="confirmation-badge">CONFIRMACIÓN DE PAGO</div>
        <div style="margin-top:6px;font-size:8px;color:#555;">
          {{ now()->format('d/m/Y H:i') }}
        </div>
      </td>
    </tr>
  </table>

  <hr class="divider">

  <div class="status-box">
    <div class="status-text">✓ PAGO CONFIRMADO</div>
    <div class="status-sub">Orden {{ $order->order_number }}</div>
  </div>

  <div class="section-title">DATOS DE LA ORDEN</div>
  <table class="info-table">
    <tr>
      <td class="label">N° de Orden:</td>
      <td class="value">{{ $order->order_number }}</td>
    </tr>
    <tr>
      <td class="label">Fecha de Compra:</td>
      <td class="value">{{ $order->created_at->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
      <td class="label">Método de Pago:</td>
      <td class="value">{{ ucfirst($order->payment_method ?? '—') }}</td>
    </tr>
    <tr>
      <td class="label">Estado del Pago:</td>
      <td class="value">{{ ucfirst($order->payment_status ?? '—') }}</td>
    </tr>
    <tr>
      <td class="label">Cliente:</td>
      <td class="value">{{ $order->shipping_name ?: $order->user->name }}</td>
    </tr>
    <tr>
      <td class="label">Email:</td>
      <td class="value">{{ $order->shipping_email ?: $order->user->email }}</td>
    </tr>
    @if($order->shipping_phone)
    <tr>
      <td class="label">Teléfono:</td>
      <td class="value">{{ $order->shipping_phone }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">Dirección de Envío:</td>
      <td class="value">{{ $order->shipping_address ?? '—' }}{{ $order->shipping_city ? ', ' . $order->shipping_city : '' }}</td>
    </tr>
  </table>

  <div class="section-title">DETALLE DE PRODUCTOS</div>
  <table class="items-table">
    <thead>
      <tr>
        <th width="10%">Cant.</th>
        <th width="50%">Producto</th>
        <th width="20%">Precio Unit.</th>
        <th width="20%">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td class="center">{{ $item->quantity }}</td>
        <td class="left">{{ $item->product_name }}</td>
        <td class="right">S/ {{ number_format((float) $item->unit_price, 2) }}</td>
        <td class="right">S/ {{ number_format((float) $item->line_total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <td class="label">Subtotal:</td>
      <td class="value">S/ {{ number_format((float) $order->subtotal, 2) }}</td>
    </tr>
    <tr>
      <td class="label">Envío:</td>
      <td class="value">S/ {{ number_format((float) $order->shipping_cost, 2) }}</td>
    </tr>
    @if((float) $order->discount_amount > 0)
    <tr>
      <td class="label">Descuento:</td>
      <td class="value">— S/ {{ number_format((float) $order->discount_amount, 2) }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">IGV (18%):</td>
      <td class="value">S/ {{ number_format((float) $order->tax_amount, 2) }}</td>
    </tr>
    <tr class="grand">
      <td class="label">TOTAL PAGADO:</td>
      <td class="value">S/ {{ number_format((float) $order->total, 2) }}</td>
    </tr>
  </table>

  <hr class="footer-divider">

  <div class="footer">
    <div>Lyrium EIRL — RUC 20612731838</div>
    <div>MZ.KD LT.5 URB.SANTA MARGARITA — VILLA MARÍA DEL TRIUNFO — LIMA</div>
    <div style="margin-top:4px;">Este documento es una confirmación de pago generada automáticamente.</div>
    <div>No es un comprobante de pago electrónico ante SUNAT.</div>
  </div>

</body>
</html>
