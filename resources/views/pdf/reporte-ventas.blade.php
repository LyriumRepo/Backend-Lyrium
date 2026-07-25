<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #ffffff; color: #1e293b; font-size: 9px; padding: 20px; }

        /* Header band */
        .header-band { background: #f8fafc; border-bottom: 2px solid #22c55e; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; margin: -20px -20px 16px -20px; }
        .logo-img { width: 90px; height: auto; }
        .header-title { font-size: 14px; font-weight: bold; color: #15803d; text-align: right; }
        .header-subtitle { font-size: 8px; color: #64748b; text-align: right; margin-top: 2px; }

        /* KPI cards */
        .kpi-row { display: flex; gap: 10px; margin: 16px 0; }
        .kpi-card { flex: 1; background: #f0fdf4; border: 1px solid #e2e8f0; border-radius: 6px; border-left: 3px solid #16a34a; padding: 10px; }
        .kpi-card .label { font-size: 7px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }
        .kpi-card .value { font-size: 14px; font-weight: bold; color: #1e293b; margin-top: 4px; }
        .kpi-card .sub { font-size: 7px; color: #94a3b8; margin-top: 2px; }

        /* Section separator */
        .section-sep { display: flex; align-items: center; gap: 8px; margin: 20px 0 10px; }
        .section-bar { width: 4px; height: 16px; background: #166534; border-radius: 2px; }
        .section-line { width: 30px; height: 1px; background: #22c55e; }
        .section-title { font-size: 11px; font-weight: bold; color: #1e293b; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { background: #dcfce7; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #15803d; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        .method-table { width: auto; margin-top: 4px; }
        .method-table th, .method-table td { padding: 4px 12px; }

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #22c55e; padding-top: 8px; display: flex; justify-content: space-between; font-size: 7px; color: #64748b; }
        .footer-brand { font-weight: bold; color: #15803d; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/nombrelogo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    <div class="header-band">
        <div>
            @if($logoBase64)<img src="{{ $logoBase64 }}" class="logo-img" alt="Lyrium">@endif
        </div>
        <div>
            <div class="header-title">REPORTE DE VENTAS</div>
            <div class="header-subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="label">Total Órdenes</div>
            <div class="value">{{ $totalOrders }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Pagadas</div>
            <div class="value">{{ $paidOrders }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Fallidas</div>
            <div class="value">{{ $failedOrders }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Pendientes</div>
            <div class="value">{{ $pendingOrders }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Total S/</div>
            <div class="value">S/ {{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>

    @if($methodDistribution->isNotEmpty())
    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">DISTRIBUCIÓN POR MÉTODO DE PAGO</div>
    </div>
    <table class="method-table">
        <thead>
            <tr>
                <th>Metodo</th>
                <th class="right">Cantidad</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($methodDistribution as $m)
            <tr>
                <td>{{ $m['method'] }}</td>
                <td class="right">{{ $m['count'] }}</td>
                <td class="right">S/ {{ number_format($m['totalInCents'] / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($dailyTotals->isNotEmpty())
    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">RESUMEN DIARIO</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th class="right">Ordenes</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $d)
            <tr>
                <td>{{ $d['date'] }}</td>
                <td class="right">{{ $d['count'] }}</td>
                <td class="right">S/ {{ number_format($d['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">DETALLE DE TRANSACCIONES</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Orden</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Email</th>
                <th class="right">Total</th>
                <th>Metodo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->user?->name ?? 'N/A' }}</td>
                <td>{{ $order->user?->email ?? 'N/A' }}</td>
                <td class="right">S/ {{ number_format($order->total, 2) }}</td>
                <td>{{ $order->latestIzipayTransaction?->payment_method_type ?? '—' }}</td>
                <td>{{ $order->payment_status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($orders->isEmpty())
        <p style="text-align:center;margin-top:20px;color:#64748b;">No se encontraron transacciones.</p>
    @endif

    <div class="footer">
        <div><span class="footer-brand">Lyrium BioMarketplace</span> — Reporte de Ventas</div>
        <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
