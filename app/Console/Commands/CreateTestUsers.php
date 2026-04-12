<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUsers extends Command
{
    protected $signature = 'user:create-test';

    protected $description = 'Crear usuarios de prueba para testing';

    public function handle(): int
    {
        $password = 'password';

        $this->info('Buscando usuarios existentes (incluyendo eliminados)...');
        $allUsers = User::withTrashed()->select('id', 'name', 'email', 'username', 'deleted_at')->get();

        foreach ($allUsers as $user) {
            $deleted = $user->deleted_at ? ' [ELIMINADO]' : '';
            $role = $user->getRoleNames()->first() ?? 'sin rol';
            $this->line("ID: {$user->id}, Email: {$user->email}, Username: {$user->username}, Rol: {$role}{$deleted}");
        }

        $this->newLine();

        $adminEmail = 'angel.enginner08@gmail.com';
        $adminUsername = 'angel_engineer';
        $sellerEmail = 'angel.ipanaque.torre@gmail.com';
        $sellerUsername = 'angel_ipanaque';

        $admin = User::withTrashed()->where('username', $adminUsername)->first();

        if ($admin) {
            if ($admin->deleted_at) {
                $this->warn("Restaurando admin eliminado (ID: {$admin->id})...");
                $admin->restore();
            }
            $admin->update([
                'email' => $adminEmail,
                'username' => $adminUsername,
                'name' => 'Angel Engineer',
                'nicename' => 'angel-engineer',
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $admin->syncRoles(['administrator']);
            $this->info("✓ Admin actualizado: {$adminEmail} / {$password}");
            $this->info("  ID: {$admin->id}, Username: {$admin->username}");
        } else {
            User::create([
                'email' => $adminEmail,
                'username' => $adminUsername,
                'name' => 'Angel Engineer',
                'nicename' => 'angel-engineer',
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $admin = User::where('email', $adminEmail)->first();
            $admin->syncRoles(['administrator']);
            $this->info("✓ Admin creado: {$adminEmail} / {$password}");
            $this->info("  ID: {$admin->id}, Username: {$admin->username}");
        }

        $seller = User::withTrashed()->where('username', $sellerUsername)->first();

        if ($seller) {
            if ($seller->deleted_at) {
                $this->warn("Restaurando vendedor eliminado (ID: {$seller->id})...");
                $seller->restore();
            }
            $seller->update([
                'email' => $sellerEmail,
                'username' => $sellerUsername,
                'name' => 'Angel Ipanque',
                'nicename' => 'angel-ipanaque',
                'phone' => '999888777',
                'document_type' => 'RUC',
                'document_number' => '20123456789',
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $seller->syncRoles(['seller']);
            $this->info("✓ Vendedor actualizado: {$sellerEmail} / {$password}");
            $this->info("  ID: {$seller->id}, Username: {$seller->username}");
        } else {
            User::create([
                'email' => $sellerEmail,
                'username' => $sellerUsername,
                'name' => 'Angel Ipanque',
                'nicename' => 'angel-ipanaque',
                'phone' => '999888777',
                'document_type' => 'RUC',
                'document_number' => '20123456789',
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $seller = User::where('email', $sellerEmail)->first();
            $seller->syncRoles(['seller']);
            $this->info("✓ Vendedor creado: {$sellerEmail} / {$password}");
            $this->info("  ID: {$seller->id}, Username: {$seller->username}");
        }

        $existingStore = Store::withTrashed()->where('ruc', '20123456789')->first();
        if ($existingStore) {
            if ($existingStore->deleted_at) {
                $existingStore->restore();
            }
            $existingStore->update([
                'owner_id' => $seller->id,
                'trade_name' => 'BioTienda Demo',
                'corporate_email' => 'vendedor@lyrium.com',
                'slug' => 'biotienda-demo',
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            $this->info("✓ Tienda actualizada (ID: {$existingStore->id})");
        } else {
            Store::create([
                'owner_id' => $seller->id,
                'ruc' => '20123456789',
                'trade_name' => 'BioTienda Demo',
                'corporate_email' => 'vendedor@lyrium.com',
                'slug' => 'biotienda-demo',
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            $this->info('✓ Tienda creada');
        }
        $this->info('✓ Tienda creada/actualizada');

        $this->newLine();
        $this->warn('===========================================');
        $this->warn('   CREDENCIALES DE PRUEBA');
        $this->warn('===========================================');
        $this->info('Admin:    '.$adminEmail);
        $this->info('Vendedor: '.$sellerEmail);
        $this->info('Contraseña para ambos: '.$password);
        $this->newLine();

        return Command::SUCCESS;
    }
}
