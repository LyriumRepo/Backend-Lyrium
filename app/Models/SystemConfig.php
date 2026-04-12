<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SystemConfig extends Model
{
    protected $table = 'system_configs';

    protected $fillable = [
        'key',
        'name',
        'value',
        'type',
        'category',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function getByKey(string $key, $default = null): mixed
    {
        $config = self::where('key', $key)->first();

        if (! $config) {
            return $default;
        }

        return match ($config->type) {
            'boolean' => $config->value === 'true' || $config->value === '1',
            'json' => json_decode($config->value, true),
            'color' => $config->value,
            default => $config->value,
        };
    }

    public static function setByKey(string $key, mixed $value): self
    {
        $config = self::where('key', $key)->firstOrFail();

        if (is_array($value) || is_object($value)) {
            $config->value = json_encode($value);
            $config->type = 'json';
        } elseif (is_bool($value)) {
            $config->value = $value ? 'true' : 'false';
            $config->type = 'boolean';
        } else {
            $config->value = (string) $value;
        }

        $config->save();

        return $config;
    }

    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Builder
    {
        return self::where('category', $category)->orderBy('name');
    }

    public static function getPublicConfigs(): array
    {
        return self::where('is_public', true)
            ->get()
            ->mapWithKeys(fn ($config) => [$config->key => $config->value])
            ->toArray();
    }
}
