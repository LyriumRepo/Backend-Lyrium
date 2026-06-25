<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Logistics\LogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogisticsController extends Controller
{
    public function __construct(
        private readonly LogisticsService $logistics
    ) {}
    public function departamentos(): JsonResponse
    {
        return response()->json([
            'success'       => true,
            'departamentos' => $this->logistics->getDepartamentos(),
        ]);
    }

    public function provincias(string $departamento): JsonResponse
    {
        $provincias = $this->logistics->getProvincias($departamento);

        if (empty($provincias)) {
            return response()->json([
                'success' => false,
                'message' => "No se encontraron provincias para: {$departamento}",
            ], 404);
        }

        return response()->json([
            'success'   => true,
            'provincias' => $provincias,
        ]);
    }

    public function distritos(Request $request): JsonResponse
    {
        $request->validate([
            'depto' => 'required|string',
            'prov'  => 'required|string',
        ]);

        $distritos = $this->logistics->getDistritos(
            $request->string('depto')->toString(),
            $request->string('prov')->toString()
        );

        return response()->json([
            'success'   => true,
            'distritos' => $distritos,
        ]);
    }

    public function operadores(Request $request): JsonResponse
    {
        $request->validate([
            'depto' => 'required|string',
            'prov'  => 'required|string',
            'dist'  => 'required|string',
        ]);

        $ops = $this->logistics->getOperadores(
            $request->string('depto')->toString(),
            $request->string('prov')->toString(),
            $request->string('dist')->toString()
        );

        return response()->json([
            'success'    => true,
            'operadores' => $ops,
        ]);
    }
    public function shalomTerminal(Request $request): JsonResponse
    {
        $request->validate([
            'dept' => 'required|string',
            'prov' => 'required|string',
            'dist' => 'required|string',
        ]);

        $terminal = $this->logistics->buscarTerminalShalom(
            $request->string('dept')->toString(),
            $request->string('prov')->toString(),
            $request->string('dist')->toString()
        );

        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal Shalom no encontrada',
            ], 404);
        }

        return response()->json([
            'success'  => true,
            'terminal' => $terminal,
        ]);
    }
    public function shalomReparto(Request $request): JsonResponse
    {
        $request->validate([
            'dept_id' => 'required|integer',
            'prov_id' => 'required|integer',
            'dist'    => 'required|string',
        ]);

        $reparto = $this->logistics->buscarRepartoShalom(
            (int) $request->input('dept_id'),
            (int) $request->input('prov_id'),
            $request->string('dist')->toString()
        );

        if (!$reparto) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de reparto no encontrados para ese distrito',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'reparto' => $reparto,
        ]);
    }

    public function calcularCaja(Request $request): JsonResponse
    {
        $request->validate([
            'productos'              => 'required_without:tiendas|array|min:1|max:200',
            'productos.*.nombre'     => 'required|string',
            'productos.*.cantidad'   => 'required|integer|min:1',
            'productos.*.peso'       => 'required|numeric|min:0.001',
            'productos.*.largo'      => 'required|numeric|min:0.5',
            'productos.*.ancho'      => 'required|numeric|min:0.5',
            'productos.*.alto'       => 'required|numeric|min:0.5',
            'tiendas'                => 'required_without:productos|array|min:1',
            'tiendas.*.tienda'       => 'required|string',
            'tiendas.*.productos'    => 'required|array|min:1',
        ]);

        try {
            if ($request->has('tiendas')) {
                $resultados = [];
                foreach ($request->input('tiendas') as $tienda) {
                    $resultados[] = array_merge(
                        ['tienda' => $tienda['tienda']],
                        $this->logistics->calcularCaja($tienda['productos'])
                    );
                }
                return response()->json([
                    'success' => true,
                    'tiendas' => $resultados,
                ]);
            }
            return response()->json(
                $this->logistics->calcularCaja($request->input('productos'))
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[LogisticsController] calcularCaja: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error interno'], 500);
        }
    }
    public function calcular(Request $request): JsonResponse
    {
        $request->validate([
            'productos'                     => 'required|array|min:1|max:200',
            'productos.*.store_id'          => 'required|integer',
            'productos.*.store_name'        => 'required|string',
            'productos.*.name'              => 'required|string',
            'productos.*.quantity'          => 'required|integer|min:1',
            'productos.*.peso'              => 'required|numeric|min:0.001',
            'productos.*.largo'             => 'required|numeric|min:0.5',
            'productos.*.ancho'             => 'required|numeric|min:0.5',
            'productos.*.alto'              => 'required|numeric|min:0.5',
            'productos.*.origen'            => 'required|array',
            'productos.*.origen.departamento' => 'required|string',
            'productos.*.origen.provincia'    => 'required|string',
            'productos.*.origen.distrito'     => 'required|string',
            'destino'                       => 'required|array',
            'destino.departamento'          => 'required|string',
            'destino.provincia'             => 'required|string',
            'destino.distrito'              => 'required|string',
            'tipoEntrega'                   => 'sometimes|string|in:domicilio,agencia',
        ]);

        try {
            $result = $this->logistics->calcular(
                $request->input('productos'),
                $request->input('destino'),
                $request->input('tipoEntrega', 'domicilio')
            );

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[LogisticsController] calcular: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'error' => 'Error calculando envío'], 500);
        }
    }
    public function confirmarEnvio(Request $request): JsonResponse
    {
        $request->validate([
            'modo'                          => 'required|string|in:unificado,multi',
            'courierUnificado'              => 'nullable|string|in:SHALOM,OLVA,SHARF,URBANO',
            'productos'                     => 'required|array|min:1|max:200',
            'productos.*.store_id'          => 'required|integer',
            'productos.*.name'              => 'required|string',
            'productos.*.quantity'          => 'required|integer|min:1',
            'productos.*.peso'              => 'required|numeric|min:0.001',
            'productos.*.largo'             => 'required|numeric|min:0.5',
            'productos.*.ancho'             => 'required|numeric|min:0.5',
            'productos.*.alto'              => 'required|numeric|min:0.5',
            'productos.*.origen'            => 'required|array',
            'destino'                       => 'required|array',
            'destino.departamento'          => 'required|string',
            'destino.provincia'             => 'required|string',
            'destino.distrito'              => 'required|string',
            'tipoEntrega'                   => 'required|string|in:domicilio,agencia',
            'selecciones'                   => 'sometimes|array',
        ]);

        try {
            $result = $this->logistics->confirmarEnvio(
                $request->input('modo'),
                $request->input('productos'),
                $request->input('destino'),
                $request->input('tipoEntrega', 'domicilio'),
                $request->input('courierUnificado'),
                $request->input('selecciones', [])
            );

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[LogisticsController] confirmarEnvio: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error confirmando envío'], 500);
        }
    }
}
