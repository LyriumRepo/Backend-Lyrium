@extends('emails.layout')

@section('email_title', 'Tienda actualizada — Lyrium Admin')
@section('email_tagline', 'Panel de Administración')

@section('email_content')
<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#14532d;">Hola, {{ $adminName }}</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    <td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 8px 8px 0;padding:14px 16px;">
      <p style="margin:0;font-size:15px;font-weight:600;color:#15803d;">✏️ Actualización de datos</p>
      <p style="margin:6px 0 0;font-size:14px;color:#374151;">
        La tienda <strong>{{ $storeName }}</strong> ha actualizado sus <strong>{{ $changeLabel }}</strong>.
      </p>
    </td>
  </tr>
</table>

<p style="margin:0 0 20px;font-size:14px;color:#4b5563;line-height:1.6;">
  Puedes revisar los cambios en el panel de administración y verificar que la información cumpla con las políticas de Lyrium BioMarketplace.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;">
        Ver tienda en el panel
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
  Esta notificación es automática. Solo tú como administrador la recibes.
</p>
@endsection
