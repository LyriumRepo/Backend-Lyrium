<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends Controller
{
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
        ]);

        return $this->created(new InvoiceResource($invoice));
    }
}
