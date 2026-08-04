<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Adjunta logo, logo_marketplace y banner a las tiendas `approved` del
 * marketplace vía Spatie MediaLibrary. Idempotente: puede correrse varias
 * veces sin duplicar media (limpia la colección antes de re-adjuntar).
 *
 * Fuente de imágenes: no fueron provistas por el dueño del proyecto para
 * estas colecciones (solo existen fotos de producto en `PRODUCTOS Y
 * SERVICIOS`), así que se generaron programáticamente con estilo de marca
 * (teal/sky) vía `storage/app/generate-store-assets.php` — ver
 * TAREA_BANNERS_LOGOS_TIENDAS.md §5 opción B.
 */
final class StoreMediaSeeder extends Seeder
{
    private string $assetsBasePath;

    /** @var array<string, string> colección => nombre de archivo fuente */
    private const COLLECTIONS = [
        'logo' => 'logo.png',
        'logo_marketplace' => 'logo_marketplace.png',
        'banner' => 'banner.jpg',
    ];

    public function __construct()
    {
        $this->assetsBasePath = storage_path('app/public/store-assets');
    }

    public function run(): void
    {
        if (! is_dir($this->assetsBasePath)) {
            $this->command?->warn("StoreMediaSeeder: no se encontró la carpeta de assets en {$this->assetsBasePath}, se omite el seed.");

            return;
        }

        config(['media-library.max_file_size' => 30 * 1024 * 1024]);

        $stores = Store::where('status', 'approved')->orderBy('id')->get();

        $ok = 0;
        foreach ($stores as $store) {
            $folder = $this->assetsBasePath.DIRECTORY_SEPARATOR.$store->slug;

            if (! is_dir($folder)) {
                $this->command?->warn("[store missing assets] {$store->slug} (id={$store->id}): {$folder}");

                continue;
            }

            $this->command?->info("Tienda {$store->id}: {$store->slug}");

            foreach (self::COLLECTIONS as $collection => $filename) {
                $this->attach($store, $collection, $folder.DIRECTORY_SEPARATOR.$filename);
            }

            $ok++;
        }

        $this->command?->info("✓ StoreMediaSeeder: {$ok}/{$stores->count()} tiendas approved procesadas.");
    }

    private function attach(Store $store, string $collection, string $path): void
    {
        if (! is_file($path)) {
            $this->command?->warn("  [img missing] {$collection} -> ".basename($path));

            return;
        }

        try {
            // Idempotente: limpia la colección antes de re-adjuntar para que
            // correr el seeder de nuevo no duplique media.
            $store->clearMediaCollection($collection);

            $store->addMedia($path)
                ->preservingOriginal()
                ->toMediaCollection($collection);

            $this->command?->info("  ✓ {$collection}");
        } catch (\Throwable $e) {
            $this->command?->warn("  [img error] {$collection}: {$e->getMessage()}");
        }
    }
}
