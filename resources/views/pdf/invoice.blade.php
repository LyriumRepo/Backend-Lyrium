<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Electrónica {{ $series }}-{{ $number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #fff;
            color: #1c2b24;
            font-size: 10px;
            line-height: 1.5;
            padding: 20px;
        }
        .page { max-width: 800px; margin: 0 auto; }
        .header {
            display: table;
            width: 100%;
            padding-bottom: 16px;
            border-bottom: 2px solid #e8f5ee;
            margin-bottom: 16px;
        }
        .header-left { display: table-cell; vertical-align: top; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }
        .logo-title {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 24px;
            font-weight: bold;
            color: #1a3a2e;
        }
        .tagline {
            font-size: 7px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #4a9e74;
            margin-top: 1px;
        }
        .company-info {
            margin-top: 8px;
            font-size: 8px;
            color: #5a7266;
            line-height: 1.7;
        }
        .company-info strong { color: #1c2b24; }
        .badge-label {
            font-size: 7px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #3ab5a0;
            font-weight: bold;
        }
        .badge-number {
            font-size: 22px;
            font-weight: bold;
            color: #1a3a2e;
            margin: 2px 0;
        }
        .badge-date { font-size: 8px; color: #5a7266; }
        .badge-date span { color: #1c2b24; font-weight: bold; }
        .section {
            padding: 12px 0;
            border-bottom: 1px solid #c8ddd4;
        }
        .section-title {
            font-size: 7px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #2e6b50;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e8f5ee;
        }
        .client-table { width: 100%; border-collapse: collapse; }
        .client-table td { padding: 2px 8px 2px 0; vertical-align: top; }
        .client-table .label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5a7266;
        }
        .client-table .value { font-size: 9px; font-weight: bold; color: #1c2b24; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        .items-table thead tr { background: #e8f5ee; }
        .items-table th {
            padding: 6px 8px;
            text-align: left;
            font-size: 7px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #2e6b50;
            font-weight: bold;
        }
        .items-table th.right, .items-table td.right { text-align: right; }
        .items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e8f5ee;
            color: #1c2b24;
        }
        .items-table tr:last-child td { border-bottom: none; }
        .summary-block { text-align: right; margin-top: 8px; }
        .summary-row {
            width: 250px;
            margin-left: auto;
            display: table;
            table-layout: fixed;
        }
        .summary-row .key {
            display: table-cell;
            text-align: left;
            color: #5a7266;
            font-size: 8.5px;
            padding: 2px 0;
        }
        .summary-row .val {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            color: #1c2b24;
            font-size: 8.5px;
        }
        .summary-divider {
            width: 250px;
            margin: 4px 0 4px auto;
            border: none;
            border-top: 1px solid #c8ddd4;
        }
        .summary-total {
            width: 250px;
            margin-left: auto;
            background: #1a3a2e;
            color: #fff;
            padding: 8px 12px;
            margin-top: 4px;
            margin-bottom: 4px;
            display: table;
        }
        .summary-total .key {
            display: table-cell;
            font-weight: bold;
            letter-spacing: 1px;
            font-size: 8px;
            text-transform: uppercase;
            color: #fff;
        }
        .summary-total .val {
            display: table-cell;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
        }
        .commission-row { width: 250px; margin-left: auto; }
        .commission-row table { width: 100%; }
        .commission-row td {
            font-size: 7.5px;
            color: #5a7266;
            padding: 1px 0;
        }
        .commission-row td.right { text-align: right; }
        .commission-note {
            width: 250px;
            margin-left: auto;
            margin-top: 6px;
            font-size: 6.5px;
            color: #5a7266;
            text-align: center;
            font-style: italic;
            line-height: 1.5;
        }
        .letras-block {
            text-align: center;
            padding: 12px;
            background: #e8f5ee;
            margin-top: 8px;
        }
        .letras-block p {
            font-size: 14px;
            font-weight: bold;
            color: #1a3a2e;
        }
        .letras-block small { font-size: 8px; color: #5a7266; }
        .footer-meta {
            padding: 10px 0;
            font-size: 7px;
            color: #5a7266;
            line-height: 1.8;
            border-bottom: 1px solid #c8ddd4;
        }
        .footer-meta strong { color: #1c2b24; word-break: break-all; }
        .footer-contact {
            padding: 8px 0;
            text-align: center;
            font-size: 7.5px;
            color: #5a7266;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="header-left">
            <div class="logo-title">LYRIUM</div>
            <div class="tagline">Biomarketplace</div>
            <div class="company-info">
                <strong>{{ $companyName }}</strong><br>
                RUC {{ $companyRuc }}<br>
                {{ $companyAddress }}<br>
                {{ $companyEmail }}
            </div>
        </div>
        <div class="header-right">
            <div class="badge-label">{{ $documentTypeLabel }}</div>
            <div class="badge-number">{{ $series }}-{{ $number }}</div>
            <div class="badge-date">Fecha de Emisión: <span>{{ $emissionDate }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Información del Cliente</div>
        <table class="client-table">
            <tr>
                <td width="50%">
                    <div class="label">Cliente</div>
                    <div class="value">{{ $customerName }}</div>
                </td>
                <td width="50%">
                    <div class="label">Tipo Documento</div>
                    <div class="value">{{ $customerDocType }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Número Documento</div>
                    <div class="value">{{ $customerDocNumber }}</div>
                </td>
                <td>
                    <div class="label">Dirección</div>
                    <div class="value">{{ $customerAddress }}</div>
                </td>
            </tr>
            @if ($customerEmail)
            <tr>
                <td>
                    <div class="label">Correo</div>
                    <div class="value">{{ $customerEmail }}</div>
                </td>
                <td></td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Productos / Servicios</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">Cant.</th>
                    <th width="44%">Descripción</th>
                    <th width="16%" class="right">Valor Unit.</th>
                    <th width="14%" class="right">Dto.</th>
                    <th width="18%" class="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                <tr>
                    <td>{{ $item['cantidad'] }}</td>
                    <td>{{ $item['descripcion'] }}</td>
                    <td class="right">S/ {{ number_format($item['valor_unitario'], 2) }}</td>
                    <td class="right">S/ {{ number_format($item['descuento'] ?? 0, 2) }}</td>
                    <td class="right">S/ {{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
                @if ($shippingCost > 0)
                <tr>
                    <td>—</td>
                    <td><em>Costo de Envío</em></td>
                    <td class="right">—</td>
                    <td class="right">—</td>
                    <td class="right">S/ {{ number_format($shippingCost, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Resumen Económico</div>
        <div class="summary-block">
            <div class="summary-row">
                <span class="key">Valor de Venta</span>
                <span class="val">S/ {{ number_format($totalGravada, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="key">Valor de Venta (Op. Inafectas)</span>
                <span class="val">S/ 0.00</span>
            </div>
            @if ($shippingCost > 0)
            <div class="summary-row">
                <span class="key">Envío</span>
                <span class="val">S/ {{ number_format($shippingCost, 2) }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span class="key">I.G.V. (18%)</span>
                <span class="val">S/ {{ number_format($igv, 2) }}</span>
            </div>
            <hr class="summary-divider">
            <div class="summary-total">
                <span class="key">Precio Final</span>
                <span class="val">S/ {{ number_format($total, 2) }}</span>
            </div>

            @if ($showCommission)
            <hr class="summary-divider">
            <div class="commission-row">
                <table>
                    <tr>
                        <td>Subtotal productos (base comisión)</td>
                        <td class="right">S/ {{ number_format($commissionBase, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tasa de Comisión Lyrium</td>
                        <td class="right">{{ number_format($commissionRate, 1) }}%</td>
                    </tr>
                    <tr>
                        <td>Comisión Lyrium</td>
                        <td class="right">S/ {{ number_format($commissionTotal, 2) }}</td>
                    </tr>
                </table>
            </div>
            <p class="commission-note">
                Comisión calculada sobre el subtotal de productos × tasa vigente (sin envío).<br>
                No modifica los importes fiscales del comprobante.
            </p>
            @endif
        </div>
    </div>

    <div class="letras-block">
        <p>{{ $amountInWords }}</p>
        <small>(S/ {{ number_format($total, 2) }})</small>
    </div>

    @if($qrImage)
    <div style="border: 1px solid #c8ddd4; border-radius: 6px; padding: 14px; margin-bottom: 14px; background: #f4fbf7;">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; vertical-align: middle; width: 90px;">
                <img src="{{ $qrImage }}" width="80" height="80" alt="QR SUNAT" />
            </div>
            <div style="display: table-cell; vertical-align: middle; padding-left: 14px;">
                <span style="background: #15803D; color: #fff; font-size: 7px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; padding: 2px 8px; border-radius: 3px;">Verificación SUNAT</span>
                <p style="font-size: 11px; font-weight: bold; color: #1a3a2e; margin: 4px 0 6px;">Código QR de Autenticidad</p>
                <p style="font-size: 8px; color: #5a7266; line-height: 1.7;">
                    Escanea este código para verificar la validez de este comprobante electrónico ante SUNAT.<br>
                    Comprobante: <strong style="color: #1c2b24;">{{ $series }}-{{ $number }}</strong> &nbsp;·&nbsp; RUC Emisor: <strong style="color: #1c2b24;">20612731838</strong><br>
                    @if($authorizationCode !== '—')
                    Autorización: <strong style="color: #2e6b50;">{{ $authorizationCode }}</strong><br>
                    @endif
                    <span style="color: #2e6b50;">e-consulta.sunat.gob.pe</span>
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="footer-meta">
        Resolución Nro. 0340050010017 / SUNAT<br>
        Representación impresa de la Factura Electrónica. Consulte su validez en: <strong>e-consulta.sunat.gob.pe</strong><br>
        Generado el {{ $generatedAt }}
    </div>

    <div class="footer-contact">
        lyriumbiomarketplace.com | {{ $companyEmail }} | {{ $companyPhone ?? '+51 999 888 777' }}
    </div>

</div>
</body>
</html>
