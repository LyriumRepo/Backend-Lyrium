<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private const PRODUCT_CATEGORIES = [
        [
            'name' => 'Bebés y recién nacidos',
            'slug' => 'bebes-recien-nacidos',
            'description' => 'Productos para bebés y recién nacidos',
            'image' => null,
            'sort_order' => 1,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'De paseo y en el coche',
                    'slug' => 'bebes-paseo-coche',
                    'image' => '/img/Productos/Bebes/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'De paseo', 'slug' => 'bebes-de-paseo', 'sort_order' => 1],
                        ['name' => 'En el coche', 'slug' => 'bebes-en-coche', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Alimentación',
                    'slug' => 'bebes-alimentacion',
                    'image' => '/img/Productos/Bebes/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Menaje infantil', 'slug' => 'bebes-menaje-infantil', 'sort_order' => 1],
                        ['name' => 'Suplementos nutricionales', 'slug' => 'bebes-suplementos-nutricionales', 'sort_order' => 2],
                        ['name' => 'Tronas y elevadores', 'slug' => 'bebes-tronas-elevadores', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Juguetes',
                    'slug' => 'bebes-juguetes',
                    'image' => '/img/Productos/Bebes/3.webp',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Ropa',
                    'slug' => 'bebes-ropa',
                    'image' => '/img/Productos/Bebes/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Bebé (0–2 años)', 'slug' => 'bebes-ropa-0-2', 'sort_order' => 1],
                        ['name' => 'Infante (2–4 años)', 'slug' => 'bebes-ropa-2-4', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Calzado',
                    'slug' => 'bebes-calzado',
                    'image' => '/img/Productos/Bebes/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Bebé (0–2 años)', 'slug' => 'bebes-calzado-0-2', 'sort_order' => 1],
                        ['name' => 'Infante (2–4 años)', 'slug' => 'bebes-calzado-2-4', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Lactancia y chupetes',
                    'slug' => 'bebes-lactancia-chupetes',
                    'image' => '/img/Productos/Bebes/6.webp',
                    'sort_order' => 6,
                ],
            ],
        ],
        [
            'name' => 'Belleza',
            'slug' => 'productos-belleza',
            'description' => 'Productos de belleza y cuidado personal',
            'image' => '/storage/img/categorias/productos/belleza.webp',
            'sort_order' => 2,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Hombres',
                    'slug' => 'belleza-hombres',
                    'image' => '/img/Productos/Belleza/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Aseo e higiene personal', 'slug' => 'belleza-hombres-aseo', 'sort_order' => 1],
                        ['name' => 'Coloración', 'slug' => 'belleza-hombres-coloracion', 'sort_order' => 2],
                        ['name' => 'Cuidado del cabello', 'slug' => 'belleza-hombres-cabello', 'sort_order' => 3],
                        ['name' => 'Cuidado facial', 'slug' => 'belleza-hombres-facial', 'sort_order' => 4],
                        ['name' => 'Maquillaje', 'slug' => 'belleza-hombres-maquillaje', 'sort_order' => 5],
                    ],
                ],
                [
                    'name' => 'Mujeres',
                    'slug' => 'belleza-mujeres',
                    'image' => '/img/Productos/Belleza/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Aseo e higiene personal', 'slug' => 'belleza-mujeres-aseo', 'sort_order' => 1],
                        ['name' => 'Coloración', 'slug' => 'belleza-mujeres-coloracion', 'sort_order' => 2],
                        ['name' => 'Cuidado corporal', 'slug' => 'belleza-mujeres-corporal', 'sort_order' => 3],
                        ['name' => 'Cuidado del cabello', 'slug' => 'belleza-mujeres-cabello', 'sort_order' => 4],
                        ['name' => 'Cuidado facial', 'slug' => 'belleza-mujeres-facial', 'sort_order' => 5],
                        ['name' => 'Maquillaje', 'slug' => 'belleza-mujeres-maquillaje', 'sort_order' => 6],
                    ],
                ],
                [
                    'name' => 'Adolescentes, Niños y bebés',
                    'slug' => 'belleza-jovenes',
                    'image' => '/img/Productos/Belleza/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Aseo e higiene personal', 'slug' => 'belleza-jovenes-aseo', 'sort_order' => 1],
                        ['name' => 'Coloración', 'slug' => 'belleza-jovenes-coloracion', 'sort_order' => 2],
                        ['name' => 'Cuidado corporal', 'slug' => 'belleza-jovenes-corporal', 'sort_order' => 3],
                        ['name' => 'Cuidado del cabello', 'slug' => 'belleza-jovenes-cabello', 'sort_order' => 4],
                        ['name' => 'Cuidado facial', 'slug' => 'belleza-jovenes-facial', 'sort_order' => 5],
                        ['name' => 'Maquillaje', 'slug' => 'belleza-jovenes-maquillaje', 'sort_order' => 6],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Bienestar emocional y medicina natural',
            'slug' => 'bienestar-emocional',
            'description' => 'Productos de bienestar emocional y medicina natural',
            'image' => null,
            'sort_order' => 3,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Sistema Nervioso',
                    'slug' => 'bienestar-nervioso',
                    'image' => '/img/Productos/Bienestar/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Ansiedad y estrés', 'slug' => 'bienestar-nervioso-ansiedad', 'sort_order' => 1],
                        ['name' => 'Sueño y relax', 'slug' => 'bienestar-nervioso-sueno', 'sort_order' => 2],
                        ['name' => 'Vitaminas del grupo B', 'slug' => 'bienestar-nervioso-vitaminas-b', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Sistema Digestivo',
                    'slug' => 'bienestar-digestivo',
                    'image' => '/img/Productos/Bienestar/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Probióticos', 'slug' => 'bienestar-digestivo-probioticos', 'sort_order' => 1],
                        ['name' => 'Enzimas digestivas', 'slug' => 'bienestar-digestivo-enzimas', 'sort_order' => 2],
                        ['name' => 'Fibra y reguladores', 'slug' => 'bienestar-digestivo-fibra', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Sistema Circulatorio',
                    'slug' => 'bienestar-circulatorio',
                    'image' => '/img/Productos/Bienestar/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Circulación piernas', 'slug' => 'bienestar-circulatorio-piernas', 'sort_order' => 1],
                        ['name' => 'Tensión arterial', 'slug' => 'bienestar-circulatorio-tension', 'sort_order' => 2],
                        ['name' => 'Colesterol', 'slug' => 'bienestar-circulatorio-colesterol', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Sistema Óseo',
                    'slug' => 'bienestar-oseo',
                    'image' => '/img/Productos/Bienestar/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Calcio y vitamina D', 'slug' => 'bienestar-oseo-calcio', 'sort_order' => 1],
                        ['name' => 'Colágeno', 'slug' => 'bienestar-oseo-colageno', 'sort_order' => 2],
                        ['name' => 'Articulaciones', 'slug' => 'bienestar-oseo-articulaciones', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Sistema Muscular',
                    'slug' => 'bienestar-muscular',
                    'image' => '/img/Productos/Bienestar/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Magnesio', 'slug' => 'bienestar-muscular-magnesio', 'sort_order' => 1],
                        ['name' => 'Recuperación muscular', 'slug' => 'bienestar-muscular-recuperacion', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Sistema Inmunológico',
                    'slug' => 'bienestar-inmune',
                    'image' => '/img/Productos/Bienestar/6.webp',
                    'sort_order' => 6,
                    'children' => [
                        ['name' => 'Vitamina C', 'slug' => 'bienestar-inmune-vitamina-c', 'sort_order' => 1],
                        ['name' => 'Equinácea', 'slug' => 'bienestar-inmune-equinacea', 'sort_order' => 2],
                        ['name' => 'Zinc', 'slug' => 'bienestar-inmune-zinc', 'sort_order' => 3],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Bienestar físico y deportes',
            'slug' => 'productos-bienestar-fisico',
            'description' => 'Productos para bienestar físico y deporte',
            'image' => '/storage/img/categorias/productos/bienestar-fisico-deporte.png',
            'sort_order' => 4,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Calzado Mujer',
                    'slug' => 'deportes-calzado-mujer',
                    'image' => '/img/Productos/BienestarF/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Zapatillas running', 'slug' => 'deportes-calzado-mujer-running', 'sort_order' => 1],
                        ['name' => 'Zapatillas entrenamiento', 'slug' => 'deportes-calzado-mujer-entrenamiento', 'sort_order' => 2],
                        ['name' => 'Sandalias deportivas', 'slug' => 'deportes-calzado-mujer-sandalias', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Ropa Mujer',
                    'slug' => 'deportes-ropa-mujer',
                    'image' => '/img/Productos/BienestarF/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Camisetas deportivas', 'slug' => 'deportes-ropa-mujer-camisetas', 'sort_order' => 1],
                        ['name' => 'Leggings y shorts', 'slug' => 'deportes-ropa-mujer-leggings', 'sort_order' => 2],
                        ['name' => 'Sudaderas', 'slug' => 'deportes-ropa-mujer-sudaderas', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Calzado Hombre',
                    'slug' => 'deportes-calzado-hombre',
                    'image' => '/img/Productos/BienestarF/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Zapatillas running', 'slug' => 'deportes-calzado-hombre-running', 'sort_order' => 1],
                        ['name' => 'Zapatillas entrenamiento', 'slug' => 'deportes-calzado-hombre-entrenamiento', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Ropa Hombre',
                    'slug' => 'deportes-ropa-hombre',
                    'image' => '/img/Productos/BienestarF/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Camisetas deportivas', 'slug' => 'deportes-ropa-hombre-camisetas', 'sort_order' => 1],
                        ['name' => 'Pantalones cortos', 'slug' => 'deportes-ropa-hombre-pantalones', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Deportes Niños',
                    'slug' => 'deportes-ninos',
                    'image' => '/img/Productos/BienestarF/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Ropa deportiva niño', 'slug' => 'deportes-ninos-ropa', 'sort_order' => 1],
                        ['name' => 'Calzado deportivo niño', 'slug' => 'deportes-ninos-calzado', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Deportes Hombre',
                    'slug' => 'deportes-hombre',
                    'image' => '/img/Productos/BienestarF/6.webp',
                    'sort_order' => 6,
                    'children' => [
                        ['name' => 'Fitness', 'slug' => 'deportes-hombre-fitness', 'sort_order' => 1],
                        ['name' => 'Running', 'slug' => 'deportes-hombre-running', 'sort_order' => 2],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Digestión saludable',
            'slug' => 'productos-digestion-saludable',
            'description' => 'Productos para digestión saludable',
            'image' => '/storage/img/categorias/productos/digestion-saludable.png',
            'sort_order' => 5,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Abarrotes',
                    'slug' => 'digestion-abarrotes',
                    'image' => '/img/Productos/Digestion/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Arroz y legumbres', 'slug' => 'digestion-abarrotes-arroz', 'sort_order' => 1],
                        ['name' => 'Pasta', 'slug' => 'digestion-abarrotes-pasta', 'sort_order' => 2],
                        ['name' => 'Aceites y vinagres', 'slug' => 'digestion-abarrotes-aceites', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Desayunos',
                    'slug' => 'digestion-desayunos',
                    'image' => '/img/Productos/Digestion/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Cereales', 'slug' => 'digestion-desayunos-cereales', 'sort_order' => 1],
                        ['name' => 'Mermeladas y miel', 'slug' => 'digestion-desayunos-mermeladas', 'sort_order' => 2],
                        ['name' => 'Galletas integrales', 'slug' => 'digestion-desayunos-galletas', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Lácteos y frescos',
                    'slug' => 'digestion-lacteos',
                    'image' => '/img/Productos/Digestion/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Leche descremada', 'slug' => 'digestion-lacteos-leche', 'sort_order' => 1],
                        ['name' => 'Yogur natural', 'slug' => 'digestion-lacteos-yogur', 'sort_order' => 2],
                        ['name' => 'Quesos bajos en grasa', 'slug' => 'digestion-lacteos-quesos', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Bebidas',
                    'slug' => 'digestion-bebidas',
                    'image' => '/img/Productos/Digestion/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Jugos naturales', 'slug' => 'digestion-bebidas-jugos', 'sort_order' => 1],
                        ['name' => 'Infusiones', 'slug' => 'digestion-bebidas-infusiones', 'sort_order' => 2],
                        ['name' => 'Agua mineral', 'slug' => 'digestion-bebidas-agua', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Dulces y snacks',
                    'slug' => 'digestion-dulces',
                    'image' => '/img/Productos/Digestion/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Chocolate oscuro', 'slug' => 'digestion-dulces-chocolate', 'sort_order' => 1],
                        ['name' => 'Frutos secos', 'slug' => 'digestion-dulces-frutos', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Panadería',
                    'slug' => 'digestion-panaderia',
                    'image' => '/img/Productos/Digestion/6.webp',
                    'sort_order' => 6,
                    'children' => [
                        ['name' => 'Pan integral', 'slug' => 'digestion-panaderia-integral', 'sort_order' => 1],
                        ['name' => 'Pan sin gluten', 'slug' => 'digestion-panaderia-sin-gluten', 'sort_order' => 2],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Equipos y dispositivos médicos',
            'slug' => 'productos-equipos-medicos',
            'description' => 'Equipos y dispositivos médicos',
            'image' => '/storage/img/categorias/productos/equipos y dispositivos-medicos.png',
            'sort_order' => 6,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Diagnóstico',
                    'slug' => 'equipos-diagnostico',
                    'image' => '/img/Productos/Equipos/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Básculas', 'slug' => 'equipos-diagnostico-basculas', 'sort_order' => 1],
                        ['name' => 'Glucómetros', 'slug' => 'equipos-diagnostico-glucometros', 'sort_order' => 2],
                        ['name' => 'Oxímetros', 'slug' => 'equipos-diagnostico-oximetros', 'sort_order' => 3],
                        ['name' => 'Termómetros', 'slug' => 'equipos-diagnostico-termometros', 'sort_order' => 4],
                        ['name' => 'Tensiómetros', 'slug' => 'equipos-diagnostico-tensiometros', 'sort_order' => 5],
                    ],
                ],
                [
                    'name' => 'Tratamiento',
                    'slug' => 'equipos-tratamiento',
                    'image' => '/img/Productos/Equipos/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Nebulizadores', 'slug' => 'equipos-tratamiento-nebulizadores', 'sort_order' => 1],
                        ['name' => 'Concentrador de oxígeno', 'slug' => 'equipos-tratamiento-oxigeno', 'sort_order' => 2],
                        ['name' => 'Bombas de infusión', 'slug' => 'equipos-tratamiento-bombas', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Rehabilitación',
                    'slug' => 'equipos-rehabilitacion',
                    'image' => '/img/Productos/Equipos/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Ejercitadores', 'slug' => 'equipos-rehabilitacion-ejercitadores', 'sort_order' => 1],
                        ['name' => 'Fisioterapia', 'slug' => 'equipos-rehabilitacion-fisioterapia', 'sort_order' => 2],
                        ['name' => 'Terapias', 'slug' => 'equipos-rehabilitacion-terapias', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Movilidad',
                    'slug' => 'equipos-movilidad',
                    'image' => '/img/Productos/Equipos/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Sillas de ruedas', 'slug' => 'equipos-movilidad-sillas', 'sort_order' => 1],
                        ['name' => 'Bastones', 'slug' => 'equipos-movilidad-bastones', 'sort_order' => 2],
                        ['name' => 'Andadores', 'slug' => 'equipos-movilidad-andadores', 'sort_order' => 3],
                        ['name' => 'Grúas', 'slug' => 'equipos-movilidad-gruas', 'sort_order' => 4],
                    ],
                ],
                [
                    'name' => 'Cuidado en casa',
                    'slug' => 'equipos-cuidado-casa',
                    'image' => '/img/Productos/Equipos/5.webp',
                    'sort_order' => 5,
                ],
                [
                    'name' => 'Emergencias',
                    'slug' => 'equipos-emergencias',
                    'image' => '/img/Productos/Equipos/6.webp',
                    'sort_order' => 6,
                    'children' => [
                        ['name' => 'Kits de primeros auxilios', 'slug' => 'equipos-emergencias-kits', 'sort_order' => 1],
                        ['name' => 'Desfibriladores', 'slug' => 'equipos-emergencias-desfibriladores', 'sort_order' => 2],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Mascotas',
            'slug' => 'productos-mascotas',
            'description' => 'Productos para mascotas',
            'image' => '/storage/img/categorias/productos/mascotas.png',
            'sort_order' => 7,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Perros',
                    'slug' => 'mascotas-perros',
                    'image' => '/img/Productos/Mascotas/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Alimento seco', 'slug' => 'mascotas-perros-seco', 'sort_order' => 1],
                        ['name' => 'Alimento húmedo', 'slug' => 'mascotas-perros-humedo', 'sort_order' => 2],
                        ['name' => 'Juguetes', 'slug' => 'mascotas-perros-juguetes', 'sort_order' => 3],
                        ['name' => 'Camas y mantas', 'slug' => 'mascotas-perros-camas', 'sort_order' => 4],
                        ['name' => 'Collares y correas', 'slug' => 'mascotas-perros-collares', 'sort_order' => 5],
                    ],
                ],
                [
                    'name' => 'Gatos',
                    'slug' => 'mascotas-gatos',
                    'image' => '/img/Productos/Mascotas/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Alimento seco', 'slug' => 'mascotas-gatos-seco', 'sort_order' => 1],
                        ['name' => 'Alimento húmedo', 'slug' => 'mascotas-gatos-humedo', 'sort_order' => 2],
                        ['name' => 'Arena para gatos', 'slug' => 'mascotas-gatos-arena', 'sort_order' => 3],
                        ['name' => 'Juguetes', 'slug' => 'mascotas-gatos-juguetes', 'sort_order' => 4],
                    ],
                ],
                [
                    'name' => 'Aves',
                    'slug' => 'mascotas-aves',
                    'image' => '/img/Productos/Mascotas/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Alimento para aves', 'slug' => 'mascotas-aves-alimento', 'sort_order' => 1],
                        ['name' => 'Jaulas', 'slug' => 'mascotas-aves-jaulas', 'sort_order' => 2],
                        ['name' => 'Juguetes', 'slug' => 'mascotas-aves-juguetes', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Peces',
                    'slug' => 'mascotas-peces',
                    'image' => '/img/Productos/Mascotas/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Alimento para peces', 'slug' => 'mascotas-peces-alimento', 'sort_order' => 1],
                        ['name' => 'Acuarios', 'slug' => 'mascotas-peces-acuarios', 'sort_order' => 2],
                        ['name' => 'Filtros y bombas', 'slug' => 'mascotas-peces-filtros', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Otros',
                    'slug' => 'mascotas-otros',
                    'image' => '/img/Productos/Mascotas/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Roedores', 'slug' => 'mascotas-otros-roedores', 'sort_order' => 1],
                        ['name' => 'Reptiles', 'slug' => 'mascotas-otros-reptiles', 'sort_order' => 2],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Protección, limpieza y desinfección',
            'slug' => 'productos-limpieza',
            'description' => 'Productos de protección, limpieza y desinfección',
            'image' => '/storage/img/categorias/productos/protecion-limpieza-desinfencion.png',
            'sort_order' => 8,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Limpieza Hogar',
                    'slug' => 'limpieza-hogar',
                    'image' => '/img/Productos/Limpieza/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Detergentes', 'slug' => 'limpieza-hogar-detergentes', 'sort_order' => 1],
                        ['name' => 'Suavizantes', 'slug' => 'limpieza-hogar-suavizantes', 'sort_order' => 2],
                        ['name' => 'Limpiadores multiuso', 'slug' => 'limpieza-hogar-limpiadores', 'sort_order' => 3],
                        ['name' => 'Escobas y trapeadores', 'slug' => 'limpieza-hogar-escobas', 'sort_order' => 4],
                    ],
                ],
                [
                    'name' => 'Desinfección',
                    'slug' => 'limpieza-desinfeccion',
                    'image' => '/img/Productos/Limpieza/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Cloro', 'slug' => 'limpieza-desinfeccion-cloro', 'sort_order' => 1],
                        ['name' => 'Alcohol', 'slug' => 'limpieza-desinfeccion-alcohol', 'sort_order' => 2],
                        ['name' => 'Sprays antibacteriales', 'slug' => 'limpieza-desinfeccion-sprays', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Protección Personal',
                    'slug' => 'limpieza-proteccion',
                    'image' => '/img/Productos/Limpieza/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Guantes', 'slug' => 'limpieza-proteccion-guantes', 'sort_order' => 1],
                        ['name' => 'Mascarillas', 'slug' => 'limpieza-proteccion-mascarillas', 'sort_order' => 2],
                        ['name' => 'Cofias y batas', 'slug' => 'limpieza-proteccion-cofias', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Antibacteriales',
                    'slug' => 'limpieza-antibacteriales',
                    'image' => '/img/Productos/Limpieza/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'Jabón líquido', 'slug' => 'limpieza-antibacteriales-jabon', 'sort_order' => 1],
                        ['name' => 'Toallas húmedas', 'slug' => 'limpieza-antibacteriales-toallas', 'sort_order' => 2],
                        ['name' => 'Desinfectantes de manos', 'slug' => 'limpieza-antibacteriales-manos', 'sort_order' => 3],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Suplementos vitamínicos',
            'slug' => 'productos-suplementos',
            'description' => 'Suplementos vitamínicos y nutricionales',
            'image' => '/storage/img/categorias/productos/sumplementos-vitaminicos.png',
            'sort_order' => 9,
            'type' => 'product',
            'children' => [
                [
                    'name' => 'Vitaminas',
                    'slug' => 'suplementos-vitaminas',
                    'image' => '/img/Productos/Suplementos/1.webp',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Vitamina C', 'slug' => 'suplementos-vitaminas-c', 'sort_order' => 1],
                        ['name' => 'Vitamina D', 'slug' => 'suplementos-vitaminas-d', 'sort_order' => 2],
                        ['name' => 'Vitamina E', 'slug' => 'suplementos-vitaminas-e', 'sort_order' => 3],
                        ['name' => 'Complejo B', 'slug' => 'suplementos-vitaminas-complejo-b', 'sort_order' => 4],
                    ],
                ],
                [
                    'name' => 'Minerales',
                    'slug' => 'suplementos-minerales',
                    'image' => '/img/Productos/Suplementos/2.webp',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Magnesio', 'slug' => 'suplementos-minerales-magnesio', 'sort_order' => 1],
                        ['name' => 'Zinc', 'slug' => 'suplementos-minerales-zinc', 'sort_order' => 2],
                        ['name' => 'Hierro', 'slug' => 'suplementos-minerales-hierro', 'sort_order' => 3],
                        ['name' => 'Calcio', 'slug' => 'suplementos-minerales-calcio', 'sort_order' => 4],
                    ],
                ],
                [
                    'name' => 'Proteínas',
                    'slug' => 'suplementos-proteinas',
                    'image' => '/img/Productos/Suplementos/3.webp',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Whey protein', 'slug' => 'suplementos-proteinas-whey', 'sort_order' => 1],
                        ['name' => 'Proteína vegetal', 'slug' => 'suplementos-proteinas-vegetal', 'sort_order' => 2],
                        ['name' => 'Aminoácidos BCAA', 'slug' => 'suplementos-proteinas-bcaa', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Aminoácidos',
                    'slug' => 'suplementos-aminoacidos',
                    'image' => '/img/Productos/Suplementos/4.webp',
                    'sort_order' => 4,
                    'children' => [
                        ['name' => 'L-Glutamina', 'slug' => 'suplementos-aminoacidos-glutamina', 'sort_order' => 1],
                        ['name' => 'L-Arginina', 'slug' => 'suplementos-aminoacidos-arginina', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Hierbas',
                    'slug' => 'suplementos-hierbas',
                    'image' => '/img/Productos/Suplementos/5.webp',
                    'sort_order' => 5,
                    'children' => [
                        ['name' => 'Valeriana', 'slug' => 'suplementos-hierbas-valeriana', 'sort_order' => 1],
                        ['name' => 'Ginkgo biloba', 'slug' => 'suplementos-hierbas-ginkgo', 'sort_order' => 2],
                        ['name' => 'Equinácea', 'slug' => 'suplementos-hierbas-equinacea', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Deportivos',
                    'slug' => 'suplementos-deportivos',
                    'image' => '/img/Productos/Suplementos/6.webp',
                    'sort_order' => 6,
                    'children' => [
                        ['name' => 'Pre-entreno', 'slug' => 'suplementos-deportivos-pre-entreno', 'sort_order' => 1],
                        ['name' => 'Creatina', 'slug' => 'suplementos-deportivos-creatina', 'sort_order' => 2],
                        ['name' => 'Reponedores electrolitos', 'slug' => 'suplementos-deportivos-electrolitos', 'sort_order' => 3],
                    ],
                ],
            ],
        ],
    ];

    private const SERVICE_CATEGORIES = [
        [
            'name' => 'Servicios médicos',
            'slug' => 'servicios-medicos',
            'description' => 'Servicios médicos profesionales',
            'image' => '/storage/img/categorias/servicios/medicos.webp',
            'sort_order' => 1,
            'type' => 'service',
            'children' => [
                [
                    'name' => 'Medicina general',
                    'slug' => 'servicios-medicina-general',
                    'image' => '/img/Servicios/ServiciosMedicos/4.svg',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Chequeo general', 'slug' => 'servicios-medicina-chequeo', 'sort_order' => 1],
                        ['name' => 'Atención primaria', 'slug' => 'servicios-medicina-atencion', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Pediatría',
                    'slug' => 'servicios-pediatria',
                    'image' => '/img/Servicios/ServiciosMedicos/6.svg',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Control de crecimiento', 'slug' => 'servicios-pediatria-crecimiento', 'sort_order' => 1],
                        ['name' => 'Vacunación', 'slug' => 'servicios-pediatria-vacunacion', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Gastroenterología',
                    'slug' => 'servicios-gastroenterologia',
                    'image' => '/img/Servicios/ServiciosMedicos/1.svg',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Geriatría',
                    'slug' => 'servicios-geriatria',
                    'image' => '/img/Servicios/ServiciosMedicos/2.svg',
                    'sort_order' => 4,
                ],
                [
                    'name' => 'Laboratorio clínico',
                    'slug' => 'servicios-laboratorio',
                    'image' => '/img/Servicios/ServiciosMedicos/3.svg',
                    'sort_order' => 5,
                ],
                [
                    'name' => 'Nutriología',
                    'slug' => 'servicios-nutriologia',
                    'image' => '/img/Servicios/ServiciosMedicos/5.svg',
                    'sort_order' => 6,
                ],
                [
                    'name' => 'Psicología',
                    'slug' => 'servicios-psicologia',
                    'image' => '/img/Servicios/ServiciosMedicos/7.svg',
                    'sort_order' => 7,
                ],
            ],
        ],
        [
            'name' => 'Belleza servicios',
            'slug' => 'servicios-belleza',
            'description' => 'Servicios de belleza y cuidado personal',
            'image' => '/storage/img/categorias/servicios/belleza.webp',
            'sort_order' => 2,
            'type' => 'service',
            'children' => [
                [
                    'name' => 'Tratamientos faciales',
                    'slug' => 'servicios-belleza-faciales',
                    'image' => '/img/Servicios/Belleza/1.svg',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Limpieza facial', 'slug' => 'servicios-belleza-limpieza', 'sort_order' => 1],
                        ['name' => 'Hidratación facial', 'slug' => 'servicios-belleza-hidratacion', 'sort_order' => 2],
                        ['name' => 'Antiaging', 'slug' => 'servicios-belleza-antiaging', 'sort_order' => 3],
                    ],
                ],
                [
                    'name' => 'Tratamientos corporales',
                    'slug' => 'servicios-belleza-corporales',
                    'image' => '/img/Servicios/Belleza/2.svg',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Masajes reductores', 'slug' => 'servicios-belleza-masajes', 'sort_order' => 1],
                        ['name' => 'Drenaje linfático', 'slug' => 'servicios-belleza-drenaje', 'sort_order' => 2],
                        ['name' => 'Tratamientos celulitis', 'slug' => 'servicios-belleza-celulitis', 'sort_order' => 3],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Deportes servicios',
            'slug' => 'servicios-deportes',
            'description' => 'Servicios deportivos y actividad física',
            'image' => '/storage/img/categorias/servicios/deportes.webp',
            'sort_order' => 3,
            'type' => 'service',
            'children' => [
                [
                    'name' => 'Entrenamiento personal',
                    'slug' => 'servicios-deportes-entrenamiento',
                    'image' => '/img/Servicios/Deportes/1.png',
                    'sort_order' => 1,
                    'children' => [
                        ['name' => 'Planes personalizados', 'slug' => 'servicios-deportes-planes', 'sort_order' => 1],
                        ['name' => 'Evaluación física', 'slug' => 'servicios-deportes-evaluacion', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Fisioterapia deportiva',
                    'slug' => 'servicios-deportes-fisioterapia',
                    'sort_order' => 2,
                    'children' => [
                        ['name' => 'Rehabilitación', 'slug' => 'servicios-deportes-rehabilitacion', 'sort_order' => 1],
                        ['name' => 'Terapia manual', 'slug' => 'servicios-deportes-terapia', 'sort_order' => 2],
                    ],
                ],
                [
                    'name' => 'Nutrición deportiva',
                    'slug' => 'servicios-deportes-nutricion',
                    'sort_order' => 3,
                    'children' => [
                        ['name' => 'Planes nutricionales', 'slug' => 'servicios-deportes-nutricion-planes', 'sort_order' => 1],
                        ['name' => 'Suplementación', 'slug' => 'servicios-deportes-suplementacion', 'sort_order' => 2],
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        Category::where('type', 'service')->delete();
        Category::where('type', 'product')->delete();
        $this->createCategories(self::PRODUCT_CATEGORIES);
        $this->createCategories(self::SERVICE_CATEGORIES);
    }

    private function createCategories(array $categories, ?int $parentId = null, ?string $parentType = null): void
    {
        foreach ($categories as $cat) {
            $children = $cat['children'] ?? [];
            unset($cat['children']);

            $cat['parent_id'] = $parentId;
            $cat['type'] = $cat['type'] ?? $parentType ?? 'product';

            $category = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );

            if (! empty($children)) {
                $this->createCategories($children, $category->id, $cat['type']);
            }
        }
    }
}
