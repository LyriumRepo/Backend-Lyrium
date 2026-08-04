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
    .items-table td.c { text-align: center; color: #9CA3AF; font-size: 7.5px; }
    .items-table td.r { text-align: right; font-weight: bold; }
    .item-title { font-size: 8.5px; font-weight: bold; color: #111827; margin-bottom: 3px; }
    .item-sub   { font-size: 6.5px; color: #9CA3AF; }

    /* ── SUMMARY ────────────────────────────── */
    .sum-outer { width: 100%; border-collapse: collapse; }
    .sum-spacer { width: 52%; vertical-align: top; padding: 0; }
    .sum-box   { width: 48%; border: 1px solid #E5E7EB; border-top: none; border-collapse: collapse; vertical-align: top; padding: 0; }
    .sum-table { width: 100%; border-collapse: collapse; }
    .sum-table td { padding: 6px 12px; font-size: 7.5px; border-bottom: 1px solid #F3F4F6; }
    .sum-lbl { text-align: right; color: #6B7280; }
    .sum-val { text-align: right; font-weight: bold; color: #111827; min-width: 65px; }
    .sum-div td { padding: 0; border-top: 1px solid #E5E7EB; border-bottom: none; }
    .sum-total td { background: #14532D; padding: 10px 12px; border-bottom: none; }
    .sum-total .sum-lbl { color: #BBF7D0; font-size: 8px; font-weight: bold; }
    .sum-total .sum-val { color: #FFFFFF; font-size: 12px; }

    /* ── OBSERVACIONES ──────────────────────── */
    .obs-block {
      margin-top: 18px; margin-bottom: 18px;
      padding: 10px 14px;
      background: #F9FAFB;
      border-left: 3px solid #15803D;
      page-break-inside: avoid;
    }
    .obs-tag   { font-size: 5.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px; color: #9CA3AF; margin-bottom: 4px; }
    .obs-text  { font-size: 7.5px; color: #374151; line-height: 1.6; }
    .obs-text b { color: #14532D; }

    /* ── IMPORTE EN LETRAS ──────────────────── */
    .words-block {
      text-align: center;
      border: 1px dashed #86EFAC;
      background: #F0FDF4;
      padding: 12px 16px;
      margin-bottom: 20px;
      page-break-inside: avoid;
    }
    .words-tag  { font-size: 5.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #15803D; margin-bottom: 5px; }
    .words-text { font-size: 10px; font-weight: bold; color: #14532D; }
    .words-num  { font-size: 7px; color: #9CA3AF; margin-top: 3px; }

    /* ── QR SUNAT SECTION ───────────────────── */
    .qr-section { width: 100%; border-collapse: collapse; margin-bottom: 18px; page-break-inside: avoid; }
    .qr-section td { vertical-align: middle; padding: 0; }
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
    .footer-wrap { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .footer-wrap td { vertical-align: top; padding: 0; }
    .footer-txt  { font-size: 6.5px; color: #9CA3AF; line-height: 1.9; }
    .footer-txt b { color: #374151; }
    .footer-qr   { text-align: right; }
    .footer-qr img { width: 64px; height: 64px; }
    .footer-qr-lbl { font-size: 5px; color: #9CA3AF; margin-top: 2px; text-align: center; }

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
  $invoice = $invoice ?? \App\Models\Invoice::where('order_id', $order->id)->first();
  $issuerRuc     = '20612731838';
  $issuerName    = 'LYRIUM E.I.R.L.';
  $issuerAddress = 'MZ.KD LT.5 URB.SANTA MARGARITA';
  $issuerCity    = 'VILLA MARÍA DEL TRIUNFO - LIMA';
  $docType       = $invoice ? $invoice->type : 'FACTURA';
  $docSeries     = $invoice ? $invoice->series . '-' . $invoice->number : $order->order_number;

  $customerName    = $invoice ? $invoice->customer_name : ($order->shipping_name ?: $order->user->name);
  $customerDoc     = $invoice ? $invoice->customer_ruc  : ($order->user->document_number ?? '00000000');
  $customerDocType = strlen($customerDoc) === 11 ? 'RUC' : 'DNI';
  $customerAddress = $order->shipping_address ?? ($order->shipping_city ?? 'Lima, Perú');
  $customerEmail   = $order->shipping_email ?: ($order->user->email ?? '—');
  $authCode        = $invoice ? $invoice->authorization_code : '—';
  $qrData          = $invoice ? $invoice->qr_data : '';
  $emissionDate    = $invoice ? \Carbon\Carbon::parse($invoice->emission_date) : $order->created_at;
  $orderRef        = $order->order_number ?? ($invoice->order_id ?? '—');

  $iconPath   = public_path('images/nombrelogo.png');
  $iconBase64 = file_exists($iconPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath)) : '';
  $bolitaPath   = public_path('images/iconologo.png');
  $bolitaBase64 = file_exists($bolitaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($bolitaPath)) : '';

  // Base = subtotal de los ítems de ESTA tienda (invoice->store_id) — productos Y
  // servicios. Faltaba sumar serviceItems, así que un pedido mixto solo calculaba
  // la comisión sobre la parte de producto y omitía la de servicio por completo.
  // Si no hay store_id (legacy/manual), usa el subtotal total del pedido como fallback.
  if ($invoice && $invoice->store_id) {
      $order->loadMissing(['items', 'serviceItems']);
      $baseConIgv = (float) $order->items->where('store_id', $invoice->store_id)->sum('line_total')
          + (float) $order->serviceItems->where('store_id', $invoice->store_id)->sum('line_total');
  } else {
      $baseConIgv = (float) $order->subtotal;
  }

  $commissionCalc   = app(\App\Services\CommissionCalculatorService::class)->calculate($baseConIgv);
  $comisionSinIgv   = round($commissionCalc['comision_base'], 2);
  $comisionIgv      = round($commissionCalc['igv_comision'], 2);
  $comisionTotal    = round($commissionCalc['comision_total'], 2);
  $comisionTasaTexto = $commissionCalc['tasa_texto'];

  $docTotal = $comisionTotal;

  $qrBase64 = '';
  if (!empty($qrData)) {
      try {
          $qrOpts = new \chillerlan\QRCode\QROptions;
          $qrOpts->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
          $qrOpts->outputBase64 = true;
          $qrBase64 = (new \chillerlan\QRCode\QRCode($qrOpts))->render($qrData);
      } catch (\Throwable $e) { $qrBase64 = ''; }
  }
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
        <div class="doc-badge-type">{{ $docType === 'FACTURA' ? 'Factura' : 'Boleta' }} Electrónica</div>
        <div class="doc-badge-num">{{ $docSeries }}</div>
        <div class="doc-badge-date">Emisión: <b>{{ $emissionDate->format('d/m/Y') }}</b></div>
        <div class="doc-badge-cur">Moneda: PEN — Soles</div>
      </div>
    </td>
  </tr>
</table>

<hr class="line-accent">

{{-- ═══════════════════════════════════════════════
     2. EMISOR / CLIENTE
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
      <div class="party-tag">Cliente / Vendedor</div>
      <div class="party-name">{{ $customerName }}</div>
      <div class="party-ruc">{{ $customerDocType }}: {{ $customerDoc }}</div>
      <div class="party-info">
        {{ $customerAddress }}<br>
        {{ $customerEmail }}
      </div>
      <div class="party-ref">Ref. pedido: <b>{{ $orderRef }}</b></div>
    </td>
  </tr>
</table>

<hr class="line-light">

{{-- ═══════════════════════════════════════════════
     3. CONCEPTOS FACTURADOS
     ═══════════════════════════════════════════════ --}}
<div class="items-wrap">
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:7%;">Cant.</th>
        <th style="width:7%;">U.M.</th>
        <th>Descripción del Servicio</th>
        <th class="r" style="width:18%;">Valor Unit. (sin IGV)</th>
        <th class="r" style="width:18%;">Valor de Venta</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="c">1</td>
        <td class="c">ZZ</td>
        <td>
          <div class="item-title">Comisión por uso del Biomarketplace Lyrium</div>
          <div class="item-sub">Tasa {{ $comisionTasaTexto }} · Venta neta de referencia: S/ {{ number_format($baseConIgv / 1.18, 2) }}</div>
        </td>
        <td class="r">S/ {{ number_format($comisionSinIgv, 2) }}</td>
        <td class="r">S/ {{ number_format($comisionSinIgv, 2) }}</td>
      </tr>
    </tbody>
  </table>
</div>

{{-- ═══════════════════════════════════════════════
     4. RESUMEN ECONÓMICO
     ═══════════════════════════════════════════════ --}}
<table class="sum-outer">
  <tr>
    <td class="sum-spacer"></td>
    <td class="sum-box">
      <table class="sum-table">
        <tr>
          <td class="sum-lbl">Base Imponible</td>
          <td class="sum-val">S/ {{ number_format($comisionSinIgv, 2) }}</td>
        </tr>
        <tr>
          <td class="sum-lbl">IGV (18%)</td>
          <td class="sum-val">S/ {{ number_format($comisionIgv, 2) }}</td>
        </tr>
        <tr class="sum-div"><td colspan="2"></td></tr>
        <tr class="sum-total">
          <td class="sum-lbl">Total a Pagar</td>
          <td class="sum-val">S/ {{ number_format($comisionTotal, 2) }}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

{{-- ═══════════════════════════════════════════════
     5. OBSERVACIONES
     ═══════════════════════════════════════════════ --}}
<div class="obs-block">
  <div class="obs-tag">Observaciones</div>
  <div class="obs-text">
    Forma de pago: <b>Contado</b> &nbsp;·&nbsp;
    Fecha de vencimiento: <b>{{ $emissionDate->format('d/m/Y') }}</b>
  </div>
</div>

{{-- ═══════════════════════════════════════════════
     6. IMPORTE EN LETRAS
     ═══════════════════════════════════════════════ --}}
<div class="words-block">
  @php
    $entero  = (int) floor($docTotal);
    $decimal = str_pad((string) round(($docTotal - $entero) * 100), 2, '0', STR_PAD_LEFT);

    $un = ['','UN','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE'];
    $de = ['','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $es = ['ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
    $ce = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];

    if (!function_exists('numToWordsHundreds')) {
      function numToWordsHundreds(int $n, array $u, array $d, array $e, array $c): string {
        if ($n === 0) return '';
        if ($n === 100) return 'CIEN';
        $r = '';
        if ($n >= 100) { $r .= $c[(int)($n/100)] . ' '; $n %= 100; }
        if ($n >= 11 && $n <= 19) return trim($r . $e[$n - 11]);
        if ($n >= 10) { $r .= $d[(int)($n/10)] . ' '; $n %= 10; }
        if ($n > 0)   { $r .= $u[$n] . ' '; }
        return trim($r);
      }
    }

    $palabras = '';
    if ($entero === 0) {
      $palabras = 'CERO';
    } else {
      $mil = (int)(($entero % 1000000) / 1000);
      $res = $entero % 1000;
      $mil > 0 && $palabras .= ($mil === 1 ? 'MIL' : numToWordsHundreds($mil,$un,$de,$es,$ce).' MIL');
      $res > 0 && $palabras .= ($palabras ? ' ' : '') . numToWordsHundreds($res,$un,$de,$es,$ce);
      $palabras = $palabras ?: 'CERO';
    }
  @endphp
  <div class="words-tag">Importe en Letras</div>
  <div class="words-text">{{ ucfirst(strtolower($palabras)) }} y {{ $decimal }}/100 Soles</div>
  <div class="words-num">(S/ {{ number_format($docTotal, 2) }})</div>
</div>

{{-- ═══════════════════════════════════════════════
     7. QR SUNAT
     ═══════════════════════════════════════════════ --}}
@if($qrBase64)
<div class="qr-card" style="margin-bottom: 18px;">
  <table class="qr-card-inner">
    <tr>
      <td class="qr-left">
        <div class="qr-img-wrap">
          <img src="{{ $qrBase64 }}" alt="QR SUNAT">
        </div>
      </td>
      <td class="qr-right">
        <div class="qr-badge">Verificación SUNAT</div>
        <div class="qr-title">Código QR de Autenticidad</div>
        <div class="qr-desc">
          Escanea este código para verificar la validez de este comprobante electrónico ante SUNAT.<br>
          Comprobante: <b>{{ $docSeries }}</b> &nbsp;·&nbsp; RUC Emisor: <b>{{ $issuerRuc }}</b><br>
          Autorización: <b>{{ $authCode }}</b>
        </div>
        <div class="qr-url">e-consulta.sunat.gob.pe</div>
      </td>
    </tr>
  </table>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     8. PIE DE PÁGINA
     ═══════════════════════════════════════════════ --}}
<hr class="line-light">

<table class="footer-wrap">
  <tr>
    <td style="width:100%;">
      <div class="footer-txt">
        Resolución Nro. 0340050010017 / SUNAT<br>
        Representación impresa de la {{ $docType === 'FACTURA' ? 'Factura' : 'Boleta' }} Electrónica. Consulte su validez en: <b>e-consulta.sunat.gob.pe</b><br>
        Generado el {{ now()->format('d/m/Y H:i') }}
      </div>
    </td>
  </tr>
</table>

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
