@extends('emails.layout')

@section('email_title', $status === 'approved' ? 'Tu producto fue aprobado - Lyrium' : 'Tu producto no fue aprobado - Lyrium')

@section('email_content')

@if($status === 'approved')
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;margin-bottom:20px;">
    <tr>
      <td style="width:52px;padding:12px 0 12px 14px;vertical-align:middle;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#15803D,#064e3b);border-radius:10px;text-align:center;line-height:42px;font-size:20px;">✅</div>
      </td>
      <td style="padding:12px 14px;vertical-align:middle;">
        <div style="font-size:9px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:1.5px;">Producto Aprobado</div>
        <div style="font-size:13px;font-weight:600;color:#14532d;margin-top:3px;">{{ $productName }} &mdash; ya está visible en el marketplace</div>
      </td>
    </tr>
  </table>

  <h1 style="font-size:18px;color:#111827;margin:0 0 8px;font-weight:700;">¡Hola, {{ $name }}!</h1>
  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 16px;">Tu producto <strong>{{ $productName }}</strong> de la tienda <strong>{{ $storeName }}</strong> ha sido revisado y <strong>aprobado</strong> por el equipo Lyrium. Ya está visible para los clientes en el marketplace.</p>

  <div style="background:#f0fdf4;border-left:3px solid #15803D;border-radius:0 9px 9px 0;padding:12px 15px;margin-bottom:20px;">
    <p style="margin:0;font-size:13px;color:#166534;font-weight:500;line-height:1.5;">🌿 Sigue publicando productos de calidad para crecer en la comunidad Lyrium BioMarketplace.</p>
  </div>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="text-align:center;padding:6px 0 10px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 40px;background:linear-gradient(135deg,#15803D,#166534);color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;border-radius:9px;">Ver Mis Productos &rarr;</a>
      </td>
    </tr>
  </table>

@else

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:20px;">
    <tr>
      <td style="width:52px;padding:12px 0 12px 14px;vertical-align:middle;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#b45309,#7c2d12);border-radius:10px;text-align:center;line-height:42px;font-size:20px;">❌</div>
      </td>
      <td style="padding:12px 14px;vertical-align:middle;">
        <div style="font-size:9px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:1.5px;">Producto No Aprobado</div>
        <div style="font-size:13px;font-weight:600;color:#7c2d12;margin-top:3px;">{{ $productName }}</div>
      </td>
    </tr>
  </table>

  <h1 style="font-size:18px;color:#111827;margin:0 0 8px;font-weight:700;">Hola, {{ $name }}</h1>
  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 16px;">Tu producto <strong>{{ $productName }}</strong> de la tienda <strong>{{ $storeName }}</strong> no fue aprobado en esta oportunidad.</p>

  @if($reason)
  <div style="background:#fffbeb;border-left:3px solid #b45309;border-radius:0 9px 9px 0;padding:12px 15px;margin-bottom:20px;">
    <p style="margin:0;font-size:13px;color:#92400e;font-weight:500;line-height:1.5;"><strong>Motivo:</strong> {{ $reason }}</p>
  </div>
  @endif

  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 16px;">Puedes editar el producto corrigiendo los puntos señalados y enviarlo nuevamente para revisión.</p>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="text-align:center;padding:6px 0 10px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 40px;background:linear-gradient(135deg,#15803D,#166534);color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;border-radius:9px;">Editar Producto &rarr;</a>
      </td>
    </tr>
  </table>

@endif

  <p style="text-align:center;font-size:11px;color:#9ca3af;margin:6px 0 0;">¿Preguntas? Escríbenos a soporte@lyrium.pe</p>

@endsection
