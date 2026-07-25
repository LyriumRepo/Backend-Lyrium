<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #0c2316; color: #d7ebcd; font-size: 9px; padding: 20px; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; color: #a3e635; }
        .subtitle { text-align: center; font-size: 8px; color: #6b8f71; margin-bottom: 16px; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 16px; gap: 8px; }
        .summary-card { flex: 1; background: #143420; padding: 10px; border-radius: 6px; text-align: center; border-left: 3px solid #166534; }
        .summary-card .label { font-size: 7px; text-transform: uppercase; letter-spacing: 1px; color: #6b8f71; }
        .summary-card .value { font-size: 14px; font-weight: bold; color: #e6ffe6; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #123c26; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #a3e635; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #265a37; color: #d7ebcd; }
        tr:nth-child(even) td { background: #143420; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section-title { font-size: 11px; font-weight: bold; color: #a3e635; margin-top: 20px; margin-bottom: 8px; }
        .method-table { width: auto; margin-top: 4px; }
        .method-table th, .method-table td { padding: 4px 12px; }
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte de Ventas</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Ordenes</div>
            <div class="value">{{ $totalOrders }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Pagadas</div>
            <div class="value">{{ $paidOrders }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Fallidas</div>
            <div class="value">{{ $failedOrders }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Pendientes</div>
            <div class="value">{{ $pendingOrders }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total S/</div>
            <div class="value">S/ {{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>

    @if($methodDistribution->isNotEmpty())
    <div class="section-title">Distribucion por Metodo de Pago</div>
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
    <div class="section-title">Resumen Diario</div>
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

    <div class="section-title">Detalle de Transacciones</div>
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
        <p style="text-align:center;margin-top:20px;color:#6b8f71;">No se encontraron transacciones.</p>
    @endif
</body>
</html>
