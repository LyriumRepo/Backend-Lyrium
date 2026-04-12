<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = Plan::all();

        return PlanResource::collection($plans);
    }

    public function show(int $id): PlanResource
    {
        $plan = Plan::findOrFail($id);

        return new PlanResource($plan);
    }
}
