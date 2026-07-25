<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Productos - Lyrium</title>
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
        .kpi-card .value { font-size: 12px; font-weight: bold; color: #1e293b; margin-top: 4px; }
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

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #22c55e; padding-top: 8px; display: flex; justify-content: space-between; font-size: 7px; color: #64748b; }
        .footer-brand { font-weight: bold; color: #15803d; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/nombrelogo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $topProduct = $products->first();
        $topService = $services->first();
    @endphp

    <div class="header-band">
        <div>
            @if($logoBase64)<img src="{{ $logoBase64 }}" class="logo-img" alt="Lyrium">@endif
        </div>
        <div>
            <div class="header-title">REPORTE DE PRODUCTOS Y SERVICIOS</div>
            <div class="header-subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="label">Total Productos</div>
            <div class="value">{{ $products->count() }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Total Servicios</div>
            <div class="value">{{ $services->count() }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Más Vendido</div>
            <div class="value">{{ $topProduct['name'] ?? '—' }}</div>
            <div class="sub">{{ $topProduct['sold_qty'] ?? 0 }} unidades</div>
        </div>
        <div class="kpi-card">
            <div class="label">Más Reservado</div>
            <div class="value">{{ $topService['name'] ?? '—' }}</div>
            <div class="sub">{{ $topService['bookings_count'] ?? 0 }} reservas</div>
        </div>
    </div>

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">PRODUCTOS (ordenados por vendidos)</div>
    </div>
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
            <tr><td colspan="9" style="text-align:center;color:#64748b;">Sin productos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">SERVICIOS (ordenados por reservas)</div>
    </div>
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
            <tr><td colspan="7" style="text-align:center;color:#64748b;">Sin servicios</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div><span class="footer-brand">Lyrium BioMarketplace</span> — Reporte de Productos y Servicios</div>
        <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
