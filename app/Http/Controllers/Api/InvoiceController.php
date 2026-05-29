<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\RapifacException;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Services\RapifacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly RapifacService $rapifac,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Invoice::with('order');

        if (! $user->hasRole('administrator')) {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $user->id));
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        return $this->success([
            'data' => InvoiceResource::collection($invoices),
            'pagination' => [
                'page' => $invoices->currentPage(),
                'perPage' => $invoices->perPage(),
                'total' => $invoices->total(),
                'totalPages' => $invoices->lastPage(),
                'hasMore' => $invoices->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $invoice = Invoice::with('order')->findOrFail($id);

        if (! $user->hasRole('administrator') && $invoice->order->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a esta factura.');
        }

        return $this->success(new InvoiceResource($invoice));
    }

    public function generate(Request $request, string $orderId): JsonResponse
    {
        $data = $request->validate([
            'nit' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $order = Order::with('items.product')->findOrFail($orderId);

        if (! $user->hasRole('administrator') && $order->user_id !== $user->id) {
            return $this->forbidden('No tienes acceso a esta orden.');
        }

        $existingInvoice = Invoice::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            return $this->error('Esta orden ya tiene una factura generada.', 400, [
                'invoice' => new InvoiceResource($existingInvoice),
            ]);
        }

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'nit' => $data['nit'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'provider' => 'rapifac',
            'total' => $order->total,
            'status' => 'pending',
            'sunat_status' => Invoice::SUNAT_STATUS_DRAFT,
            'type' => Invoice::TYPE_FACTURA,
            'customer_name' => $data['business_name'] ?? $order->user?->name,
        ]);

        return $this->created(new InvoiceResource($invoice));
    }

    /**
     * Listar comprobantes del cliente autenticado.
     * Se leen desde la base local (metadatos) y se enriquecen con URLs de Rapifac.
     */
    public function customerInvoices(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Invoice::with('order')
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('sunat_status', [
                Invoice::SUNAT_STATUS_ACCEPTED,
                Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
            ]);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $invoices = $query->orderBy('emission_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => InvoiceResource::collection($invoices),
            'pagination' => [
                'page' => $invoices->currentPage(),
                'perPage' => $invoices->perPage(),
                'total' => $invoices->total(),
                'totalPages' => $invoices->lastPage(),
                'hasMore' => $invoices->hasMorePages(),
            ],
        ]);
    }

    /**
     * Listar facturas/comprobantes del vendedor (store).
     */
    private function getStore($user): Store
    {
        $store = $user->stores()->first()
            ?? $user->ownedStores()->first()
            ?? throw new \RuntimeException(
                'El usuario no tiene una tienda asociada. ' .
                'Debes crear una tienda antes de emitir comprobantes.'
            );
        return $store;
    }

    public function sellerInvoices(Request $request): JsonResponse
    {
        $store = $this->getStore($request->user());

        $query = Invoice::with('order')
            ->where('store_id', $store->id);

        if ($request->filled('status')) {
            $query->where('sunat_status', $request->query('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(
            (int) $request->query('per_page', 15)
        );

        return $this->success([
            'data' => InvoiceResource::collection($invoices),
            'pagination' => [
                'page' => $invoices->currentPage(),
                'perPage' => $invoices->perPage(),
                'total' => $invoices->total(),
                'totalPages' => $invoices->lastPage(),
                'hasMore' => $invoices->hasMorePages(),
            ],
        ]);
    }

    /**
     * Emitir un nuevo comprobante electrónico vía Rapifac.
     */
    public function sellerEmit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', Invoice::TYPES)],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_ruc' => ['required', 'string', 'max:20'],
            'series' => ['required', 'string', 'max:10'],
            'number' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'order_id' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();

        Log::debug('InvoiceController::sellerEmit — Iniciando emisión', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'payload' => $data,
        ]);

        $store = $this->getStore($user);

        $order = Order::where('id', $data['order_id'])
            ->orWhere('order_number', $data['order_id'])
            ->firstOrFail();

        $invoice = Invoice::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'series' => $data['series'],
            'number' => $data['number'],
            'type' => $data['type'],
            'customer_name' => $data['customer_name'],
            'customer_ruc' => $data['customer_ruc'],
            'total' => $data['amount'],
            'provider' => 'rapifac',
            'status' => 'active',
            'sunat_status' => Invoice::SUNAT_STATUS_DRAFT,
            'emission_date' => now(),
            'history' => [
                [
                    'status' => Invoice::SUNAT_STATUS_DRAFT,
                    'note' => 'Documento generado por el vendedor',
                    'timestamp' => now()->format('Y-m-d H:i'),
                    'user' => $user->display_name ?? $user->name,
                ],
            ],
        ]);

        try {
            Log::debug('InvoiceController::sellerEmit — Enviando a Rapifac');

            $rapifacResponse = $this->rapifac->emitInvoice([
                'customer_name' => $data['customer_name'],
                'customer_ruc' => $data['customer_ruc'],
                'type' => $data['type'],
                'series' => $data['series'],
                'number' => $data['number'],
                'amount' => (float) $data['amount'],
                'order_id' => $data['order_id'],
            ]);

            Log::debug('InvoiceController::sellerEmit — Respuesta exitosa de Rapifac', [
                'response' => $rapifacResponse,
            ]);

            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
                'Enviado a Rapifac. Pendiente de CDR de SUNAT',
                'Sistema',
            );

            $invoice->sunat_status = Invoice::SUNAT_STATUS_SENT_WAIT_CDR;
            $invoice->provider_invoice_id = $rapifacResponse['id'] ?? null;
            $invoice->pdf_url = $rapifacResponse['pdf_url'] ?? $this->rapifac->getInvoicePdfUrl($invoice->provider_invoice_id);
            $invoice->authorization_code = $rapifacResponse['authorization_code'] ?? null;
            $invoice->qr_data = $rapifacResponse['qr_data'] ?? null;
            $invoice->save();

            Log::debug('InvoiceController::sellerEmit — Comprobante registrado', [
                'invoice_id' => $invoice->id,
                'provider_invoice_id' => $invoice->provider_invoice_id,
                'sunat_status' => $invoice->sunat_status,
            ]);

            return $this->created(new InvoiceResource($invoice->fresh()));
        } catch (RapifacException $e) {
            $errorMsg = $e->getMessage();

            Log::error('InvoiceController::sellerEmit — Error de Rapifac', [
                'rapifac_code' => $e->getRapifacCode(),
                'message' => $errorMsg,
                'context' => $e->getContext(),
            ]);

            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_REJECTED,
                $errorMsg,
                'Sistema',
            );
            $invoice->sunat_status = Invoice::SUNAT_STATUS_REJECTED;
            $invoice->save();

            return $this->error(
                'Error en la emisión — ' . $errorMsg,
                $e->getCode() ?: 502,
            );
        } catch (\Throwable $e) {
            Log::error('InvoiceController::sellerEmit — Error general', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_REJECTED,
                'Error al enviar a Rapifac: ' . $e->getMessage(),
                'Sistema',
            );
            $invoice->sunat_status = Invoice::SUNAT_STATUS_REJECTED;
            $invoice->save();

            return $this->error(
                'Error en la emisión: ' . $e->getMessage(),
                502,
            );
        }
    }

    /**
     * Reintentar envío de un comprobante rechazado.
     */
    public function sellerRetry(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $store = $this->getStore($user);

        $invoice = Invoice::where('id', $id)
            ->where('store_id', $store->id)
            ->firstOrFail();

        if (! in_array($invoice->sunat_status, [
            Invoice::SUNAT_STATUS_REJECTED,
            Invoice::SUNAT_STATUS_OBSERVED,
        ])) {
            return $this->error('Solo se pueden reintentar comprobantes rechazados u observados.', 400);
        }

        Log::debug('InvoiceController::sellerRetry — Reintentando emisión', [
            'invoice_id' => $invoice->id,
            'type' => $invoice->type,
            'series' => $invoice->series,
            'number' => $invoice->number,
        ]);

        $invoice->sunat_status = Invoice::SUNAT_STATUS_SENT_WAIT_CDR;
        $invoice->addHistoryEntry(
            Invoice::SUNAT_STATUS_SENT_WAIT_CDR,
            'Reintento solicitado por el vendedor. Enviando a Rapifac...',
            $user->display_name ?? $user->name,
        );
        $invoice->save();

        try {
            $rapifacResponse = $this->rapifac->emitInvoice([
                'customer_name' => $invoice->customer_name,
                'customer_ruc' => $invoice->customer_ruc,
                'type' => $invoice->type,
                'series' => $invoice->series,
                'number' => $invoice->number,
                'amount' => (float) $invoice->total,
                'order_id' => (string) $invoice->order_id,
            ]);

            Log::debug('InvoiceController::sellerRetry — Respuesta exitosa de Rapifac', [
                'response' => $rapifacResponse,
            ]);

            $invoice->provider_invoice_id = $rapifacResponse['id'] ?? $invoice->provider_invoice_id;
            $invoice->pdf_url = $rapifacResponse['pdf_url'] ?? $this->rapifac->getInvoicePdfUrl($invoice->provider_invoice_id);
            $invoice->authorization_code = $rapifacResponse['authorization_code'] ?? $invoice->authorization_code;
            $invoice->qr_data = $rapifacResponse['qr_data'] ?? $invoice->qr_data;
            $invoice->sunat_status = Invoice::SUNAT_STATUS_SENT_WAIT_CDR;
            $invoice->save();

            return $this->success(new InvoiceResource($invoice->fresh()));
        } catch (RapifacException $e) {
            $errorMsg = $e->getMessage();

            Log::error('InvoiceController::sellerRetry — Error de Rapifac', [
                'rapifac_code' => $e->getRapifacCode(),
                'message' => $errorMsg,
                'context' => $e->getContext(),
            ]);

            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_REJECTED,
                $errorMsg,
                'Sistema',
            );
            $invoice->sunat_status = Invoice::SUNAT_STATUS_REJECTED;
            $invoice->save();

            return $this->error("Error al reintentar — {$errorMsg}", $e->getCode() ?: 502);
        } catch (\Throwable $e) {
            Log::error('InvoiceController::sellerRetry — Error general', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $invoice->sunat_status = Invoice::SUNAT_STATUS_REJECTED;
            $invoice->addHistoryEntry(
                Invoice::SUNAT_STATUS_REJECTED,
                'Error: ' . $e->getMessage(),
                'Sistema',
            );
            $invoice->save();

            return $this->error(
                'Error al reintentar: ' . $e->getMessage(),
                502,
            );
        }
    }

    public function sellerKpis(Request $request): JsonResponse
    {
        $store = $this->getStore($request->user());

        $invoices = Invoice::where('store_id', $store->id)->get();

        $totalFacturado = $invoices
            ->where('sunat_status', Invoice::SUNAT_STATUS_ACCEPTED)
            ->sum('total');

        $acceptedCount = $invoices
            ->where('sunat_status', Invoice::SUNAT_STATUS_ACCEPTED)
            ->count();

        $totalCount = $invoices->count();
        $successRate = $totalCount > 0 ? ($acceptedCount / $totalCount) * 100 : 0;

        $pendingCount = $invoices
            ->whereIn('sunat_status', [Invoice::SUNAT_STATUS_SENT_WAIT_CDR, Invoice::SUNAT_STATUS_DRAFT])
            ->count();

        $rejectedCount = $invoices
            ->whereIn('sunat_status', [Invoice::SUNAT_STATUS_REJECTED, Invoice::SUNAT_STATUS_OBSERVED])
            ->count();

        return $this->success([
            'totalFacturado' => (float) $totalFacturado,
            'successRate' => round($successRate, 1),
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }
}
