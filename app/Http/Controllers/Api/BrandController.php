<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

final class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::active()
            ->orderBy('position')
            ->get();

        return $this->success(BrandResource::collection($brands));
    }
}
