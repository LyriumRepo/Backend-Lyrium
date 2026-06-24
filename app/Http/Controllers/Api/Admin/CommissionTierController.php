<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommissionTierController extends Controller
{
    public function index(): JsonResponse
    {
        $tiers = CommissionTier::orderBy('sort_order')->get();

        return $this->success([
            'data' => $tiers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'minAmount' => (float) $t->min_amount,
                'maxAmount' => $t->max_amount !== null ? (float) $t->max_amount : null,
                'rate' => (float) $t->rate,
                'sortOrder' => $t->sort_order,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_amount'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $tier = CommissionTier::create($data);

        return $this->created([
            'id' => $tier->id,
            'name' => $tier->name,
            'minAmount' => (float) $tier->min_amount,
            'maxAmount' => $tier->max_amount !== null ? (float) $tier->max_amount : null,
            'rate' => (float) $tier->rate,
            'sortOrder' => $tier->sort_order,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tier = CommissionTier::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_amount'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $tier->update($data);

        return $this->success([
            'id' => $tier->id,
            'name' => $tier->name,
            'minAmount' => (float) $tier->min_amount,
            'maxAmount' => $tier->max_amount !== null ? (float) $tier->max_amount : null,
            'rate' => (float) $tier->rate,
            'sortOrder' => $tier->sort_order,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $tier = CommissionTier::findOrFail($id);
        $tier->delete();

        return $this->success(null, 'Tramo de comisión eliminado.');
    }
}
