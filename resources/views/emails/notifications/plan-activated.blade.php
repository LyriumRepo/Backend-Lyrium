@extends('emails.layout')

@section('email_title', 'Plan activado - Lyrium')

@section('email_content')

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;margin-bottom:20px;">
    <tr>
      <td style="width:52px;padding:12px 0 12px 14px;vertical-align:middle;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#15803d,#166534);border-radius:10px;text-align:center;line-height:42px;font-size:20px;">✅</div>
      </td>
      <td style="padding:12px 14px;vertical-align:middle;">
        <div style="font-size:9px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:1.5px;">Plan Activado</div>
        <div style="font-size:13px;font-weight:600;color:#166534;margin-top:3px;">{{ $planName }} &mdash; activo hasta {{ $endsAt }}</div>
      </td>
    </tr>
  </table>

  <h1 style="font-size:18px;color:#111827;margin:0 0 8px;font-weight:700;">Hola, {{ $name }}</h1>
  <p style="font-size:13.5px;color:#4b5563;line-height:1.7;margin:0 0 18px;">Tu plan <strong>{{ $planName }}</strong> para la tienda <strong>{{ $storeName }}</strong> ha sido activado exitosamente. Ya puedes publicar productos y servicios en el marketplace.</p>

  <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #6ee7b7;border-radius:10px;overflow:hidden;margin-bottom:20px;">
    <tr>
      <td style="background:#ecfdf5;padding:8px 16px;border-bottom:1px solid #6ee7b7;">
        <span style="font-size:9px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:1.5px;">Detalle del Plan</span>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #d1fae5;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Plan activo</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ $planName }}</td>
        </tr></table>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;border-bottom:1px solid #d1fae5;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Tienda</td>
          <td style="font-size:12.5px;font-weight:600;color:#111827;text-align:right;">{{ $storeName }}</td>
        </tr></table>
      </td>
    </tr>
    <tr>
      <td style="padding:9px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td style="font-size:12px;color:#6b7280;">Válido hasta</td>
          <td style="font-size:16px;font-weight:800;color:#15803d;text-align:right;">{{ $endsAt }}</td>
        </tr></table>
      </td>
    </tr>
  </table>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="text-align:center;padding:6px 0 10px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 40px;background:linear-gradient(135deg,#15803D,#166534);color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;border-radius:9px;">Ver mi plan &rarr;</a>
      </td>
    </tr>
  </table>

  <p style="text-align:center;font-size:11px;color:#9ca3af;margin:6px 0 0;">¿Preguntas? Escríbenos a soporte@lyrium.pe</p>

@endsection
