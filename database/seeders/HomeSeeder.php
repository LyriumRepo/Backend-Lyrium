<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Benefit;
use App\Models\Brand;
use Illuminate\Database\Seeder;

final class HomeSeeder extends Seeder
{
    private const BASE_URL = 'https://lyriumbiomarketplace.com/wp-content/uploads';

    public function run(): void
    {
        $this->heroes();
        $this->banners();
        $this->brands();
        $this->benefits();
    }

    private function heroes(): void
    {
        $heroes = [
            [
                'titulo' => 'Bienvenido a Lyrium',
                'descripcion' => 'Tu marketplace de productos saludables y de calidad',
                'imagen' => '/storage/img/Inicio/1.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/1.webp',
                'seccion' => 'slider1',
                'position' => 1,
                'is_active' => true,
                'enlace' => '/productos',
            ],
            [
                'titulo' => 'Productos 100% Saludables',
                'descripcion' => 'Encuentra los mejores productos para tu bienestar',
                'imagen' => '/storage/img/Inicio/2.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/2.webp',
                'seccion' => 'slider1',
                'position' => 2,
                'is_active' => true,
                'enlace' => '/categorias',
            ],
            [
                'titulo' => 'Cuida tu Salud',
                'descripcion' => 'Los mejores suplementos y alimentación natural',
                'imagen' => '/storage/img/Inicio/3.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/3.webp',
                'seccion' => 'slider1',
                'position' => 3,
                'is_active' => true,
                'enlace' => '/suplementos-alimentacion',
            ],
            [
                'titulo' => 'Ofertas Especiales',
                'descripcion' => 'Descuentos exclusivos en productos selectedos',
                'imagen' => '/storage/img/Inicio/4.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/4.webp',
                'seccion' => 'slider1',
                'position' => 4,
                'is_active' => true,
                'enlace' => '/ofertas',
            ],
            [
                'titulo' => 'Nuevos Productos',
                'descripcion' => 'Descubre las últimas incorporaciones',
                'imagen' => '/storage/img/Inicio/5.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/5.webp',
                'seccion' => 'slider1',
                'position' => 5,
                'is_active' => true,
                'enlace' => '/novedades',
            ],
            [
                'titulo' => 'Compra Segura',
                'descripcion' => 'Tus compras protegidas y garantizadas',
                'imagen' => '/storage/img/Inicio/6.png',
                'imagen_mobile' => '/storage/img/Inicio/movil/6.webp',
                'seccion' => 'slider1',
                'position' => 6,
                'is_active' => true,
                'enlace' => '/productos',
            ],
        ];

        Banner::where('seccion', 'slider1')->delete();
        foreach ($heroes as $hero) {
            Banner::create($hero);
        }
    }

    private function banners(): void
    {
        Banner::whereIn('seccion', ['pequenos1', 'sliderMedianos1', 'pequenos2', 'sliderMedianos2', 'categoria_productos-digestion-saludable', 'categoria_productos-belleza'])->delete();

        Banner::create([
            'titulo' => 'Digestión Saludable',
            'descripcion' => 'Productos para tu digestión',
            'imagen' => '/storage/img/banners/categorias/digestion-saludable.png',
            'seccion' => 'categoria_productos-digestion-saludable',
            'position' => 1,
            'is_active' => true,
            'enlace' => '/productos/digestion-saludable',
        ]);

        Banner::create([
            'titulo' => 'Belleza',
            'descripcion' => 'Productos de belleza y cuidado personal',
            'imagen' => '/storage/img/banners/categorias/belleza.png',
            'seccion' => 'categoria_productos-belleza',
            'position' => 1,
            'is_active' => true,
            'enlace' => '/productos/belleza',
        ]);

        Banner::create([
            'titulo' => 'Servicios Médicos',
            'descripcion' => 'Servicios médicos profesionales',
            'imagen' => '/storage/img/banners/categorias/servicios-medicos.png',
            'seccion' => 'categoria_servicios-medicos',
            'position' => 1,
            'is_active' => true,
            'enlace' => '/servicios/medicos',
        ]);

        $banners = [
            // Pequenos 1 (4 banners) - slider1 folder
            [
                'titulo' => 'Banner Pequeno 1',
                'descripcion' => '/ofertas',
                'imagen' => '/storage/img/banners/slider1/banner_peq1.png',
                'seccion' => 'pequenos1',
                'position' => 1,
                'is_active' => true,
                'enlace' => '/ofertas',
            ],
            [
                'titulo' => 'Banner Pequeno 2',
                'descripcion' => '/suplementos',
                'imagen' => '/storage/img/banners/slider1/banner_peq2.png',
                'seccion' => 'pequenos1',
                'position' => 2,
                'is_active' => true,
                'enlace' => '/suplementos-alimentacion',
            ],
            [
                'titulo' => 'Banner Pequeno 3',
                'descripcion' => '/belleza',
                'imagen' => '/storage/img/banners/slider1/banner_peq3.png',
                'seccion' => 'pequenos1',
                'position' => 3,
                'is_active' => true,
                'enlace' => '/belleza',
            ],
            [
                'titulo' => 'Banner Pequeno 4',
                'descripcion' => '/mascotas',
                'imagen' => '/storage/img/banners/slider1/banner_peq4.webp',
                'seccion' => 'pequenos1',
                'position' => 4,
                'is_active' => true,
                'enlace' => '/mascotas',
            ],
            // Slider Medianos 1 (4 banners) - slider1 folder
            [
                'titulo' => 'Banner Mediano 1',
                'descripcion' => '/belleza',
                'imagen' => '/storage/img/banners/slider1/banner_med1.png',
                'seccion' => 'sliderMedianos1',
                'position' => 1,
                'is_active' => true,
                'enlace' => '/belleza',
            ],
            [
                'titulo' => 'Banner Mediano 2',
                'descripcion' => '/suplementos',
                'imagen' => '/storage/img/banners/slider1/banner_med2.png',
                'seccion' => 'sliderMedianos1',
                'position' => 2,
                'is_active' => true,
                'enlace' => '/suplementos-alimentacion',
            ],
            [
                'titulo' => 'Banner Mediano 3',
                'descripcion' => '/salud',
                'imagen' => '/storage/img/banners/slider1/banner_med3.png',
                'seccion' => 'sliderMedianos1',
                'position' => 3,
                'is_active' => true,
                'enlace' => '/salud-medicina',
            ],
            [
                'titulo' => 'Banner Mediano 4',
                'descripcion' => '/ofertas',
                'imagen' => '/storage/img/banners/slider1/banner_med4.png',
                'seccion' => 'sliderMedianos1',
                'position' => 4,
                'is_active' => true,
                'enlace' => '/ofertas',
            ],
            // Pequenos 2 (4 banners) - slider2 folder
            [
                'titulo' => 'Banner Pequeno 5',
                'descripcion' => '/ofertas',
                'imagen' => '/storage/img/banners/slider2/banner_peq1.webp',
                'seccion' => 'pequenos2',
                'position' => 1,
                'is_active' => true,
                'enlace' => '/ofertas',
            ],
            [
                'titulo' => 'Banner Pequeno 6',
                'descripcion' => '/nuevos',
                'imagen' => '/storage/img/banners/slider2/banner_peq2.webp',
                'seccion' => 'pequenos2',
                'position' => 2,
                'is_active' => true,
                'enlace' => '/novedades',
            ],
            [
                'titulo' => 'Banner Pequeno 7',
                'descripcion' => '/belleza',
                'imagen' => '/storage/img/banners/slider2/banner_peq3.webp',
                'seccion' => 'pequenos2',
                'position' => 3,
                'is_active' => true,
                'enlace' => '/belleza',
            ],
            [
                'titulo' => 'Banner Pequeno 8',
                'descripcion' => '/mascotas',
                'imagen' => '/storage/img/banners/slider2/banner_peq4.webp',
                'seccion' => 'pequenos2',
                'position' => 4,
                'is_active' => true,
                'enlace' => '/mascotas',
            ],
            // Slider Medianos 2 (3 banners) - slider2 folder
            [
                'titulo' => 'Banner Mediano 5',
                'descripcion' => '/bienestar',
                'imagen' => '/storage/img/banners/slider2/banner_med1.webp',
                'seccion' => 'sliderMedianos2',
                'position' => 1,
                'is_active' => true,
                'enlace' => '/bienestar',
            ],
            [
                'titulo' => 'Banner Mediano 6',
                'descripcion' => '/nuevos',
                'imagen' => '/storage/img/banners/slider2/banner_med2.webp',
                'seccion' => 'sliderMedianos2',
                'position' => 2,
                'is_active' => true,
                'enlace' => '/novedades',
            ],
            [
                'titulo' => 'Banner Mediano 7',
                'descripcion' => '/servicios',
                'imagen' => '/storage/img/banners/slider2/banner_med3.webp',
                'seccion' => 'sliderMedianos2',
                'position' => 3,
                'is_active' => true,
                'enlace' => '/servicios',
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }

    private function brands(): void
    {
        $brands = [
            ['name' => 'Natura Verde', 'slug' => 'natura-verde', 'logo' => '/storage/img/brands/1.png', 'is_active' => true, 'position' => 1],
            ['name' => 'Eco Vida', 'slug' => 'eco-vida', 'logo' => '/storage/img/brands/2.png', 'is_active' => true, 'position' => 2],
            ['name' => 'Bio Pure', 'slug' => 'bio-pure', 'logo' => '/storage/img/brands/3.png', 'is_active' => true, 'position' => 3],
            ['name' => 'Green Fresh', 'slug' => 'green-fresh', 'logo' => '/storage/img/brands/4.png', 'is_active' => true, 'position' => 4],
            ['name' => 'Vita Plus', 'slug' => 'vita-plus', 'logo' => '/storage/img/brands/5.png', 'is_active' => true, 'position' => 5],
            ['name' => 'Pure Life', 'slug' => 'pure-life', 'logo' => '/storage/img/brands/6.png', 'is_active' => true, 'position' => 6],
            ['name' => 'Healthy Mix', 'slug' => 'healthy-mix', 'logo' => '/storage/img/brands/7.png', 'is_active' => true, 'position' => 7],
            ['name' => 'Organic Mix', 'slug' => 'organic-mix', 'logo' => '/storage/img/brands/8p.ng.webp', 'is_active' => true, 'position' => 8],
            ['name' => 'Bio Natur', 'slug' => 'bio-natur', 'logo' => '/storage/img/brands/9p.ng.webp', 'is_active' => true, 'position' => 9],
        ];

        Brand::truncate();
        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }

    private function benefits(): void
    {
        $benefits = [
            ['titulo' => 'Envío Gratis', 'descripcion' => 'En pedidos mayores a S/100', 'icono' => 'truck', 'is_active' => true, 'position' => 1],
            ['titulo' => '100% Orgánico', 'descripcion' => 'Productos certificados', 'icono' => 'leaf', 'is_active' => true, 'position' => 2],
            ['titulo' => 'Pago Seguro', 'descripcion' => 'Tus datos protegidos', 'icono' => 'shield', 'is_active' => true, 'position' => 3],
            ['titulo' => 'Soporte 24/7', 'descripcion' => 'Atención personalizada', 'icono' => 'headphones', 'is_active' => true, 'position' => 4],
            ['titulo' => 'Todo Salud', 'descripcion' => 'Tiendas saludables y ecoamigables', 'icono' => 'heart', 'is_active' => true, 'position' => 5],
            ['titulo' => 'Tiendas Selectas', 'descripcion' => 'Tiendas de calidad seleccionadas', 'icono' => 'storefront', 'is_active' => true, 'position' => 6],
        ];

        foreach ($benefits as $benefit) {
            Benefit::updateOrCreate(['titulo' => $benefit['titulo']], $benefit);
        }
    }
}
