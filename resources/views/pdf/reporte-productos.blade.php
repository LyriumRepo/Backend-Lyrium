<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Productos - Lyrium</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #0c2316; color: #d7ebcd; font-size: 9px; padding: 20px; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; color: #a3e635; }
        .subtitle { text-align: center; font-size: 8px; color: #6b8f71; margin-bottom: 16px; }
        .section-title { font-size: 11px; font-weight: bold; color: #a3e635; margin-top: 20px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #123c26; padding: 6px 4px; text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #a3e635; font-weight: bold; }
        td { padding: 5px 4px; border-bottom: 1px solid #265a37; color: #d7ebcd; }
        tr:nth-child(even) td { background: #143420; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h2>LYRIUM - Reporte de Productos y Servicios</h2>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <div class="section-title">Productos (ordenados por vendidos)</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Tienda</th>
                <th>Categoria</th>
                <th class="right">Precio</th>
                <th>Estado</th>
                <th class="right">Vend.</th>
                <th class="right">Reviews</th>
                <th class="right">Rating</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['store'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td class="right">S/ {{ number_format($product['price'], 2) }}</td>
                <td>{{ $product['status'] }}</td>
                <td class="right">{{ $product['sold_qty'] }}</td>
                <td class="right">{{ $product['reviews_count'] }}</td>
                <td class="right">{{ $product['rating'] }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:#6b8f71;">Sin productos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Servicios (ordenados por reservas)</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Servicio</th>
                <th>Tienda</th>
                <th>Categoria</th>
                <th class="right">Precio</th>
                <th>Estado</th>
                <th class="right">Reservas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $service['name'] }}</td>
                <td>{{ $service['store'] }}</td>
                <td>{{ $service['category'] }}</td>
                <td class="right">S/ {{ number_format($service['price'], 2) }}</td>
                <td>{{ $service['status'] }}</td>
                <td class="right">{{ $service['bookings_count'] }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#6b8f71;">Sin servicios</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
