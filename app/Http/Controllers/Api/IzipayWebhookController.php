<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\OrderPaymentConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class IzipayWebhookController extends Controller
{
    /**
     * Webhook Izipay para pagos de órdenes de compra.
     *
     * Izipay envía una notificación POST cuando un pago es procesado.
     * Si el pago es exitoso, dispara la generación automática de
     * comprobantes electrónicos y el cálculo de comisiones.
     */
    public function handleOrderPayment(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::debug('IzipayWebhook: notificación recibida para orden', [
            'payload' => $data,
        ]);

        $orderId = $data['orderId'] ?? $data['order_id'] ?? null;
        $transactionState = $data['transactionState'] ?? $data['status'] ?? '';

        if (! $orderId) {
            Log::warning('IzipayWebhook: orderId no proporcionado', ['payload' => $data]);
            return $this->error('orderId es requerido', 422);
        }

        $order = Order::where('id', $orderId)
            ->orWhere('order_number', $orderId)
            ->first();

        if (! $order) {
            Log::warning('IzipayWebhook: orden no encontrada', ['orderId' => $orderId]);
            return $this->error('Orden no encontrada', 404);
        }

        $orderState = strtoupper((string) $transactionState);

        if ($orderState === 'AUTHORIZED' || $orderState === 'CAPTURED' || $orderState === 'PAID') {
            Log::info('IzipayWebhook: pago confirmado para orden', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transactionState' => $transactionState,
            ]);

            event(new OrderPaymentConfirmed(
                order: $order,
                paymentMethod: 'izipay',
            ));

            return $this->success([
                'message' => 'Pago confirmado. Generando comprobantes...',
                'order_id' => (string) $order->id,
            ]);
        }

        if ($orderState === 'FAILED' || $orderState === 'EXPIRED' || $orderState === 'CANCELLED') {
            Log::info('IzipayWebhook: pago fallido para orden', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transactionState' => $transactionState,
            ]);

            $order->update(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

            return $this->success([
                'message' => 'Pago registrado como fallido.',
                'order_id' => (string) $order->id,
            ]);
        }

        Log::warning('IzipayWebhook: estado no manejado', [
            'order_id' => $orderId,
            'transactionState' => $transactionState,
        ]);

        return $this->success([
            'message' => 'Notificación recibida, estado no procesado.',
            'order_id' => (string) $order->id,
        ]);
    }
}
