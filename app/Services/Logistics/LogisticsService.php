<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use App\Services\Logistics\BoxCalculatorService;
use App\Services\Logistics\Couriers\OlvaService;
use App\Services\Logistics\Couriers\ShalomService;
use App\Services\Logistics\Couriers\SharfService;
use App\Services\Logistics\Couriers\UrbanoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogisticsService
{
    public function __construct(
        private readonly ShalomService $shalom,
        private readonly OlvaService   $olva,
        private readonly SharfService  $sharf,
        private readonly UrbanoService $urbano,
    ) {}

    public function getDepartamentos(): array
    {
        return DB::table('courier_operators')
            ->select('departamento as nombre')->distinct()->orderBy('departamento')
            ->pluck('nombre')->map(fn($d) => ['nombre' => $d])->toArray();
    }

    public function getProvincias(string $departamento): array
    {
        return DB::table('courier_operators')
            ->where('departamento', $this->norm($departamento))
            ->select('provincia as nombre')->distinct()->orderBy('provincia')
            ->pluck('nombre')->map(fn($p) => ['nombre' => $p])->toArray();
    }

    public function getDistritos(string $departamento, string $provincia): array
    {
        return DB::table('courier_operators')
            ->where('departamento', $this->norm($departamento))
            ->where('provincia',   $this->norm($provincia))
            ->orderBy('distrito')->pluck('distrito')->toArray();
    }

    public function getOperadores(string $departamento, string $provincia, string $distrito): array
    {
        $row = DB::table('courier_operators')
            ->where('departamento', $this->norm($departamento))
            ->where('provincia',   $this->norm($provincia))
            ->where('distrito',    $this->norm($distrito))
            ->first();

        if (!$row) return [];
        $ops = [];
        if ($row->tiene_shalom) $ops[] = 'Shalom';
        if ($row->tiene_olva)   $ops[] = 'Olva';
        if ($row->tiene_sharf)  $ops[] = 'Sharf';
        if ($row->tiene_urbano) $ops[] = 'Urbano';
        return $ops;
    }
    public function buscarTerminalShalom(string $dept, string $prov, string $dist): ?array
    {
        $nd  = $this->norm($dept);
        $ndi = $this->norm($dist);

        $row = DB::table('shalom_data')
            ->whereRaw("UPPER(REPLACE(REPLACE(zona,'á','a'),'é','e')) = ?", [$ndi])
            ->whereNotNull('ter_id')->first()
            ?? DB::table('shalom_data')
            ->whereRaw("UPPER(REPLACE(REPLACE(nombre,'á','a'),'é','e')) = ?", [$ndi])
            ->whereNotNull('ter_id')->first()
            ?? DB::table('shalom_data')
            ->where('departamento', $nd)
            ->where(fn($q) => $q->whereRaw('UPPER(zona) LIKE ?', ["%{$ndi}%"])->orWhereRaw('UPPER(nombre) LIKE ?', ["%{$ndi}%"]))
            ->whereNotNull('ter_id')->first()
            ?? DB::table('shalom_data')->where('departamento', $nd)->whereNotNull('ter_id')->first()
            ?? DB::table('shalom_data')->where('ter_id', 4)->first();

        return $row ? (array) $row : null;
    }

    public function buscarRepartoShalom(int $deptId, int $provId, string $dist): ?array
    {
        $ndi = $this->norm($dist);

        $row = DB::table('shalom_data')
            ->where('tiene_reparto', true)->where('departamento_id', $deptId)->where('provincia_id', $provId)
            ->whereRaw("UPPER(REPLACE(REPLACE(nombre,'á','a'),'é','e')) = ?", [$ndi])->first()
            ?? DB::table('shalom_data')
            ->where('tiene_reparto', true)->where('departamento_id', $deptId)->where('provincia_id', $provId)
            ->whereRaw('UPPER(zona) LIKE ?', ["%{$ndi}%"])->first();

        if (!$row) return null;

        return [
            'disponible'      => true,
            'departamento_id' => $row->departamento_id,
            'provincia_id'    => $row->provincia_id,
            'distrito_id'     => $row->distrito_id,
            'ubi_id'          => $row->ubi_id,
            'tarifas_reparto' => is_string($row->tarifas_reparto)
                ? json_decode($row->tarifas_reparto, true)
                : $row->tarifas_reparto,
        ];
    }

    public function calcularCaja(array $productos): array
    {
        $result = app(BoxCalculatorService::class)->calcular($productos);
        return [
            'success'    => true,
            'caja'       => $result['resumen'],
            'cajas'      => $result['cajas'],
            'eficiencia' => $result['eficiencia'],
        ];
    }
    public function calcular(array $productos, array $destino, string $tipoEntrega = 'domicilio'): array
    {
        $destDept = $this->norm($destino['departamento'] ?? '');
        $destProv = $this->norm($destino['provincia']    ?? '');
        $destDist = $this->norm($destino['distrito']     ?? '');

        $grupos  = $this->agruparPorTienda($productos);
        $tiendas = [];

        Log::info("[Logistics] calcular: {$destDist} | " . count($grupos) . ' tienda(s)');

        foreach ($grupos as $grupo) {
            try {
                $boxResult         = app(BoxCalculatorService::class)->calcular($grupo['productos']);
                $cajasNormalizadas = $this->normalizarCajas($boxResult['cajas']);

                $origDept  = $this->norm($grupo['origen']['departamento']);
                $isLocal   = $origDept === $destDept;
                $eligibles = $this->eligibleCouriers($grupo['origen'], $destino);

                Log::info("[Logistics] {$grupo['store_name']}: " . ($isLocal ? 'LOCAL' : 'INTERP.') . ' → ' . implode('+', $eligibles));

                $opciones = $this->cotizarTodos($eligibles, $grupo['origen'], $destino, $cajasNormalizadas);

                usort($opciones, fn($a, $b) => $this->precioMin($a) <=> $this->precioMin($b));

                $tiendas[] = [
                    'tiendaId'   => $grupo['store_id'],
                    'tienda'     => $grupo['store_name'],
                    'origen'     => array_merge($grupo['origen'], ['display' => strtoupper($grupo['origen']['distrito'])]),
                    'productos'  => [
                        'cantidad' => (int) array_sum(array_column($grupo['productos'], 'cantidad')),
                        'items'    => $grupo['productos'],
                    ],
                    'cajas'      => [
                        'resumen'    => $boxResult['resumen'],
                        'cantidad'   => count($cajasNormalizadas),
                        'eficiencia' => $boxResult['eficiencia'] ?? 1,
                        'detalle'    => $cajasNormalizadas,
                    ],
                    'logistica'  => [
                        'esLocal'             => $isLocal,
                        'couriersDisponibles' => $eligibles,
                        'opciones'            => $opciones,
                        'soportaDomicilio'    => (bool) collect($opciones)->contains(fn($o) => ($o['domicilio']['disponible'] ?? false) === true),
                        'soportaAgencia'      => (bool) collect($opciones)->contains(fn($o) => ($o['agencia']['disponible']  ?? false) === true),
                    ],
                ];
            } catch (\Throwable $e) {
                Log::error("[Logistics] Error tienda {$grupo['store_name']}: {$e->getMessage()}");
                $tiendas[] = ['tiendaId' => $grupo['store_id'], 'tienda' => $grupo['store_name'], 'error' => $e->getMessage()];
            }
        }

        return [
            'success'     => true,
            'destino'     => [
                'departamento' => $destino['departamento'],
                'provincia'    => $destino['provincia']   ?? '',
                'distrito'     => $destino['distrito']    ?? '',
                'display'      => strtoupper($destino['distrito'] ?? ''),
            ],
            'tipoEntrega' => $tipoEntrega,
            'tiendas'     => $tiendas,
            'resumen'     => [
                'totalTiendas'   => count($tiendas),
                'totalProductos' => count($productos),
                'totalOpciones'  => (int) array_sum(array_map(fn($t) => count($t['logistica']['opciones'] ?? []), $tiendas)),
                'hayLocal'       => (bool) collect($tiendas)->contains(fn($t) => $t['logistica']['esLocal'] ?? false),
            ],
        ];
    }

    public function confirmarEnvio(string $modo, array $productos, array $destino, string $tipoEntrega, ?string $courierUnificado, array $selecciones): array
    {
        $grupos     = $this->agruparPorTienda($productos);
        $tiendas    = [];
        $totalEnvio = 0.0;

        foreach ($grupos as $grupo) {
            $boxResult         = app(BoxCalculatorService::class)->calcular($grupo['productos']);
            $cajasNormalizadas = $this->normalizarCajas($boxResult['cajas']);
            $isLocal           = $this->norm($grupo['origen']['departamento']) === $this->norm($destino['departamento'] ?? '');
            $eligibles         = $this->eligibleCouriers($grupo['origen'], $destino);

            $courierFinal = $modo === 'unificado'
                ? ($isLocal ? ($eligibles[0] ?? null) : $courierUnificado)
                : ($selecciones[(string) $grupo['store_id']] ?? null);

            if (!$courierFinal) throw new \InvalidArgumentException("Falta courier para {$grupo['store_name']}");

            $opcion = $this->cotizarUno($courierFinal, $grupo['origen'], $destino, $cajasNormalizadas);
            if (!$opcion) throw new \RuntimeException("Sin precio de {$courierFinal} para {$grupo['store_name']}");

            $precio = $this->precioSegunEntrega($opcion, $tipoEntrega);
            if ($precio === null) throw new \InvalidArgumentException("{$courierFinal} no soporta {$tipoEntrega}");

            $tiendas[]   = ['tienda' => $grupo['store_name'], 'courier' => $courierFinal, 'precioEnvio' => round($precio, 2)];
            $totalEnvio += $precio;
        }

        return ['success' => true, 'resumen' => ['tiendas' => $tiendas, 'totales' => ['precioEnvio' => round($totalEnvio, 2)]]];
    }
    private function cotizarTodos(array $eligibles, array $origen, array $destino, array $cajas): array
    {
        $opciones = [];
        foreach ($eligibles as $courier) {
            try {
                $op = $this->cotizarUno($courier, $origen, $destino, $cajas);
                if ($op) {
                    $op['courier'] = $this->normNombre($op['courier'] ?? $courier);
                    $opciones[]    = $op;
                }
            } catch (\Throwable $e) {
                Log::warning("[Logistics] {$courier}: {$e->getMessage()}");
            }
        }
        return $opciones;
    }

    private function cotizarUno(string $courier, array $origen, array $destino, array $cajas): ?array
    {
        return match (strtolower($courier)) {
            'shalom' => $this->shalom->cotizar($origen, $destino, $cajas),
            'olva'   => $this->olva->cotizar($origen, $destino, $cajas),
            'sharf'  => $this->sharf->cotizar($origen, $destino, $cajas),
            'urbano' => $this->urbano->cotizar($origen, $destino, $cajas),
            default  => null,
        };
    }

    private function eligibleCouriers(array $origen, array $destino): array
    {
        $isLocal = $this->norm($origen['departamento']) === $this->norm($destino['departamento'] ?? '');

        $row = DB::table('courier_operators')
            ->where('departamento', $this->norm($destino['departamento'] ?? ''))
            ->where('provincia',   $this->norm($destino['provincia']    ?? ''))
            ->where('distrito',    $this->norm($destino['distrito']     ?? ''))
            ->first()
            ?? DB::table('courier_operators')
            ->where('departamento', $this->norm($destino['departamento'] ?? ''))
            ->first();

        if ($isLocal) {
            $couriers = [];
            if ($row?->tiene_shalom) $couriers[] = 'Shalom';
            if ($row?->tiene_sharf)  $couriers[] = 'Sharf';
            if ($row?->tiene_urbano) $couriers[] = 'Urbano';
            return $couriers ?: ['Shalom', 'Sharf', 'Urbano'];
        }

        if (!$row) return ['Shalom'];

        $couriers = [];
        if ($row->tiene_shalom) $couriers[] = 'Shalom';
        if ($row->tiene_olva)   $couriers[] = 'Olva';
        if ($row->tiene_sharf)  $couriers[] = 'Sharf';
        if ($row->tiene_urbano) $couriers[] = 'Urbano';
        return $couriers ?: ['Shalom'];
    }

    private function agruparPorTienda(array $productos): array
    {
        $grupos = [];
        foreach ($productos as $p) {
            $sid = (int) ($p['store_id'] ?? 0);
            $key = "s{$sid}";
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'store_id'   => $sid,
                    'store_name' => $p['store_name'] ?? ($p['storeName'] ?? 'Tienda'),
                    'origen'     => [
                        'departamento' => strtoupper($p['origen']['departamento'] ?? 'LA LIBERTAD'),
                        'provincia'    => strtoupper($p['origen']['provincia']    ?? 'TRUJILLO'),
                        'distrito'     => strtoupper($p['origen']['distrito']     ?? 'TRUJILLO'),
                    ],
                    'productos'  => [],
                ];
            }
            $grupos[$key]['productos'][] = [
                'nombre'   => $p['name']     ?? ($p['nombre']  ?? 'Producto'),
                'cantidad' => (int)   ($p['quantity'] ?? ($p['cantidad'] ?? 1)),
                'peso'     => (float) ($p['peso']  ?? 0.5),
                'largo'    => (float) ($p['largo'] ?? 30),
                'ancho'    => (float) ($p['ancho'] ?? 20),
                'alto'     => (float) ($p['alto']  ?? 15),
                'precio'   => (float) ($p['unitPrice'] ?? ($p['precio'] ?? 0)),
            ];
        }
        return array_values($grupos);
    }

    private function normalizarCajas(array $cajas): array
    {
        return array_map(fn($c) => [
            'tipo'            => $c['tipo']            ?? 'M',
            'largo'           => (float) ($c['largo']  ?? 30),
            'ancho'           => (float) ($c['ancho']  ?? 20),
            'alto'            => (float) ($c['alto']   ?? 15),
            'pesoReal'        => (float) ($c['pesoReal']        ?? 1),
            'pesoVolumetrico' => (float) ($c['pesoVolumetrico'] ?? 1),
            'pesoFacturable'  => (float) ($c['pesoFacturable']  ?? 1),
        ], $cajas);
    }

    private function precioMin(array $op): float
    {
        return (float) min(
            $op['precio']              ?? 9999,
            $op['agencia']['precio']   ?? 9999,
            $op['domicilio']['precio'] ?? 9999,
        );
    }

    private function precioSegunEntrega(array $op, string $tipo): ?float
    {
        if ($tipo === 'agencia') {
            if ($op['agencia']['disponible'] ?? false) return (float) $op['agencia']['precio'];
            return isset($op['precio']) ? (float) $op['precio'] : null;
        }
        if ($op['domicilio']['disponible'] ?? false) return (float) $op['domicilio']['precio'];
        return isset($op['precio']) ? (float) $op['precio'] : null;
    }

    private function normNombre(string $n): string
    {
        return match (strtolower(trim($n))) {
            'shalom express', 'shalom' => 'Shalom',
            'olva courier',   'olva'   => 'Olva',
            'sharf express',  'sharf'  => 'Sharf',
            'urbano envíos',  'urbano' => 'Urbano',
            default => $n,
        };
    }

    private function norm(string $s): string
    {
        $s = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'],
            $s
        );
        $s = preg_replace('/[^a-zA-Z0-9\s]/u', '', $s);
        return strtoupper(trim($s));
    }
}
