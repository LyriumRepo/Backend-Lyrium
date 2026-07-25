<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Vendedores - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #0c2316; color: #d7ebcd; font-size: 9px; padding: 20px; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; color: #a3e635; }
        .subtitle { text-align: center; font-size: 8px; color: #6b8f71; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #123c26; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #a3e635; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #265a37; color: #d7ebcd; }
        tr:nth-child(even) td { background: #143420; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row td { border-top: 2px solid #a3e635; font-weight: bold; background: #143420; }
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte de Vendedores</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tienda</th>
                <th>Propietario</th>
                <th>Email</th>
                <th>Estado</th>
                <th class="right">Prod.</th>
                <th class="right">Serv.</th>
                <th class="right">Vendido</th>
                <th class="right">Comision</th>
                <th>Plan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stores as $store)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $store['store_name'] }}</td>
                <td>{{ $store['owner'] }}</td>
                <td>{{ $store['email'] }}</td>
                <td>{{ $store['status'] }}</td>
                <td class="right">{{ $store['products_count'] }}</td>
                <td class="right">{{ $store['services_count'] }}</td>
                <td class="right">S/ {{ number_format($store['total_sold'], 2) }}</td>
                <td class="right">S/ {{ number_format($store['commission'], 2) }}</td>
                <td>{{ $store['plan'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($stores->isEmpty())
        <p style="text-align:center;margin-top:20px;color:#6b8f71;">No se encontraron vendedores.</p>
    @endif
</body>
</html>
