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

final class DemoStoresWithImagesSeeder extends Seeder
{
    /** Carpeta con las imágenes fuente, hermana de Backend-Lyrium/ dentro de PROYECTO_LYRIUM/. */
    private string $imagesBasePath;

    public function __construct()
    {
        $this->imagesBasePath = dirname(base_path()).DIRECTORY_SEPARATOR.'PRODUCTOS Y SERVICIOS';
    }

    public function run(): void
    {
        if (! is_dir($this->imagesBasePath)) {
            $this->command?->warn("DemoStoresWithImagesSeeder: no se encontró la carpeta de imágenes en {$this->imagesBasePath}, se omite el seed.");

            return;
        }

        // Las fotos de origen pesan más que el límite por defecto de MediaLibrary (10MB).
        // Se sube solo para este proceso de artisan; no persiste ni afecta subidas reales de usuarios.
        config(['media-library.max_file_size' => 30 * 1024 * 1024]);

        $this->seedVidaNatural();
        $this->seedVinaSol();
        $this->seedMasNatural();
        $this->seedAmoSpa();
        $this->seedFitBody();
        $this->seedCentroMedicoDigital();
        $this->seedPlazaMedic();
        $this->seedFisiocenter();
        $this->seedRydent();
        $this->seedSotomayor();
        $this->seedSanJuanDeDios();

        $this->command?->info('✓ DemoStoresWithImagesSeeder: 11 tiendas con imágenes sembradas.');
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
     */
    private function makeBranch(Store $store, string $department, string $province, string $district): void
    {
        $branch = $store->branches()->where('is_principal', true)->first()
            ?? new StoreBranch(['store_id' => $store->id]);

        $branch->forceFill([
            'store_id' => $store->id,
            'name' => 'Sede Principal',
            'address' => $store->address ?? 'Av. Principal 123',
            'city' => $district,
            'department' => $department,
            'province' => $province,
            'district' => $district,
            'phone' => $store->phone ?? '999000000',
            'hours' => '09:00 - 20:00',
            'is_principal' => true,
            'is_active' => true,
        ])->save();
    }

    /**
     * Adjunta una imagen de la carpeta fuente al modelo vía Spatie MediaLibrary.
     * `preservingOriginal()` es obligatorio: sin él, Spatie MUEVE (borra) el
     * archivo original de la carpeta fuente al agregarlo a la librería.
     */
    private function attachImage($model, string $folder, string $filename): void
    {
        $path = $this->imagesBasePath.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            $this->command?->warn("  [img missing] {$folder}/{$filename}");

            return;
        }

        try {
            $model->addMedia($path)
                ->preservingOriginal()
                ->toMediaCollection('images');
        } catch (\Throwable $e) {
            $this->command?->warn("  [img error] {$folder}/{$filename}: {$e->getMessage()}");
        }
    }

    private function addProduct(Store $store, string $name, float $price, string $sku, string $catSlug, string $folder, string $imgFile, int $stock = 30): void
    {
        $slug = Str::slug($name);

        $product = Product::where('slug', $slug)->where('store_id', $store->id)->first();

        if (! $product) {
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

            $category = Category::where('slug', $catSlug)->first();
            if ($category) {
                $product->categories()->attach($category->id);
            }

            $this->command?->info("  + {$name}");
        }

        // Reintenta adjuntar la imagen si en una corrida previa falló (p. ej. por tamaño).
        if ($product->getMedia('images')->isEmpty()) {
            $this->attachImage($product, $folder, $imgFile);
        }
    }

    private function addService(Store $store, string $name, float $price, string $catSlug, string $folder, string $imgFile, int $duration = 60): void
    {
        $service = Service::where('store_id', $store->id)->where('name', $name)->first();

        if (! $service) {
            $slug = Str::slug($name);
            $counter = 1;
            $base = $slug;
            while (Service::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$counter++;
            }

            $cat = Category::where('slug', $catSlug)->first()
                ?? Category::where('type', 'service')->first();

            $service = Service::create([
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

            $this->command?->info("  + [svc] {$name}");
        }

        // Reintenta adjuntar la imagen si en una corrida previa falló (p. ej. por tamaño).
        if ($service->getMedia('images')->isEmpty()) {
            $this->attachImage($service, $folder, $imgFile);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 1: VIDA NATURAL — productos naturales
    // ──────────────────────────────────────────────────────────────────
    private function seedVidaNatural(): void
    {
        $user = $this->makeUser('vida.natural@lyrium.com', 'Vida Natural', '999111001', '20111111112');
        $store = $this->makeStore($user->id, [
            'ruc' => '20111111112',
            'trade_name' => 'Vida Natural',
            'razon_social' => 'Vida Natural SAC',
            'nombre_comercial' => 'Vida Natural',
            'corporate_email' => 'vida.natural@lyrium.com',
            'slug' => 'vida-natural',
            'phone' => '999111001',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Vida Natural',
            'rep_legal_dni' => '11111101',
            'tax_condition' => 'RUC',
            'store_name' => 'Vida Natural',
            'address' => 'Av. La Molina 100, Lima, Perú',
            'description' => 'Tienda de productos naturales y orgánicos para tu bienestar.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'La Molina');
        $this->command?->info('Tienda 1: Vida Natural');

        $folder = '1 VIDA NATURAL';
        $this->addProduct($store, 'Moringa Cápsulas', 70.00, 'VN-MOR-001', 'suplementos-hierbas', $folder, '1 moringa capsulas.png');
        $this->addProduct($store, 'Aceite MCT', 90.00, 'VN-MCT-001', 'digestion-abarrotes-aceites', $folder, '1 vida natural aceite MCT.png');
        $this->addProduct($store, 'Bebida de Almendras', 61.50, 'VN-BEV-001', 'digestion-bebidas', $folder, '1 vida natural bebida de almendras.png');
        $this->addProduct($store, 'Colágeno Marino', 120.00, 'VN-COL-001', 'bienestar-oseo-colageno', $folder, '1 vida natural colageno marino.png');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 2: VIÑA SOL — suplementos y nutrición
    // ──────────────────────────────────────────────────────────────────
    private function seedVinaSol(): void
    {
        $user = $this->makeUser('vina.sol@lyrium.com', 'Viña Sol', '999222001', '20222222223');
        $store = $this->makeStore($user->id, [
            'ruc' => '20222222223',
            'trade_name' => 'Viña Sol',
            'razon_social' => 'Viña Sol EIRL',
            'nombre_comercial' => 'Viña Sol',
            'corporate_email' => 'vina.sol@lyrium.com',
            'slug' => 'vina-sol',
            'phone' => '999222001',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Viña Sol',
            'rep_legal_dni' => '22222201',
            'tax_condition' => 'RUC',
            'store_name' => 'Viña Sol',
            'address' => 'Av. Javier Prado 200, San Isidro, Lima, Perú',
            'description' => 'Tienda especializada en suplementos y nutrición natural.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'San Isidro');
        $this->command?->info('Tienda 2: Viña Sol');

        $folder = '2 VIÑA SOL';
        $this->addProduct($store, 'Cápsulas de Espirulina', 50.00, 'VS-SPI-001', 'suplementos-hierbas', $folder, '2 CAPSULAS DE ESPIRULINA.jpeg');
        $this->addProduct($store, 'Eritritol', 48.50, 'VS-ERI-001', 'digestion-abarrotes', $folder, '2 ERITITOL.jpeg');
        $this->addProduct($store, 'Maca Negra', 75.00, 'VS-MAC-001', 'suplementos-deportivos-creatina', $folder, '2 MACA NEGRA.jpeg');
        $this->addProduct($store, 'Pecanas Peladas', 79.90, 'VS-PEC-001', 'suplementos-minerales-magnesio', $folder, '2 PECANAS PELADAS.jpeg');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 3: MÁS NATURAL — productos naturales y suplementos
    // ──────────────────────────────────────────────────────────────────
    private function seedMasNatural(): void
    {
        $user = $this->makeUser('mas.natural@lyrium.com', 'Más Natural', '999333001', '20333333334');
        $store = $this->makeStore($user->id, [
            'ruc' => '20333333334',
            'trade_name' => 'Más Natural',
            'razon_social' => 'Más Natural SAC',
            'nombre_comercial' => 'Más Natural',
            'corporate_email' => 'mas.natural@lyrium.com',
            'slug' => 'mas-natural',
            'phone' => '999333001',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Más Natural',
            'rep_legal_dni' => '33333301',
            'tax_condition' => 'RUC',
            'store_name' => 'Más Natural',
            'address' => 'Calle Monte 300, Miraflores, Lima, Perú',
            'description' => 'Productos naturales y suplementos de calidad.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Miraflores');
        $this->command?->info('Tienda 3: Más Natural');

        $folder = '3 MAS NATURAL';
        $this->addProduct($store, 'Aloe Vera', 69.00, 'MN-ALO-001', 'belleza-mujeres-corporal', $folder, '3 ALOE VERUM.png');
        $this->addProduct($store, 'Citrato de Magnesio', 50.00, 'MN-MAG-001', 'suplementos-minerales-magnesio', $folder, '3 CITRATO DE MAGNESIO.png');
        $this->addProduct($store, 'L-Carnitine', 70.00, 'MN-CAR-001', 'suplementos-deportivos', $folder, '3 L-CARNITINE.jpeg');
        $this->addProduct($store, 'Melatonina', 45.00, 'MN-MEL-001', 'bienestar-nervioso-sueno', $folder, '3 Melatonina .jpeg');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 4: AMÓ SPA — spa y estética
    // ──────────────────────────────────────────────────────────────────
    private function seedAmoSpa(): void
    {
        $user = $this->makeUser('amo.spa@lyrium.com', 'Amó Spa', '999444001', '20444444445');
        $store = $this->makeStore($user->id, [
            'ruc' => '20444444445',
            'trade_name' => 'Amó Spa',
            'razon_social' => 'Amó Spa EIRL',
            'nombre_comercial' => 'Amó Spa',
            'corporate_email' => 'amo.spa@lyrium.com',
            'slug' => 'amo-spa',
            'phone' => '999444001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Amó Spa',
            'rep_legal_dni' => '44444401',
            'tax_condition' => 'RUC',
            'store_name' => 'Amó Spa',
            'address' => 'Av. Larco 400, Miraflores, Lima, Perú',
            'description' => 'Spa y estética con tratamientos de belleza y bienestar.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Miraflores');
        $this->command?->info('Tienda 4: Amó Spa');

        $folder = '4 AMO SPA';
        $this->addService($store, 'Botox Orgánico', 220.00, 'servicios-belleza-antiaging', $folder, '4 BOTOX ORGANICO.png', 90);
        $this->addService($store, 'Exfoliación Corporal', 150.00, 'servicios-belleza-corporales', $folder, '4 EXFOLIACION CORPORAL.png', 75);
        $this->addService($store, 'Limpieza Hidrafacial', 150.00, 'servicios-belleza-faciales', $folder, '4 LIMPIEZA HIDRAFACIAL.png', 75);
        $this->addService($store, 'Limpieza Hyalu-C', 120.00, 'servicios-belleza-hidratacion', $folder, '4 Limpieza Hyalu- C.png', 60);
        $this->addService($store, 'Manicura', 40.00, 'servicios-belleza', $folder, '4 MANICURA.png', 45);
        $this->addService($store, 'Masaje Corporal', 80.00, 'servicios-belleza-masajes', $folder, '4 MASAJE CORPORAL.png', 60);
        $this->addService($store, 'Pedicura', 60.00, 'servicios-belleza', $folder, '4 PEDICURA.png', 60);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 5: FIT BODY — gimnasio y entrenamiento
    // ──────────────────────────────────────────────────────────────────
    private function seedFitBody(): void
    {
        $user = $this->makeUser('fit.body@lyrium.com', 'Fit Body', '999555001', '20555555556');
        $store = $this->makeStore($user->id, [
            'ruc' => '20555555556',
            'trade_name' => 'Fit Body',
            'razon_social' => 'Fit Body SAC',
            'nombre_comercial' => 'Fit Body',
            'corporate_email' => 'fit.body@lyrium.com',
            'slug' => 'fit-body',
            'phone' => '999555001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Fit Body',
            'rep_legal_dni' => '55555501',
            'tax_condition' => 'RUC',
            'store_name' => 'Fit Body',
            'address' => 'Av. Benavides 500, Surco, Lima, Perú',
            'description' => 'Gimnasio y entrenamiento funcional para tu bienestar físico.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Surco');
        $this->command?->info('Tienda 5: Fit Body');

        $folder = '5 FIT BODY';
        $this->addService($store, 'Boxeo', 180.00, 'servicios-deportes-entrenamiento', $folder, '5 BOXEO.jpeg', 60);
        $this->addService($store, 'Entrenamiento Personalizado', 450.00, 'servicios-deportes-planes', $folder, '5 ENTRENAMIENTO PERSONALIZADO.jpeg', 60);
        $this->addService($store, 'Master Class Funcional', 350.00, 'servicios-deportes-entrenamiento', $folder, '5 FUNCIONAL.jpeg', 90);
        $this->addService($store, 'Mensualidad Gimnasio', 350.00, 'servicios-deportes-planes', $folder, '5 Mensualidad.jpeg', 60);
        $this->addService($store, 'Clases de Zumba', 200.00, 'servicios-deportes', $folder, '5 ZUMBA.png', 60);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 6: CENTRO MÉDICO DIGITAL — centro médico
    // ──────────────────────────────────────────────────────────────────
    private function seedCentroMedicoDigital(): void
    {
        $user = $this->makeUser('centro.medico@lyrium.com', 'Centro Médico Digital', '999666001', '20666666667');
        $store = $this->makeStore($user->id, [
            'ruc' => '20666666667',
            'trade_name' => 'Centro Médico Digital',
            'razon_social' => 'Centro Médico Digital SAC',
            'nombre_comercial' => 'Centro Médico Digital',
            'corporate_email' => 'centro.medico@lyrium.com',
            'slug' => 'centro-medico-digital',
            'phone' => '999666001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Centro Médico Digital',
            'rep_legal_dni' => '66666601',
            'tax_condition' => 'RUC',
            'store_name' => 'Centro Médico Digital',
            'address' => 'Av. Brasil 600, Jesús María, Lima, Perú',
            'description' => 'Centro médico especializado en diagnóstico y atención integral.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Jesús María');
        $this->command?->info('Tienda 6: Centro Médico Digital');

        $folder = '6 CENTRO MEDICO DIGITAL';
        $this->addService($store, 'Ecografía Obstétrica', 80.00, 'servicios-medicina-general', $folder, '6 Ecografía obstetrica.jpeg', 30);
        $this->addService($store, 'Examen Cardiovascular', 190.00, 'servicios-medicina-chequeo', $folder, '6 examen cardiovascular.jpeg', 60);
        $this->addService($store, 'Examen de Hemoglobina', 50.00, 'servicios-laboratorio', $folder, '6 EXAMEN HEMOGLOBINA.jpeg', 30);
        $this->addService($store, 'Tomografía Multicorte', 500.00, 'servicios-medicina-general', $folder, '6 TOMOGRAFIA MULTICORTE.png', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 7: PLAZA MEDIC — equipos médicos
    // ──────────────────────────────────────────────────────────────────
    private function seedPlazaMedic(): void
    {
        $user = $this->makeUser('plaza.medic@lyrium.com', 'Plaza Medic', '999777001', '20777777778');
        $store = $this->makeStore($user->id, [
            'ruc' => '20777777778',
            'trade_name' => 'Plaza Medic',
            'razon_social' => 'Plaza Medic EIRL',
            'nombre_comercial' => 'Plaza Medic',
            'corporate_email' => 'plaza.medic@lyrium.com',
            'slug' => 'plaza-medic',
            'phone' => '999777001',
            'seller_type' => 'products',
            'rep_legal_nombre' => 'Plaza Medic',
            'rep_legal_dni' => '77777701',
            'tax_condition' => 'RUC',
            'store_name' => 'Plaza Medic',
            'address' => 'Av. Universitaria 700, Lima, Perú',
            'description' => 'Tienda especializada en equipos y dispositivos médicos.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Los Olivos');
        $this->command?->info('Tienda 7: Plaza Medic');

        $folder = '7 PLAZA MEDIC';
        $this->addProduct($store, 'Glucómetro Accu-Chek Instant', 180.00, 'PM-GLU-001', 'equipos-diagnostico-glucometros', $folder, '7 Glucómetro Accu-Chek Instant + 10 Tiras + 10 Lanc.png');
        $this->addProduct($store, 'Muletas Ortopédicas de Aluminio', 120.00, 'PM-MUL-001', 'equipos-movilidad-bastones', $folder, '7 Muletas Ortopedicas de Aluminio Importadas Regulable.png');
        $this->addProduct($store, 'Silla de Ruedas Traumatológica', 900.00, 'PM-SRU-001', 'equipos-movilidad-sillas', $folder, '7 Silla de Ruedas Traumatológica.png', 10);
        $this->addProduct($store, 'Tensiómetro de Brazo BP1307', 110.00, 'PM-TEN-001', 'equipos-diagnostico-glucometros', $folder, '7 Tensiometro De Brazo Bp 1307 Gama Presión Arterial Lcd 3.2 Color Blanco.png');
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 8: FISIOCENTER — fisioterapia y rehabilitación
    // ──────────────────────────────────────────────────────────────────
    private function seedFisiocenter(): void
    {
        $user = $this->makeUser('fisiocenter@lyrium.com', 'Fisiocenter', '999888001', '20888888889');
        $store = $this->makeStore($user->id, [
            'ruc' => '20888888889',
            'trade_name' => 'Fisiocenter',
            'razon_social' => 'Fisiocenter SAC',
            'nombre_comercial' => 'Fisiocenter',
            'corporate_email' => 'fisiocenter@lyrium.com',
            'slug' => 'fisiocenter',
            'phone' => '999888001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Fisiocenter',
            'rep_legal_dni' => '88888801',
            'tax_condition' => 'RUC',
            'store_name' => 'Fisiocenter',
            'address' => 'Av. Angamos 800, Surquillo, Lima, Perú',
            'description' => 'Centro de fisioterapia y rehabilitación física integral.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Surquillo');
        $this->command?->info('Tienda 8: Fisiocenter');

        $folder = '8 FISIOCENTER';
        $this->addService($store, 'Crioterapia', 250.00, 'servicios-deportes-fisioterapia', $folder, '8 Crioterapia.png', 60);
        $this->addService($store, 'Electroterapia', 150.00, 'servicios-deportes-fisioterapia', $folder, '8 Electroterapia.jpeg', 45);
        $this->addService($store, 'Terapia Neurológica', 180.00, 'servicios-deportes-terapia', $folder, '8 Terapia Neurológica.jpeg', 60);
        $this->addService($store, 'Termoterapia', 120.00, 'servicios-deportes-rehabilitacion', $folder, '8 Termoterapia.png', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 9: RYDENT — odontología
    // ──────────────────────────────────────────────────────────────────
    private function seedRydent(): void
    {
        $user = $this->makeUser('rydent@lyrium.com', 'Rydent', '999901001', '20999999991');
        $store = $this->makeStore($user->id, [
            'ruc' => '20999999991',
            'trade_name' => 'Rydent',
            'razon_social' => 'Rydent SAC',
            'nombre_comercial' => 'Rydent',
            'corporate_email' => 'rydent@lyrium.com',
            'slug' => 'rydent',
            'phone' => '999901001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Rydent',
            'rep_legal_dni' => '99999901',
            'tax_condition' => 'RUC',
            'store_name' => 'Rydent',
            'address' => 'Av. Reducto 900, Miraflores, Lima, Perú',
            'description' => 'Clínica dental especializada en odontología moderna.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Miraflores');
        $this->command?->info('Tienda 9: Rydent');

        // No existe categoría de odontología en el catálogo actual; se usa el
        // fallback genérico 'servicios-medicos' (Servicios médicos).
        $folder = '9 RYDENT';
        $this->addService($store, 'Blanqueamiento Dental', 120.00, 'servicios-medicos', $folder, '9 BLANQUEAMIENTO DENTAL.png', 60);
        $this->addService($store, 'Curación Temporal', 80.00, 'servicios-medicos', $folder, '9 CURACION TEMPORAL.png', 30);
        $this->addService($store, 'Ortodoncia con Brackets', 2000.00, 'servicios-medicos', $folder, '9 Ortodoncia con Brackets.png', 60);
        $this->addService($store, 'Profilaxis Destartaje Flúor', 50.00, 'servicios-medicos', $folder, '9 Profilaxis Destartaje Fluor.png', 45);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 10: SOTOMAYOR — oftalmología
    // ──────────────────────────────────────────────────────────────────
    private function seedSotomayor(): void
    {
        $user = $this->makeUser('sotomayor@lyrium.com', 'Sotomayor', '999902001', '20100000009');
        $store = $this->makeStore($user->id, [
            'ruc' => '20100000009',
            'trade_name' => 'Sotomayor',
            'razon_social' => 'Sotomayor SAC',
            'nombre_comercial' => 'Sotomayor',
            'corporate_email' => 'sotomayor@lyrium.com',
            'slug' => 'sotomayor',
            'phone' => '999902001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'Sotomayor',
            'rep_legal_dni' => '10000001',
            'tax_condition' => 'RUC',
            'store_name' => 'Sotomayor',
            'address' => 'Av. Salaverry 1000, Jesús María, Lima, Perú',
            'description' => 'Clínica oftalmológica de alto nivel con tecnología de vanguardia.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'Jesús María');
        $this->command?->info('Tienda 10: Sotomayor');

        // No existe categoría de oftalmología en el catálogo actual; se usa el
        // fallback genérico 'servicios-medicos' (Servicios médicos).
        $folder = '10 SOTOMAYOR';
        $this->addService($store, 'Angiografía Retina Unilateral', 170.00, 'servicios-medicos', $folder, '10 ANGIOGRAFIA RETINA UNILATERAL.jpeg', 45);
        $this->addService($store, 'Gonioscopía', 60.00, 'servicios-medicos', $folder, '10 GONIOSCOPÌA.png', 30);
        $this->addService($store, 'Lavado y Sondeo del Tracto Lagrimal', 80.00, 'servicios-medicos', $folder, '10 LAVADO Y SONDEO DEL TRACTO LAGRIMAL.png', 30);
    }

    // ──────────────────────────────────────────────────────────────────
    // TIENDA 11: SAN JUAN DE DIOS — salud mental
    // ──────────────────────────────────────────────────────────────────
    private function seedSanJuanDeDios(): void
    {
        $user = $this->makeUser('sanjuandedios@lyrium.com', 'San Juan de Dios Psiquiatría', '999903001', '20110000007');
        $store = $this->makeStore($user->id, [
            'ruc' => '20110000007',
            'trade_name' => 'San Juan de Dios - Psiquiatría',
            'razon_social' => 'San Juan de Dios SAC',
            'nombre_comercial' => 'San Juan de Dios - Psiquiatría',
            'corporate_email' => 'sanjuandedios@lyrium.com',
            'slug' => 'san-juan-de-dios-psiquiatria',
            'phone' => '999903001',
            'seller_type' => 'services',
            'rep_legal_nombre' => 'San Juan de Dios',
            'rep_legal_dni' => '11000001',
            'tax_condition' => 'RUC',
            'store_name' => 'San Juan de Dios - Psiquiatría',
            'address' => 'Av. Guardia Civil 1100, San Borja, Lima, Perú',
            'description' => 'Centro especializado en salud mental, psicología y psiquiatría.',
            'commission_rate' => 0.1000,
        ]);
        $this->makeBranch($store, 'Lima', 'Lima', 'San Borja');
        $this->command?->info('Tienda 11: San Juan de Dios - Psiquiatría');

        $folder = '11 SAN JUAN DE DIOS';
        $this->addService($store, 'Diagnóstico Unipolar', 120.00, 'servicios-psicologia', $folder, '11 Diagnostico unipolar.jpeg', 60);
        $this->addService($store, 'Programas de Rehabilitación Integral', 60.00, 'servicios-psicologia', $folder, '11 Programas de Rehabilitación Integral.png', 90);
        $this->addService($store, 'Terapia Psicológica', 80.00, 'servicios-psicologia', $folder, '11 Terapia Psicologica.jpeg', 60);
    }
}
