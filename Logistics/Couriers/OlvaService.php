<?php

declare(strict_types=1);

namespace App\Services\Logistics\Couriers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OlvaService
{
    private const BASE      = 'https://www.olvacourier.com';
    private const UA        = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36';
    private const POOL_SIZE = 5;
    private const SESSION_TTL = 1500;
    private const DEPT_IDS = [
        'AMAZONAS' => '01',
        'ANCASH' => '02',
        'APURIMAC' => '03',
        'AREQUIPA' => '04',
        'AYACUCHO' => '05',
        'CAJAMARCA' => '06',
        'CALLAO' => '07',
        'CUSCO' => '08',
        'HUANCAVELICA' => '09',
        'HUANUCO' => '10',
        'ICA' => '11',
        'JUNIN' => '12',
        'LA LIBERTAD' => '13',
        'LAMBAYEQUE' => '14',
        'LIMA' => '15',
        'LORETO' => '16',
        'MADRE DE DIOS' => '17',
        'MOQUEGUA' => '18',
        'PASCO' => '19',
        'PIURA' => '20',
        'PUNO' => '21',
        'SAN MARTIN' => '22',
        'TACNA' => '23',
        'TUMBES' => '24',
        'UCAYALI' => '25',
    ];
    private function getSession(): ?string
    {
        $idx = Cache::increment('olva_pool_counter') % self::POOL_SIZE;

        $cacheKey = "olva_pool_session_{$idx}";
        $session  = Cache::get($cacheKey);

        if ($session) {
            return $session;
        }

        return Cache::lock("olva_pool_lock_{$idx}", 10)->block(8, function () use ($cacheKey) {
            $existing = Cache::get($cacheKey);
            if ($existing) return $existing;

            $session = $this->createSession();
            if ($session) {
                Cache::put($cacheKey, $session, self::SESSION_TTL);
                Log::info("[Olva Pool] Slot {$cacheKey} renovada");
            }
            return $session;
        });
    }

    /**
     * Marca una sesión como inválida (forzar renovación en próximo uso).
     * Se llama cuando una cotización devuelve 401/403 o falla.
     */
    private function invalidateSession(int $idx): void
    {
        Cache::forget("olva_pool_session_{$idx}");
        Log::info("[Olva Pool] Slot olva_pool_session_{$idx} invalidada — se renovará en próximo uso");
    }

    /**
     * Crea una ci_session nueva haciendo GET a /cotizador.
     * Sin Playwright, sin browser — OLVA solo necesita una petición HTTP.
     */
    private function createSession(): ?string
    {
        try {
            $res = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => self::UA,
                    'Accept'     => 'text/html,application/xhtml+xml',
                ])
                ->get(self::BASE . '/cotizador');

            $setCookie = $res->header('Set-Cookie') ?? '';
            preg_match('/ci_session=([^;]+)/', $setCookie, $m);

            return $m[1] ?? null;
        } catch (\Throwable $e) {
            Log::warning("[Olva] Error creando sesión: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Precalienta el pool completo (llamar al arrancar la app si quieres).
     * Opcional — el pool se autocrea con el primer uso.
     */
    public static function warmPool(): void
    {
        $service = app(self::class);
        for ($i = 0; $i < self::POOL_SIZE; $i++) {
            $cacheKey = "olva_pool_session_{$i}";
            if (!Cache::has($cacheKey)) {
                $session = $service->createSession();
                if ($session) {
                    Cache::put($cacheKey, $session, self::SESSION_TTL);
                    Log::info("[Olva Pool] Precalentando slot {$i}");
                }
            }
        }
    }
    private function headers(string $ciSession): array
    {
        return [
            'User-Agent'       => self::UA,
            'X-Requested-With' => 'XMLHttpRequest',
            'Origin'           => self::BASE,
            'Referer'          => self::BASE . '/cotizador',
            'Accept'           => 'application/json',
            'Cookie'           => "ci_session={$ciSession}",
        ];
    }
    private function getProvId(string $deptId, string $provNombre, string $ci): ?string
    {
        $map = Cache::remember("olva_prov_{$deptId}", 86400, function () use ($deptId, $ci) {
            try {
                $res = Http::timeout(8)
                    ->withHeaders($this->headers($ci))
                    ->asForm()
                    ->post(self::BASE . '/provincias', ['departamento' => $deptId]);

                $map = [];
                foreach ($res->json() ?? [] as $p) {
                    $map[$this->norm($p['provincia'] ?? '')] = $p['id'];
                }
                return $map ?: null;
            } catch (\Throwable) {
                return null;
            }
        });

        return ($map ?? [])[$this->norm($provNombre)] ?? null;
    }

    private function getDistId(string $provId, string $distNombre, string $ci): ?string
    {
        $map = Cache::remember("olva_dist_{$provId}", 86400, function () use ($provId, $ci) {
            try {
                $res = Http::timeout(8)
                    ->withHeaders($this->headers($ci))
                    ->asForm()
                    ->post(self::BASE . '/distritos', ['provincia' => $provId]);

                $map = [];
                foreach ($res->json() ?? [] as $d) {
                    $map[$this->norm($d['name'] ?? '')] = $d['id'];
                }
                return $map ?: null;
            } catch (\Throwable) {
                return null;
            }
        });

        return ($map ?? [])[$this->norm($distNombre)] ?? null;
    }
    private function cotizarCaja(
        array  $origIds,
        array  $destIds,
        array  $caja,
        string $recojo,
        string $ci
    ): ?float {
        $body = http_build_query([
            'HddPartner'              => 0,
            'encuentras-departamento' => $origIds['dept'],
            'encuentras-provincia'    => $origIds['prov'] ?? ($origIds['dept'] . '01'),
            'encuentras-distrito'     => $origIds['dist'] ?? ($origIds['prov']  . '01'),
            'llevamos-departamento'   => $destIds['dept'],
            'llevamos-provincia'      => $destIds['prov'] ?? ($destIds['dept']  . '01'),
            'llevamos-distrito'       => $destIds['dist'] ?? ($destIds['prov']  . '01'),
            'que'                     => 'Paquetes',
            'pesa'                    => $caja['pesoFacturable'] ?? 1,
            'ancho'                   => $caja['ancho'] ?? 20,
            'largo'                   => $caja['largo'] ?? 30,
            'alto'                    => $caja['alto']  ?? 15,
            'recojo'                  => $recojo,
        ]);

        try {
            $res = Http::timeout(12)
                ->withHeaders(array_merge(
                    $this->headers($ci),
                    ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8']
                ))
                ->withBody($body, 'application/x-www-form-urlencoded')
                ->post(self::BASE . '/cotizar');

            if ($res->status() === 401 || $res->status() === 403) {
                $idx = Cache::get('olva_pool_counter', 0) % self::POOL_SIZE;
                $this->invalidateSession($idx);
                return null;
            }

            $d  = $res->json();
            $ok = in_array($d['estado'] ?? '', [true, 1, '1', 'true'], true)
                || (float) ($d['costo'] ?? 0) > 0;

            return $ok ? (float) $d['costo'] : null;
        } catch (\Throwable $e) {
            Log::debug("[Olva] cotizarCaja {$recojo}: {$e->getMessage()}");
            return null;
        }
    }
    public function cotizar(array $origen, array $destino, array $cajas): ?array
    {
        if (empty($cajas)) return null;

        $ci = $this->getSession();
        if (!$ci) {
            Log::warning('[Olva] Sin sesión disponible en el pool');
            return null;
        }

        $origDeptId = self::DEPT_IDS[$this->norm($origen['departamento'])]         ?? null;
        $destDeptId = self::DEPT_IDS[$this->norm($destino['departamento'] ?? '')]  ?? null;

        if (!$origDeptId || !$destDeptId) {
            Log::warning("[Olva] Departamento no reconocido: {$origen['departamento']}");
            return null;
        }

        $origProvId = $this->getProvId($origDeptId, $origen['provincia'],        $ci);
        $origDistId = $origProvId ? $this->getDistId($origProvId, $origen['distrito'],   $ci) : null;
        $destProvId = $this->getProvId($destDeptId, $destino['provincia'] ?? '', $ci);
        $destDistId = $destProvId ? $this->getDistId($destProvId, $destino['distrito'],  $ci) : null;

        $origIds = ['dept' => $origDeptId, 'prov' => $origProvId, 'dist' => $origDistId];
        $destIds = ['dept' => $destDeptId, 'prov' => $destProvId, 'dist' => $destDistId];

        $totalAg  = 0.0;
        $totalDom = 0.0;
        $agOk     = false;
        $domOk    = false;

        foreach ($cajas as $caja) {
            $ag  = $this->cotizarCaja($origIds, $destIds, $caja, 'TDA', $ci);
            $dom = $this->cotizarCaja($origIds, $destIds, $caja, 'REG', $ci);

            if ($ag  !== null) {
                $totalAg  += $ag;
                $agOk  = true;
            }
            if ($dom !== null) {
                $totalDom += $dom;
                $domOk = true;
            }
        }

        if (!$agOk && !$domOk) {
            Log::warning('[Olva] Sin cobertura para esta ruta');
            return null;
        }

        return [
            'courier'   => 'Olva',
            'tiempo'    => '24-48h',
            'precio'    => $agOk ? round($totalAg, 2) : round($totalDom, 2),
            'agencia'   => $agOk
                ? ['disponible' => true,  'precio' => round($totalAg, 2),  'label' => 'Retiro en Agencia']
                : ['disponible' => false, 'precio' => null],
            'domicilio' => $domOk
                ? ['disponible' => true,  'precio' => round($totalDom, 2), 'label' => 'Entrega a Domicilio']
                : ['disponible' => false, 'precio' => null],
            'origen'    => "{$origen['distrito']}, {$origen['departamento']}",
            'destino'   => "{$destino['distrito']}, {$destino['departamento']}",
        ];
    }
    private function norm(string $s): string
    {
        return strtoupper(trim(
            preg_replace(
                '/[^a-z0-9 ]/u',
                '',
                str_replace(
                    ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
                    ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
                    mb_strtolower($s)
                )
            )
        ));
    }
}
