<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Transacciones - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #fff; color: #1c2b24; font-size: 9px; padding: 20px; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; color: #1a3a2e; }
        .subtitle { text-align: center; font-size: 8px; color: #5a7266; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e8f5ee; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #2e6b50; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #e8f5ee; color: #1c2b24; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row td { border-top: 2px solid #1a3a2e; font-weight: bold; background: #f0f9f4; }
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte de Transacciones</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Orden</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Email</th>
                <th class="right">Total</th>
                <th>Método</th>
                <th>Estado Pago</th>
                <th>Estado Trans.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->user?->name ?? 'N/A' }}</td>
                <td>{{ $order->user?->email ?? 'N/A' }}</td>
                <td class="right">S/ {{ number_format($order->total, 2) }}</td>
                <td>{{ $order->latestIzipayTransaction?->payment_method_type ?? '—' }}</td>
                <td>{{ $order->payment_status }}</td>
                <td>{{ $order->latestIzipayTransaction?->transaction_status ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if ($orders->isEmpty())
        <p style="text-align:center;margin-top:20px;color:#999;">No se encontraron transacciones.</p>
    @endif
</body>
</html>
