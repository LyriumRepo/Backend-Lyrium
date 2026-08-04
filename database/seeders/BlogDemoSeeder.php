<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogPodcast;
use App\Models\BlogShort;
use App\Models\BlogVideo;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class BlogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $storeId = Store::query()->value('id');

        if (! $storeId) {
            $this->command?->warn('BlogDemoSeeder: no hay ninguna tienda creada, se omite el seed del blog.');

            return;
        }

        $categories = [
            ['name' => 'Salud Alimentaria', 'slug' => 'salud-alimentaria', 'description' => 'Alimentación consciente y nutrición saludable', 'sort_order' => 1],
            ['name' => 'Salud Ambiental', 'slug' => 'salud-ambiental', 'description' => 'Sostenibilidad y cuidado del medio ambiente', 'sort_order' => 2],
            ['name' => 'Salud Emocional', 'slug' => 'salud-emocional', 'description' => 'Bienestar emocional y manejo del estrés', 'sort_order' => 3],
            ['name' => 'Salud Espiritual', 'slug' => 'salud-espiritual', 'description' => 'Mindfulness, meditación y propósito', 'sort_order' => 4],
            ['name' => 'Salud Familiar', 'slug' => 'salud-familiar', 'description' => 'Bienestar y hábitos en familia', 'sort_order' => 5],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $categoryIds[$cat['slug']] = BlogCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat,
            )->id;
        }

        $articles = [
            ['cat' => 'salud-alimentaria', 'title' => '5 hábitos alimenticios que transforman tu energía diaria', 'img' => 'cuidado_personal.jpg', 'featured' => true, 'days_ago' => 3],
            ['cat' => 'salud-alimentaria', 'title' => 'Lo que dice la ciencia sobre los superalimentos', 'img' => 'entrevista_doctora-scaled.webp', 'featured' => false, 'days_ago' => 16],
            ['cat' => 'salud-alimentaria', 'title' => 'Cómo leer las etiquetas nutricionales sin perderte', 'img' => 'Fondos_BioBlog-6.webp', 'featured' => false, 'days_ago' => 31],
            ['cat' => 'salud-alimentaria', 'title' => 'Guía práctica para llevar tu negocio al mundo digital sin complicaciones', 'img' => 'laptop.jpg', 'featured' => true, 'days_ago' => 45],
            ['cat' => 'salud-ambiental', 'title' => 'Empaques biodegradables: la nueva tendencia en marketplaces', 'img' => 'laptop.jpg', 'featured' => false, 'days_ago' => 9],
            ['cat' => 'salud-ambiental', 'title' => 'Reduce tu huella de carbono con pequeños cambios diarios', 'img' => 'joven-sentado.jpg', 'featured' => false, 'days_ago' => 23],
            ['cat' => 'salud-ambiental', 'title' => 'Métodos de pago sostenibles para tu eCommerce', 'img' => 'blog-teclas.jpg', 'featured' => false, 'days_ago' => 47],
            ['cat' => 'salud-emocional', 'title' => 'Cómo manejar la ansiedad en tiempos de incertidumbre', 'img' => 'chica-sentada.jpg', 'featured' => true, 'days_ago' => 11],
            ['cat' => 'salud-emocional', 'title' => 'El poder de la gratitud en tu bienestar diario', 'img' => 'Familia-en-picnic-scaled.webp', 'featured' => false, 'days_ago' => 39],
            ['cat' => 'salud-emocional', 'title' => 'El poder de la meditación para una vida plena', 'img' => 'chica-sentada.jpg', 'featured' => false, 'days_ago' => 62],
            ['cat' => 'salud-espiritual', 'title' => 'Prácticas de mindfulness para principiantes', 'img' => 'entrevista_doctora-scaled.webp', 'featured' => false, 'days_ago' => 14],
            ['cat' => 'salud-espiritual', 'title' => 'Conecta con tu propósito: ejercicios de introspección', 'img' => 'cuidado_personal.jpg', 'featured' => false, 'days_ago' => 40],
            ['cat' => 'salud-espiritual', 'title' => 'Meditar en familia: rituales sencillos para conectar en casa', 'img' => 'Fondos_BioBlog-6.webp', 'featured' => true, 'days_ago' => 5],
            ['cat' => 'salud-familiar', 'title' => 'Recetas saludables para cocinar en familia', 'img' => 'blog-teclas.jpg', 'featured' => false, 'days_ago' => 20],
            ['cat' => 'salud-familiar', 'title' => 'Actividades al aire libre para fortalecer los lazos familiares', 'img' => 'Familia-en-picnic-scaled.webp', 'featured' => false, 'days_ago' => 2],
            ['cat' => 'salud-familiar', 'title' => 'Cómo convertir tu tienda física en una tienda online', 'img' => 'laptop.jpg', 'featured' => false, 'days_ago' => 51],
        ];

        foreach ($articles as $a) {
            $slug = Str::slug($a['title']);
            BlogArticle::updateOrCreate(
                ['slug' => $slug],
                [
                    'store_id' => $storeId,
                    'blog_category_id' => $categoryIds[$a['cat']],
                    'title' => $a['title'],
                    'summary' => $a['title'].'. Descubre consejos prácticos y respaldados por la comunidad Lyrium para vivir de forma más saludable y sostenible.',
                    'content' => '<p>'.$a['title'].'</p><p>Este es un artículo del BioBlog de Lyrium sobre bienestar, sostenibilidad y vida saludable. Próximamente ampliaremos este contenido con más detalle.</p>',
                    'main_image' => '/img/bioblog/'.$a['img'],
                    'status' => 'published',
                    'is_featured' => $a['featured'],
                    'published_at' => now()->subDays($a['days_ago']),
                    'views_count' => random_int(50, 400),
                ],
            );
        }

        $videos = [
            ['title' => 'Mejorando mi calidad de vida', 'category' => 'salud-alimentaria', 'category_label' => 'SALUD ALIMENTARIA', 'youtube_id' => 'ACPkTAPJLnM'],
            ['title' => 'Cambiar mis hábitos para bien', 'category' => 'salud-alimentaria', 'category_label' => 'SALUD ALIMENTARIA', 'youtube_id' => 'E0bria5w1lc'],
            ['title' => 'Salud, Belleza y Bienestar', 'category' => 'salud-alimentaria', 'category_label' => 'SALUD ALIMENTARIA', 'youtube_id' => 'wiJzsSP_5Ao'],
            ['title' => 'Impacto de las emociones en la salud', 'category' => 'salud-espiritual', 'category_label' => 'SALUD ESPIRITUAL', 'youtube_id' => '9reNgtVBcJ4'],
            ['title' => 'La salud mental también es importante', 'category' => 'salud-mental', 'category_label' => 'SALUD MENTAL', 'youtube_id' => 'G2vjOVda6og'],
            ['title' => 'COMIDA SALUDABLE', 'category' => 'salud-alimentaria', 'category_label' => 'SALUD ALIMENTARIA', 'youtube_id' => 'wiJzsSP_5Ao'],
            ['title' => 'Relaciones y Salud Social', 'category' => 'salud-social', 'category_label' => 'SALUD SOCIAL', 'youtube_id' => 'wiJzsSP_5Ao'],
            ['title' => 'Bienestar Emocional Diario', 'category' => 'salud-emocional', 'category_label' => 'SALUD EMOCIONAL', 'youtube_id' => 'ACPkTAPJLnM'],
            ['title' => 'Nuevas Historias Inspiradoras', 'category' => 'salud-familiar', 'category_label' => 'SALUD FAMILIAR', 'youtube_id' => 'E0bria5w1lc'],
        ];

        foreach ($videos as $i => $v) {
            BlogVideo::updateOrCreate(
                ['youtube_id' => $v['youtube_id'], 'title' => $v['title']],
                [
                    'store_id' => $storeId,
                    'category' => $v['category'],
                    'category_label' => $v['category_label'],
                    'platform' => 'youtube',
                    'url' => 'https://www.youtube.com/watch?v='.$v['youtube_id'],
                    'thumbnail' => 'https://img.youtube.com/vi/'.$v['youtube_id'].'/hqdefault.jpg',
                    'is_published' => true,
                    'status' => 'published',
                    'published_at' => now()->subDays($i * 4),
                ],
            );
        }

        $podcasts = [
            ['title' => 'Conversando sobre nutrición consciente', 'description' => 'Un episodio dedicado a la alimentación saludable y sus beneficios a largo plazo.'],
            ['title' => 'Sostenibilidad en el comercio local', 'description' => 'Cómo los pequeños negocios están adoptando prácticas más sostenibles.'],
            ['title' => 'Bienestar emocional en la vida diaria', 'description' => 'Herramientas prácticas para gestionar el estrés y las emociones.'],
            ['title' => 'El camino del emprendedor bio', 'description' => 'Historias de vendedores que apostaron por productos naturales.'],
        ];

        foreach ($podcasts as $i => $p) {
            BlogPodcast::updateOrCreate(
                ['title' => $p['title']],
                [
                    'store_id' => $storeId,
                    'type' => 'audio',
                    'platform' => 'spotify',
                    'description' => $p['description'],
                    'cover_image' => '/img/bioblog/'.['laptop.jpg', 'joven-sentado.jpg', 'chica-sentada.jpg', 'cuidado_personal.jpg'][$i],
                    'duration' => '2'.random_int(0, 5).':'.str_pad((string) random_int(0, 59), 2, '0', STR_PAD_LEFT),
                    'is_published' => true,
                    'status' => 'published',
                    'published_at' => now()->subDays($i * 6),
                ],
            );
        }

        $shorts = [
            ['title' => 'Tip rápido: hidrátate mejor', 'img' => 'chica-sentada.jpg'],
            ['title' => '3 snacks saludables en 1 minuto', 'img' => 'cuidado_personal.jpg'],
            ['title' => 'Respiración para calmar la ansiedad', 'img' => 'joven-sentado.jpg'],
            ['title' => 'Estiramientos rápidos para la espalda', 'img' => 'Fondos_BioBlog-6.webp'],
            ['title' => 'Cómo armar una lonchera saludable', 'img' => 'blog-teclas.jpg'],
            ['title' => '5 minutos de mindfulness antes de dormir', 'img' => 'entrevista_doctora-scaled.webp'],
        ];

        foreach ($shorts as $i => $s) {
            BlogShort::updateOrCreate(
                ['title' => $s['title']],
                [
                    'store_id' => $storeId,
                    'blog_category_id' => $categoryIds['salud-emocional'],
                    'platform' => 'youtube',
                    'url' => 'https://www.youtube.com/shorts/ACPkTAPJLnM',
                    'description' => $s['title'],
                    'thumbnail' => '/img/bioblog/'.$s['img'],
                    'duration' => random_int(20, 55),
                    'status' => 'published',
                    'published_at' => now()->subDays($i * 3),
                ],
            );
        }

        $this->command?->info('BlogDemoSeeder: '.count($articles).' artículos, '.count($videos).' videos, '.count($podcasts).' podcasts y '.count($shorts).' shorts sembrados.');
    }
}
