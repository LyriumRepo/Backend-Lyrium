<?php

declare(strict_types=1);

use App\Models\SystemConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SystemConfig::create([
            'key' => 'top_medal_default_image_url',
            'name' => 'URL de imagen predeterminada para medallas Top 100',
            'value' => '/img/medal-top-100.png',
            'type' => 'string',
            'category' => 'medallas',
            'description' => 'Imagen por defecto mostrada en las medallas Top 100 cuando no tienen una imagen personalizada.',
            'is_public' => true,
        ]);
    }

    public function down(): void
    {
        SystemConfig::where('key', 'top_medal_default_image_url')->delete();
    }
};
