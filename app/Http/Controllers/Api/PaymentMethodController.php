<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($methods);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo_metodo' => 'required|string|in:tarjeta,yape,plin',
            'documento' => 'required|string|max:255',
            'titular' => 'required|string|max:255',
            'detalle_extra' => 'nullable|string|max:255',
            'is_default' => 'sometimes|boolean',
            'ruc_dni' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
        ]);

        $data['user_id'] = $request->user()->id;

        if ($data['is_default'] ?? false) {
            PaymentMethod::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        $method = PaymentMethod::create($data);

        return $this->created($method);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);
        return $this->success($method);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'tipo_metodo' => 'sometimes|string|in:tarjeta,yape,plin',
            'documento' => 'sometimes|string|max:255',
            'titular' => 'sometimes|string|max:255',
            'detalle_extra' => 'nullable|string|max:255',
            'is_default' => 'sometimes|boolean',
            'ruc_dni' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
        ]);

        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);

        if ($data['is_default'] ?? false) {
            PaymentMethod::where('user_id', $request->user()->id)
                ->where('id', '!=', $method->id)
                ->update(['is_default' => false]);
        }

        $method->update($data);

        return $this->success($method->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);
        $method->delete();
        return $this->success(['success' => true]);
    }
}
