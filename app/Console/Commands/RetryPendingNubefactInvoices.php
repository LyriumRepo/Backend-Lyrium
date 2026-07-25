<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\OrderPaymentService;
use Illuminate\Console\Command;

final class RetryPendingNubefactInvoices extends Command
{
    protected $signature = 'nubefact:retry-pending
        {--id=* : Retry only these specific invoice IDs (repeatable)}
        {--dry-run : List what would be retried without sending anything to NubeFact}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Retry order invoices stuck in DRAFT/REJECTED (e.g. after the NubeFact DEMO account limit was cleared)';

    public function handle(OrderPaymentService $orderPaymentService): int
    {
        $ids = array_map('intval', $this->option('id'));

        $query = Invoice::query()
            ->where('provider', 'nubefact')
            ->where('source', Invoice::SOURCE_ORDER)
            ->whereIn('sunat_status', [Invoice::SUNAT_STATUS_DRAFT, Invoice::SUNAT_STATUS_REJECTED])
            ->whereNotNull('order_id')
            ->whereNotNull('store_id');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $invoices = $query->orderBy('id')->get();

        if ($invoices->isEmpty()) {
            $this->info('No hay facturas en DRAFT/REJECTED elegibles para reintentar.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Serie-Número', 'Order ID', 'Store ID', 'Estado actual', 'Total'],
            $invoices->map(fn (Invoice $inv) => [
                $inv->id,
                "{$inv->series}-{$inv->number}",
                $inv->order_id,
                $inv->store_id,
                $inv->sunat_status,
                $inv->total,
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->info(count($invoices) . ' factura(s) serían reintentadas. Corre sin --dry-run para ejecutar.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('¿Reintentar el envío de estas ' . count($invoices) . ' factura(s) a NubeFact ahora?', false)) {
            $this->warn('Cancelado. No se envió nada.');

            return self::SUCCESS;
        }

        $succeeded = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            $this->line("Reintentando factura #{$invoice->id} ({$invoice->series}-{$invoice->number})...");

            try {
                $ok = $orderPaymentService->retryFailedInvoice($invoice);
            } catch (\Throwable $e) {
                $this->error("  Error inesperado: {$e->getMessage()}");
                $failed++;
                continue;
            }

            if ($ok) {
                $this->info("  OK — nuevo estado: {$invoice->fresh()->sunat_status}");
                $succeeded++;
            } else {
                $this->error("  Falló — nuevo estado: {$invoice->fresh()->sunat_status} (ver storage/logs/laravel.log)");
                $failed++;
            }

            // Pequeña pausa entre llamadas para no golpear la API de NubeFact de golpe
            // si hay varias facturas pendientes.
            if ($invoices->count() > 1) {
                usleep(500_000);
            }
        }

        $this->newLine();
        $this->info("Listo: {$succeeded} exitosa(s), {$failed} fallida(s) de " . count($invoices) . ' total.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
