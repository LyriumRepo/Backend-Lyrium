<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VidaNaturalSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = base_path('../1 VIDA NATURAL');
        if (!is_dir($sourceDir)) {
            $this->command->warn('Directorio 1 VIDA NATURAL no encontrado en la raíz del proyecto.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'vida.natural@lyrium.com'],
            [
                'name' => 'María Vida Natural',
                'username' => 'vida_natural',
                'nicename' => 'vida-natural',
                'phone' => '999111222',
                'document_type' => 'RUC',
                'document_number' => '20111111112',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('seller');

        $store = Store::updateOrCreate(
            ['ruc' => '20111111112'],
            [
                'owner_id' => $user->id,
                'trade_name' => 'Vida Natural',
                'razon_social' => 'Vida Natural EIRL',
                'nombre_comercial' => 'Vida Natural',
                'corporate_email' => 'vida.natural@lyrium.com',
                'slug' => 'vida-natural',
                'phone' => '999111222',
                'status' => 'approved',
                'approved_at' => now(),
                'seller_type' => 'products',
                'rep_legal_nombre' => 'María Vida Natural',
                'rep_legal_dni' => '11111111',
                'experience_years' => 3,
                'tax_condition' => 'RUC',
                'direccion_fiscal' => 'Av. La Molina 567, Lima, Peru',
                'store_name' => 'Vida Natural',
                'address' => 'Av. La Molina 567, Lima, Peru',
                'description' => 'Productos naturales y saludables para tu bienestar.',
                'commission_rate' => 0.1000,
            ]
        );

        $this->command->info("Store 'Vida Natural' created/updated.");

        $products = [
            [
                'name' => 'Moringa Cápsulas',
                'description' => 'Moringa orgánica en cápsulas, rica en vitaminas, minerales y antioxidantes. Ideal para reforzar el sistema inmunológico y aumentar la energía de forma natural. Cultivada sin pesticidas ni químicos.',
                'short_description' => 'Moringa orgánica en cápsulas, rica en antioxidantes',
                'price' => 35.00,
                'regular_price' => 42.00,
                'stock' => 100,
                'weight' => 0.15,
                'sticker' => 'organic',
                'status' => 'approved',
                'type' => 'physical',
                'sku' => 'VN-MOR-001',
                'categories' => ['suplementos-hierbas', 'bienestar-inmune-equinacea'],
                'image_file' => '1 moringa capsulas.png',
            ],
            [
                'name' => 'Aceite MCT',
                'description' => 'Aceite MCT puro extraído de coco, ideal para dietas keto y low-carb. Proporciona energía rápida, mejora la concentración y acelera el metabolismo. 100% natural, sin aditivos ni conservantes.',
                'short_description' => 'Aceite MCT puro de coco, ideal para keto',
                'price' => 45.00,
                'regular_price' => 55.00,
                'stock' => 60,
                'weight' => 0.5,
                'sticker' => 'natural',
                'status' => 'approved',
                'type' => 'physical',
                'sku' => 'VN-MCT-001',
                'categories' => ['digestion-abarrotes-aceites', 'suplementos-deportivos'],
                'image_file' => '1 vida natural aceite MCT.png',
            ],
            [
                'name' => 'Bebida de Almendras',
                'description' => 'Bebida vegetal de almendras, sin azúcar añadida ni lácteos. Rica en vitamina E, calcio y baja en calorías. Perfecta para desayunos, smoothies y como alternativa saludable a la leche tradicional.',
                'short_description' => 'Bebida vegetal de almendras sin azúcar',
                'price' => 18.00,
                'regular_price' => 22.00,
                'stock' => 80,
                'weight' => 1.0,
                'sticker' => 'vegan',
                'status' => 'approved',
                'type' => 'physical',
                'sku' => 'VN-BEB-001',
                'categories' => ['digestion-bebidas'],
                'image_file' => '1 vida natural bebida de almendras.png',
            ],
            [
                'name' => 'Colágeno Marino',
                'description' => 'Colágeno marino hidrolizado en polvo, péptidos de alta biodisponibilidad. Contribuye a la salud de la piel, articulaciones y huesos. Sin sabor, fácil de disolver en agua, jugos o smoothies.',
                'short_description' => 'Colágeno marino hidrolizado en polvo',
                'price' => 55.00,
                'regular_price' => 65.00,
                'stock' => 75,
                'weight' => 0.3,
                'sticker' => 'natural',
                'status' => 'approved',
                'type' => 'physical',
                'sku' => 'VN-COL-001',
                'categories' => ['bienestar-oseo-colageno', 'suplementos-proteinas'],
                'image_file' => '1 vida natural colageno marino.png',
            ],
        ];

        config(['media-library.max_file_size' => 20 * 1024 * 1024]);

        $destinationDir = storage_path('app/public/products/vida-natural');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        foreach ($products as $data) {
            $catSlugs = $data['categories'];
            $imageFile = $data['image_file'];
            unset($data['categories'], $data['image_file']);

            $slug = Str::slug($data['name']);
            $data['slug'] = $slug;

            if (Product::where('slug', $slug)->where('store_id', $store->id)->exists()) {
                $this->command->warn("Product '{$data['name']}' already exists. Skipping.");
                continue;
            }

            $data['store_id'] = $store->id;
            $data['discount_percentage'] = $data['regular_price'] > $data['price']
                ? round((($data['regular_price'] - $data['price']) / $data['regular_price']) * 100, 2)
                : 0;

            $product = Product::create($data);

            foreach ($catSlugs as $catSlug) {
                $category = Category::where('slug', $catSlug)->first();
                if ($category) {
                    $product->categories()->attach($category->id);
                }
            }

            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $imageFile;
            if (file_exists($sourcePath)) {
                $destPath = $destinationDir . DIRECTORY_SEPARATOR . $imageFile;
                copy($sourcePath, $destPath);
                $product->addMedia($destPath)
                    ->toMediaCollection('images');
                $this->command->info("  Image added for '{$data['name']}'");
            } else {
                $this->command->warn("  Image not found: {$sourcePath}");
            }

            $product->save();
        }

        $this->command->info('VidaNaturalSeeder: ' . count($products) . ' products seeded.');
    }
}
