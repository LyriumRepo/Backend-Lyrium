<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DemoStoresSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVidaNatural();
        $this->seedBienestarTotal();
        $this->seedNaturalezaViva();

        $this->command?->info('✓ DemoStoresSeeder: 3 tiendas, 24 productos, 6 servicios.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function makeUser(string $email, string $name, string $phone, string $docNumber): User
    {
        $username = Str::slug($name).'-'.substr($docNumber, -4);
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'nicename' => Str::slug($name),
                'phone' => $phone,
                'document_type' => 'RUC',
                'document_number' => $docNumber,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $user->hasRole('seller')) {
            $user->assignRole('seller');
        }

        return $user;
    }

    private function makeStore(int $ownerId, array $data): Store
    {
        return Store::updateOrCreate(
            ['ruc' => $data['ruc']],
            array_merge($data, [
                'owner_id' => $ownerId,
                'status' => 'approved',
                'approved_at' => now(),
            ])
        );
    }

    /**
     * `store_branches.city` es NOT NULL en la BD pero no está en $fillable
     * del modelo (columna legacy reemplazada por department/province/district).
     * Por eso se usa forceFill en vez de updateOrCreate/create.
     */
    private function makeBranch(Store $store): void
    {
        $branch = $store->branches()->where('is_principal', true)->first()
            ?? new StoreBranch(['store_id' => $store->id]);

        $district = 'San Isidro';

        $branch->forceFill([
            'store_id' => $store->id,
            'name' => 'Sede Principal',
            'address' => $store->address ?? 'Av. Principal 123',
            'city' => $district,
            'department' => 'Lima',
            'province' => 'Lima',
            'district' => $district,
            'phone' => $store->phone ?? '999000000',
            'hours' => '09:00 - 18:00',
            'is_principal' => true,
            'is_active' => true,
        ])->save();
    }

    private function addProduct(Store $store, string $name, float $price, string $sku, array $categories, int $stock = 30): void
    {
        $slug = Str::slug($name);

        if (Product::where('slug', $slug)->where('store_id', $store->id)->exists()) {
            return;
        }

        $product = Product::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $name.'. Producto de alta calidad para tu bienestar.',
            'short_description' => $name,
            'price' => $price,
            'regular_price' => round($price * 1.15, 2),
            'discount_percentage' => 13,
            'stock' => $stock,
            'weight' => 0.3,
            'status' => 'approved',
            'type' => 'physical',
        ]);

        // 'sku' no está en $fillable de Product; se fuerza aparte para no perderlo.
        $product->forceFill(['sku' => $sku])->save();

        foreach ($categories as $catSlug) {
            $category = Category::where('slug', $catSlug)->first();
            if ($category) {
                $product->categories()->attach($category->id);
            }
        }
    }

    private function addService(Store $store, string $name, float $price, string $catSlug, int $duration = 60): void
    {
        if (Service::where('store_id', $store->id)->where('name', $name)->exists()) {
            return;
        }

        $slug = Str::slug($name);
        $counter = 1;
        $base = $slug;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        $cat = Category::where('slug', $catSlug)->first()
            ?? Category::where('type', 'service')->first();

        Service::create([
            'store_id' => $store->id,
            'category_id' => $cat?->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $name.'. Servicio profesional disponible con reserva previa.',
            'price' => $price,
            'duration_minutes' => $duration,
            'buffer_minutes' => 15,
            'is_home_service' => false,
            'booking_advance_hours' => 24,
            'max_capacity' => 1,
            'status' => Service::STATUS_ACTIVE,
            'cancellation_policy' => 'flexible',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 1: VIDA NATURAL — productos naturales
    // ──────────────────────────────────────────────────────────────────
    private function seedVidaNatural(): void
    {
        $user = $this->makeUser('vida.demo@lyrium.com', 'Vida Natural Demo', '999551001', '20551111114');
        $store = $this->makeStore($user->id, [
            'ruc' => '20551111114',
            'trade_name' => 'Vida Natural',
            'razon_social' => 'Vida Natural Demo SAC',
            'nombre_comercial' => 'Vida Natural',
            'corporate_email' => 'vida.demo@lyrium.com',
            'slug' => 'vida-natural-demo',
            'phone' => '999551001',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Vida Natural Demo',
            'rep_legal_dni' => '55111101',
            'tax_condition' => 'RUC',
            'store_name' => 'Vida Natural',
            'address' => 'Av. La Molina 100, Lima, Perú',
            'description' => 'Tienda de productos naturales y orgánicos para tu bienestar.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store);
        $this->command?->info('Tienda 1: Vida Natural');

        $this->addProduct($store, 'Moringa Cápsulas', 70.00, 'VND-MOR-001', ['suplementos-hierbas-equinacea']);
        $this->addProduct($store, 'Aceite MCT', 90.00, 'VND-MCT-001', ['digestion-abarrotes-aceites']);
        $this->addProduct($store, 'Bebida de Almendras', 45.00, 'VND-ALM-001', ['digestion-bebidas']);
        $this->addProduct($store, 'Colágeno Marino', 120.00, 'VND-COL-001', ['bienestar-oseo-colageno']);
        $this->addProduct($store, 'Probióticos', 80.00, 'VND-PRO-001', ['bienestar-digestivo-probioticos']);
        $this->addProduct($store, 'Vitamina C', 55.00, 'VND-VTC-001', ['suplementos-vitaminas-c', 'bienestar-inmune-vitamina-c']);
        $this->addProduct($store, 'Té Verde Matcha', 65.00, 'VND-MAT-001', ['digestion-bebidas']);
        $this->addProduct($store, 'Jabón de Castilla', 35.00, 'VND-JAB-001', ['belleza-mujeres-corporal']);

        $this->addService($store, 'Limpieza Facial Natural', 90.00, 'servicios-belleza-limpieza', 60);
        $this->addService($store, 'Asesoría Nutricional', 100.00, 'servicios-nutriologia', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 2: BIENESTAR TOTAL — suplementos deportivos
    // ──────────────────────────────────────────────────────────────────
    private function seedBienestarTotal(): void
    {
        $user = $this->makeUser('bienestar.demo@lyrium.com', 'Bienestar Total Demo', '999552002', '20552222221');
        $store = $this->makeStore($user->id, [
            'ruc' => '20552222221',
            'trade_name' => 'Bienestar Total',
            'razon_social' => 'Bienestar Total Demo SAC',
            'nombre_comercial' => 'Bienestar Total',
            'corporate_email' => 'bienestar.demo@lyrium.com',
            'slug' => 'bienestar-total',
            'phone' => '999552002',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Bienestar Total Demo',
            'rep_legal_dni' => '55222201',
            'tax_condition' => 'RUC',
            'store_name' => 'Bienestar Total',
            'address' => 'Av. Benavides 500, Surco, Lima, Perú',
            'description' => 'Suplementos deportivos y nutrición para tu entrenamiento.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store);
        $this->command?->info('Tienda 2: Bienestar Total');

        $this->addProduct($store, 'Whey Protein', 180.00, 'BTD-WHY-001', ['suplementos-proteinas-whey']);
        $this->addProduct($store, 'Creatina', 95.00, 'BTD-CRE-001', ['suplementos-deportivos-creatina']);
        $this->addProduct($store, 'Magnesio', 50.00, 'BTD-MAG-001', ['suplementos-minerales-magnesio']);
        $this->addProduct($store, 'Zinc', 45.00, 'BTD-ZNC-001', ['bienestar-inmune-zinc']);
        $this->addProduct($store, 'Proteína Vegetal', 150.00, 'BTD-PRV-001', ['suplementos-proteinas-vegetal']);
        $this->addProduct($store, 'BCAA', 110.00, 'BTD-BCA-001', ['suplementos-deportivos-electrolitos']);
        $this->addProduct($store, 'Pre-entreno', 130.00, 'BTD-PRE-001', ['suplementos-deportivos-creatina', 'suplementos-deportivos-electrolitos']);
        $this->addProduct($store, 'Omega 3', 85.00, 'BTD-OMG-001', ['bienestar-nervioso-sueno']);

        $this->addService($store, 'Entrenamiento Personalizado', 200.00, 'servicios-deportes-entrenamiento', 60);
        $this->addService($store, 'Plan Nutricional', 120.00, 'servicios-deportes-nutricion-planes', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 3: NATURALEZA VIVA — mixto productos + servicios
    // ──────────────────────────────────────────────────────────────────
    private function seedNaturalezaViva(): void
    {
        $user = $this->makeUser('naturaleza.demo@lyrium.com', 'Naturaleza Viva Demo', '999553003', '20553333335');
        $store = $this->makeStore($user->id, [
            'ruc' => '20553333335',
            'trade_name' => 'Naturaleza Viva',
            'razon_social' => 'Naturaleza Viva Demo SAC',
            'nombre_comercial' => 'Naturaleza Viva',
            'corporate_email' => 'naturaleza.demo@lyrium.com',
            'slug' => 'naturaleza-viva',
            'phone' => '999553003',
            'seller_type' => 'both',
            'rep_legal_nombre' => 'Naturaleza Viva Demo',
            'rep_legal_dni' => '55333301',
            'tax_condition' => 'RUC',
            'store_name' => 'Naturaleza Viva',
            'address' => 'Av. Larco 400, Miraflores, Lima, Perú',
            'description' => 'Productos naturales y terapias de bienestar integral.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store);
        $this->command?->info('Tienda 3: Naturaleza Viva');

        $this->addProduct($store, 'Valeriana', 40.00, 'NVD-VAL-001', ['suplementos-hierbas-valeriana']);
        $this->addProduct($store, 'Equinácea', 42.00, 'NVD-EQU-001', ['suplementos-hierbas-equinacea']);
        $this->addProduct($store, 'Aloe Vera', 38.00, 'NVD-ALO-001', ['belleza-mujeres-corporal']);
        $this->addProduct($store, 'Cacao Orgánico', 48.00, 'NVD-CAC-001', ['digestion-dulces-chocolate']);
        $this->addProduct($store, 'Miel de Abeja', 55.00, 'NVD-MIE-001', ['digestion-dulces-frutos']);
        $this->addProduct($store, 'Semillas de Chía', 32.00, 'NVD-CHI-001', ['digestion-desayunos-cereales']);
        $this->addProduct($store, 'Aceite de Coco', 60.00, 'NVD-COC-001', ['digestion-abarrotes-aceites']);
        $this->addProduct($store, 'Pastillas de Spirulina', 58.00, 'NVD-SPI-001', ['suplementos-minerales-magnesio']);

        $this->addService($store, 'Masaje Relajante', 110.00, 'servicios-belleza-masajes', 60);
        $this->addService($store, 'Terapia Holística', 130.00, 'servicios-psicologia', 60);
    }
}
