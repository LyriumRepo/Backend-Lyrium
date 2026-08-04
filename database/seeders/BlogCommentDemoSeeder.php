<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogArticle;
use App\Models\BlogComment;
use App\Models\BlogPodcast;
use App\Models\BlogShort;
use App\Models\BlogVideo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class BlogCommentDemoSeeder extends Seeder
{
    private const ANONYMOUS_AUTHORS = [
        ['name' => 'Carla Mendoza', 'email' => 'carla.mendoza.demo@example.com'],
        ['name' => 'Jorge Salazar', 'email' => 'jorge.salazar.demo@example.com'],
        ['name' => 'Fiorella Rojas', 'email' => 'fiorella.rojas.demo@example.com'],
        ['name' => 'Renzo Vargas', 'email' => 'renzo.vargas.demo@example.com'],
        ['name' => 'Lucía Paredes', 'email' => 'lucia.paredes.demo@example.com'],
        ['name' => 'Diego Huamán', 'email' => 'diego.huaman.demo@example.com'],
    ];

    private const COMMENTS_POOL = [
        'article' => [
            'Excelente artículo, me ayudó mucho a entender el tema.',
            'Gracias por compartir esto, lo voy a poner en práctica.',
            'Muy interesante, ¿tienen alguna recomendación de productos relacionados?',
            '¡Justo lo que necesitaba leer hoy! Muy claro y bien explicado.',
            'Coincido con varios puntos, aunque me gustaría más detalle en algunos.',
            'Comparto esto con mi familia, seguro les sirve también.',
            'Buen contenido, ¿planean escribir sobre este tema con más profundidad?',
            'Aplicable y fácil de seguir, gracias al equipo de Lyrium.',
            'Yo he notado cambios similares desde que hago esto mismo.',
            'Muy útil, lo guardo para volver a leerlo después.',
        ],
        'video' => [
            'Excelente video, muy bien explicado de principio a fin.',
            '¡Justo el contenido que buscaba! Gracias por compartirlo.',
            'La edición y el ritmo del video están muy buenos.',
            '¿Podrían hacer un video con más ejemplos prácticos como este?',
            'Lo vi completo y aprendí bastante, gracias equipo de Lyrium.',
            'Me suscribo para no perderme los próximos videos.',
            'Buena explicación, aunque me gustaría un poco más de detalle.',
            'Compartido con mis amigos, seguro les sirve también.',
        ],
        'podcast' => [
            'Gran episodio, lo escuché en mi camino al trabajo y se me pasó rápido.',
            'Muy buena conversación, los invitados aportan mucho valor.',
            'Justo el tema que necesitaba escuchar esta semana.',
            '¿Cuándo sale el próximo episodio? Ya quiero escucharlo.',
            'Excelente producción de audio, se escucha muy claro.',
            'Aprendí varios tips que voy a aplicar desde hoy.',
            'Buen episodio, aunque se sintió un poco corto.',
        ],
        'short' => [
            'Corto pero muy útil, directo al punto.',
            '¡Justo el tip que necesitaba! Gracias.',
            'Me encantan estos formatos rápidos, sigan así.',
            'Lo probé y funciona, gracias por el dato.',
            'Excelente, ¿tienen más shorts como este?',
            'Simple y claro, se agradece este tipo de contenido.',
        ],
    ];

    public function run(): void
    {
        $userIds = User::inRandomOrder()->limit(6)->pluck('id')->all();
        $total = 0;

        $total += $this->seedFor(BlogArticle::all(), 'article', $userIds);
        $total += $this->seedFor(BlogVideo::all(), 'video', $userIds);
        $total += $this->seedFor(BlogPodcast::all(), 'podcast', $userIds);
        $total += $this->seedFor(BlogShort::all(), 'short', $userIds);

        $this->command?->info("BlogCommentDemoSeeder: {$total} comentarios sembrados en total (artículos, videos, podcasts y shorts).");
    }

    private function seedFor(Collection $items, string $type, array $userIds): int
    {
        if ($items->isEmpty()) {
            $this->command?->warn("BlogCommentDemoSeeder: no hay contenido de tipo '{$type}', se omite.");

            return 0;
        }

        $pool = self::COMMENTS_POOL[$type];
        $count = 0;

        foreach ($items as $item) {
            $perItem = 3 + ($item->id % 3);

            for ($i = 0; $i < $perItem; $i++) {
                $useRegisteredUser = ! empty($userIds) && random_int(0, 100) < 50;
                $content = $pool[$i % count($pool)].' #'.($i + 1);

                if ($useRegisteredUser) {
                    $userId = $userIds[array_rand($userIds)];
                    $user = User::find($userId);
                    $authorName = $user?->name ?? 'Usuario Lyrium';
                    $authorEmail = $user?->email ?? '';
                } else {
                    $anon = self::ANONYMOUS_AUTHORS[array_rand(self::ANONYMOUS_AUTHORS)];
                    $userId = null;
                    $authorName = $anon['name'];
                    $authorEmail = $anon['email'];
                }

                BlogComment::updateOrCreate(
                    [
                        'commentable_type' => $type,
                        'commentable_id' => $item->id,
                        'content' => $content,
                    ],
                    [
                        'user_id' => $userId,
                        'author_name' => $authorName,
                        'author_email' => $authorEmail,
                        'is_approved' => true,
                    ],
                );

                $count++;
            }
        }

        return $count;
    }
}
