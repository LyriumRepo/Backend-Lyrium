<?php

declare(strict_types=1);

namespace App\Services\Logistics\Couriers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SharfService
{
    private const API     = 'https://saio.holasharf.com';
    private const SIZE_ID = 7;

    public function cotizar(array $origen, array $destino, array $cajas): ?array
    {
        if (empty($cajas)) {
            return null;
        }

        try {
            [$distOrigen, $distDestino] = $this->resolveDistritos(
                $origen['distrito'],
                $destino['distrito']
            );

            if (!$distOrigen || !$distDestino) {
                Log::warning("[Sharf] Distrito no encontrado: {$origen['distrito']} → {$destino['distrito']}");
                return null;
            }

            $total = 0.0;

            foreach ($cajas as $caja) {
                $precio = $this->cotizarCaja((string) $distOrigen['id'], (string) $distDestino['id'], $caja);
                if ($precio === null) {
                    Log::warning("[Sharf] Sin precio para caja {$caja['tipo']}");
                    return null;
                }
                $total += $precio;
            }

            $total = round($total, 2);

            return [
                'courier'   => 'Sharf',
                'tiempo'    => 'Mismo día / 24h',
                'precio'    => $total,
                'agencia'   => ['disponible' => true, 'precio' => $total, 'label' => 'Puerta a Puerta'],
                'domicilio' => ['disponible' => true, 'precio' => $total, 'label' => 'Puerta a Puerta'],
                'origen'    => $distOrigen['text']  ?? $origen['distrito'],
                'destino'   => $distDestino['text'] ?? $destino['distrito'],
            ];
        } catch (\Throwable $e) {
            Log::warning("[Sharf] Error: {$e->getMessage()}");
            return null;
        }
    }

    private function resolveDistritos(string $orig, string $dest): array
    {
        $responses = Http::pool(fn($pool) => [
            $pool->as('orig')->timeout(8)->get(
                self::API . '/api/v1/feed/getDistrictsbyName/' . rawurlencode($this->norm($orig)),
            ),
            $pool->as('dest')->timeout(8)->get(
                self::API . '/api/v1/feed/getDistrictsbyName/' . rawurlencode($this->norm($dest)),
            ),
        ]);

        $oResp = $responses['orig'];
        $dResp = $responses['dest'];

        if ($oResp instanceof \Throwable || $dResp instanceof \Throwable) {
            Log::warning('[Sharf] resolveDistritos: error de red en pool');
            return [null, null];
        }

        $distOrigen  = $this->pickDistrito($oResp->json(), $orig);
        $distDestino = $this->pickDistrito($dResp->json(), $dest);

        if (!$distOrigen) {
            Log::debug("[Sharf] Sin match para origen '{$orig}' (status {$oResp->status()})");
        }
        if (!$distDestino) {
            Log::debug("[Sharf] Sin match para destino '{$dest}' (status {$dResp->status()})");
        }

        return [$distOrigen, $distDestino];
    }

    private function pickDistrito(?array $data, string $nombre): ?array
    {
        if (!$data || ($data['statusCode'] ?? 0) !== 200 || empty($data['result'])) {
            return null;
        }

        $results = $data['result'];
        $q       = $this->norm($nombre);

        foreach ($results as $r) {
            if ($this->norm($r['text'] ?? '') === $q) {
                return $r;
            }
        }

        foreach ($results as $r) {
            if (str_contains($this->norm($r['text'] ?? ''), $q)) {
                return $r;
            }
        }

        return $results[0];
    }

    private function cotizarCaja(string $origId, string $destId, array $caja): ?float
    {
        $payload = [
            'fareItems' => [[
                'originDistrictId'      => $origId,
                'destinationDistrictId' => $destId,
                'packageWidth'          => (float) ($caja['ancho']          ?? 20),
                'packageHeight'         => (float) ($caja['alto']           ?? 15),
                'packageWeight'         => (float) ($caja['pesoFacturable'] ?? 1),
                'packageDeep'           => (float) ($caja['largo']          ?? 30),
                'serviceType'           => 2,
                'sizeId'                => self::SIZE_ID,
                'shippingTypeId'        => 4,
            ]],
            'userId' => null,
        ];

        $res  = Http::timeout(10)->post(self::API . '/api2/fare', $payload);
        $data = $res->json();
        $item = $data['data']['itemsFareResponse'][0] ?? null;

        if (!$item || ($item['error'] ?? false)) {
            return null;
        }

        return (float) ($item['total'] ?? 0) ?: null;
    }

    private function norm(string $s): string
    {
        return strtolower(trim(
            preg_replace(
                '/[^a-z0-9 ]/i',
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
