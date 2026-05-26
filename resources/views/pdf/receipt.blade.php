<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 30px 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #16a34a; padding-bottom: 15px; }
    .header h1 { color: #16a34a; font-size: 18px; margin: 0 0 5px; text-transform: uppercase; }
    .header p { color: #666; font-size: 9px; margin: 0; }
    .order-info { margin-bottom: 20px; }
    .order-info table { width: 100%; border-collapse: collapse; }
    .order-info td { padding: 3px 0; font-size: 9px; }
    .order-info td:first-child { font-weight: bold; width: 130px; color: #555; }
    .order-info td:last-child { font-weight: bold; color: #222; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.items th { background: #16a34a; color: #fff; font-size: 8px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
    table.items td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
    table.items tr:nth-child(even) td { background: #f9fafb; }
    table.items .right { text-align: right; }
    .totals { margin-left: auto; width: 250px; }
    .totals table { width: 100%; border-collapse: collapse; }
    .totals td { padding: 4px 8px; font-size: 9px; }
    .totals td:first-child { text-align: right; font-weight: bold; color: #555; }
    .totals td:last-child { text-align: right; font-weight: bold; }
    .totals .grand-total td { border-top: 2px solid #16a34a; font-size: 12px; color: #16a34a; padding-top: 6px; }
    .footer { text-align: center; margin-top: 30px; font-size: 8px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 10px; }
  </style>
</head>
<body>
  <div class="header">
    <h1>Lyrium</h1>
    <p>Comprobante de Pedido</p>
  </div>

  <div class="order-info">
    <table>
      <tr><td>N° Pedido:</td><td>{{ $order->order_number }}</td></tr>
      <tr><td>Fecha:</td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>
      <tr><td>Cliente:</td><td>{{ $order->shipping_name ?: $order->user->name }}</td></tr>
      <tr><td>Email:</td><td>{{ $order->shipping_email ?: $order->user->email }}</td></tr>
      @if($order->shipping_phone)<tr><td>Teléfono:</td><td>{{ $order->shipping_phone }}</td></tr>@endif
      @if($order->shipping_address)<tr><td>Dirección:</td><td>{{ $order->shipping_address }}, {{ $order->shipping_city }} {{ $order->shipping_postal_code }}</td></tr>@endif
      <tr><td>Estado:</td><td>{{ $order->getStatusLabel() }}</td></tr>
    </table>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th>Producto</th>
        <th class="right">Precio Unit.</th>
        <th class="right">Cant.</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td>{{ $item->product_name }}</td>
        <td class="right">S/ {{ number_format($item->unit_price, 2) }}</td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">S/ {{ number_format($item->line_total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="totals">
    <table>
      <tr><td>Subtotal:</td><td>S/ {{ number_format($order->subtotal, 2) }}</td></tr>
      @if((float)$order->shipping_cost > 0)
      <tr><td>Envío:</td><td>S/ {{ number_format($order->shipping_cost, 2) }}</td></tr>
      @endif
      <tr><td>IGV (16%):</td><td>S/ {{ number_format($order->tax_amount, 2) }}</td></tr>
      @if((float)$order->discount_amount > 0)
      <tr><td>Descuento:</td><td>- S/ {{ number_format($order->discount_amount, 2) }}</td></tr>
      @endif
      <tr class="grand-total"><td>Total:</td><td>S/ {{ number_format($order->total, 2) }}</td></tr>
    </table>
  </div>

  <div class="footer">
    <p>Lyrium — Marketplace de productos saludables y sostenibles</p>
    <p>Este comprobante fue generado el {{ now()->format('d/m/Y H:i:s') }}</p>
  </div>
</body>
</html>
