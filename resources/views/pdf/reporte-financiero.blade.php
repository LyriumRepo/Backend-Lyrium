<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #fff; color: #1c2b24; font-size: 9px; padding: 20px; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; color: #1a3a2e; }
        .subtitle { text-align: center; font-size: 8px; color: #5a7266; margin-bottom: 16px; }
        .summary { margin-bottom: 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #e8f5ee; font-size: 10px; }
        .summary-row:last-child { border-bottom: none; }
        .summary-row .label { color: #5a7266; }
        .summary-row .value { font-weight: bold; color: #1c2b24; }
        .summary-row.total { border-top: 2px solid #1a3a2e; margin-top: 4px; padding-top: 8px; font-size: 12px; background: #f0f9f4; }
        .summary-row.total .value { color: #1a3a2e; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #e8f5ee; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #2e6b50; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #e8f5ee; color: #1c2b24; font-size: 8px; }
        .right { text-align: right; }
        .section-title { font-size: 11px; font-weight: bold; color: #1a3a2e; margin-top: 20px; margin-bottom: 8px; }
        .negative { color: #c0392b; }
        .positive { color: #27ae60; }
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte Financiero</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <div class="section-title">Resumen</div>
    <div class="summary">
        <div class="summary-row">
            <span class="label">Ingresos (Ordenes Pagadas)</span>
            <span class="value">S/ {{ number_format($summary['totalIncome'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Gastos</span>
            <span class="value negative">- S/ {{ number_format($summary['totalExpenses'], 2) }}</span>
        </div>
        <div class="summary-row total">
            <span class="label">Ingreso Neto</span>
            <span class="value {{ $summary['netIncome'] >= 0 ? 'positive' : 'negative' }}">S/ {{ number_format($summary['netIncome'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Facturado</span>
            <span class="value">S/ {{ number_format($summary['totalInvoiced'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Total Ordenes</span>
            <span class="value">{{ $summary['totalOrdersCount'] }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Ordenes Pagadas</span>
            <span class="value">{{ $summary['paidOrdersCount'] }}</span>
        </div>
    </div>

    <div class="section-title">Ultimas Ordenes</div>
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
            <tr><td colspan="5" style="text-align:center;color:#999;">Sin ordenes</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Gastos</div>
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
            <tr><td colspan="4" style="text-align:center;color:#999;">Sin gastos</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
