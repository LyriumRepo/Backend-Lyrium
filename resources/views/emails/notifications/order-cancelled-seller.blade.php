@extends('emails.layout')

@section('email_title', 'Pedido cancelado - Lyrium')

@section('email_content')

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:20px;">
    <tr>
      <td style="width:52px;padding:12px 0 12px 14px;vertical-align:middle;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#b45309,#7c2d12);border-radius:10px;text-align:center;line-height:42px;font-size:20px;">⚠️</div>
      </td>
      <td style="padding:12px 14px;vertical-align:middle;">
        <div style="font-size:9px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:1.5px;">Pedido Cancelado</div>
        <div style="font-size:13px;font-weight:600;color:#7c2d12;margin-top:3px;">{{ $storeName }} &middot; Pedido #{{ $orderNumber }}</div>
      </td>
    </tr>
  </table>

  <h1 style="font-size:18px;color:#111827;margin:0 0 8px;font-weight:700;">Hola, {{ $name }}</h1>
  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 18px;">El pedido <strong>#{{ $orderNumber }}</strong> del cliente <strong>{{ $customerName }}</strong> en tu tienda <strong>{{ $storeName }}</strong> ha sido cancelado.</p>

  {{-- Detalle del pedido cancelado --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #fde68a;border-radius:10px;overflow:hidden;margin-bottom:20px;">
    <tr>
      <td style="background:#fffbeb;padding:8px 16px;border-bottom:1px solid #fde68a;">
        <span style="font-size:9px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:1.5px;">Detalle del Pedido Cancelado</span>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #fef3c7;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <th scope="row" style="font-size:12px;color:#6b7280;font-weight:normal;text-align:left;">Cliente</th>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ $customerName }}</td>
        </tr></table>
      </td>
    </tr>
    @foreach($items as $item)
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #fef3c7;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">{{ $item['quantity'] }}x {{ $item['name'] }}</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">S/ {{ number_format($item['line_total'], 2) }}</td>
        </tr></table>
      </td>
    </tr>
    @endforeach
    <tr>
      <td style="padding:9px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <th scope="row" style="font-size:12px;color:#6b7280;font-weight:normal;text-align:left;">Total cancelado</th>
          <td style="font-size:16px;font-weight:800;color:#b45309;text-align:right;">S/ {{ number_format($total, 2) }}</td>
        </tr></table>
      </td>
    </tr>
  </table>

  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 16px;">El stock de los productos ha sido repuesto automáticamente.</p>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="text-align:center;padding:6px 0 10px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 40px;background:linear-gradient(135deg,#15803D,#166534);color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;border-radius:9px;">Ver Mis Pedidos &rarr;</a>
      </td>
    </tr>
  </table>

  <p style="text-align:center;font-size:11px;color:#9ca3af;margin:6px 0 0;">¿Preguntas? Escríbenos a soporte@lyrium.pe</p>

@endsection
