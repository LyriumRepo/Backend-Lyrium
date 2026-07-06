<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SecurityAdminSeeder extends Seeder
{
    public function run(): void
    {
        $security = User::firstOrCreate(
            ['email' => 'seguridad@lyrium.com'],
            [
                'name' => 'Security Admin',
                'username' => 'security_admin',
                'nicename' => 'security-admin',
                'phone' => '955113344',
                'password' => bcrypt('seguridad2024'),
                'email_verified_at' => now(),
            ]
        );
        $security->assignRole('security_admin');

        $this->command->info('Security Admin creado: seguridad@lyrium.com / seguridad2024');
    }
}
