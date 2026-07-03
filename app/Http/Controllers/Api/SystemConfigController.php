<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SystemConfigController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

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

        $this->auditService->record(
            event: 'config.system.updated',
            module: 'config',
            description: 'Configuración creada: ' . $config->key,
            source: AuditService::SOURCE_WEB,
            metadata: ['config_key' => $config->key, 'action' => 'created'],
        );

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

        $oldValue = $config->value;
        $config->update($data);

        $this->auditService->record(
            event: 'config.system.updated',
            module: 'config',
            description: 'Configuración actualizada: ' . $config->key,
            source: AuditService::SOURCE_WEB,
            oldValues: ['value' => $oldValue],
            newValues: ['value' => $config->value],
            metadata: ['config_key' => $config->key, 'action' => 'updated'],
        );

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

        $this->auditService->record(
            event: 'config.system.updated',
            module: 'config',
            description: 'Configuración eliminada: ' . $config->key,
            source: AuditService::SOURCE_WEB,
            oldValues: ['value' => $config->value],
            metadata: ['config_key' => $config->key, 'action' => 'deleted'],
        );

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

    public function updateColors(Request $request): JsonResponse
    {
        $data = $request->validate([
            'primary_color'        => ['nullable', 'string', 'max:30'],
            'success_color'        => ['nullable', 'string', 'max:30'],
            'background_color'     => ['nullable', 'string', 'max:30'],
            'text_secondary_color' => ['nullable', 'string', 'max:30'],
            'error_color'          => ['nullable', 'string', 'max:30'],
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                SystemConfig::updateOrCreate(
                    ['key' => $key],
                    [
                        'value'       => $value,
                        'category'    => 'colors',
                        'name'        => ucwords(str_replace('_', ' ', $key)),
                        'type'        => 'color',
                        'is_public'   => true,
                    ]
                );
            }
        }

        $this->auditService->record(
            event: 'config.colors.updated',
            module: 'config',
            description: 'Colores del sistema actualizados',
            source: AuditService::SOURCE_WEB,
            metadata: ['updated_keys' => array_keys($data)],
        );

        $colors = SystemConfig::getByCategory('colors')->get();

        return response()->json([
            'success' => true,
            'data' => $colors->mapWithKeys(fn ($c) => [$c->key => $c->value])->toArray(),
        ]);
    }
}
