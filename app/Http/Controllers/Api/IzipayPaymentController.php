<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\OrderPaymentConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\IzipayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class IzipayPaymentController extends Controller
{
    public function __construct(
        private readonly IzipayService $izipay,
    ) {}

    /**
     * Inicializa el pago Izipay para una orden.
     * POST /api/payments/izipay/init/{order}
     */
    public function init(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();
        $order = Order::with('user')->findOrFail($orderId);

        if ($order->user_id !== $user->id && !$user->hasRole('administrator')) {
            return $this->forbidden('Esta orden no te pertenece.');
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return $this->error('La orden ya está pagada.', 400);
        }

        Log::info('[IzipayPayment] Iniciando pago', [
            'order_id' => $order->id,
            'mode'     => $this->izipay->isMock() ? 'mock' : 'real',
        ]);

        try {
            $data = $this->izipay->initPayment($order);

            return $this->success($data);
        } catch (\Throwable $e) {
            Log::error('[IzipayPayment] Error al iniciar pago', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return $this->error('Error al iniciar el pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Confirma el pago (modo MOCK / simulación).
     * POST /api/payments/izipay/confirm/{order}
     */
    public function confirm(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();
        $order = Order::with('user')->findOrFail($orderId);

        if ($order->user_id !== $user->id && !$user->hasRole('administrator')) {
            return $this->forbidden('Esta orden no te pertenece.');
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return $this->error('La orden ya está pagada.', 400, [
                'invoice_count' => Invoice::where('order_id', $order->id)->count(),
            ]);
        }

        Log::info('[IzipayPayment:MOCK] ===== INICIO confirmación mock =====', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'triggered_by' => $user->id,
        ]);

        $result = $this->izipay->mockConfirmPayment($order);

        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'payment_method' => 'izipay',
        ]);

        $order->refresh();

        event(new OrderPaymentConfirmed(
            order: $order,
            paymentMethod: 'izipay',
        ));

        $invoiceCount = Invoice::where('order_id', $order->id)->count();

        Log::info('[IzipayPayment:MOCK] ===== FIN confirmación mock =====', [
            'order_id'        => $order->id,
            'transaction_id'  => $result['transaction_id'],
            'invoices_creadas' => $invoiceCount,
            'nubefact_llamado' => $invoiceCount > 0 ? 'SI' : 'NO (sin stores o error)',
        ]);

        return $this->success([
            'message'        => 'Pago confirmado exitosamente',
            'order_id'       => (string) $order->id,
            'transaction_id' => $result['transaction_id'],
            'invoices_creadas' => $invoiceCount,
        ]);
    }
}
