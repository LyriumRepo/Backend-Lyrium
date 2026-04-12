<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

final class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            // slider1: Hero banners (Inicio/1.png + movil/1.webp = 1 slide con 2 imagenes)
            [
                'titulo' => 'Campaña Especial 1',
                'descripcion' => 'Promoción especial de temporada',
                'imagen' => '/storage/img/Inicio/1.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/1.webp',
                'seccion' => 'slider1',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'titulo' => 'Campaña Especial 2',
                'descripcion' => 'Descubre nuestras ofertas exclusivas',
                'imagen' => '/storage/img/Inicio/2.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/2.webp',
                'seccion' => 'slider1',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'titulo' => 'Campaña Especial 3',
                'descripcion' => 'Novedades para tu bienestar',
                'imagen' => '/storage/img/Inicio/3.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/3.webp',
                'seccion' => 'slider1',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'titulo' => 'Campaña Especial 4',
                'descripcion' => 'Ofertas por tiempo limitado',
                'imagen' => '/storage/img/Inicio/4.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/4.webp',
                'seccion' => 'slider1',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'titulo' => 'Campaña Especial 5',
                'descripcion' => 'Marcas aliadas',
                'imagen' => '/storage/img/Inicio/5.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/5.webp',
                'seccion' => 'slider1',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'titulo' => 'Campaña Especial 6',
                'descripcion' => 'Compra fácil y segura',
                'imagen' => '/storage/img/Inicio/6.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/6.webp',
                'seccion' => 'slider1',
                'position' => 6,
                'is_active' => true,
            ],

            // pequenos1: 4 banners publicitarios pequeños
            [
                'titulo' => 'Banner Pequeño 1',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.1.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos1',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 2',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.2.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos1',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 3',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.3.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos1',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 4',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.4.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos1',
                'position' => 4,
                'is_active' => true,
            ],

            // slider2: Banners medianos (2 slides de 2 imagenes c/u)
            [
                'titulo' => 'Banner Mediano 1',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_mediano/banner_mediano_3.1.webp',
                'imagen_mobile' => '/storage/img/banners_publicitarios/banner_mediano/banner_mediano_3.2.webp',
                'seccion' => 'slider2',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Mediano 2',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_mediano/banner_mediano_3.3.webp',
                'imagen_mobile' => '/storage/img/banners_publicitarios/banner_mediano/banner_mediano_3.1.webp',
                'seccion' => 'slider2',
                'position' => 2,
                'is_active' => true,
            ],

            // pequenos2: 4 banners pequeños adicionales
            [
                'titulo' => 'Banner Pequeño 5',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.1.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos2',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 6',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.2.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos2',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 7',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.3.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos2',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'titulo' => 'Banner Pequeño 8',
                'descripcion' => null,
                'imagen' => '/storage/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.4.webp',
                'imagen_mobile' => null,
                'seccion' => 'pequenos2',
                'position' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['titulo' => $banner['titulo'], 'seccion' => $banner['seccion'], 'position' => $banner['position']],
                $banner
            );
        }
    }
}
