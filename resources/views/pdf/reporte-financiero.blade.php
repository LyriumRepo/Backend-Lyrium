<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero - Lyrium</title>
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
        .kpi-card.negative { border-left-color: #dc2626; }
        .kpi-card.negative .value { color: #dc2626; }

        /* Section separator */
        .section-sep { display: flex; align-items: center; gap: 8px; margin: 20px 0 10px; }
        .section-bar { width: 4px; height: 16px; background: #166534; border-radius: 2px; }
        .section-line { width: 30px; height: 1px; background: #22c55e; }
        .section-title { font-size: 11px; font-weight: bold; color: #1e293b; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { background: #dcfce7; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #15803d; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 8px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }

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
            <div class="header-title">REPORTE FINANCIERO</div>
            <div class="header-subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="label">Ingresos</div>
            <div class="value">S/ {{ number_format($summary['totalIncome'], 2) }}</div>
            <div class="sub">Ordenes pagadas</div>
        </div>
        <div class="kpi-card negative">
            <div class="label">Gastos</div>
            <div class="value">- S/ {{ number_format($summary['totalExpenses'], 2) }}</div>
        </div>
        <div class="kpi-card {{ $summary['netIncome'] < 0 ? 'negative' : '' }}">
            <div class="label">Ingreso Neto</div>
            <div class="value">S/ {{ number_format($summary['netIncome'], 2) }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Total Ordenes</div>
            <div class="value">{{ $summary['totalOrdersCount'] }}</div>
            <div class="sub">{{ $summary['paidOrdersCount'] }} pagadas</div>
        </div>
    </div>

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">ÚLTIMAS ÓRDENES</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Orden</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th class="right">Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders->take(50) as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                <td>{{ $order->user?->name ?? 'N/A' }}</td>
                <td class="right">S/ {{ number_format($order->total, 2) }}</td>
                <td>{{ $order->payment_status }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#64748b;">Sin ordenes</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">GASTOS</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Fecha</th>
                <th class="right">Monto</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td>{{ $expense->concept }}</td>
                <td>{{ $expense->created_at->format('d/m/Y') }}</td>
                <td class="right">S/ {{ number_format($expense->amount, 2) }}</td>
                <td>{{ $expense->status }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:#64748b;">Sin gastos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div><span class="footer-brand">Lyrium BioMarketplace</span> — Reporte Financiero</div>
        <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
