<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\InvoiceProviderInterface;
use App\Exceptions\NubefactException;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Services\NubefactService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly NubefactService $nubefact,
        private readonly InvoiceProviderInterface $provider,
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

    public function downloadPdf(Request $request, string $id)
    {
        $user = $request->user();
        $invoice = Invoice::with('order.items', 'store')->findOrFail($id);
        $order = $invoice->order;

        if (! $order) {
            return $this->error('La factura no tiene una orden asociada.', 404);
        }

        $isStoreOwner = $invoice->store_id && (
            $user->stores()->where('stores.id', $invoice->store_id)->exists() ||
            $user->ownedStores()->where('id', $invoice->store_id)->exists()
        );

        if (! $user->hasRole('administrator') && $order->user_id !== $user->id && ! $isStoreOwner) {
            return $this->forbidden('No tienes acceso a esta factura.');
        }

        $pdf = Pdf::loadView('pdf.receipt', [
            'order' => $order,
            'invoice' => $invoice,
        ]);

        $filename = $invoice->series . '-' . $invoice->number . '.pdf';

        return $pdf->download($filename);
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
            'provider' => 'nubefact',
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

        $query = Invoice::with(['order.user'])
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

    public function sellerSeries(Request $request): JsonResponse
    {
        return $this->success($this->provider->getAvailableSeries());
    }

    public function sellerOrderData(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();
        $store = $this->getStore($user);

        $order = Order::where('id', $orderId)
            ->orWhere('order_number', $orderId)
            ->with('items')
            ->firstOrFail();

        $hasAccess = $order->items->every(fn ($item) => (int) $item->store_id === (int) $store->id);
        if (! $hasAccess) {
            return $this->forbidden('Esta orden no pertenece a tu tienda.');
        }

        return $this->success([
            'id' => (string) $order->id,
            'orderNumber' => $order->order_number,
            'customer_name' => $order->shipping_name,
            'total' => (float) $order->total,
        ]);
    }
}
