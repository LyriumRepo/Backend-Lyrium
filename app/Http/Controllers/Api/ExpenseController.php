<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanBatchStoreRequest;
use App\Http\Requests\ScanDocumentRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ScannedDocumentResource;
use App\Models\Expense;
use App\Models\Supplier;
use App\Services\DocumentScanner\DocumentScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        if ($voucherType = $request->query('voucher_type')) {
            $query->where('voucher_type', $voucherType);
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
     * POST /api/expenses/upload
     * Sube un PDF temporalmente y devuelve la ruta para asociarlo al gasto.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('file');
        $hash = md5_file($file->getRealPath());
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = "{$originalName}_{$hash}.pdf";

        $path = $file->storeAs('uploads/expenses', $fileName, 'public');

        if (! $path) {
            return $this->error('Error al guardar el archivo.', 500);
        }

        return response()->json([
            'success' => true,
            'file_url' => Storage::disk('public')->url($path),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * POST /api/expenses/scan
     * Escanea un PDF (archivo o ruta), extrae los datos y guarda el gasto en BD.
     * Para estados de cuenta BCP, retorna las líneas sin crear gastos.
     */
    public function scan(ScanDocumentRequest $request, DocumentScannerService $scanner): JsonResponse
    {
        $filePath = $request->input('file_path');

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $hash = md5_file($file->getRealPath());
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileName = "{$originalName}_{$hash}.pdf";
            $filePath = $file->storeAs('uploads/expenses', $fileName, 'public');
        }

        if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return $this->error('El archivo no existe.', 404);
        }

        $absolutePath = Storage::disk('public')->path($filePath);

        $password = $request->input('password');

        \Illuminate\Support\Facades\Log::debug('Scanning document', [
            'path' => $absolutePath,
            'size' => filesize($absolutePath),
            'has_password' => $password !== null && $password !== '',
        ]);

        try {
            $result = $scanner->scan($absolutePath, $password);
        } catch (\Throwable $e) {
            return $this->error('Error al escanear el documento: '.$e->getMessage(), 500);
        }

        if ($result->documentType === null && $result->rawText === '') {
            return $this->error(
                'No se pudo extraer el texto del PDF. Verifica que la contraseña sea correcta o que el archivo no esté dañado.',
                400,
            );
        }

        if ($result->documentType === null) {
            \Illuminate\Support\Facades\Log::warning('No parser matched for document', [
                'text_preview' => mb_substr($result->rawText, 0, 500),
            ]);

            return $this->error(
                'El formato del documento no es reconocido. Solo se aceptan facturas, boletas, recibos por honorarios o estados de cuenta BCP.',
                400,
            );
        }

        // ─── Estado de Cuenta BCP: retornar líneas sin crear gastos ────
        if ($result->documentType === 'ESTADO_CUENTA_BCP') {
            return response()->json([
                'file_url' => Storage::disk('public')->url($filePath),
                'file_path' => $filePath,
                'is_bank_statement' => true,
                'scan' => new ScannedDocumentResource($result),
            ]);
        }

        // ─── Mapear tipo de documento ─────────────────────────────────
        $voucherType = match ($result->documentType) {
            'RECIBO_POR_HONORARIOS' => 'Honorarios',
            'FACTURA' => 'Factura',
            'BOLETA' => 'Boleta',
            default => $result->documentType,
        };

        $amount = match ($result->documentType) {
            'RECIBO_POR_HONORARIOS' => $result->payment?->grossAmount ?? $result->payment?->netAmount,
            'FACTURA', 'BOLETA' => $result->totals?->grandTotal,
            default => 0,
        };

        $concept = match ($result->documentType) {
            'RECIBO_POR_HONORARIOS' => $result->serviceDescription ?? 'Recibo por honorarios',
            'FACTURA' => 'Factura '.($result->documentNumber ?? ''),
            'BOLETA' => 'Boleta '.($result->documentNumber ?? ''),
            default => 'Documento escaneado',
        };

        // ─── Encontrar o crear proveedor desde el emisor ──────────────
        $supplier = null;
        if ($result->issuer !== null && $result->issuer->ruc) {
            $supplier = Supplier::where('ruc', $result->issuer->ruc)->first();
        }

        if (! $supplier && $result->issuer !== null && $result->issuer->name) {
            $supplier = Supplier::create([
                'name' => $result->issuer->name,
                'slug' => Str::slug($result->issuer->name.'-'.($result->issuer->ruc ?? uniqid())),
                'ruc' => $result->issuer->ruc,
                'type' => $voucherType === 'Honorarios' ? 'Persona Natural' : 'Proveedor',
                'status' => 'Activo',
            ]);
        }

        if (! $supplier) {
            return $this->error('No se pudo identificar el emisor del documento.', 400);
        }

        // ─── 1. Filtro Anti-Duplicados (Idempotency) ───────────────────
        if ($result->documentNumber) {
            $existingExpense = Expense::where('supplier_id', $supplier->id)
                ->where('voucher_type', $voucherType)
                ->where('voucher_number', $result->documentNumber)
                ->first();

            if ($existingExpense) {
                $existingExpense->load(['supplier', 'registeredBy']);

                return response()->json([
                    'file_url' => Storage::disk('public')->url($filePath),
                    'file_path' => $filePath,
                    'scan' => new ScannedDocumentResource($result),
                    'expense' => new ExpenseResource($existingExpense),
                    'message' => 'Este documento ya se encontraba registrado en el sistema.',
                ]);
            }
        }

        // ─── 2. Guardado Seguro con Transacción ────────────────────────
        try {
            $expense = DB::transaction(function () use ($request, $supplier, $concept, $amount, $voucherType, $result, $filePath) {
                $receiptNumber = Expense::nextReceiptNumber($voucherType);

                return Expense::create([
                    'receipt_number' => $receiptNumber,
                    'supplier_id' => $supplier->id,
                    'concept' => $concept,
                    'amount' => $amount,
                    'status' => 'Pendiente',
                    'issued_at' => $result->issueDate ?? now()->toDateString(),
                    'voucher_type' => $voucherType,
                    'voucher_number' => $result->documentNumber,
                    'file_url' => Storage::disk('public')->url($filePath),
                    'registered_by' => $request->user()->id,
                    'scan_data' => [
                        'document_type' => $result->documentType,
                        'document_number' => $result->documentNumber,
                        'issue_date' => $result->issueDate,
                        'due_date' => $result->dueDate,
                        'currency' => $result->currency,
                        'issuer' => $result->issuer !== null ? [
                            'name' => $result->issuer->name,
                            'ruc' => $result->issuer->ruc,
                            'address' => $result->issuer->address,
                        ] : null,
                        'customer' => $result->customer !== null ? [
                            'name' => $result->customer->name,
                            'ruc' => $result->customer->ruc,
                            'address' => $result->customer->address,
                        ] : null,
                        'payment' => $result->payment !== null ? [
                            'payment_method' => $result->payment->paymentMethod,
                            'amount_words' => $result->payment->amountWords,
                            'gross_amount' => $result->payment->grossAmount,
                            'retention_ir' => $result->payment->retentionIr,
                            'net_amount' => $result->payment->netAmount,
                            'currency' => $result->payment->currency,
                        ] : null,
                        'items' => array_map(fn ($item) => [
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unitPrice,
                            'total' => $item->total,
                        ], $result->items),
                        'totals' => $result->totals !== null ? [
                            'taxable_amount' => $result->totals->taxableAmount,
                            'inafect_amount' => $result->totals->inafectAmount,
                            'exempt_amount' => $result->totals->exemptAmount,
                            'free_amount' => $result->totals->freeAmount,
                            'igv' => $result->totals->igv,
                            'isc' => $result->totals->isc,
                            'icbper' => $result->totals->icbper,
                            'other_taxes' => $result->totals->otherTaxes,
                            'other_charges' => $result->totals->otherCharges,
                            'discounts' => $result->totals->discounts,
                            'grand_total' => $result->totals->grandTotal,
                        ] : null,
                        'service_description' => $result->serviceDescription,
                        'amount_in_words' => $result->amountInWords,
                        'authorization_date' => $result->authorizationDate,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            return $this->error('Error al registrar el gasto de forma segura: '.$e->getMessage(), 500);
        }

        $expense->load(['supplier', 'registeredBy']);

        return response()->json([
            'file_url' => Storage::disk('public')->url($filePath),
            'file_path' => $filePath,
            'scan' => new ScannedDocumentResource($result),
            'expense' => new ExpenseResource($expense),
        ]);
    }

    /**
     * POST /api/expenses/scan/batch-store
     * Crea múltiples gastos desde las líneas escaneadas de un estado de cuenta BCP.
     */
    public function scanBatchStore(ScanBatchStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $supplier = Supplier::findOrFail($data['supplier_id']);

        $fileUrl = Storage::disk('public')->url($data['file_path']);

        $totalAmount = collect($data['lines'])->sum('amount');
        $firstLine = $data['lines'][0];
        $parsedDate = $this->parseBankDate($firstLine['date']);

        $periodFull = $data['period_full'] ?? $data['period'] ?? '';
        $monthYear = $periodFull !== '' ? $periodFull : now()->format('m/Y');
        $concept = 'Estado de Cuenta '.$monthYear;

        try {
            $expense = DB::transaction(function () use ($data, $supplier, $fileUrl, $request, $totalAmount, $parsedDate, $concept) {
                $linesForScanData = array_map(fn ($line) => array_filter([
                    'date' => $line['date'],
                    'description' => $line['description'],
                    'amount' => $line['amount'],
                    'reference' => $line['reference'] ?? null,
                    'glossary_key' => $line['glossary_key'] ?? null,
                    'glossary_description' => $line['glossary_description'] ?? null,
                    'hour' => $line['hour'] ?? null,
                    'med' => $line['med'] ?? null,
                    'tipo' => $line['tipo'] ?? null,
                    'place' => $line['place'] ?? null,
                    'balance' => $line['balance'] ?? null,
                ], fn ($v) => $v !== null), $data['lines']);

                return Expense::create([
                    'receipt_number' => Expense::nextReceiptNumber('Servicio'),
                    'supplier_id' => $supplier->id,
                    'concept' => $concept,
                    'amount' => $totalAmount,
                    'status' => 'Pendiente',
                    'issued_at' => $parsedDate,
                    'voucher_type' => 'Servicio',
                    'file_url' => $fileUrl,
                    'registered_by' => $request->user()->id,
                    'scan_data' => [
                        'document_type' => 'ESTADO_CUENTA_BCP',
                        'period' => $data['period'] ?? null,
                        'period_full' => $data['period_full'] ?? null,
                        'opening_balance' => $data['opening_balance'] ?? null,
                        'closing_balance' => $data['closing_balance'] ?? null,
                        'lines' => $linesForScanData,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            return $this->error('Error al registrar el gasto resumen: '.$e->getMessage(), 500);
        }

        $expense->load(['supplier', 'registeredBy']);

        return response()->json([
            'success' => true,
            'expense' => new ExpenseResource($expense),
            'count' => count($data['lines']),
        ], 201);
    }

    private function parseBankDate(string $date): string
    {
        if (preg_match('/^(\d{2})\/(\d{2})$/', $date, $m)) {
            $year = date('Y');
            $month = $m[2];
            $day = $m[1];

            if ($month > date('m')) {
                $year = (int) date('Y') - 1;
            }

            return "{$year}-{$month}-{$day}";
        }

        return now()->toDateString();
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

        return response()->json(['success' => true]);
    }
}
