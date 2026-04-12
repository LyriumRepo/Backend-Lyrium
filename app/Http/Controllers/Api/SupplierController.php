<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SupplierController extends Controller
{
    /**
     * GET /api/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('especialidad', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $suppliers = $query->orderBy('name')->paginate($perPage);

        return SupplierResource::collection($suppliers)->response();
    }

    /**
     * GET /api/suppliers/{id}
     */
    public function show(string $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json(new SupplierResource($supplier));
    }

    /**
     * POST /api/suppliers
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();

        $supplier = Supplier::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(5),
            'ruc' => $data['ruc'] ?? null,
            'type' => $data['tipo'] ?? 'Economista',
            'especialidad' => $data['especialidad'] ?? null,
            'status' => 'Activo',
            'fecha_renovacion' => $data['fechaRenovacion'] ?? null,
            'proyectos' => $data['proyectos'] ?? null,
            'certificaciones' => $data['certificaciones'] ?? null,
        ]);

        return response()->json(new SupplierResource($supplier), 201);
    }

    /**
     * PUT /api/suppliers/{id}
     */
    public function update(UpdateSupplierRequest $request, string $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $data = $request->validated();

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('ruc', $data)) {
            $updateData['ruc'] = $data['ruc'];
        }
        if (isset($data['tipo'])) {
            $updateData['type'] = $data['tipo'];
        }
        if (array_key_exists('especialidad', $data)) {
            $updateData['especialidad'] = $data['especialidad'];
        }
        if (isset($data['estado'])) {
            $updateData['status'] = $data['estado'];
        }
        if (array_key_exists('fechaRenovacion', $data)) {
            $updateData['fecha_renovacion'] = $data['fechaRenovacion'];
        }
        if (array_key_exists('proyectos', $data)) {
            $updateData['proyectos'] = $data['proyectos'];
        }
        if (array_key_exists('certificaciones', $data)) {
            $updateData['certificaciones'] = $data['certificaciones'];
        }

        $supplier->update($updateData);

        return response()->json(new SupplierResource($supplier));
    }

    /**
     * DELETE /api/suppliers/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json(['success' => true]);
    }
}
