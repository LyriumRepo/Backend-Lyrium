<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Validación de recepción — Lyrium Biomarketplace</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #F0FDF4;
    color: #111827;
    margin: 0;
    padding: 32px 16px;
    display: flex;
    justify-content: center;
  }
  .card {
    background: #ffffff;
    border: 1.5px solid {{ $estado === 'validated' ? '#15803D' : '#D1D5DB' }};
    border-radius: 12px;
    max-width: 420px;
    width: 100%;
    padding: 28px 24px;
  }
  .brand { font-size: 13px; font-weight: 700; color: #14532D; letter-spacing: -0.2px; }
  .brand span { color: #9CA3AF; font-weight: 400; }
  .check {
    width: 56px; height: 56px; border-radius: 50%;
    background: {{ $estado === 'validated' ? '#15803D' : '#9CA3AF' }}; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; margin: 20px auto 12px;
  }
  h1 { text-align: center; font-size: 16px; color: #14532D; margin: 0 0 4px; }
  .sub { text-align: center; font-size: 12.5px; color: #6B7280; margin: 0 0 22px; line-height: 1.6; }
  .bonus {
    background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px;
    text-align: center; padding: 12px; font-size: 13px; color: #14532D;
    margin-bottom: 18px;
  }
  .bonus strong { font-size: 16px; }
  .btn {
    display: block; text-align: center; background: #15803D; color: #fff;
    text-decoration: none; border-radius: 8px; padding: 11px 16px;
    font-size: 13.5px; font-weight: 600;
  }
  .footer {
    margin-top: 22px; text-align: center; font-size: 10.5px; color: #9CA3AF;
    line-height: 1.6;
  }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">LYRIUM <span>Biomarketplace</span></div>
    <div class="check">{!! $estado === 'validated' ? '&#10003;' : '&#8505;' !!}</div>

    @if ($estado === 'validated')
      <h1>¡Gracias! Tu {{ $tipo }} ha sido validad{{ $tipo === 'reserva' ? 'a' : 'o' }}</h1>
      <p class="sub">{{ $tipo === 'reserva' ? 'Reserva' : 'Pedido' }} #{{ $numero }} — confirmaste que todo llegó bien.</p>
      @if ($liriosBonus)
        <div class="bonus">Has ganado <strong>{{ $liriosBonus }} Lirios</strong> 🎉<br>Úsalos en tu próxima compra.</div>
      @endif
    @elseif ($estado === 'already_validated')
      <h1>Est{{ $tipo === 'reserva' ? 'a reserva' : 'e pedido' }} ya fue validad{{ $tipo === 'reserva' ? 'a' : 'o' }}</h1>
      <p class="sub">{{ $tipo === 'reserva' ? 'Reserva' : 'Pedido' }} #{{ $numero }} — no es necesario volver a confirmar.</p>
    @elseif ($estado === 'auto_expired')
      <h1>Est{{ $tipo === 'reserva' ? 'a reserva' : 'e pedido' }} ya se cerró automáticamente</h1>
      <p class="sub">{{ $tipo === 'reserva' ? 'Reserva' : 'Pedido' }} #{{ $numero }} — el plazo de validación venció y se cerró por inacción.</p>
    @else
      <h1>Aún no se puede validar</h1>
      <p class="sub">{{ $tipo === 'reserva' ? 'La reserva' : 'El pedido' }} #{{ $numero }} todavía no figura como entregad{{ $tipo === 'reserva' ? 'a' : 'o' }}.</p>
    @endif

    <a class="btn" href="{{ $frontendUrl }}/{{ $tipo === 'reserva' ? 'customer/bookings' : 'customer/orders' }}">Ir a mi panel</a>

    <div class="footer">Lyrium Biomarketplace<br>Este enlace es personal y de un solo uso.</div>
  </div>
</body>
</html>
