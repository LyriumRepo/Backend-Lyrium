<?php

declare(strict_types=1);

namespace App\Services\Logistics\Couriers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrbanoService
{
    private const API = 'https://api.urbanoenvios.pe/api/v1/order';

    private const HEADERS = [
        'Accept'  => 'application/json',
        'Origin'  => 'https://envia.urbanoenvios.pe',
        'Referer' => 'https://envia.urbanoenvios.pe/',
    ];

    public function cotizar(array $origen, array $destino, array $cajas): ?array
    {
        if (empty($cajas)) {
            return null;
        }

        try {
            [$ciuOrigen, $ciuDestino] = $this->resolveDistritos($origen, $destino);

            if (!$ciuOrigen || !$ciuDestino) {
                Log::warning("[Urbano] Distrito no encontrado: {$origen['distrito']} → {$destino['distrito']}");
                return null;
            }

            $totalAgencia   = 0.0;
            $totalDomicilio = 0.0;

            foreach ($cajas as $caja) {
                $q = $this->cotizarCaja($ciuOrigen['ciu_id'], $ciuDestino['ciu_id'], $caja);
                if (!$q) {
                    return null;
                }
                $totalAgencia   += $q['pickup'];
                $totalDomicilio += $q['total'];
            }

            return [
                'courier'        => 'Urbano',
                'tiempo'         => '24-72h',
                'precio'         => round($totalAgencia, 2),
                'agencia'        => ['disponible' => true, 'precio' => round($totalAgencia,   2), 'label' => 'Retiro en Agencia'],
                'domicilio'      => ['disponible' => true, 'precio' => round($totalDomicilio, 2), 'label' => 'Entrega a Domicilio'],
                'origen'         => $ciuOrigen['ciudad']  ?? $origen['distrito'],
                'destino'        => $ciuDestino['ciudad'] ?? $destino['distrito'],
            ];
        } catch (\Throwable $e) {
            Log::warning("[Urbano] Error: {$e->getMessage()}");
            return null;
        }
    }

    private function resolveDistritos(array $origen, array $destino): array
    {
        [$dO, $dD] = [
            $this->buscarDistrito($origen['distrito'],  $origen['departamento'],   $origen['provincia']),
            $this->buscarDistrito($destino['distrito'], $destino['departamento'] ?? '', $destino['provincia'] ?? ''),
        ];
        return [$dO, $dD];
    }

    private function buscarDistrito(string $distrito, string $dept = '', string $prov = ''): ?array
    {
        $res  = Http::timeout(10)->withHeaders(self::HEADERS)
            ->post(self::API . '/search_distric', [
                'distrito' => strtoupper(substr($distrito, 0, 3)),
            ]);

        if (!$res->successful()) {
            return null;
        }

        $data       = $res->json();
        $candidates = $data['data'] ?? [];
        if (empty($candidates)) {
            return null;
        }

        $nDept = $this->norm($dept);
        $nProv = $this->norm($prov);
        $nDist = $this->norm($distrito);

        if ($nDept) {
            $f = array_filter($candidates, fn($d) =>
            str_contains($this->norm(explode(' - ', $d['ciudad'])[0] ?? ''), $nDept));
            if ($f) {
                $candidates = array_values($f);
            }
        }

        if ($nProv) {
            $f = array_filter($candidates, fn($d) =>
            str_contains($this->norm(explode(' - ', $d['ciudad'])[1] ?? ''), $nProv));
            if ($f) {
                $candidates = array_values($f);
            }
        }

        foreach ($candidates as $c) {
            $parts = explode(' - ', $c['ciudad']);
            if ($this->norm(end($parts)) === $nDist) {
                return $c;
            }
        }

        return $candidates[0];
    }

    private function clasificarCaja(array $caja): array
    {
        $pesoR   = (float) ($caja['pesoReal']        ?? 0);
        $pesoF   = (float) ($caja['pesoFacturable']  ?? 0);
        $largo   = (float) ($caja['largo'] ?? 30);
        $ancho   = (float) ($caja['ancho'] ?? 20);
        $alto    = (float) ($caja['alto']  ?? 15);
        $suma    = $largo + $ancho + $alto;
        $pesoVol = ($largo * $ancho * $alto) / 5000;
        $pFinal  = max($pesoR, $pesoF, $pesoVol);

        return match (true) {
            $pFinal <= 1  && $suma <= 55  => ['id' => 11],
            $pFinal <= 2  && $suma <= 64  => ['id' => 5],
            $pFinal <= 5  && $suma <= 87  => ['id' => 2],
            $pFinal <= 10 && $suma <= 110 => ['id' => 3],
            $pFinal <= 20 && $suma <= 139 => ['id' => 4],
            $pFinal <= 30 && $suma <= 159 => ['id' => 6],
            $pFinal <= 40 && $suma <= 174 => ['id' => 7],
            $pFinal <= 50 && $suma <= 188 => ['id' => 8],
            $pFinal <= 60 && $suma <= 200 => ['id' => 9],
            default                       => ['id' => 10],
        };
    }

    private function cotizarCaja(int|string $ciuOrig, int|string $ciuDest, array $caja): ?array
    {
        $clasif     = $this->clasificarCaja($caja);
        $servIdDis  = "2, 1, 0, {$clasif['id']}, {$caja['largo']}, {$caja['ancho']}, {$caja['alto']}";

        $res  = Http::timeout(15)->withHeaders(self::HEADERS)->post(self::API . '/shippingQuote', [
            'vp_serv_id_dis'  => $servIdDis,
            'vp_custom_sizes' => null,
            'vp_serv_id_seg'  => '',
            'vp_tot_seg'      => '',
            'vp_ciu_id_org'   => (string) $ciuOrig,
            'vp_ciu_id_des'   => (string) $ciuDest,
            'vp_cobro'        => 'R',
            'vp_usu_id'       => 0,
            'vp_tipo_entrega' => 1,
        ]);

        $data = $res->json();
        $r    = $data['data'][0] ?? null;
        if (!$r) {
            return null;
        }

        return [
            'total'  => (float) ($r['total']        ?? 0),
            'pickup' => (float) ($r['total_pickup']  ?? 0),
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
