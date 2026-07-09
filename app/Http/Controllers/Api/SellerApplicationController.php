<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Contract;
use Illuminate\Support\Str;

final class SellerApplicationController extends Controller
{
    private const ESTADOS_VALIDOS = ['ACEPTADO', 'REVISION', 'RECHAZADO'];

    public function index(Request $request): JsonResponse
    {
        $query = SellerApplication::with(['user:id,name,email', 'store:id,status,trade_name']);

        if ($estado = $request->query('estado')) {
            if ($estado !== 'TODOS') {
                $query->where('estado', strtoupper($estado));
            }
        }

        if ($buscar = $request->query('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_comercial', 'like', "%{$buscar}%")
                    ->orWhere('ruc', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%")
                    ->orWhere('razon_social', 'like', "%{$buscar}%");
            });
        }

        $applications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 50));

        return response()->json([
            'data' => $applications->map(fn($app) => $this->format($app)),
            'pagination' => [
                'page' => $applications->currentPage(),
                'perPage' => $applications->perPage(),
                'total' => $applications->total(),
                'totalPages' => $applications->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $app = SellerApplication::with(['user', 'store'])->findOrFail($id);
        return response()->json(['data' => $this->format($app)]);
    }

    public function updateEstado(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:' . implode(',', self::ESTADOS_VALIDOS),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $app = SellerApplication::findOrFail($id);
        $nuevoEstado = strtoupper($request->input('estado'));
        $estadoAnterior = $app->estado;

        $app->estado = $nuevoEstado;
        $app->save();

        if ($nuevoEstado === 'ACEPTADO' && $estadoAnterior !== 'ACEPTADO' && !$app->store_id) {
            $store = $this->crearTiendaDesdeSolicitud($app);
            $app->store_id = $store->id;
            $app->save();
            $this->crearContratoDesdeSolicitud($app, $store);
        }

        if ($nuevoEstado === 'RECHAZADO' && $app->store && $app->store->status === 'pending') {
            $app->store->status = 'rejected';
            $app->store->save();
        }

        return response()->json([
            'success' => true,
            'data' => $this->format($app->refresh()->load(['user', 'store'])),
        ]);
    }

    private function crearTiendaDesdeSolicitud(SellerApplication $app): Store
    {
        $sunat = $app->sunat_data ?? [];
        $baseSlug = Str::slug($app->nombre_comercial);
        $slug = $baseSlug;
        $i = 1;
        while (Store::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $nombreComercialSunat = $sunat['nombreComercial'] ?? null;

        return Store::create([
            'owner_id' => $app->user_id,
            'ruc' => $app->ruc,
            'trade_name' => $app->nombre_comercial,
            'razon_social' => $app->razon_social,
            'nombre_comercial' => ($nombreComercialSunat && $nombreComercialSunat !== '-')
                ? $nombreComercialSunat
                : $app->nombre_comercial,
            'corporate_email' => $app->correo,
            'slug' => $slug,
            'phone' => $app->telefono,
            'status' => 'pending',
            'rep_legal_nombre' => $sunat['representantes'][0]['nombre'] ?? null,
            'rep_legal_dni' => $app->dni,
            'activity' => $sunat['actividad'] ?? null,
        ]);
    }

    private function crearContratoDesdeSolicitud(SellerApplication $app, Store $store): void
    {
        $year = now()->year;
        $lastContract = Contract::where('contract_number', 'like', "CTR-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();
        $nextSeq = 1;
        if ($lastContract) {
            $parts = explode('-', $lastContract->contract_number);
            $nextSeq = ((int) end($parts)) + 1;
        }
        $contractNumber = sprintf('CTR-%d-%03d', $year, $nextSeq);
        $startDate = now()->toDateString();
        $endDate = now()->addYear()->toDateString();

        Contract::create([
            'contract_number' => $contractNumber,
            'store_id' => $store->id,
            'company' => $app->razon_social ?? $app->nombre_comercial,
            'ruc' => $app->ruc,
            'representative' => $app->user?->name,
            'dni' => $app->dni,
            'modality' => 'VIRTUAL',
            'status' => 'ACTIVE',
            'type' => 'Venta',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => 'Contrato generado automáticamente al aprobar solicitud',
        ]);
    }

    private function format(SellerApplication $app): array
    {
        return [
            'id' => $app->id,
            'nombre_comercial' => $app->nombre_comercial,
            'ruc' => $app->ruc,
            'dni' => $app->dni,
            'razon_social' => $app->razon_social,
            'sunat_data' => $app->sunat_data,
            'telefono' => $app->telefono,
            'correo' => $app->correo,
            'categoria' => $app->categoria,
            'estado' => $app->estado,
            'score' => $app->score,
            'riesgo' => $app->riesgo,
            'etapa' => $app->etapa,
            'tipo_evidencia' => $app->tipo_evidencia,
            'evidencia_valor' => $app->evidencia_valor,
            'diagnostico' => $app->diagnostico,
            'created_at' => $app->created_at?->toIso8601String(),
            'user' => $app->user ? [
                'id' => $app->user->id,
                'name' => $app->user->name,
                'email' => $app->user->email,
            ] : null,
            'store' => $app->store ? [
                'id' => $app->store->id,
                'trade_name' => $app->store->trade_name,
                'status' => $app->store->status,
            ] : null,
        ];
    }
}
