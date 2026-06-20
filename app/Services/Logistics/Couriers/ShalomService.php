<?php

declare(strict_types=1);

namespace App\Services\Logistics\Couriers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShalomService
{
    private const API     = 'https://serviceswebapi.shalomcontrol.com/api/v1/web';
    private const HEADERS = [
        'Accept'  => 'application/json',
        'Origin'  => 'https://shalom.com.pe',
        'Referer' => 'https://shalom.com.pe/',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ];

    private const ENCRYPTION_KEY = 'uQn/bQ94PXBEfId70zjN+VE1hSU7kh9VBXTOUd68Ssc=';

    private function decryptIfNeeded(array $response): array
    {
        if (!($response['encrypted'] ?? false)) {
            return $response;
        }

        $encryptedData = $response['data'] ?? null;
        if (!$encryptedData || !is_string($encryptedData)) {
            Log::warning('[Shalom] Respuesta marcada encrypted pero sin data válida');
            return $response;
        }

        try {
            $key      = base64_decode(self::ENCRYPTION_KEY);
            $combined = base64_decode($encryptedData);

            if (strlen($combined) < 32) {
                throw new \RuntimeException('Ciphertext demasiado corto');
            }

            $iv         = substr($combined, 0, 16);
            $ciphertext = substr($combined, 16);

            $decrypted = openssl_decrypt(
                $ciphertext,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('openssl_decrypt falló: ' . openssl_error_string());
            }
            $parsed = json_decode($decrypted, true);
            return $parsed ?? ['success' => false, 'message' => 'Error al parsear JSON descifrado'];
        } catch (\Throwable $e) {
            Log::error('[Shalom] Error al descifrar: ' . $e->getMessage());
            return $response;
        }
    }
    private function getLBCookie(): string
    {
        $cached = Cache::get('shalom_do_lb');
        if ($cached) return $cached;

        return Cache::lock('shalom_lb_init', 10)->block(8, function () {
            $existing = Cache::get('shalom_do_lb');
            if ($existing) return $existing;

            try {
                $res        = Http::timeout(10)->withHeaders(self::HEADERS)
                    ->asForm()->post(self::API . '/tarifa/mostrar', ['origin' => 45, 'destiny' => 4]);
                $setCookie  = $res->header('Set-Cookie') ?? '';
                preg_match('/DO-LB=([^;]+)/', $setCookie, $m);
                $lb         = $m[1] ?? '';

                if ($lb) {
                    Cache::put('shalom_do_lb', $lb, 60 * 60 * 24 * 4);
                    Log::info('[Shalom] DO-LB obtenida y cacheada');
                }
                return $lb;
            } catch (\Throwable $e) {
                Log::warning('[Shalom] Error obteniendo DO-LB: ' . $e->getMessage());
                return '';
            }
        });
    }

    private function headers(string $lb = ''): array
    {
        $h = self::HEADERS;
        if ($lb) $h['Cookie'] = "DO-LB={$lb}";
        return $h;
    }
    private function findTerminal(string $dept, string $prov, string $dist): ?array
    {
        $nd  = $this->norm($dept);
        $np  = $this->norm($prov);
        $ndi = $this->norm($dist);

        $row = DB::table('shalom_data')
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(zona,'á','a'),'é','e'),'í','i')) = ?", [$ndi])
            ->whereNotNull('ter_id')->first();

        $row ??= DB::table('shalom_data')
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(nombre,'á','a'),'é','e'),'í','i')) = ?", [$ndi])
            ->whereNotNull('ter_id')->first();

        if (!$row && $nd) {
            $row = DB::table('shalom_data')
                ->where('departamento', $nd)
                ->where(fn($q) => $q
                    ->whereRaw('UPPER(zona) LIKE ?',   ["%{$ndi}%"])
                    ->orWhereRaw('UPPER(nombre) LIKE ?', ["%{$ndi}%"]))
                ->whereNotNull('ter_id')->first();
        }

        if (!$row && $np && $nd) {
            $row = DB::table('shalom_data')
                ->where('departamento', $nd)
                ->where(fn($q) => $q
                    ->whereRaw('UPPER(zona) LIKE ?',   ["%{$np}%"])
                    ->orWhereRaw('UPPER(nombre) LIKE ?', ["%{$np}%"]))
                ->whereNotNull('ter_id')->first();
        }

        $row ??= DB::table('shalom_data')
            ->where('departamento', $nd)
            ->whereNotNull('ter_id')->first();

        $row ??= DB::table('shalom_data')->where('ter_id', 4)->first();

        return $row ? (array) $row : null;
    }
    private function getTariff(int $origTerId, int $destTerId, string $lb): ?array
    {
        try {
            $res  = Http::timeout(15)
                ->withHeaders($this->headers($lb))
                ->asForm()
                ->post(self::API . '/tarifa/mostrar', [
                    'origin'  => $origTerId,
                    'destiny' => $destTerId,
                ]);

            if ($res->status() === 503) {
                Cache::forget('shalom_do_lb');
                $lb  = $this->getLBCookie();
                $res = Http::timeout(15)->withHeaders($this->headers($lb))
                    ->asForm()->post(self::API . '/tarifa/mostrar', [
                        'origin'  => $origTerId,
                        'destiny' => $destTerId,
                    ]);
            }

            $data = $this->decryptIfNeeded($res->json());
            if (!($data['success'] ?? false)) return null;

            return $data['data'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('[Shalom] getTariff error: ' . $e->getMessage());
            return null;
        }
    }
    private function precioAgencia(array $tariff, array $caja): float
    {
        $t    = $tariff['tariff']      ?? [];
        $dt   = $tariff['data_tarifa'] ?? [];
        $peso = (float) ($caja['pesoFacturable'] ?? 1);
        $vol  = ($caja['largo'] * $caja['ancho'] * $caja['alto']) / 1_000_000;

        $minPrecio = (float) ($dt['tar_preminimo'] ?? 0);

        $directo = match (true) {
            $peso <= 0.5  => (float) ($t['sobre']          ?? 0),
            $peso <= 1.0  => (float) ($t['cajapaquetexxs'] ?? 0),
            $peso <= 2.0  => (float) ($t['cajapaquetexs']  ?? 0),
            $peso <= 5.0  => (float) ($t['cajapaquetes']   ?? 0),
            $peso <= 10.0 => (float) ($t['cajapaquetem']   ?? 0),
            default       => (float) ($t['cajapaquetel']   ?? 0),
        };

        if ($directo > 0) {
            return max($directo, $minPrecio);
        }

        $minPeso = (float) ($dt['tar_minimopeso']    ?? $t['peso']    ?? 1);
        $minVol  = (float) ($dt['tar_minimovolumen'] ?? $t['volumen'] ?? 220);

        return max($peso * $minPeso, $vol * $minVol, $minPrecio);
    }

    private function getRepartoTiers(array $terminal, string $lb): ?array
    {
        $depId  = $terminal['departamento_id'] ?? null;
        $provId = $terminal['provincia_id']    ?? null;
        $distId = $terminal['distrito_id']     ?? null;

        if (!$depId || !$provId) return null;
        $cacheKey = "shalom_reparto_{$depId}_{$provId}_" . ($distId ?? 0);

        return Cache::remember($cacheKey, 3600, function () use ($depId, $provId, $distId, $lb) {
            try {
                $body = ['dep_id' => $depId, 'prov_id' => $provId];
                if ($distId) $body['dist_id'] = $distId;

                $res  = Http::timeout(10)
                    ->withHeaders($this->headers($lb))
                    ->asForm()
                    ->post(self::API . '/tarifa/reparto', $body);

                $data = $this->decryptIfNeeded($res->json());
                return ($data['success'] ?? false) ? ($data['data'] ?? null) : null;
            } catch (\Throwable $e) {
                Log::warning('[Shalom] getRepartoTiers: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function parseTierMaxKg(string $des): float
    {
        $des = mb_strtoupper(trim($des));
        $des = preg_replace_callback('/(\d+)\.(\d{3})\b/', fn($m) => $m[1] . $m[2], $des);
        if (
            preg_match('/TALLAS|PAQUETE\s+(XXS|XS|S\b|M\b)|^SOBRE$|UNIDAD HASTA 5/i', $des)
            && !preg_match('/\d{2,}/', $des)
        ) {
            return 5.0;
        }

        if (preg_match_all('/HASTA\s+([\d]+(?:[.,]\d+)?)\s*KG/i', $des, $matches)) {
            $valores = array_map(fn($v) => (float) str_replace(',', '.', $v), $matches[1]);
            return max($valores);
        }
        if (preg_match_all('/([\d]+(?:[.,]\d+)?)\s*KG/i', $des, $matches)) {
            $valores = array_map(fn($v) => (float) str_replace(',', '.', $v), $matches[1]);
            if (count($valores) >= 2) {
                return max($valores);
            }
        }

        return INF;
    }
    private function surchargeDomicilio(array $tiers, array $caja): ?float
    {
        $peso = (float) ($caja['pesoFacturable'] ?? 1);
        Log::info('[Shalom surcharge]', ['peso' => $peso, 'tiers_count' => count($tiers)]);
        foreach ($tiers as $tier) {
            $des   = $tier['des_nombre'] ?? '';
            $costo = isset($tier['cs_costo']) ? (float) $tier['cs_costo'] : null;
            $maxKg = $this->parseTierMaxKg($des);

            if ($peso <= $maxKg) {
                Log::info('[Shalom surcharge match]', [
                    'peso'   => $peso,
                    'tier'   => $des,
                    'max_kg' => $maxKg === INF ? 'INF' : $maxKg,
                    'costo'  => $costo,
                ]);
                return $costo;
            }
        }

        $ultimo = end($tiers);
        $costo  = $ultimo ? (float) ($ultimo['cs_costo'] ?? 0) : null;
        Log::warning('[Shalom surcharge] Peso supera todos los tiers, usando último', [
            'peso'  => $peso,
            'tier'  => $ultimo['des_nombre'] ?? '?',
            'costo' => $costo,
        ]);
        return $costo;
    }

    public function cotizar(array $origen, array $destino, array $cajas): ?array
    {
        if (empty($cajas)) return null;

        try {
            $lb = $this->getLBCookie();
            $termOrig = $this->findTerminal(
                $origen['departamento'],
                $origen['provincia']  ?? '',
                $origen['distrito']   ?? ''
            );
            $termDest = $this->findTerminal(
                $destino['departamento']        ?? '',
                $destino['provincia']           ?? '',
                $destino['distrito']            ?? ''
            );

            if (!$termOrig || !$termDest) {
                Log::warning('[Shalom] Terminal no encontrada para ruta');
                return null;
            }

            Log::info("[Shalom] {$termOrig['nombre']} (ter_id:{$termOrig['ter_id']}) → {$termDest['nombre']} (ter_id:{$termDest['ter_id']})");

            $tariff = $this->getTariff((int) $termOrig['ter_id'], (int) $termDest['ter_id'], $lb);
            if (!$tariff) {
                Log::warning('[Shalom] Sin tarifa agencia');
                return null;
            }

            $totalAgencia = 0.0;
            foreach ($cajas as $caja) {
                $totalAgencia += $this->precioAgencia($tariff, $caja);
            }

            $repartoTiers   = $this->getRepartoTiers($termDest, $lb);
            $totalDomicilio = $totalAgencia;
            $domicilioOk    = false;

            if ($repartoTiers) {
                $extra  = 0.0;
                $allOk  = true;
                foreach ($cajas as $caja) {
                    $sur = $this->surchargeDomicilio($repartoTiers, $caja);
                    if ($sur === null) {
                        $allOk = false;
                        break;
                    }
                    $extra += $sur;
                }
                if ($allOk) {
                    $totalDomicilio = $totalAgencia + $extra;
                    $domicilioOk    = true;
                    Log::info('[Shalom totales]', [
                        'agencia'    => $totalAgencia,
                        'extra'      => $extra,
                        'domicilio'  => $totalDomicilio,
                    ]);
                }
            }

            $leadTime = $tariff['lead_time'] ?? ($tariff['data_tarifa']['lead_time'] ?? '24-48h');

            return [
                'courier'   => 'Shalom',
                'tiempo'    => $leadTime,
                'precio'    => round($totalAgencia, 2),
                'agencia'   => [
                    'disponible' => true,
                    'precio'     => round($totalAgencia, 2),
                    'label'      => 'Retiro en Agencia',
                    'detalle'    => count($cajas) > 1 ? count($cajas) . ' paquetes' : null,
                ],
                'domicilio' => $domicilioOk ? [
                    'disponible' => true,
                    'precio'     => round($totalDomicilio, 2),
                    'label'      => 'Entrega a Domicilio',
                ] : [
                    'disponible' => false,
                    'precio'     => null,
                ],
                'agenciaOrigen'  => [
                    'nombre'    => $termOrig['nombre'],
                    'zona'      => $termOrig['zona']      ?? $termOrig['nombre'],
                    'direccion' => $termOrig['direccion'] ?? null,
                    'display'   => $termOrig['nombre'] . ' — ' . ($termOrig['zona'] ?? $termOrig['nombre']),
                ],
                'agenciaDestino' => [
                    'nombre'    => $termDest['nombre'],
                    'zona'      => $termDest['zona']      ?? $termDest['nombre'],
                    'direccion' => $termDest['direccion'] ?? null,
                    'display'   => $termDest['nombre'] . ' — ' . ($termDest['zona'] ?? $termDest['nombre']),
                ],
                'origen'  => $termOrig['nombre'] . ' (' . ($termOrig['zona'] ?? '') . ')',
                'destino' => $termDest['nombre'] . ' (' . ($termDest['zona'] ?? '') . ')',
            ];
        } catch (\Throwable $e) {
            Log::warning('[Shalom] cotizar error: ' . $e->getMessage());
            return null;
        }
    }
    private function norm(string $s): string
    {
        return strtoupper(trim(
            preg_replace(
                '/[^a-z0-9\s]/u',
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
