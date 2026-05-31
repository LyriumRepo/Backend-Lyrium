<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\AuditLog;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExpenseController extends Controller
{
    /**
     * GET /api/expenses
     * Lista paginada de recibos con filtros de proveedor, estado y fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query()
            ->with(['supplier', 'registeredBy'])
            ->orderByDesc('issued_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('concept', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->query('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('issued_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('issued_at', '<=', $to);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $expenses = $query->paginate($perPage);

        return response()->json([
            'data' => ExpenseResource::collection($expenses),
            'pagination' => [
                'page' => $expenses->currentPage(),
                'perPage' => $expenses->perPage(),
                'total' => $expenses->total(),
                'totalPages' => $expenses->lastPage(),
                'hasMore' => $expenses->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/expenses/stats
     * Totales para el widget "Gestión de Gastos" (cálculo RF-13).
     */
    public function stats(): JsonResponse
    {
        $total = Expense::whereNot('status', 'Anulado')->sum('amount');
        $paid = Expense::paid()->sum('amount');
        $pending = Expense::pending()->sum('amount');
        $count = Expense::whereNot('status', 'Anulado')->count();

        return response()->json([
            'total_invertido' => (float) $total,
            'total_pagado' => (float) $paid,
            'total_pendiente' => (float) $pending,
            'total_recibos' => $count,
            'recibos_pendientes' => Expense::pending()->count(),
        ]);
    }

    /**
     * GET /api/expenses/{id}
     */
    public function show(int $id): JsonResponse
    {
        $expense = Expense::with(['supplier', 'registeredBy'])->findOrFail($id);

        return response()->json(new ExpenseResource($expense));
    }

    /**
     * POST /api/expenses
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $expense = Expense::create([
            ...$data,
            'receipt_number' => Expense::nextReceiptNumber(),
            'registered_by' => $request->user()->id,
            'status' => $data['status'] ?? 'Pendiente',
        ]);

        $expense->load(['supplier', 'registeredBy']);

        AuditLog::record(
            event: 'created',
            module: 'expenses',
            description: "Registró recibo {$expense->receipt_number} — {$expense->concept} (S/ {$expense->amount})",
            auditable: $expense,
            newValues: $expense->toArray(),
        );

        return response()->json(new ExpenseResource($expense), 201);
    }

    /**
     * PUT /api/expenses/{id}
     */
    public function update(UpdateExpenseRequest $request, int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $oldData = $expense->toArray();
        $data = $request->validated();

        // Si se marca como Pagado y no hay fecha de pago, asignar hoy
        if (isset($data['status']) && $data['status'] === 'Pagado' && ! isset($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        }

        $expense->update($data);

        AuditLog::record(
            event: 'updated',
            module: 'expenses',
            description: "Actualizó recibo {$expense->receipt_number}",
            auditable: $expense,
            oldValues: $oldData,
            newValues: $expense->fresh()->toArray(),
        );

        return response()->json(new ExpenseResource($expense->fresh()->load(['supplier', 'registeredBy'])));
    }

    /**
     * DELETE /api/expenses/{id}
     * Soft delete (anula el recibo sin eliminarlo del historial).
     */
    public function destroy(int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        $expense->update(['status' => 'Anulado']);
        $expense->delete();

        AuditLog::record(
            event: 'deleted',
            module: 'expenses',
            description: "Anuló recibo {$expense->receipt_number} — {$expense->concept}",
            auditable: $expense,
        );

        return response()->json(['success' => true]);
    }
}
