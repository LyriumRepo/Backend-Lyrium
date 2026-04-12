<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BenefitResource;
use App\Models\Benefit;
use Illuminate\Http\JsonResponse;

final class BenefitController extends Controller
{
    public function index(): JsonResponse
    {
        $benefits = Benefit::active()
            ->orderBy('position')
            ->get();

        return $this->success(BenefitResource::collection($benefits));
    }
}
