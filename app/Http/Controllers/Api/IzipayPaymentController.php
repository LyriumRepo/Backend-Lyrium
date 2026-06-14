<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\OrderPaymentConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentMethod;
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

        try {
            event(new OrderPaymentConfirmed(
                order: $order,
                paymentMethod: 'izipay',
            ));
        } catch (\Throwable $e) {
            Log::error('[IzipayPayment] Error al disparar evento OrderPaymentConfirmed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

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

    /**
     * Carga contra un cardToken guardado (sin Smart Form).
     * POST /api/payments/izipay/charge-with-token
     */
    public function chargeWithToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'          => 'required|string',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
        ]);

        $user = $request->user();
        $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $paymentMethod->isCardTokenized()) {
            return $this->error('Esta tarjeta no tiene un token válido.', 400);
        }

        $order = Order::with('user')->findOrFail($data['order_id']);

        if ($order->user_id !== $user->id && !$user->hasRole('administrator')) {
            return $this->forbidden('Esta orden no te pertenece.');
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return $this->error('La orden ya está pagada.', 400);
        }

        Log::info('[IzipayPayment] Cargando con token guardado', [
            'order_id'          => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'card_last4'        => $paymentMethod->card_last4,
            'mode'              => $this->izipay->isMock() ? 'mock' : 'real',
        ]);

        try {
            $result = $this->izipay->chargeWithToken($order, $paymentMethod->card_token, $order->user->email);

            if ($result['success'] ?? false) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'payment_method' => 'izipay_token',
                ]);

                event(new OrderPaymentConfirmed(
                    order: $order,
                    paymentMethod: 'izipay_token',
                ));

                $invoiceCount = Invoice::where('order_id', $order->id)->count();

                return $this->success([
                    'message'          => 'Pago exitoso con tarjeta guardada',
                    'order_id'         => (string) $order->id,
                    'transaction_id'   => $result['transaction_id'] ?? null,
                    'invoices_creadas' => $invoiceCount,
                ]);
            }

            return $this->error($result['message'] ?? 'Error al procesar el pago.', 500);
        } catch (\Throwable $e) {
            Log::error('[IzipayPayment] Error en chargeWithToken', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return $this->error('Error al procesar el pago: ' . $e->getMessage(), 500);
        }
    }
}
