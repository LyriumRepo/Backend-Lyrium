<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 30px 42px; }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 8px;
      color: #111827;
      margin: 0; padding: 0;
      line-height: 1.5;
      background: #ffffff;
    }

    /* ── HEADER ─────────────────────────────── */
    .hdr { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .hdr td { vertical-align: middle; padding: 0; }

    .logo-row { margin-bottom: 6px; }
    .logo-icon { width: 54px; height: 54px; vertical-align: middle; }
    .logo-img  { width: 130px; vertical-align: middle; margin-left: 6px; }

    .brand-name { font-size: 12px; font-weight: bold; color: #14532D; letter-spacing: -0.2px; margin-bottom: 3px; }
    .brand-ruc  { font-size: 7.5px; color: #374151; margin-bottom: 1px; }
    .brand-ruc b { color: #111827; }
    .brand-addr { font-size: 6.5px; color: #9CA3AF; line-height: 1.7; }

    .doc-badge {
      text-align: center;
      border: 1.5px solid #15803D;
      background: #ffffff;
      padding: 11px 22px;
      float: right;
    }
    .doc-badge-type { font-size: 7px; font-weight: bold; color: #15803D; text-transform: uppercase; letter-spacing: 1.3px; }
    .doc-badge-num  { font-size: 15px; font-weight: bold; color: #111827; margin: 4px 0 3px; }
    .doc-badge-date { font-size: 6.5px; color: #6B7280; }
    .doc-badge-date b { color: #374151; }
    .doc-badge-cur  { font-size: 6px; font-weight: bold; color: #15803D; margin-top: 4px; letter-spacing: 0.5px; }

    /* ── DIVIDERS ───────────────────────────── */
    .line-accent { height: 2px; background: #15803D; border: none; margin: 0 0 18px; }
    .line-light  { height: 1px; background: #E5E7EB; border: none; margin: 0 0 16px; }

    /* ── PARTIES ────────────────────────────── */
    .parties { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .parties td { vertical-align: top; padding: 0; }
    .party-col-l { width: 48%; padding-right: 10px; }
    .party-col-r { width: 48%; padding-left: 20px; border-left: 1px solid #E5E7EB; }

    .party-tag  { font-size: 5.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.3px; color: #9CA3AF; margin-bottom: 5px; }
    .party-name { font-size: 9px; font-weight: bold; color: #111827; margin-bottom: 2px; }
    .party-ruc  { font-size: 7.5px; color: #374151; font-weight: bold; margin-bottom: 1px; }
    .party-info { font-size: 7px; color: #6B7280; line-height: 1.7; }
    .party-ref  { font-size: 6.5px; color: #9CA3AF; margin-top: 5px; }
    .party-ref b { color: #374151; }

    /* ── ITEMS TABLE ────────────────────────── */
    .items-wrap { border: 1px solid #E5E7EB; margin-bottom: 0; page-break-inside: avoid; }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table thead tr { background: #F9FAFB; }
    .items-table th {
      font-size: 5.5px; font-weight: bold;
      text-transform: uppercase; letter-spacing: 0.8px;
      color: #9CA3AF; padding: 8px 10px;
      border-bottom: 1px solid #E5E7EB;
      text-align: left;
    }
    .items-table th.r { text-align: right; }
    .items-table td { padding: 13px 10px; font-size: 8px; color: #111827; vertical-align: top; }
    .items-table td.r { text-align: right; font-weight: bold; }
    .item-title { font-size: 8.5px; font-weight: bold; color: #111827; margin-bottom: 3px; }
    .item-sub   { font-size: 6.5px; color: #9CA3AF; }

    /* ── TOTAL ──────────────────────────────── */
    .total-bar {
      width: 100%; margin-top: 14px; margin-bottom: 14px;
      background: #14532D; color: #ffffff;
      padding: 10px 16px; border-collapse: collapse;
    }
    .total-bar .lbl { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #BBF7D0; }
    .total-bar .val { font-size: 14px; font-weight: bold; text-align: right; }

    .status-wrap { text-align: center; margin-bottom: 18px; }
    .status-badge {
      display: inline-block;
      background: #F0FDF4;
      color: #15803D;
      border: 1px solid #BBF7D0;
      font-size: 7px; font-weight: bold;
      text-transform: uppercase; letter-spacing: 1px;
      padding: 4px 14px;
    }
    .status-badge.pending { background: #FFFBEB; color: #B45309; border-color: #FDE68A; }
    .status-badge.failed  { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }

    /* ── QR VERIFICACIÓN ────────────────────── */
    .qr-section { width: 100%; border-collapse: collapse; margin-bottom: 18px; page-break-inside: avoid; }
    .qr-card {
      border: 1.5px solid #15803D;
      background: #F0FDF4;
      padding: 14px 20px;
      display: table;
      width: 100%;
      box-sizing: border-box;
    }
    .qr-card-inner { display: table; width: 100%; border-collapse: collapse; }
    .qr-left  { display: table-cell; vertical-align: middle; width: 110px; padding-right: 16px; }
    .qr-right { display: table-cell; vertical-align: middle; }
    .qr-img-wrap {
      background: #ffffff;
      padding: 6px;
      border: 1px solid #BBF7D0;
      display: inline-block;
    }
    .qr-img-wrap img { display: block; width: 90px; height: 90px; }
    .qr-badge {
      display: inline-block;
      background: #15803D;
      color: #ffffff;
      font-size: 5px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      padding: 3px 7px;
      margin-bottom: 7px;
    }
    .qr-title  { font-size: 9px; font-weight: bold; color: #14532D; margin-bottom: 4px; }
    .qr-desc   { font-size: 6.5px; color: #374151; line-height: 1.8; }
    .qr-desc b { color: #14532D; }
    .qr-url    { font-size: 6px; color: #15803D; margin-top: 5px; font-weight: bold; }

    /* ── FOOTER ─────────────────────────────── */
    .footer-txt  { font-size: 6.5px; color: #9CA3AF; line-height: 1.9; margin-bottom: 4px; }
    .footer-txt b { color: #374151; }

    .footer-bar {
      margin-top: 14px; padding: 8px 14px;
      background: #14532D;
      color: #ffffff; font-size: 6.5px; text-align: center;
    }
    .footer-bar .slogan { font-style: italic; opacity: 0.85; }
    .footer-bar .sep { opacity: 0.35; margin: 0 9px; }
  </style>
</head>
<body>

@php
  $issuerRuc     = '20612731838';
  $issuerName    = 'LYRIUM E.I.R.L.';
  $issuerAddress = 'MZ.KD LT.5 URB.SANTA MARGARITA';
  $issuerCity    = 'VILLA MARÍA DEL TRIUNFO - LIMA';

  $store       = $invoice->store;
  $planRequest = $invoice->planRequest;
  $plan        = $planRequest?->plan;

  $months      = (int) ($planRequest?->months ?? 1);
  $planLabel   = $plan?->name ?? 'Plan Lyrium';
  $metodoPago  = match (strtolower($planRequest?->payment_method ?? '')) {
      'izipay' => 'Tarjeta (Izipay)',
      'trial'  => 'Prueba gratuita',
      default  => ucfirst($planRequest?->payment_method ?? '—'),
  };

  $estado = match ($invoice->status) {
      'ERROR' => 'failed',
      default => 'paid',
  };

  $iconPath   = public_path('images/nombrelogo.png');
  $iconBase64 = file_exists($iconPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath)) : '';
  $bolitaPath   = public_path('images/iconologo.png');
  $bolitaBase64 = file_exists($bolitaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($bolitaPath)) : '';
@endphp

{{-- ═══════════════════════════════════════════════
     1. ENCABEZADO
     ═══════════════════════════════════════════════ --}}
<table class="hdr">
  <tr>
    <td style="width:55%;">
      <div class="logo-row">
        @if($bolitaBase64)<img src="{{ $bolitaBase64 }}" class="logo-icon" alt="">@endif
        @if($iconBase64)<img src="{{ $iconBase64 }}" class="logo-img" alt="Lyrium">@endif
      </div>
      <div class="brand-name">{{ $issuerName }}</div>
      <div class="brand-ruc">RUC &nbsp;<b>{{ $issuerRuc }}</b></div>
      <div class="brand-addr">
        {{ $issuerAddress }}<br>
        {{ $issuerCity }}<br>
        contacto@lyrium.pe
      </div>
    </td>
    <td style="width:45%; text-align:right; vertical-align:top;">
      <div class="doc-badge">
        <div class="doc-badge-type">Recibo de Suscripción</div>
        <div class="doc-badge-num">{{ $invoice->series }}-{{ $invoice->number }}</div>
        <div class="doc-badge-date">Emisión: <b>{{ ($invoice->emission_date ?? $invoice->created_at)->format('d/m/Y') }}</b></div>
        <div class="doc-badge-cur">Moneda: PEN — Soles</div>
      </div>
    </td>
  </tr>
</table>

<hr class="line-accent">

{{-- ═══════════════════════════════════════════════
     2. EMISOR / FACTURADO A
     ═══════════════════════════════════════════════ --}}
<table class="parties">
  <tr>
    <td class="party-col-l">
      <div class="party-tag">Emisor</div>
      <div class="party-name">{{ $issuerName }}</div>
      <div class="party-ruc">{{ $issuerRuc }}</div>
      <div class="party-info">
        {{ $issuerAddress }}<br>
        {{ $issuerCity }}
      </div>
    </td>
    <td class="party-col-r">
      <div class="party-tag">Facturado a</div>
      <div class="party-name">{{ $invoice->customer_name ?: ($store->trade_name ?? 'Tienda') }}</div>
      <div class="party-ruc">RUC: {{ $invoice->customer_ruc ?: ($store->ruc ?? '—') }}</div>
      <div class="party-info">
        {{ $store->address ?? '—' }}<br>
        {{ $invoice->customer_email ?? '—' }}
      </div>
    </td>
  </tr>
</table>

<hr class="line-light">

{{-- ═══════════════════════════════════════════════
     3. CONCEPTO FACTURADO
     ═══════════════════════════════════════════════ --}}
<div class="items-wrap">
  <table class="items-table">
    <thead>
      <tr>
        <th>Concepto</th>
        <th class="r" style="width:20%;">Importe</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <div class="item-title">Plan {{ $planLabel }} — Suscripción</div>
          <div class="item-sub">{{ $months }} {{ $months === 1 ? 'mes' : 'meses' }} · {{ $metodoPago }}</div>
        </td>
        <td class="r">S/ {{ number_format((float) $invoice->total, 2) }}</td>
      </tr>
    </tbody>
  </table>
</div>

<table class="total-bar">
  <tr>
    <td class="lbl">Total pagado</td>
    <td class="val">S/ {{ number_format((float) $invoice->total, 2) }}</td>
  </tr>
</table>

<div class="status-wrap">
  <span class="status-badge {{ $estado === 'failed' ? 'failed' : '' }}">
    {{ $estado === 'failed' ? 'No procesado' : 'Pagado' }}
  </span>
</div>

{{-- ═══════════════════════════════════════════════
     4. QR DE VERIFICACIÓN LYRIUM
     ═══════════════════════════════════════════════ --}}
@if(!empty($qrBase64))
<div class="qr-card" style="margin-bottom: 18px;">
  <table class="qr-card-inner">
    <tr>
      <td class="qr-left">
        <div class="qr-img-wrap">
          <img src="{{ $qrBase64 }}" alt="QR verificación Lyrium">
        </div>
      </td>
      <td class="qr-right">
        <div class="qr-badge">Verificación Lyrium</div>
        <div class="qr-title">Código QR de Autenticidad</div>
        <div class="qr-desc">
          Escanea este código para verificar este recibo en nuestro sitio.<br>
          Recibo: <b>{{ $invoice->series }}-{{ $invoice->number }}</b> &nbsp;·&nbsp; Tienda: <b>{{ $store->trade_name ?? '—' }}</b>
        </div>
        <div class="qr-url">{{ $verifyUrlLabel ?? 'lyriumbiomarketplace.com' }}</div>
      </td>
    </tr>
  </table>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     5. PIE DE PÁGINA
     ═══════════════════════════════════════════════ --}}
<hr class="line-light">

<div class="footer-txt">
  Comprobante interno de Lyrium Biomarketplace. No constituye factura electrónica SUNAT.<br>
  Generado el {{ now()->format('d/m/Y H:i') }}
</div>

<div class="footer-bar">
  <span class="slogan">Lyrium Biomarket de confianza</span>
  <span class="sep">|</span>
  <span>lyriumbiomarketplace.com</span>
  <span class="sep">|</span>
  <span>contacto@lyrium.pe</span>
  <span class="sep">|</span>
  <span>+51 999 888 777</span>
</div>

</body>
</html>
