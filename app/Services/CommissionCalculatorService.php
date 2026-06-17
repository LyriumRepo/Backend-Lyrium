<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemConfig;

final class CommissionCalculatorService
{
    private const IGV_RATE = 0.18;

    private const DEFAULT_TIERS = [
        ['min' => 0,     'max' => 400,   'rate' => 0.15],
        ['min' => 401,   'max' => 800,   'rate' => 0.14],
        ['min' => 801,   'max' => 1200,  'rate' => 0.13],
        ['min' => 1201,  'max' => null,  'rate' => 0.12],
    ];

    public function getTiers(): array
    {
        $stored = SystemConfig::getByKey('commission_tiers');

        if (is_array($stored) && !empty($stored)) {
            return $stored;
        }

        return self::DEFAULT_TIERS;
    }

    public function findTier(float $montoVenta): array
    {
        $tiers = $this->getTiers();

        foreach ($tiers as $tier) {
            if ($montoVenta >= $tier['min']) {
                if ($tier['max'] === null || $montoVenta <= $tier['max']) {
                    return $tier;
                }
            }
        }

        return end($tiers);
    }

    /**
     * Fórmula aprobada por administrador/contador:
     *   comision_total  = (montoVenta / 1.18) × tasa   → total con IGV contenido
     *   comision_base   = comision_total / 1.18          → base imponible (sin IGV)
     *   igv_comision    = comision_base × 18%            → IGV contenido dentro del total
     *
     * Verificación: comision_base + igv_comision = comision_total ✓
     */
    public function calculate(float $montoVenta): array
    {
        $tier = $this->findTier($montoVenta);
        $tasa = (float) $tier['rate'];

        $comisionTotal = ($montoVenta / (1 + self::IGV_RATE)) * $tasa;
        $comisionBase  = $comisionTotal / (1 + self::IGV_RATE);
        $igvComision   = $comisionBase * self::IGV_RATE;

        return [
            'monto_venta'    => $montoVenta,
            'tasa'           => $tasa,
            'tasa_porcentaje'=> $tasa * 100,
            'tasa_texto'     => round($tasa * 100) . '%',
            'categoria'      => $this->getCategoryLabel($tier),
            'comision_base'  => round($comisionBase, 2),
            'igv_comision'   => round($igvComision, 2),
            'comision_total' => round($comisionTotal, 2),
        ];
    }

    private function getCategoryLabel(array $tier): string
    {
        $min = $tier['min'];
        $max = $tier['max'];

        if ($max === null) {
            return "S/ {$min} a más";
        }

        return "S/ {$min} – S/ {$max}";
    }
}
