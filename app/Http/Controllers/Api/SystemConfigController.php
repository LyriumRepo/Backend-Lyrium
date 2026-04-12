<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SystemConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SystemConfig::query();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($publicOnly = $request->query('public')) {
            $query->where('is_public', true);
        }

        $configs = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'data' => $configs->map(fn ($config) => [
                'id' => $config->id,
                'key' => $config->key,
                'name' => $config->name,
                'value' => $config->value,
                'type' => $config->type,
                'category' => $config->category,
                'description' => $config->description,
                'is_public' => $config->is_public,
            ]),
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $config = SystemConfig::where('key', $key)->firstOrFail();

        return response()->json([
            'id' => $config->id,
            'key' => $config->key,
            'name' => $config->name,
            'value' => $config->value,
            'type' => $config->type,
            'category' => $config->category,
            'description' => $config->description,
            'is_public' => $config->is_public,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:system_configs,key'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'in:string,color,json,boolean'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $config = SystemConfig::create($data);

        return response()->json([
            'message' => 'Configuración creada correctamente',
            'data' => [
                'id' => $config->id,
                'key' => $config->key,
                'name' => $config->name,
                'value' => $config->value,
            ],
        ], 201);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $config = SystemConfig::where('key', $key)->firstOrFail();

        $data = $request->validate([
            'value' => ['nullable'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:string,color,json,boolean'],
            'category' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        if (isset($data['value'])) {
            if (is_array($data['value']) || is_object($data['value'])) {
                $data['value'] = json_encode($data['value']);
                $data['type'] = $data['type'] ?? 'json';
            } elseif (is_bool($data['value'])) {
                $data['value'] = $data['value'] ? 'true' : 'false';
                $data['type'] = $data['type'] ?? 'boolean';
            } else {
                $data['value'] = (string) $data['value'];
            }
        }

        $config->update($data);

        return response()->json([
            'message' => 'Configuración actualizada correctamente',
            'data' => [
                'key' => $config->key,
                'value' => $config->value,
            ],
        ]);
    }

    public function destroy(string $key): JsonResponse
    {
        $config = SystemConfig::where('key', $key)->firstOrFail();
        $config->delete();

        return response()->json(['message' => 'Configuración eliminada correctamente']);
    }

    public function colors(): JsonResponse
    {
        $colors = SystemConfig::getByCategory('colors')->get();

        return response()->json([
            'data' => $colors->mapWithKeys(fn ($config) => [$config->key => $config->value])->toArray(),
        ]);
    }

    public function publicConfigs(): JsonResponse
    {
        $configs = SystemConfig::getPublicConfigs();

        return response()->json([
            'data' => $configs,
        ]);
    }
}
