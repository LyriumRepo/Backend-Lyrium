<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\OrderServiceItem;
use App\Models\Store;
use App\Models\Subscription;
use App\Notifications\StoreStatusNotification;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * "PLANES PARA MI TIENDA LYRIUM.pdf" (pág. 11 y 13): toda tienda debe alcanzar una
 * venta mínima mensual — S/350 si solo vende productos, S/450 si vende servicios o
 * ambos — salvo los primeros 6 meses (bonificados) desde su primera suscripción.
 * Si al vencer su suscripción no la renovó Y no alcanzó la venta mínima del último
 * periodo, corresponde "resolución de pleno derecho del acuerdo y eliminación de
 * cuenta" — aquí implementado como soft-delete (recuperable) + status 'banned',
 * nunca un borrado físico.
 *
 * IMPORTANTE: este comando NO está registrado en el scheduler (routes/console.php
 * / Kernel) todavía — se debe correr manualmente (con --dry-run primero) hasta
 * confirmar que el resultado es el esperado.
 *
 * Limitación conocida: tiendas sin ninguna fila en `subscriptions` (aprobadas antes
 * de que Emprende tuviera vigencia real de 12 meses) se omiten — no hay una fecha
 * de inicio real desde la cual contar el periodo de gracia ni el vencimiento.
 */
class CheckMinimumSalesCompliance extends Command
{
    protected $signature = 'app:check-minimum-sales-compliance {--dry-run : Solo muestra qué tiendas se resolverían, sin ejecutar nada}';

    protected $description = 'Resuelve (soft-delete) tiendas que vencieron su suscripción sin renovar y no alcanzaron la venta mínima. NO programado en el scheduler — correr manualmente.';

    private const UMBRAL_SOLO_PRODUCTOS = 350.0;

    private const UMBRAL_SERVICIOS_O_MIXTO = 450.0;

    private const MESES_GRACIA = 6;

    public function handle(AuditService $audit): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $vencidasSinRenovar = Subscription::with(['store.owner', 'plan'])
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->get()
            ->filter(fn (Subscription $sub) => $sub->store !== null);

        if ($vencidasSinRenovar->isEmpty()) {
            $this->info('No hay suscripciones vencidas sin renovar.');

            return self::SUCCESS;
        }

        $this->info("Suscripciones vencidas sin renovar: {$vencidasSinRenovar->count()}".($dryRun ? ' (dry-run, no se ejecuta nada)' : ''));

        $resueltas = 0;
        $omitidas = 0;

        foreach ($vencidasSinRenovar as $sub) {
            $store = $sub->store;

            $primeraSuscripcion = Subscription::where('store_id', $store->id)
                ->orderBy('starts_at')
                ->first();

            if (! $primeraSuscripcion || $primeraSuscripcion->starts_at->diffInMonths(now()) < self::MESES_GRACIA) {
                $this->line("  ⏭ Tienda #{$store->id} ({$store->trade_name}): dentro del periodo de gracia de 6 meses, se omite.");
                $omitidas++;

                continue;
            }

            $ventas = $this->calcularVentas($store, $sub->starts_at, $sub->ends_at);
            $umbral = $this->umbralParaTienda($store);

            if ($ventas >= $umbral) {
                $this->line("  ✓ Tienda #{$store->id} ({$store->trade_name}): S/{$ventas} ≥ S/{$umbral} mínimo — cumple, no se toca.");

                continue;
            }

            $this->error("  ✗ Tienda #{$store->id} ({$store->trade_name}): S/{$ventas} < S/{$umbral} mínimo y no renovó.");

            if ($dryRun) {
                $this->line('    [dry-run] se resolvería la cuenta — no se ejecuta nada.');
                $resueltas++;

                continue;
            }

            $this->resolverCuenta($store, $ventas, $umbral, $audit);
            $resueltas++;
        }

        $this->info("Resumen: {$resueltas} resueltas, {$omitidas} omitidas por gracia, ".($vencidasSinRenovar->count() - $resueltas - $omitidas).' cumplieron.');

        return self::SUCCESS;
    }

    private function umbralParaTienda(Store $store): float
    {
        // Nota 3 del PDF: si la tienda vende ambos (productos y servicios), se le
        // considera como si vendiera solo servicios (umbral más alto).
        $vendeServicios = $store->services()->exists();

        return $vendeServicios ? self::UMBRAL_SERVICIOS_O_MIXTO : self::UMBRAL_SOLO_PRODUCTOS;
    }

    private function calcularVentas(Store $store, Carbon $desde, Carbon $hasta): float
    {
        $ventaProductos = (float) OrderItem::where('store_id', $store->id)
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled'])
                ->whereBetween('created_at', [$desde, $hasta]))
            ->sum('line_total');

        $ventaServicios = (float) OrderServiceItem::where('store_id', $store->id)
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled'])
                ->whereBetween('created_at', [$desde, $hasta]))
            ->sum('line_total');

        return round($ventaProductos + $ventaServicios, 2);
    }

    private function resolverCuenta(Store $store, float $ventas, float $umbral, AuditService $audit): void
    {
        $motivo = "No alcanzó la venta mínima (S/{$ventas} de S/{$umbral} requeridos) y no renovó su plan.";

        $store->update(['status' => 'banned']);
        $store->delete(); // soft delete — recuperable con restore()

        $audit->record(
            event: 'store.resolved.minimum_sales',
            module: 'plans',
            description: "Tienda #{$store->id} ({$store->trade_name}) resuelta por no alcanzar venta mínima ni renovar.",
            auditable: $store,
            metadata: ['ventas' => $ventas, 'umbral' => $umbral],
            severity: 'critical',
            source: AuditService::SOURCE_SCHEDULER,
        );

        if ($owner = $store->owner) {
            try {
                $owner->notify(new StoreStatusNotification($store, 'banned', $motivo));
            } catch (\Throwable) {
                // No crítico — la resolución ya quedó registrada
            }
        }
    }
}
