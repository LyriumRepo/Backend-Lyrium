<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación de recibo — Lyrium Biomarketplace</title>
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
    border: 1.5px solid #15803D;
    border-radius: 12px;
    max-width: 420px;
    width: 100%;
    padding: 28px 24px;
  }
  .brand { font-size: 13px; font-weight: 700; color: #14532D; letter-spacing: -0.2px; }
  .brand span { color: #9CA3AF; font-weight: 400; }
  .check {
    width: 56px; height: 56px; border-radius: 50%;
    background: #15803D; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; margin: 20px auto 12px;
  }
  h1 { text-align: center; font-size: 16px; color: #14532D; margin: 0 0 4px; }
  .sub { text-align: center; font-size: 12px; color: #6B7280; margin: 0 0 22px; }
  .row {
    display: flex; justify-content: space-between; padding: 8px 0;
    border-bottom: 1px solid #F3F4F6; font-size: 12.5px;
  }
  .row:last-child { border-bottom: none; }
  .row .lbl { color: #6B7280; }
  .row .val { color: #111827; font-weight: 600; text-align: right; }
  .footer {
    margin-top: 22px; text-align: center; font-size: 10.5px; color: #9CA3AF;
    line-height: 1.6;
  }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">LYRIUM <span>Biomarketplace</span></div>
    <div class="check">&#10003;</div>
    <h1>Recibo válido</h1>
    <p class="sub">Este comprobante fue emitido por Lyrium Biomarketplace</p>

    <div class="row"><span class="lbl">Recibo</span><span class="val">{{ $invoice->series }}-{{ $invoice->number }}</span></div>
    <div class="row"><span class="lbl">Tienda</span><span class="val">{{ $invoice->store?->trade_name ?? '—' }}</span></div>
    <div class="row"><span class="lbl">Plan</span><span class="val">{{ $invoice->planRequest?->plan?->name ?? '—' }}</span></div>
    <div class="row"><span class="lbl">Monto</span><span class="val">S/ {{ number_format((float) $invoice->total, 2) }}</span></div>
    <div class="row"><span class="lbl">Fecha de emisión</span><span class="val">{{ ($invoice->emission_date ?? $invoice->created_at)->format('d/m/Y') }}</span></div>

    <div class="footer">
      Comprobante interno de Lyrium Biomarketplace.<br>No constituye factura electrónica SUNAT.
    </div>
  </div>
</body>
</html>
