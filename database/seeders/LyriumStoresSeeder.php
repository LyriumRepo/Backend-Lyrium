<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LyriumStoresSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVidaNatural();
        $this->seedVinaSol();
        $this->seedMasNatural();
        $this->seedAmoSpa();
        $this->seedFitBody();
        $this->seedCentroMedico();
        $this->seedPlazaMedic();
        $this->seedFisiocenter();
        $this->seedRydent();
        $this->seedSotomayor();
        $this->seedSanJuanDeDios();

        $this->command->info('✓ LyriumStoresSeeder completado: 11 tiendas.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function makeUser(string $email, string $name, string $phone, string $docNumber): User
    {
        $username = Str::slug($name) . '-' . substr($docNumber, -4);
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'username'          => $username,
                'nicename'          => Str::slug($name),
                'phone'             => $phone,
                'document_type'     => 'RUC',
                'document_number'   => $docNumber,
                'password'          => Hash::make('password'),
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
                'owner_id'    => $ownerId,
                'status'      => 'approved',
                'approved_at' => now(),
            ])
        );
    }

    private function catId(string $slug): int
    {
        // Try requested slug first, then any available service category
        $cat = Category::where('slug', $slug)->first()
            ?? Category::where('type', 'service')->first();

        if (! $cat) {
            // Create a minimal service category so the FK never fails
            $cat = Category::create([
                'name'       => 'Servicios',
                'slug'       => 'servicios-general-lyrium',
                'type'       => 'service',
                'sort_order' => 99,
            ]);
        }

        return $cat->id;
    }

    private function addProduct(Store $store, string $name, float $price, string $sku, array $categories, string $brand = '', int $stock = 30): void
    {
        $slug = Str::slug($name);

        if (Product::where('slug', $slug)->where('store_id', $store->id)->exists()) {
            $this->command->warn("  [skip] {$name}");
            return;
        }

        $product = Product::create([
            'store_id'          => $store->id,
            'name'              => $name,
            'slug'              => $slug,
            'description'       => $name . ($brand ? " — Marca: {$brand}." : '') . ' Producto de alta calidad seleccionado para el bienestar.',
            'short_description' => $name . ($brand ? " ({$brand})" : ''),
            'price'             => $price,
            'regular_price'     => round($price * 1.15, 2),
            'discount_percentage' => 13,
            'stock'             => $stock,
            'weight'            => 0.3,
            'status'            => 'approved',
            'type'              => 'physical',
            'sku'               => $sku,
        ]);

        foreach ($categories as $catSlug) {
            $category = Category::where('slug', $catSlug)->first();
            if ($category) {
                $product->categories()->attach($category->id);
            }
        }

        $this->command->info("  + {$name}");
    }

    private function addService(Store $store, string $name, float $price, string $catSlug, int $duration = 60): void
    {
        $slug = Str::slug($name);
        $counter = 1;
        $base = $slug;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        Service::create([
            'store_id'             => $store->id,
            'category_id'          => $this->catId($catSlug),
            'name'                 => $name,
            'slug'                 => $slug,
            'description'          => $name . '. Servicio profesional disponible con reserva previa.',
            'price'                => $price,
            'duration_minutes'     => $duration,
            'buffer_minutes'       => 15,
            'is_home_service'      => false,
            'booking_advance_hours' => 24,
            'max_capacity'         => 1,
            'status'               => Service::STATUS_ACTIVE,
            'cancellation_policy'  => 'flexible',
            'max_cancellations'    => 2,
        ]);

        $this->command->info("  + [svc] {$name}");
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 1: VIDA NATURAL — productos naturales
    // ──────────────────────────────────────────────────────────────────
    private function seedVidaNatural(): void
    {
        $user  = $this->makeUser('vida.natural@lyrium.com', 'Vida Natural', '999111001', '20111111112');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20111111112',
            'trade_name'       => 'Vida Natural',
            'razon_social'     => 'Vida Natural SAC',
            'nombre_comercial' => 'Vida Natural',
            'corporate_email'  => 'vida.natural@lyrium.com',
            'slug'             => 'vida-natural',
            'phone'            => '999111001',
            'seller_type'      => 'products',
            'rep_legal_nombre' => 'Vida Natural',
            'rep_legal_dni'    => '11111101',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Vida Natural',
            'address'          => 'Av. La Molina 100, Lima, Perú',
            'description'      => 'Tienda de productos naturales y orgánicos para tu bienestar.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 1: Vida Natural");

        $this->addProduct($store, 'Bebida de Almendra Vainilla sin azúcar Pack 3 Unid. Caja 946ml', 61.50, 'VN-BEV-001', ['digestion-bebidas'], 'Silk');
        $this->addProduct($store, 'Aceite MCT Coco 500 ml Keto', 90.00, 'VN-MCT-002', ['digestion-abarrotes-aceites'], 'Keto Line');
        $this->addProduct($store, 'Moringa Cápsulas x 400mg Pack 2 Unid. 100 caps', 70.00, 'VN-MOR-002', ['suplementos-hierbas'], 'Eco Valle');
        $this->addProduct($store, 'Hidrolato de Romero 50 ml', 70.00, 'VN-HID-001', ['belleza-mujeres-facial'], 'Bitoka');
        $this->addProduct($store, 'Espuma Limpiadora Facial Segle 150 ml', 55.00, 'VN-ESP-001', ['belleza-mujeres-facial'], 'Segle');
        $this->addProduct($store, 'Ultimate Omega Líquido 2840 mg', 275.00, 'VN-OMG-001', ['suplementos-hierbas'], 'Nordic Naturals', 15);
        $this->addProduct($store, 'Kids Probiotic Gummies 60 Gomitas', 80.00, 'VN-PRO-001', ['bienestar-digestivo-probioticos'], 'Nordic Naturals');
        $this->addProduct($store, 'Agave Orgánico 1.3 kg', 90.00, 'VN-AGA-001', ['digestion-abarrotes'], 'America Organica');
        $this->addProduct($store, 'Polvo de Cacao Natural', 50.00, 'VN-CAC-001', ['digestion-desayunos-cereales'], 'Choco Plus');
        $this->addProduct($store, 'Besitos Dark 72% sin azúcar 500 g', 65.00, 'VN-BST-001', ['digestion-dulces-chocolate'], 'Choco Plus');
        $this->addProduct($store, "Stevia D'Pais Pack x 3 Frascos", 116.00, 'VN-STV-001', ['digestion-abarrotes'], "D'Pais");
        $this->addProduct($store, 'Colágeno Marino Hidrolizado', 120.00, 'VN-COL-002', ['bienestar-oseo-colageno'], 'Drasanvi');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 2: VIÑA SOL — suplementos y nutrición
    // ──────────────────────────────────────────────────────────────────
    private function seedVinaSol(): void
    {
        $user  = $this->makeUser('vina.sol@lyrium.com', 'Viña Sol', '999222001', '20222222223');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20222222223',
            'trade_name'       => 'Viña Sol',
            'razon_social'     => 'Viña Sol EIRL',
            'nombre_comercial' => 'Viña Sol',
            'corporate_email'  => 'vina.sol@lyrium.com',
            'slug'             => 'vina-sol',
            'phone'            => '999222001',
            'seller_type'      => 'products',
            'rep_legal_nombre' => 'Viña Sol',
            'rep_legal_dni'    => '22222201',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Viña Sol',
            'address'          => 'Av. Javier Prado 200, San Isidro, Lima, Perú',
            'description'      => 'Tienda especializada en suplementos y nutrición natural.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 2: Viña Sol");

        $this->addProduct($store, 'Pecanas Peladas Enteras 1 Kg', 79.90, 'VS-PEC-001', ['digestion-dulces-frutos'], 'Viña Sol');
        $this->addProduct($store, 'Eritritol 500 g', 48.50, 'VS-ERI-001', ['digestion-abarrotes'], 'Kera Super Foods');
        $this->addProduct($store, 'Wawa Vitality Chocolate 900 g', 88.00, 'VS-WAW-001', ['suplementos-proteinas'], 'Amazon Andes');
        $this->addProduct($store, 'Cápsulas de Colágeno + Camu Camu 300 g', 78.00, 'VS-COL-001', ['bienestar-oseo-colageno'], 'Energy Green');
        $this->addProduct($store, 'Cápsulas de Spirulina y Noni', 50.00, 'VS-SPI-001', ['suplementos-hierbas'], 'Energy Green');
        $this->addProduct($store, 'Maca Negra + Creatina 300 g', 75.00, 'VS-MAC-001', ['suplementos-deportivos-creatina'], 'Energy Green');
        $this->addProduct($store, 'Citrato de Magnesio 250 g', 50.00, 'VS-MAG-001', ['suplementos-minerales-magnesio'], 'Eco Valle');
        $this->addProduct($store, 'Melatonina 50 ml', 45.00, 'VS-MEL-001', ['bienestar-nervioso-sueno'], 'Drasanvi');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 3: MÁS NATURAL — productos naturales y suplementos
    // ──────────────────────────────────────────────────────────────────
    private function seedMasNatural(): void
    {
        $user  = $this->makeUser('mas.natural@lyrium.com', 'Más Natural', '999333001', '20333333334');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20333333334',
            'trade_name'       => 'Más Natural',
            'razon_social'     => 'Más Natural SAC',
            'nombre_comercial' => 'Más Natural',
            'corporate_email'  => 'mas.natural@lyrium.com',
            'slug'             => 'mas-natural',
            'phone'            => '999333001',
            'seller_type'      => 'products',
            'rep_legal_nombre' => 'Más Natural',
            'rep_legal_dni'    => '33333301',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Más Natural',
            'address'          => 'Calle Monte 300, Miraflores, Lima, Perú',
            'description'      => 'Productos naturales y suplementos de calidad.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 3: Más Natural");

        $this->addProduct($store, 'Colágeno Hidrolizado en Gel', 80.00, 'MN-COL-001', ['bienestar-oseo-colageno'], 'Plameca');
        $this->addProduct($store, 'Organic Plant Protein 1 Kg', 100.00, 'MN-PRT-001', ['suplementos-proteinas-vegetal'], 'Biocharger');
        $this->addProduct($store, 'L-Carnitine x 60 Tabletas', 70.00, 'MN-CAR-001', ['suplementos-deportivos'], 'Mason Natural');
        $this->addProduct($store, 'Aloe Vera Tópico 200 ml', 69.00, 'MN-ALO-001', ['belleza-mujeres-corporal'], 'Plameca');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 4: AMÓ SPA — spa y estética
    // ──────────────────────────────────────────────────────────────────
    private function seedAmoSpa(): void
    {
        $user  = $this->makeUser('amo.spa@lyrium.com', 'Amó Spa', '999444001', '20444444445');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20444444445',
            'trade_name'       => 'Amó Spa',
            'razon_social'     => 'Amó Spa EIRL',
            'nombre_comercial' => 'Amó Spa',
            'corporate_email'  => 'amo.spa@lyrium.com',
            'slug'             => 'amo-spa',
            'phone'            => '999444001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'Amó Spa',
            'rep_legal_dni'    => '44444401',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Amó Spa',
            'address'          => 'Av. Larco 400, Miraflores, Lima, Perú',
            'description'      => 'Spa y estética con tratamientos de belleza y bienestar.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 4: Amó Spa");

        $this->addService($store, 'Limpieza Hydrafacial', 150.00, 'servicios-belleza-spas', 75);
        $this->addService($store, 'Botox Capilar Orgánico', 220.00, 'servicios-belleza-spas', 90);
        $this->addService($store, 'Limpieza Hyalu-C', 120.00, 'servicios-belleza-spas', 60);
        $this->addService($store, 'Manicure', 40.00, 'servicios-belleza-spas', 45);
        $this->addService($store, 'Pedicura', 60.00, 'servicios-belleza-spas', 60);
        $this->addService($store, 'Masaje Corporal', 80.00, 'servicios-belleza-spas', 60);
        $this->addService($store, 'Exfoliación Corporal', 150.00, 'servicios-belleza-spas', 75);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 5: FIT BODY — gimnasio y entrenamiento
    // ──────────────────────────────────────────────────────────────────
    private function seedFitBody(): void
    {
        $user  = $this->makeUser('fit.body@lyrium.com', 'Fit Body', '999555001', '20555555556');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20555555556',
            'trade_name'       => 'Fit Body',
            'razon_social'     => 'Fit Body SAC',
            'nombre_comercial' => 'Fit Body',
            'corporate_email'  => 'fit.body@lyrium.com',
            'slug'             => 'fit-body',
            'phone'            => '999555001',
            'seller_type'      => 'both',
            'rep_legal_nombre' => 'Fit Body',
            'rep_legal_dni'    => '55555501',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Fit Body',
            'address'          => 'Av. Benavides 500, Surco, Lima, Perú',
            'description'      => 'Gimnasio y tienda de suplementos deportivos para tu entrenamiento.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 5: Fit Body");

        // Servicios
        $this->addService($store, 'Master Class Funcional', 350.00, 'servicios-deportes-gimnasios', 90);
        $this->addService($store, 'Clases de Zumba', 200.00, 'servicios-deportes-gimnasios', 60);
        $this->addService($store, 'Boxeo', 180.00, 'servicios-deportes-gimnasios', 60);
        $this->addService($store, 'Personal Trainer', 450.00, 'servicios-deportes-gimnasios', 60);
        $this->addService($store, 'Mensualidad Gimnasio', 350.00, 'servicios-deportes-gimnasios', 60);

        // Productos
        $this->addProduct($store, 'ISO-XP Whey Protein Isolate Vainilla 1.8 kg', 340.00, 'FB-WHY-001', ['suplementos-proteinas-whey'], 'Applied Nutrition', 20);
        $this->addProduct($store, 'Creatina Monohidratada Kevin Levrone 300 g + M6Teen', 99.00, 'FB-CRE-001', ['suplementos-deportivos-creatina'], 'Kevin Levrone');
        $this->addProduct($store, 'Intra Entrenamiento Caramelo Fruta del Dragón 1.6 lb', 170.00, 'FB-INT-001', ['suplementos-deportivos-electrolitos'], 'Nutra Bio');
        $this->addProduct($store, 'Melatonina Rapid Release 10 mg 120 cápsulas', 130.00, 'FB-MEL-001', ['bienestar-nervioso-sueno'], "Puritan's Pride");
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 6: CENTRO MÉDICO DIGITAL — centro médico
    // ──────────────────────────────────────────────────────────────────
    private function seedCentroMedico(): void
    {
        $user  = $this->makeUser('centro.medico@lyrium.com', 'Centro Médico Digital', '999666001', '20666666667');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20666666667',
            'trade_name'       => 'Centro Médico Digital',
            'razon_social'     => 'Centro Médico Digital SAC',
            'nombre_comercial' => 'Centro Médico Digital',
            'corporate_email'  => 'centro.medico@lyrium.com',
            'slug'             => 'centro-medico-digital',
            'phone'            => '999666001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'Centro Médico Digital',
            'rep_legal_dni'    => '66666601',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Centro Médico Digital',
            'address'          => 'Av. Brasil 600, Jesús María, Lima, Perú',
            'description'      => 'Centro médico especializado en diagnóstico y atención integral.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 6: Centro Médico Digital");

        $this->addService($store, 'Examen Cardiovascular', 190.00, 'servicios-cardiologia', 60);
        $this->addService($store, 'Examen de Hemoglobina', 50.00, 'servicios-salud', 30);
        $this->addService($store, 'Tomografía Multicorte', 500.00, 'servicios-radiologia', 45);
        $this->addService($store, 'Ecografía Obstétrica', 80.00, 'servicios-ginecologia', 30);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 7: PLAZA MEDIC — equipos médicos
    // ──────────────────────────────────────────────────────────────────
    private function seedPlazaMedic(): void
    {
        $user  = $this->makeUser('plaza.medic@lyrium.com', 'Plaza Medic', '999777001', '20777777778');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20777777778',
            'trade_name'       => 'Plaza Medic',
            'razon_social'     => 'Plaza Medic EIRL',
            'nombre_comercial' => 'Plaza Medic',
            'corporate_email'  => 'plaza.medic@lyrium.com',
            'slug'             => 'plaza-medic',
            'phone'            => '999777001',
            'seller_type'      => 'products',
            'rep_legal_nombre' => 'Plaza Medic',
            'rep_legal_dni'    => '77777701',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Plaza Medic',
            'address'          => 'Av. Universitaria 700, Lima, Perú',
            'description'      => 'Tienda especializada en equipos y dispositivos médicos.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 7: Plaza Medic");

        $this->addProduct($store, 'Silla de Ruedas', 900.00, 'PM-SRU-001', ['equipos-movilidad-sillas'], '', 10);
        $this->addProduct($store, 'Electroestimulador Digital Ondas TENS', 120.00, 'PM-TEN-001', ['equipos-rehabilitacion-fisioterapia'], 'Perumassage');
        $this->addProduct($store, 'Muletas Ortopédicas de Aluminio', 120.00, 'PM-MUL-001', ['equipos-movilidad-bastones']);
        $this->addProduct($store, 'Glucómetro Accu-Chek Instant', 180.00, 'PM-GLU-001', ['equipos-diagnostico-glucometros']);
        $this->addProduct($store, 'Tensiómetro de Brazo BP1307', 110.00, 'PM-TEN-002', ['equipos-diagnostico-tensiometros']);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 8: FISIOCENTER — fisioterapia y rehabilitación
    // ──────────────────────────────────────────────────────────────────
    private function seedFisiocenter(): void
    {
        $user  = $this->makeUser('fisiocenter@lyrium.com', 'Fisiocenter', '999888001', '20888888889');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20888888889',
            'trade_name'       => 'Fisiocenter',
            'razon_social'     => 'Fisiocenter SAC',
            'nombre_comercial' => 'Fisiocenter',
            'corporate_email'  => 'fisiocenter@lyrium.com',
            'slug'             => 'fisiocenter',
            'phone'            => '999888001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'Fisiocenter',
            'rep_legal_dni'    => '88888801',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Fisiocenter',
            'address'          => 'Av. Angamos 800, Surquillo, Lima, Perú',
            'description'      => 'Centro de fisioterapia y rehabilitación física integral.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 8: Fisiocenter");

        $this->addService($store, 'Electroterapia', 150.00, 'servicios-medicina-fisica', 45);
        $this->addService($store, 'Termoterapia', 120.00, 'servicios-medicina-fisica', 45);
        $this->addService($store, 'Crioterapia', 250.00, 'servicios-medicina-fisica', 60);
        $this->addService($store, 'Terapia Neurológica', 180.00, 'servicios-neurologia', 60);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 9: RYDENT — odontología
    // ──────────────────────────────────────────────────────────────────
    private function seedRydent(): void
    {
        $user  = $this->makeUser('rydent@lyrium.com', 'Rydent', '999901001', '20999999991');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20999999991',
            'trade_name'       => 'Rydent',
            'razon_social'     => 'Rydent SAC',
            'nombre_comercial' => 'Rydent',
            'corporate_email'  => 'rydent@lyrium.com',
            'slug'             => 'rydent',
            'phone'            => '999901001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'Rydent',
            'rep_legal_dni'    => '99999901',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Rydent',
            'address'          => 'Av. Reducto 900, Miraflores, Lima, Perú',
            'description'      => 'Clínica dental especializada en odontología moderna.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 9: Rydent");

        $this->addService($store, 'Ortodoncia con Brackets', 2000.00, 'servicios-odontologia', 60);
        $this->addService($store, 'Blanqueamiento Dental', 120.00, 'servicios-odontologia', 60);
        $this->addService($store, 'Curación Temporal', 80.00, 'servicios-odontologia', 30);
        $this->addService($store, 'Profilaxis / Destartraje / Flúor', 50.00, 'servicios-odontologia', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 10: SOTOMAYOR — oftalmología
    // ──────────────────────────────────────────────────────────────────
    private function seedSotomayor(): void
    {
        $user  = $this->makeUser('sotomayor@lyrium.com', 'Sotomayor', '999902001', '20100000009');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20100000009',
            'trade_name'       => 'Sotomayor',
            'razon_social'     => 'Sotomayor SAC',
            'nombre_comercial' => 'Sotomayor',
            'corporate_email'  => 'sotomayor@lyrium.com',
            'slug'             => 'sotomayor',
            'phone'            => '999902001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'Sotomayor',
            'rep_legal_dni'    => '10000001',
            'tax_condition'    => 'RUC',
            'store_name'       => 'Sotomayor',
            'address'          => 'Av. Salaverry 1000, Jesús María, Lima, Perú',
            'description'      => 'Clínica oftalmológica de alto nivel con tecnología de vanguardia.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 10: Sotomayor");

        $this->addService($store, 'Gonioscopía', 60.00, 'servicios-oftalmologia', 30);
        $this->addService($store, 'Examen de Miopía', 60.00, 'servicios-oftalmologia', 30);
        $this->addService($store, 'Angiografía Retina Unilateral', 170.00, 'servicios-oftalmologia', 45);
        $this->addService($store, 'Lavado y Sondeo del Tracto Lagrimal', 80.00, 'servicios-oftalmologia', 30);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 11: SAN JUAN DE DIOS — salud mental
    // ──────────────────────────────────────────────────────────────────
    private function seedSanJuanDeDios(): void
    {
        $user  = $this->makeUser('sanjuandedios@lyrium.com', 'San Juan de Dios Psiquiatría', '999903001', '20110000007');
        $store = $this->makeStore($user->id, [
            'ruc'              => '20110000007',
            'trade_name'       => 'San Juan de Dios - Psiquiatría',
            'razon_social'     => 'San Juan de Dios SAC',
            'nombre_comercial' => 'San Juan de Dios - Psiquiatría',
            'corporate_email'  => 'sanjuandedios@lyrium.com',
            'slug'             => 'san-juan-de-dios-psiquiatria',
            'phone'            => '999903001',
            'seller_type'      => 'services',
            'rep_legal_nombre' => 'San Juan de Dios',
            'rep_legal_dni'    => '11000001',
            'tax_condition'    => 'RUC',
            'store_name'       => 'San Juan de Dios - Psiquiatría',
            'address'          => 'Av. Guardia Civil 1100, San Borja, Lima, Perú',
            'description'      => 'Centro especializado en salud mental, psicología y psiquiatría.',
            'commission_rate'  => 0.1000,
        ]);
        $this->command->info("Tienda 11: San Juan de Dios - Psiquiatría");

        $this->addService($store, 'Terapia Psicológica', 80.00, 'servicios-psiquiatria', 60);
        $this->addService($store, 'Terapia Psiquiátrica', 150.00, 'servicios-psiquiatria', 60);
        $this->addService($store, 'Programas de Rehabilitación Integral', 60.00, 'servicios-psiquiatria', 90);
        $this->addService($store, 'Diagnóstico Unipolar', 120.00, 'servicios-psiquiatria', 60);
    }
}
