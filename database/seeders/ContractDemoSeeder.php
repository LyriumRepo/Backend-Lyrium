<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Genera un convenio digital ACTIVE para cada tienda que aún no tenga uno.
 *
 * `EnsureContractActive` (routes/api.php) bloquea con 403 cualquier
 * creación/edición de productos o servicios si la tienda del vendedor no
 * tiene un Contract con status=ACTIVE. Sin este seeder, ninguna tienda
 * sembrada (BioTienda Demo ni las de DemoStoresSeeder/DemoStoresWithImagesSeeder)
 * puede usarse para probar el panel seller.
 */
final class ContractDemoSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::whereDoesntHave('contracts', fn ($q) => $q->where('status', 'ACTIVE'))->get();

        if ($stores->isEmpty()) {
            $this->command?->info('ContractDemoSeeder: todas las tiendas ya tienen convenio activo.');

            return;
        }

        $year = now()->year;
        $lastContract = Contract::where('contract_number', 'like', "CTR-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastContract) {
            $parts = explode('-', $lastContract->contract_number);
            $nextNumber = ((int) end($parts)) + 1;
        }

        foreach ($stores as $store) {
            $contractNumber = sprintf('CTR-%d-%03d', $year, $nextNumber++);

            $contract = Contract::create([
                'contract_number' => $contractNumber,
                'store_id' => $store->id,
                'company' => $store->trade_name ?? $store->store_name ?? $store->razon_social ?? 'Tienda Demo',
                'ruc' => $store->ruc,
                'representative' => $store->rep_legal_nombre,
                'dni' => $store->rep_legal_dni,
                'direccion' => $store->direccion_fiscal ?? $store->address,
                'admin_name' => $store->rep_legal_nombre,
                'admin_phone' => $store->phone,
                'admin_email' => $store->corporate_email,
                'type' => 'General',
                'modality' => 'VIRTUAL',
                'plan' => 'Demo',
                'status' => 'ACTIVE',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addYear(),
                'notes' => 'Convenio digital demo generado automáticamente por ContractDemoSeeder.',
            ]);

            $contract->addAuditEntry('Contrato activado (seed demo)', 'System Seeder');
        }

        $this->command?->info("ContractDemoSeeder: {$stores->count()} convenios ACTIVE creados.");
    }
}
