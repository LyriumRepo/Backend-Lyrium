<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ServiceBooking;
use App\Services\LiriosService;
use App\Services\ReceiptValidationService;
use Illuminate\View\View;

/**
 * Validación de recepción vía link firmado del email (sin login).
 * La URL firmada (middleware 'signed') es la credencial: mismo modelo
 * de confianza que la verificación pública de recibos de plan.
 */
final class ReceiptValidationWebController extends Controller
{
    public function __construct(
        private readonly ReceiptValidationService $receiptValidationService,
    ) {}

    public function validateOrder(Order $order): View
    {
        if ($order->status !== Order::STATUS_DELIVERED) {
            return $this->view('not_ready', tipo: 'pedido', numero: $order->order_number);
        }

        $result = $this->receiptValidationService->validateOrder(
            $order,
            ReceiptValidationService::SOURCE_EMAIL,
        );

        return $this->resultView($result, tipo: 'pedido', numero: $order->order_number);
    }

    public function validateBooking(ServiceBooking $booking): View
    {
        if ($booking->status !== ServiceBooking::STATUS_COMPLETED) {
            return $this->view('not_ready', tipo: 'reserva', numero: (string) $booking->id);
        }

        $result = $this->receiptValidationService->validateBooking(
            $booking,
            ReceiptValidationService::SOURCE_EMAIL,
        );

        return $this->resultView($result, tipo: 'reserva', numero: (string) $booking->id);
    }

    /**
     * @param array{validated: bool, already_validated: bool, validation_source: ?string} $result
     */
    private function resultView(array $result, string $tipo, string $numero): View
    {
        if ($result['validated']) {
            return $this->view('validated', $tipo, $numero, liriosBonus: LiriosService::VALIDATION_BONUS_LIRIOS);
        }

        $estado = $result['validation_source'] === ReceiptValidationService::SOURCE_AUTO_EXPIRED
            ? 'auto_expired'
            : 'already_validated';

        return $this->view($estado, $tipo, $numero);
    }

    private function view(string $estado, string $tipo, string $numero, ?int $liriosBonus = null): View
    {
        return view('verify.receipt-validation', [
            'estado' => $estado, // validated | already_validated | auto_expired | not_ready
            'tipo' => $tipo, // pedido | reserva
            'numero' => $numero,
            'liriosBonus' => $liriosBonus,
            'frontendUrl' => config('app.frontend_url'),
        ]);
    }
}
