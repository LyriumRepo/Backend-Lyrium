<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TopMedal;
use Illuminate\Http\JsonResponse;

final class MedalController extends Controller
{
    public function active(): JsonResponse
    {
        $medals = TopMedal::where('status', 'approved')
            ->where('visible', true)
            ->get(['entity_type', 'medalable_id']);

        $grouped = [
            'store' => [],
            'product' => [],
            'service' => [],
        ];

        foreach ($medals as $medal) {
            $type = $medal->entity_type;
            if (isset($grouped[$type])) {
                $grouped[$type][] = (int) $medal->medalable_id;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}
