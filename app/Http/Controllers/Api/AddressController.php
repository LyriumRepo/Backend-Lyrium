<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'etiqueta' => 'required|string|in:casa,trabajo,otro',
            'destinatario' => 'required|string|max:255',
            'pais' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'distrito' => 'required|string|max:100',
            'avenida' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'piso_lote' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ]);

        $data['user_id'] = $request->user()->id;

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        $address = Address::create($data);

        return $this->created($address);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        return $this->success($address);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'etiqueta' => 'sometimes|string|in:casa,trabajo,otro',
            'destinatario' => 'sometimes|string|max:255',
            'pais' => 'sometimes|string|max:100',
            'departamento' => 'sometimes|string|max:100',
            'provincia' => 'sometimes|string|max:100',
            'distrito' => 'sometimes|string|max:100',
            'avenida' => 'sometimes|string|max:255',
            'numero' => 'sometimes|string|max:20',
            'piso_lote' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ]);

        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return $this->success($address->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();
        return $this->success(['success' => true]);
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        if ($address->is_default) {
            $address->update(['is_default' => false]);
        } else {
            Address::where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        }

        return $this->success($address->fresh());
    }
}
