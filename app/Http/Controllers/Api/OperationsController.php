<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Supplier;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OperationsController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    public function request2FA(Request $request): JsonResponse
    {
        $user = $request->user(); // Usuario logueado en el panel

        if (! $this->otpService->canResend($user)) {
            return response()->json([
                'success' => false,
                'error' => 'Espera 60 segundos antes de solicitar otro código.',
            ], 429);
        }

        // Reutilizamos tu método: genera el código y lo manda por cola de correo
        $this->otpService->generate($user);

        return response()->json([
            'success' => true,
            'message' => 'Código de verificación enviado a tu correo.',
        ]);
    }

    public function verify2FA(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        // Usamos tu método verifyOnly
        $result = $this->otpService->verifyOnly($user, $request->code);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Código incorrecto.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Código verificado con éxito.',
        ]);
    }

    /**
     * GET /api/operations/stats
     *
     * Agregado para los 4 KPI cards del dashboard de Gestión Operativa:
     *   - Inversión total (RF-13)
     *   - Proveedores activos
     *   - En pausa / suspendidos
     *   - Recibos pendientes
     */
    public function stats(): JsonResponse
    {
        // Inversión: sum de expenses no anulados
        $inversionTotal = Expense::whereNot('status', 'Anulado')->sum('amount');

        // Proveedores por estado
        $proveedoresActivos = Supplier::where('status', 'Activo')->count();
        $proveedoresSuspendidos = Supplier::whereIn('status', ['Suspendido', 'Inactivo', 'En Pausa'])->count();

        // Recibos pendientes de pago
        $recibosPendientes = Expense::pending()->count();

        return response()->json([
            'inversion_total' => (float) $inversionTotal,
            'proveedores_activos' => $proveedoresActivos,
            'proveedores_suspendidos' => $proveedoresSuspendidos,
            'recibos_pendientes' => $recibosPendientes,
        ]);
    }
}
