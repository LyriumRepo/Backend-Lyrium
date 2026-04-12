<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\Contract;
use App\Models\Store;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait WithRoles
{
    protected function seedRoles(): void
    {
        foreach (['administrator', 'seller', 'customer', 'logistics_operator'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('administrator');

        return $user;
    }

    protected function createSeller(?Store $store = null): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('seller');

        if (! $store) {
            Store::factory()->approved()->create(['owner_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Crea un seller con tienda aprobada Y contrato activo.
     * Usar en tests que requieren el middleware contract.active.
     */
    protected function createSellerWithContract(?Store $store = null): User
    {
        $user = $this->createSeller($store);
        $ownedStore = $user->ownedStores()->first();

        Contract::create([
            'contract_number' => 'CTR-TEST-001',
            'store_id'        => $ownedStore->id,
            'company'         => $ownedStore->trade_name,
            'ruc'             => $ownedStore->ruc,
            'type'            => 'Convenio Digital',
            'modality'        => 'Digital',
            'status'          => 'ACTIVE',
            'start_date'      => now()->toDateString(),
        ]);

        return $user;
    }

    protected function createCustomer(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        return $user;
    }
}
