<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Expense;
use App\Models\Supplier;

/**
 * Mantiene sincronizados suppliers.total_gastado y suppliers.total_recibos
 * cada vez que un Expense es creado, actualizado o eliminado.
 *
 * Registro en AppServiceProvider:
 *   Expense::observe(ExpenseObserver::class);
 */
final class ExpenseObserver
{
    /**
     * Recalcula los totales del proveedor después de crear un recibo.
     */
    public function created(Expense $expense): void
    {
        $this->recalculate($expense->supplier_id);
    }

    /**
     * Recalcula si cambió el monto, el estado o el proveedor asignado.
     */
    public function updated(Expense $expense): void
    {
        $dirty = $expense->getDirty();

        $needsRecalc = isset($dirty['amount'])
            || isset($dirty['status'])
            || isset($dirty['supplier_id']);

        if (! $needsRecalc) {
            return;
        }

        // Si cambió de proveedor, recalcular los dos
        if (isset($dirty['supplier_id'])) {
            $this->recalculate((int) $expense->getOriginal('supplier_id'));
        }

        $this->recalculate($expense->supplier_id);
    }

    /**
     * Recalcula al hacer soft delete (destroy en el controller anula primero,
     * luego llama delete() — el observer dispara en el softDelete).
     */
    public function deleted(Expense $expense): void
    {
        $this->recalculate($expense->supplier_id);
    }

    /**
     * Recalcula si un recibo eliminado es restaurado.
     */
    public function restored(Expense $expense): void
    {
        $this->recalculate($expense->supplier_id);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    /**
     * Recalcula total_gastado y total_recibos para el proveedor dado.
     * Solo cuenta recibos NO anulados y NO eliminados (sin trashed).
     */
    private function recalculate(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);

        if (! $supplier) {
            return;
        }

        $totals = Expense::query()
            ->where('supplier_id', $supplierId)
            ->whereNot('status', 'Anulado')
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->first();

        // updateQuietly evita disparar eventos innecesarios en Supplier
        $supplier->updateQuietly([
            'total_gastado' => $totals->total,
            'total_recibos' => $totals->count,
        ]);
    }
}
