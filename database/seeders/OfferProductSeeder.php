<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OfferProductSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();
        if (! $store) {
            $this->command->warn('No se encontró tienda. Ejecuta primero AdminUserSeeder.');

            return;
        }

        $suplementos = Category::where('slug', 'productos-suplementos')->first();
        $digestion = Category::where('slug', 'productos-digestion-saludable')->first();
        $belleza = Category::where('slug', 'productos-belleza')->first();

        $offerProducts = [
            [
                'name' => 'Colágeno Marino Premium',
                'slug' => 'colageno-marino-premium',
                'price' => 129.90,
                'regular_price' => 159.90,
                'sale_price' => 129.90,
                'short_description' => 'Colágeno hidrolizado de alta absorción para piel, cabello y articulaciones.',
                'sticker' => 'oferta',
                'category' => $digestion,
                'image' => '/storage/img/productos/digestion/colageno-marino.png',
            ],
            [
                'name' => 'Melatonina 10mg',
                'slug' => 'melatonina-10mg',
                'price' => 85.00,
                'regular_price' => 110.00,
                'sale_price' => 85.00,
                'short_description' => 'Suplemento natural para un descanso reparador.',
                'sticker' => 'oferta',
                'category' => $digestion,
                'image' => '/storage/img/productos/digestion/melatonin-10mg.png',
            ],
            [
                'name' => 'Haba Tostada',
                'slug' => 'haba-tostada',
                'price' => 25.00,
                'regular_price' => 30.00,
                'sale_price' => 25.00,
                'short_description' => 'Habas tostadas naturales, snack saludable y nutritivo.',
                'sticker' => 'oferta',
                'category' => $digestion,
                'image' => '/storage/img/productos/digestion/haba-tostada.png',
            ],
            [
                'name' => 'Espuma Limpiadora Facial 1',
                'slug' => 'espuma-limpiadora-1',
                'price' => 35.00,
                'regular_price' => 45.00,
                'sale_price' => 35.00,
                'short_description' => 'Espuma limpiadora facial para piel sensible.',
                'sticker' => 'oferta',
                'category' => $belleza,
                'image' => '/storage/img/productos/belleza/espuma-limpiadora1.png',
            ],
            [
                'name' => 'Espuma Limpiadora Facial 2',
                'slug' => 'espuma-limpiadora-2',
                'price' => 38.00,
                'regular_price' => 48.00,
                'sale_price' => 38.00,
                'short_description' => 'Espuma limpiadora facial purificante.',
                'sticker' => 'oferta',
                'category' => $belleza,
                'image' => '/storage/img/productos/belleza/espuma-limpiadora2.png',
            ],
            [
                'name' => 'Espuma Limpiadora Facial 3',
                'slug' => 'espuma-limpiadora-3',
                'price' => 42.00,
                'regular_price' => 52.00,
                'sale_price' => 42.00,
                'short_description' => 'Espuma limpiadora facial con ácido hialurónico.',
                'sticker' => 'oferta',
                'category' => $belleza,
                'image' => '/storage/img/productos/belleza/espuma-limpiadora3.png',
            ],
            [
                'name' => 'Blanqueamiento Dental',
                'slug' => 'blanqueamiento-dental',
                'price' => 150.00,
                'regular_price' => 200.00,
                'sale_price' => 150.00,
                'short_description' => 'Servicio de blanqueamiento dental profesional.',
                'sticker' => 'oferta',
                'category' => Category::where('slug', 'servicios-medicos')->first(),
                'image' => '/storage/img/productos/servicios-medicos/blanqueamiento-dental.png',
            ],
            [
                'name' => 'Diagnóstico Unipolar',
                'slug' => 'diagnostico-unipolar',
                'price' => 80.00,
                'regular_price' => 100.00,
                'sale_price' => 80.00,
                'short_description' => 'Diagnóstico médico unipolar especializado.',
                'sticker' => 'oferta',
                'category' => Category::where('slug', 'servicios-medicos')->first(),
                'image' => '/storage/img/productos/servicios-medicos/Diagnostico unipolar.png',
            ],
            [
                'name' => 'Masajes Corporales',
                'slug' => 'masajes-corporales',
                'price' => 90.00,
                'regular_price' => 120.00,
                'sale_price' => 90.00,
                'short_description' => 'Servicio de masajes corporales relajantes.',
                'sticker' => 'oferta',
                'category' => Category::where('slug', 'servicios-medicos')->first(),
                'image' => '/storage/img/productos/servicios-medicos/masajes-corporales.png',
            ],
        ];

        foreach ($offerProducts as $data) {
            $category = $data['category'];
            unset($data['category']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'store_id' => $store->id,
                    'status' => 'approved',
                    'stock' => rand(10, 100),
                    'type' => 'physical',
                    'discount_percentage' => round((($data['regular_price'] - $data['price']) / $data['regular_price']) * 100, 2),
                ])
            );

            if ($category) {
                $product->categories()->sync([$category->id]);
            }
        }

        $newProducts = [
            [
                'name' => 'Creatina Monohidratada Kevin Levrone',
                'slug' => 'creatina-monohidratada',
                'price' => 89.90,
                'regular_price' => 89.90,
                'sale_price' => null,
                'short_description' => 'Creatina de alta pureza para rendimiento muscular.',
                'sticker' => 'nuevo',
                'category' => $suplementos,
                'image' => '/storage/img/productos/nuevos/creatina.webp',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Intra Entrenamiento Caramelo',
                'slug' => 'intra-entrenamiento',
                'price' => 65.00,
                'regular_price' => 65.00,
                'sale_price' => null,
                'short_description' => 'Suplemento intra entrenamiento con sabor fruta del dragón.',
                'sticker' => 'nuevo',
                'category' => $suplementos,
                'image' => '/storage/img/productos/nuevos/intra-entrenamiento.webp',
                'created_at' => now()->subDays(5),
            ],
            [
                'name' => 'Melatonina de Liberación Rápida 10mg',
                'slug' => 'melatonina-liberacion-rapida',
                'price' => 45.00,
                'regular_price' => 45.00,
                'sale_price' => null,
                'short_description' => 'Melatonina de liberación rápida para un descanso reparador.',
                'sticker' => 'nuevo',
                'category' => $suplementos,
                'image' => '/storage/img/productos/nuevos/melatonina.webp',
                'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($newProducts as $data) {
            $category = $data['category'];
            $createdAt = $data['created_at'];
            unset($data['category'], $data['created_at']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'store_id' => $store->id,
                    'status' => 'approved',
                    'stock' => rand(10, 100),
                    'type' => 'physical',
                ])
            );

            if ($category) {
                $product->categories()->sync([$category->id]);
            }

            $product->update(['created_at' => $createdAt]);
        }

        $this->command->info('OfferProductSeeder: Productos de ofertas y nuevos creados.');
    }
}
