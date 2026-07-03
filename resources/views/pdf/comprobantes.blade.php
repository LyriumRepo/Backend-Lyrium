<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Comprobantes - Lyrium</title>
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
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte de Comprobantes</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tipo</th>
                <th>Serie-Nro</th>
                <th>Cliente</th>
                <th>RUC/DNI</th>
                <th class="right">Monto</th>
                <th>Estado SUNAT</th>
                <th>Fecha Emisión</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $inv)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $inv->document_type ?? $inv->type ?? '—' }}</td>
                <td>{{ $inv->series }}-{{ $inv->number }}</td>
                <td>{{ $inv->business_name ?? $inv->customer_name ?? '—' }}</td>
                <td>{{ $inv->nit ?? $inv->customer_ruc ?? '—' }}</td>
                <td class="right">S/ {{ number_format($inv->total ?? $inv->amount ?? 0, 2) }}</td>
                <td>{{ $inv->status ?? '—' }}</td>
                <td>{{ optional($inv->emission_date ?? $inv->created_at)->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if ($invoices->isEmpty())
        <p style="text-align:center;margin-top:20px;color:#999;">No se encontraron comprobantes.</p>
    @endif
</body>
</html>
