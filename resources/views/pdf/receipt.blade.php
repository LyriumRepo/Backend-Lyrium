<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 25px 30px; }
    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 8px;
      color: #1a1a1a;
      margin: 0;
      padding: 0;
      line-height: 1.4;
    }

    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-left { width: 50%; }
    .header-right { width: 50%; text-align: right; }
    .logo-img { width: 130px; }
    .issuer-name { font-size: 11px; font-weight: bold; color: #1a1a1a; margin-top: 3px; }
    .issuer-label { font-size: 7px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
    .header-ruc { font-size: 9px; color: #1a1a1a; margin-top: 2px; }
    .header-ruc strong { font-weight: bold; }
    .doc-type-box {
      display: inline-block;
      text-align: center;
      border: 2px solid #1a1a1a;
      padding: 6px 18px;
      margin-bottom: 4px;
    }
    .doc-type { font-size: 13px; font-weight: bold; color: #1a1a1a; letter-spacing: 1px; }
    .doc-serienum { font-size: 11px; font-weight: bold; color: #1a1a1a; margin-top: 4px; }
    .doc-meta { font-size: 7.5px; color: #666; margin-top: 3px; }
    .doc-meta span { color: #1a1a1a; }

    .header-divider { border: none; border-top: 2px solid #1a1a1a; margin: 0 0 12px 0; }

    .section-title {
      font-size: 7px; font-weight: bold;
      background-color: #1a1a1a; color: #fff;
      padding: 4px 8px; text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .client-table { width: 100%; border-collapse: collapse; border: 1px solid #ccc; border-top: none; margin-bottom: 12px; }
    .client-table td { padding: 3px 8px; font-size: 7.5px; }
    .client-table tr:last-child td { border-bottom: none; }
    .client-label { font-weight: bold; color: #555; width: 110px; }
    .client-value { color: #1a1a1a; }

    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .items-table th {
      background-color: #1a1a1a; color: #fff;
      font-size: 6.5px; text-transform: uppercase;
      padding: 4px 5px; text-align: center;
      border: 1px solid #1a1a1a;
      letter-spacing: 0.3px;
    }
    .items-table td {
      padding: 4px 5px; font-size: 7.5px;
      border: 1px solid #ccc;
    }
    .items-table .right { text-align: right; }
    .items-table .center { text-align: center; }
    .items-table .left { text-align: left; }
    .items-table tr:nth-child(even) td { background-color: #f8f8f8; }

    .totals-section { width: 100%; margin-bottom: 10px; }
    .totals-table { width: 50%; margin-left: auto; border-collapse: collapse; }
    .totals-table td { padding: 2.5px 8px; font-size: 7.5px; }
    .totals-table .label-cell { text-align: right; color: #555; }
    .totals-table .value-cell { text-align: right; font-weight: bold; color: #1a1a1a; }
    .totals-table .border-bottom td { border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    .totals-table .border-top td { border-top: 2px solid #1a1a1a; padding-top: 5px; }
    .totals-table .grand-total .label-cell { font-size: 9px; font-weight: bold; color: #1a1a1a; }
    .totals-table .grand-total .value-cell { font-size: 10px; font-weight: bold; color: #1a1a1a; }

    .amount-box {
      width: 100%;
      text-align: center;
      font-size: 8px;
      font-weight: bold;
      color: #1a1a1a;
      padding: 7px 0;
      border-top: 1px solid #999;
      border-bottom: 1px solid #999;
      margin-bottom: 10px;
    }
    .amount-box .numeral { font-weight: normal; font-size: 7px; color: #666; }

    .footer { text-align: center; font-size: 6px; color: #888; padding-top: 6px; }
    .footer .auth-code { color: #555; margin-bottom: 1px; }
    .footer .auth-code span { font-weight: bold; color: #333; }
    .footer .extra { color: #999; margin-bottom: 1px; }
    .footer-divider { border: none; border-top: 0.5px dashed #aaa; margin: 6px 0; }
    .qr-line { font-size: 5.5px; color: #999; word-break: break-all; margin-top: 3px; }
  </style>
</head>
<body>

@php
  $invoice = $invoice ?? \App\Models\Invoice::where('order_id', $order->id)->first();
  $issuerRuc = '20612731838';
  $issuerName = 'LYRIUM EIRL';
  $issuerAddress = 'MZ.KD LT.5 URB.SANTA MARGARITA';
  $issuerCity = 'VILLA MARÍA DEL TRIUNFO - LIMA';
  $docType = $invoice ? $invoice->type : 'FACTURA';
  $docSeries = $invoice ? $invoice->series . '-' . $invoice->number : $order->order_number;
  $docTotal = $invoice ? (float) $invoice->total : (float) $order->total;
  $docSubtotal = $invoice ? (float) $invoice->subtotal_sin_igv : (float) $order->subtotal;
  $docIgv = $invoice ? (float) $invoice->igv_amount : (float) $order->tax_amount;
  $customerName = $invoice ? $invoice->customer_name : ($order->shipping_name ?: $order->user->name);
  $customerDoc = $invoice ? $invoice->customer_ruc : ($order->user->document_number ?? '00000000');
  $customerDocType = strlen($customerDoc) === 11 ? 'RUC' : 'DNI';
  $customerAddress = $order->shipping_address ?? ($order->shipping_city ?? '—');
  $customerEmail = $order->shipping_email ?: $order->user->email;
  $authCode = $invoice ? $invoice->authorization_code : '—';
  $qrData = $invoice ? $invoice->qr_data : '';
  $emissionDate = $invoice ? \Carbon\Carbon::parse($invoice->emission_date) : $order->created_at;
  $iconPath = public_path('images/iconologo.png');
  $iconBase64 = file_exists($iconPath) ? base64_encode(file_get_contents($iconPath)) : '';
@endphp

  <!-- ===== HEADER ===== -->
  <table class="header-table">
    <tr>
      <td class="header-left">
        @if($iconBase64)
          <img src="data:image/png;base64,{{ $iconBase64 }}" class="logo-img" alt="Lyrium">
        @endif
        <div class="issuer-name">{{ $issuerName }}</div>
        <div class="header-ruc">R.U.C. <strong>{{ $issuerRuc }}</strong></div>
        <div class="issuer-address" style="font-size:6.5px;color:#888;margin-top:2px;">
          {{ $issuerAddress }}<br>
          {{ $issuerCity }}
        </div>
      </td>
      <td class="header-right">
        <div class="doc-type-box">
          <div class="doc-type">{{ $docType === 'FACTURA' ? 'FACTURA' : 'BOLETA' }} ELECTRÓNICA</div>
        </div>
        <div class="doc-serienum">{{ $docSeries }}</div>
        <div class="doc-meta">
          Fecha de Emisión: <span>{{ $emissionDate->format('d/m/Y') }}</span>
        </div>
      </td>
    </tr>
  </table>

  <hr class="header-divider">

  <!-- ===== CLIENT INFO ===== -->
  <div class="section-title">DATOS DEL CLIENTE</div>
  <table class="client-table">
    <tr>
      <td class="client-label">Cliente:</td>
      <td class="client-value">{{ $customerName }}</td>
    </tr>
    <tr>
      <td class="client-label">Tipo Documento:</td>
      <td class="client-value">{{ $customerDocType }}</td>
    </tr>
    <tr>
      <td class="client-label">Nro. Documento:</td>
      <td class="client-value">{{ $customerDoc }}</td>
    </tr>
    <tr>
      <td class="client-label">Dirección:</td>
      <td class="client-value">{{ $customerAddress }}</td>
    </tr>
    <tr>
      <td class="client-label">Email:</td>
      <td class="client-value">{{ $customerEmail }}</td>
    </tr>
  </table>

  <!-- ===== ITEMS ===== -->
  <div class="section-title">DETALLE DE PRODUCTOS / SERVICIOS</div>
  <table class="items-table">
    <thead>
      <tr>
        <th width="8%">Cant.</th>
        <th width="52%">Descripción</th>
        <th width="14%">Valor Unit.</th>
        <th width="8%">Dto.</th>
        <th width="14%">Importe</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td class="center">{{ $item->quantity }}</td>
        <td class="left">{{ $item->product_name }}</td>
        <td class="right">S/ {{ number_format((float) $item->unit_price, 2) }}</td>
        <td class="right">S/ 0.00</td>
        <td class="right">S/ {{ number_format((float) $item->line_total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- ===== TOTALS ===== -->
  <div class="totals-section">
    <table class="totals-table">
      <tr class="border-bottom">
        <td class="label-cell">Valor de Venta (Op. Gravadas):</td>
        <td class="value-cell">S/ {{ number_format($docSubtotal, 2) }}</td>
      </tr>
      <tr class="border-bottom">
        <td class="label-cell">Valor de Venta (Op. Inafectas):</td>
        <td class="value-cell">S/ 0.00</td>
      </tr>
      @if((float) $order->discount_amount > 0)
      <tr>
        <td class="label-cell">Descuentos:</td>
        <td class="value-cell">— S/ {{ number_format((float) $order->discount_amount, 2) }}</td>
      </tr>
      @endif
      <tr class="border-bottom">
        <td class="label-cell">I.G.V. (18%):</td>
        <td class="value-cell">S/ {{ number_format($docIgv, 2) }}</td>
      </tr>
      <tr class="border-top grand-total">
        <td class="label-cell">IMPORTE TOTAL:</td>
        <td class="value-cell">S/ {{ number_format($docTotal, 2) }}</td>
      </tr>
    </table>
  </div>

  <!-- ===== AMOUNT IN WORDS ===== -->
  @php
    $entero = (int) floor($docTotal);
    $decimal = str_pad((string) round(($docTotal - $entero) * 100), 2, '0', STR_PAD_LEFT);

    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $especiales = ['ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    if (!function_exists('numToWords')) { function numToWords(int $n, array $u, array $d, array $e, array $c): string {
      if ($n === 0) return 'CERO';
      if ($n === 100) return 'CIEN';
      $r = '';
      if ($n >= 100) {
        $r .= $c[(int)($n / 100)] . ' ';
        $n %= 100;
      }
      if ($n >= 11 && $n <= 19) {
        return trim($r . $e[$n - 11]);
      }
      if ($n >= 10) {
        $r .= $d[(int)($n / 10)] . ' ';
        $n %= 10;
      }
      if ($n > 0) {
        $r .= $u[$n] . ' ';
      }
      return trim($r);
    } }

    if ($entero >= 1000000) {
      $millones = (int)($entero / 1000000);
      $resto = $entero % 1000000;
      $palabras = $millones === 1 ? 'UN MILLÓN' : numToWords($millones, $unidades, $decenas, $especiales, $centenas) . ' MILLONES';
      if ($resto > 0) {
        $palabras .= ' ' . numToWords($resto, $unidades, $decenas, $especiales, $centenas);
      }
    } else {
      $palabras = numToWords($entero, $unidades, $decenas, $especiales, $centenas);
    }
  @endphp
  <div class="amount-box">
    Son: {{ ucfirst(strtolower($palabras)) }} Y {{ $decimal }}/100 SOLES<br>
    <span class="numeral">(S/ {{ number_format($docTotal, 2) }})</span>
  </div>

  <hr class="footer-divider">

  <!-- ===== FOOTER ===== -->
  <div class="footer">
    <div class="auth-code">
      Autorización: <span>{{ $authCode }}</span>
    </div>
    <div class="extra">
      Representación impresa de la {{ $docType === 'FACTURA' ? 'Factura' : 'Boleta' }} Electrónica
    </div>
    <div class="extra">
      Autorizado mediante resolución Nro. 0340050010017/SUNAT
    </div>
    <div class="extra">
      Lyrium EIRL — RUC {{ $issuerRuc }} — {{ $issuerAddress }}, {{ $issuerCity }}
    </div>
    <div class="extra">
      Este comprobante fue generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
    @if($qrData)
    <div class="qr-line">QR: {{ $qrData }}</div>
    @endif
  </div>

</body>
</html>