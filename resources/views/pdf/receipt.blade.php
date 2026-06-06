<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 30px 35px; }
    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 8px;
      color: #1a1a1a;
      margin: 0;
      padding: 0;
      line-height: 1.5;
    }

    /* ===== COLORES CORPORATIVOS LYRIUM ===== */
    .lyr-green { color: #2A5A4D; }
    .lyr-bg-green { background-color: #2A5A4D; color: #fff; }
    .lyr-bg-light { background-color: #E8F5F0; }
    .lyr-border { border-color: #A8D5BA; }
    .lyr-green-dark { color: #0F2A24; }

    /* ===== ENCABEZADO ===== */
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-left { width: 55%; }
    .header-right { width: 45%; text-align: right; }
    .logo-img { width: 120px; margin-bottom: 8px; }
    .issuer-name { font-size: 13px; font-weight: bold; color: #2A5A4D; margin-bottom: 2px; }
    .issuer-ruc { font-size: 8px; color: #1a1a1a; font-weight: bold; margin-bottom: 1px; }
    .issuer-ruc span { font-weight: normal; color: #6b7280; }
    .issuer-detail { font-size: 6.5px; color: #6b7280; line-height: 1.4; }

    .doc-box {
      display: inline-block;
      text-align: center;
      border: 1.5px solid #2A5A4D;
      border-radius: 4px;
      padding: 10px 22px;
      background: #E8F5F0;
    }
    .doc-type { font-size: 12px; font-weight: bold; color: #2A5A4D; letter-spacing: 0.5px; }
    .doc-serienum { font-size: 14px; font-weight: bold; color: #1a1a1a; margin-top: 5px; }
    .doc-date { font-size: 7px; color: #6b7280; margin-top: 3px; }
    .doc-date span { color: #1a1a1a; font-weight: bold; }

    .header-divider { border: none; border-top: 1.5px solid #A8D5BA; margin: 0 0 20px 0; }

    /* ===== SECCIONES ===== */
    .section-card {
      border: 1px solid #A8D5BA;
      border-radius: 4px;
      margin-bottom: 16px;
      page-break-inside: avoid;
    }
    .section-header {
      background-color: #2A5A4D;
      color: #ffffff;
      padding: 7px 14px;
      font-size: 7px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    /* ===== TABLA CLIENTE ===== */
    .client-table { width: 100%; border-collapse: collapse; }
    .client-table td {
      padding: 5px 14px;
      font-size: 7.5px;
      border-bottom: 1px solid #E8F5F0;
    }
    .client-table tr:last-child td { border-bottom: none; }
    .client-label { font-weight: bold; color: #6b7280; width: 130px; }
    .client-value { color: #1a1a1a; }
    .client-table tr:nth-child(even) td { background-color: #F5FBF8; }

    /* ===== TABLA ITEMS ===== */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .items-table th {
      background-color: #2A5A4D; color: #fff;
      font-size: 6.5px; text-transform: uppercase;
      padding: 6px 6px; text-align: center;
      letter-spacing: 0.3px;
      border: 1px solid #2A5A4D;
    }
    .items-table td {
      padding: 5px 6px; font-size: 7.5px;
      border: 1px solid #D1E8DD;
    }
    .items-table .right { text-align: right; padding-right: 10px; }
    .items-table .center { text-align: center; }
    .items-table .left { text-align: left; padding-left: 10px; }
    .items-table tr:nth-child(even) td { background-color: #F5FBF8; }

    /* ===== RESUMEN ECONÓMICO ===== */
    .summary-table { width: 55%; margin-left: auto; border-collapse: collapse; }
    .summary-table td { padding: 4px 12px; font-size: 7.5px; }
    .summary-table .label-cell { text-align: right; color: #6b7280; }
    .summary-table .value-cell { text-align: right; font-weight: bold; color: #1a1a1a; }
    .summary-table .separator td { border: none; padding: 2px 12px; }
    .summary-table .separator hr {
      border: none; border-top: 1px dashed #A8D5BA; margin: 0;
    }

    .total-row td {
      padding: 8px 14px;
      background-color: #2A5A4D;
    }
    .total-row .label-cell { color: #ffffff; font-size: 9px; font-weight: bold; text-align: right; }
    .total-row .value-cell { color: #ffffff; font-size: 11px; font-weight: bold; text-align: right; }

    .info-label { font-size: 7px; color: #9ca3af; }
    .info-value { font-size: 7px; color: #9ca3af; font-weight: normal; }

    .commission-note {
      width: 55%; margin-left: auto; margin-top: 4px;
      font-size: 5.5px; color: #9ca3af; text-align: center;
      font-style: italic; line-height: 1.3;
    }

    /* ===== IMPORTE EN LETRAS ===== */
    .amount-card {
      border: 1px solid #A8D5BA;
      border-radius: 4px;
      margin-bottom: 16px;
      page-break-inside: avoid;
    }
    .amount-card .amount-header {
      background-color: #2A5A4D; color: #fff;
      padding: 6px 14px;
      font-size: 7px; font-weight: bold; text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    .amount-card .amount-body {
      padding: 10px 14px;
      text-align: center;
    }
    .amount-card .amount-words {
      font-size: 10px; font-weight: bold; color: #1a1a1a;
    }
    .amount-card .amount-numeral {
      font-size: 7.5px; color: #6b7280; margin-top: 3px;
    }

    /* ===== PIE DE PÁGINA ===== */
    .footer-divider { border: none; border-top: 1px solid #A8D5BA; margin: 0 0 12px 0; }
    .footer { font-size: 6px; color: #6b7280; line-height: 1.5; }
    .footer .auth-line { margin-bottom: 2px; }
    .footer .auth-line span { font-weight: bold; color: #1a1a1a; }
    .footer-inner { display: flex; align-items: flex-start; gap: 20px; }
    .footer-info { flex: 1; }
    .footer-qr { flex-shrink: 0; text-align: center; }
    .footer-qr img { width: 70px; height: 70px; }
    .footer-qr-label { font-size: 5px; color: #9ca3af; margin-top: 2px; }

    .footer-bar {
      margin-top: 14px;
      padding: 8px 14px;
      background-color: #2A5A4D;
      color: #ffffff;
      font-size: 6.5px;
      text-align: center;
    }
    .footer-bar span { margin: 0 10px; }
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
  $iconBase64 = '';
  if (file_exists($iconPath)) {
      $iconBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath));
  }

  $shippingCost = (float) $order->shipping_cost;

  $comisionTotal = 0;
  $comisionStoreId = $invoice ? $invoice->store_id : null;
  $comisionTasaMin = 1;
  $comisionTasaMax = 0;
  foreach ($order->items as $item) {
      if ($comisionStoreId && (int) $item->store_id !== (int) $comisionStoreId) {
          continue;
      }
      $valorItem = (float) $item->line_total;
      $tasa = match (true) {
          $valorItem <= 400   => 0.15,
          $valorItem <= 800   => 0.14,
          $valorItem <= 1200  => 0.13,
          default             => 0.12,
      };
      $comisionTasaMin = min($comisionTasaMin, $tasa);
      $comisionTasaMax = max($comisionTasaMax, $tasa);
      $comisionTotal += round($valorItem * $tasa, 2);
  }
  $comisionTasaMin = $comisionTasaMin === 1 ? 0 : $comisionTasaMin;
  $comisionTasaTexto = $comisionTasaMin === $comisionTasaMax
      ? round($comisionTasaMin * 100) . '%'
      : round($comisionTasaMin * 100) . '%-' . round($comisionTasaMax * 100) . '%';

  $qrBase64 = '';
  if (!empty($qrData)) {
      try {
          $qrCode = (new \chillerlan\QRCode\QRCode)->render($qrData);
          $qrBase64 = $qrCode;
      } catch (\Throwable $e) {
          $qrBase64 = '';
      }
  }
@endphp

  <!-- ============================================================ -->
  <!-- 1. ENCABEZADO                                                -->
  <!-- ============================================================ -->
  <table class="header-table">
    <tr>
      <td class="header-left">
        @if($iconBase64)
          <img src="{{ $iconBase64 }}" class="logo-img" alt="Lyrium">
        @endif
        <div class="issuer-name">{{ $issuerName }}</div>
        <div class="issuer-ruc">RUC <span>{{ $issuerRuc }}</span></div>
        <div class="issuer-detail">
          {{ $issuerAddress }}<br>
          {{ $issuerCity }}<br>
          contacto@lyrium.pe
        </div>
      </td>
      <td class="header-right">
        <div class="doc-box">
          <div class="doc-type">{{ $docType === 'FACTURA' ? 'FACTURA' : 'BOLETA' }} ELECTRÓNICA</div>
          <div class="doc-serienum">{{ $docSeries }}</div>
          <div class="doc-date">Fecha de Emisión: <span>{{ $emissionDate->format('d/m/Y') }}</span></div>
        </div>
      </td>
    </tr>
  </table>

  <hr class="header-divider">

  <!-- ============================================================ -->
  <!-- 2. INFORMACIÓN DEL CLIENTE                                   -->
  <!-- ============================================================ -->
  <div class="section-card">
    <div class="section-header">Información del Cliente</div>
    <table class="client-table">
      <tr>
        <td class="client-label">Cliente</td>
        <td class="client-value">{{ $customerName }}</td>
      </tr>
      <tr>
        <td class="client-label">Tipo Documento</td>
        <td class="client-value">{{ $customerDocType }}</td>
      </tr>
      <tr>
        <td class="client-label">Número Documento</td>
        <td class="client-value">{{ $customerDoc }}</td>
      </tr>
      <tr>
        <td class="client-label">Dirección</td>
        <td class="client-value">{{ $customerAddress }}</td>
      </tr>
      <tr>
        <td class="client-label">Correo</td>
        <td class="client-value">{{ $customerEmail }}</td>
      </tr>
    </table>
  </div>

  <!-- ============================================================ -->
  <!-- 3. DETALLE DE PRODUCTOS                                      -->
  <!-- ============================================================ -->
  <div class="section-card">
    <div class="section-header">Detalle de Productos / Servicios</div>
    <table class="items-table">
      <thead>
        <tr>
          <th width="8%">Cant.</th>
          <th width="50%">Descripción</th>
          <th width="15%">Valor Unit.</th>
          <th width="8%">Dto.</th>
          <th width="15%">Importe</th>
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
  </div>

  <!-- ============================================================ -->
  <!-- 4. RESUMEN ECONÓMICO                                         -->
  <!-- ============================================================ -->
  <div class="section-card">
    <div class="section-header">Resumen Económico</div>
    <table class="summary-table">
      <tr>
        <td class="label-cell">Valor de Venta</td>
        <td class="value-cell">S/ {{ number_format($docSubtotal, 2) }}</td>
      </tr>
      <tr>
        <td class="label-cell">Valor de Venta (Op. Inafectas)</td>
        <td class="value-cell">S/ 0.00</td>
      </tr>
      @if((float) $order->discount_amount > 0)
      <tr>
        <td class="label-cell">Descuentos</td>
        <td class="value-cell">— S/ {{ number_format((float) $order->discount_amount, 2) }}</td>
      </tr>
      @endif
      <tr>
        <td class="label-cell">I.G.V. (18%)</td>
        <td class="value-cell">S/ {{ number_format($docIgv, 2) }}</td>
      </tr>
      <tr><td colspan="2" style="padding:0 12px;"><hr style="border:none;border-top:1px solid #A8D5BA;margin:2px 0;"></td></tr>
      <tr class="total-row">
        <td class="label-cell">PRECIO FINAL</td>
        <td class="value-cell">S/ {{ number_format($docTotal, 2) }}</td>
      </tr>
      <tr class="separator"><td colspan="2"><hr></td></tr>
      <tr>
        <td class="label-cell info-label">Envío</td>
        <td class="value-cell info-value">S/ {{ number_format($shippingCost, 2) }}</td>
      </tr>
      <tr>
        <td class="label-cell info-label">Comisión Referencial Lyrium ({{ $comisionTasaTexto }})</td>
        <td class="value-cell info-value">S/ {{ number_format($comisionTotal, 2) }}</td>
      </tr>
    </table>
    <div class="commission-note">
      Comisión calculada de forma referencial según la tabla vigente.<br>
      No modifica los importes fiscales del comprobante.
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- 5. IMPORTE EN LETRAS                                         -->
  <!-- ============================================================ -->
  <div class="amount-card">
    <div class="amount-header">Importe en Letras</div>
    <div class="amount-body">
      @php
        $entero = (int) floor($docTotal);
        $decimal = str_pad((string) round(($docTotal - $entero) * 100), 2, '0', STR_PAD_LEFT);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if (!function_exists('numToWordsHundreds')) { function numToWordsHundreds(int $n, array $u, array $d, array $e, array $c): string {
          if ($n === 0) return '';
          if ($n === 100) return 'CIEN';
          $r = '';
          if ($n >= 100) {
            $hundredsDigit = (int)($n / 100);
            $r .= $c[$hundredsDigit] . ' ';
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

        if ($entero === 0) {
          $palabras = 'CERO';
        } else {
          $palabras = '';
          $millones = (int)($entero / 1000000);
          $miles = (int)(($entero % 1000000) / 1000);
          $resto = $entero % 1000;

          if ($millones > 0) {
            $palabras .= ($millones === 1 ? 'UN MILLÓN' : numToWordsHundreds($millones, $unidades, $decenas, $especiales, $centenas) . ' MILLONES');
          }
          if ($miles > 0) {
            $palabras .= ($palabras !== '' ? ' ' : '') . ($miles === 1 ? 'MIL' : numToWordsHundreds($miles, $unidades, $decenas, $especiales, $centenas) . ' MIL');
          }
          if ($resto > 0) {
            $palabras .= ($palabras !== '' ? ' ' : '') . numToWordsHundreds($resto, $unidades, $decenas, $especiales, $centenas);
          }
          if ($palabras === '') {
            $palabras = 'CERO';
          }
        }
      @endphp
      <div class="amount-words">{{ ucfirst(strtolower($palabras)) }} Y {{ $decimal }}/100 SOLES</div>
      <div class="amount-numeral">(S/ {{ number_format($docTotal, 2) }})</div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- 6. PIE DE PÁGINA                                             -->
  <!-- ============================================================ -->
  <hr class="footer-divider">

  <div class="footer">
    <div class="footer-inner">
      <div class="footer-info">
        <div class="auth-line">Código de Autorización: <span>{{ $authCode }}</span></div>
        <div class="auth-line">Resolución Nro. 0340050010017 / SUNAT</div>
        <div class="auth-line">Representación impresa de la {{ $docType === 'FACTURA' ? 'Factura' : 'Boleta' }} Electrónica</div>
        <div class="auth-line">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
      </div>
      @if($qrBase64)
      <div class="footer-qr">
        <img src="{{ $qrBase64 }}" alt="QR">
        <div class="footer-qr-label">Código QR</div>
      </div>
      @endif
    </div>
  </div>

  <div class="footer-bar">
    <span>lyriumbiomarketplace.com</span> |
    <span>contacto@lyrium.pe</span> |
    <span>+51 999 888 777</span>
  </div>

</body>
</html>
