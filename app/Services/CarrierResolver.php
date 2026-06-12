<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;

/**
 * Capa de resolución de operador logístico.
 *
 * Modo AUTOMÁTICO (futuro):
 *   Cuando la rama de checkout integre selección de operador,
 *   este resolver detectará el carrier desde el shipment/order
 *   sin necesidad de modificar el modal ni el frontend.
 *
 * Modo MANUAL (fallback temporal):
 *   Si no hay operador asignado, retorna null y el frontend
 *   muestra el selector manual en LogisticsModal.
 *
 * Para integrar el modo automático en el futuro:
 *   1. Ampliar resolve() para leer desde la estructura definitiva
 *   2. No es necesario reescribir el modal ni el frontend
 */
final class CarrierResolver
{
    /**
     * Resuelve el código del operador logístico para una orden.
     *
     * @return string|null Código del carrier (shalom, olva, urbano, sharf) o null si no detectado
     */
    public static function resolve(?Order $order): ?string
    {
        if ($order === null) {
            return null;
        }

        $shipments = $order->relationLoaded('shipments')
            ? $order->shipments
            : $order->shipments()->get();

        return self::resolveFromShipments($shipments);
    }

    /**
     * Resuelve el operador desde los shipments existentes.
     */
    public static function resolveFromShipments(iterable $shipments): ?string
    {
        foreach ($shipments as $shipment) {
            $code = self::resolveFromShipment($shipment);
            if ($code !== null) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Resuelve el operador desde un shipment individual.
     *
     * Orden de detección:
     *   1. carrier_data tiene carrier_code explícito (futuro)
     *   2. carrier tiene un código reconocido
     *   3. carrier_data.tracking_code existe (infiere operador desde datos)
     *   4. No detectado → null (modo manual)
     */
    public static function resolveFromShipment(?Shipment $shipment): ?string
    {
        if ($shipment === null) {
            return null;
        }

        $carrierData = $shipment->carrier_data;

        // 1. carrier_code explícito en carrier_data (futura integración)
        if (is_array($carrierData) && isset($carrierData['carrier_code'])) {
            $code = strtolower($carrierData['carrier_code']);
            if (in_array($code, config('logistics.carrier_codes', []), true)) {
                return $code;
            }
        }

        // 2. carrier con código reconocido
        $carrier = $shipment->carrier;
        if ($carrier !== null) {
            $normalized = strtolower($carrier);
            if (in_array($normalized, config('logistics.carrier_codes', []), true)) {
                return $normalized;
            }
        }

        // 3. carrier_data tiene tracking_code → no podemos inferir operador
        //    (requiere modo manual)

        return null;
    }
}
