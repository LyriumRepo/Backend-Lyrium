<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'BioNature', 'slug' => 'bionature', 'logo' => '/img/Inicio/3/1.png', 'position' => 1],
            ['name' => 'EcoVida', 'slug' => 'ecovida', 'logo' => '/img/Inicio/3/2.png', 'position' => 2],
            ['name' => 'GreenLife', 'slug' => 'greenlife', 'logo' => '/img/Inicio/3/3.png', 'position' => 3],
            ['name' => 'PureWell', 'slug' => 'purewell', 'logo' => '/img/Inicio/3/4.png', 'position' => 4],
            ['name' => 'NaturaPlus', 'slug' => 'naturaplus', 'logo' => '/img/Inicio/3/5.png', 'position' => 5],
            ['name' => 'VitaOrganica', 'slug' => 'vitaorganica', 'logo' => '/img/Inicio/3/6.png', 'position' => 6],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(['slug' => $brand['slug']], $brand);
        }
    }
}
