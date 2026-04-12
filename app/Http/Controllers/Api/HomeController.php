<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroResource;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

final class HomeController extends Controller
{
    public function banners(): JsonResponse
    {
        $data = [];

        $pequenos1 = Banner::active()->bySection('pequenos1')->orderBy('position')->get();
        $data['pequenos1'] = $pequenos1->pluck('imagen')->values()->all();

        $sliderMedianos1 = Banner::active()->bySection('sliderMedianos1')->orderBy('position')->get();
        $data['sliderMedianos1'] = $sliderMedianos1->chunk(2)->map(function ($chunk, $index) {
            return [
                'id' => $index + 1,
                'imagenes' => $chunk->pluck('imagen')->values()->all(),
            ];
        })->values()->all();

        $pequenos2 = Banner::active()->bySection('pequenos2')->orderBy('position')->get();
        $data['pequenos2'] = $pequenos2->pluck('imagen')->values()->all();

        $sliderMedianos2 = Banner::active()->bySection('sliderMedianos2')->orderBy('position')->get();
        $data['sliderMedianos2'] = $sliderMedianos2->chunk(2)->map(function ($chunk, $index) {
            return [
                'id' => $index + 1,
                'imagenes' => $chunk->pluck('imagen')->values()->all(),
            ];
        })->values()->all();

        return $this->success($data);
    }

    public function heroes(): JsonResponse
    {
        $heroes = Banner::active()
            ->bySection('slider1')
            ->orderBy('position')
            ->get();

        return $this->success(HeroResource::collection($heroes));
    }

    public function categorySection(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->first();

        if (! $category) {
            return $this->error('Categoría no encontrada', 404);
        }

        $banner = Banner::active()
            ->bySection('categoria_'.$slug)
            ->orderBy('position')
            ->first();

        $products = Product::with(['categories', 'store'])
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $data = [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'banner' => $banner ? [
                'imagen' => $banner->imagen,
                'enlace' => $banner->enlace,
            ] : null,
            'products' => ProductResource::collection($products),
        ];

        return $this->success($data);
    }
}
