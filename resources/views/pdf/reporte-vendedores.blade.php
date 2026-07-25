<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Vendedores - Lyrium</title>
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

        /* Status badges */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 7px; font-weight: bold; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #22c55e; padding-top: 8px; display: flex; justify-content: space-between; font-size: 7px; color: #64748b; }
        .footer-brand { font-weight: bold; color: #15803d; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/nombrelogo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $totalTiendas = $stores->count();
        $activas = $stores->where('status', 'approved')->count();
        $pendientes = $stores->where('status', 'pending')->count();
        $ingresosTotales = $stores->sum('total_sold');

        $statusMap = [
            'approved' => ['label' => 'Activo', 'class' => 'badge-green'],
            'pending' => ['label' => 'Pendiente', 'class' => 'badge-amber'],
            'rejected' => ['label' => 'Rechazado', 'class' => 'badge-red'],
            'banned' => ['label' => 'Baneado', 'class' => 'badge-red'],
        ];
    @endphp

    <div class="header-band">
        <div>
            @if($logoBase64)<img src="{{ $logoBase64 }}" class="logo-img" alt="Lyrium">@endif
        </div>
        <div>
            <div class="header-title">PADRÓN DE VENDEDORES</div>
            <div class="header-subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="label">Total Tiendas</div>
            <div class="value">{{ $totalTiendas }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Activas</div>
            <div class="value">{{ $activas }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Pendientes</div>
            <div class="value">{{ $pendientes }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">Ingresos Totales</div>
            <div class="value">S/ {{ number_format($ingresosTotales, 2) }}</div>
        </div>
    </div>

    <div class="section-sep">
        <div class="section-bar"></div>
        <div class="section-line"></div>
        <div class="section-title">PADRÓN DE VENDEDORES</div>
    </div>

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
                <td>
                    @php $st = $statusMap[$store['status']] ?? ['label' => ucfirst($store['status']), 'class' => 'badge-gray']; @endphp
                    <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                </td>
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
        <p style="text-align:center;margin-top:20px;color:#64748b;">No se encontraron vendedores.</p>
    @endif

    <div class="footer">
        <div><span class="footer-brand">Lyrium BioMarketplace</span> — Padrón de Vendedores</div>
        <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
