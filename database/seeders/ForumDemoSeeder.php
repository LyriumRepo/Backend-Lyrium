<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ForumDemoSeeder extends Seeder
{
    private const ANONYMOUS_NAMES = [
        'Anónimo Curioso', 'Vecino Saludable', 'Mamá Bio', 'Runner_Lima', 'Visitante21',
        'AmigoDelBienestar', 'Usuario Nuevo', 'SoyDeSurco',
    ];

    public function run(): void
    {
        $categories = ForumCategory::pluck('id', 'slug');

        if ($categories->isEmpty()) {
            $this->command?->warn('ForumDemoSeeder: no hay categorías de foro, se omite el seed.');

            return;
        }

        $userIds = User::inRandomOrder()->limit(6)->pluck('id')->all();
        $storeIds = Store::pluck('id')->all();

        $topics = [
            ['cat' => 'general', 'title' => '¿Cómo empezaron su camino hacia una vida más saludable?', 'content' => 'Quiero conocer sus historias: ¿qué los motivó a cambiar sus hábitos? Yo empecé hace un año después de sentirme siempre cansado.', 'user' => true, 'store' => false, 'replies' => 6],
            ['cat' => 'general', 'title' => 'Recomendaciones de tiendas bio confiables en Lima', 'content' => 'Estoy buscando tiendas que vendan productos orgánicos certificados. ¿Cuáles recomiendan y por qué?', 'user' => false, 'store' => false, 'replies' => 8],
            ['cat' => 'general', 'title' => 'Bienvenida a los nuevos miembros de la comunidad Lyrium', 'content' => 'Este espacio es para presentarnos y compartir qué esperamos de esta comunidad de bienestar. ¡Cuéntennos de ustedes!', 'user' => true, 'store' => true, 'replies' => 5],
            ['cat' => 'nutricion', 'title' => '¿Qué desayuno recomiendan para tener energía todo el día?', 'content' => 'Últimamente me da mucho sueño después de almorzar. ¿Qué cambios en el desayuno les han funcionado?', 'user' => false, 'store' => false, 'replies' => 7],
            ['cat' => 'nutricion', 'title' => 'Alternativas al azúcar refinada para el café', 'content' => 'Probé con stevia pero el sabor no me convence del todo. ¿Qué otras opciones naturales existen?', 'user' => true, 'store' => false, 'replies' => 4],
            ['cat' => 'nutricion', 'title' => 'Beneficios reales de los superalimentos: ¿mito o realidad?', 'content' => 'Veo que se habla mucho de la maca, la spirulina y la moringa. ¿Alguien ha notado cambios concretos al consumirlos?', 'user' => false, 'store' => true, 'replies' => 6],
            ['cat' => 'microbiota', 'title' => 'Probióticos vs prebióticos: ¿cuál es la diferencia?', 'content' => 'Siempre los confundo. ¿Alguien puede explicar de forma simple cuándo conviene tomar cada uno?', 'user' => true, 'store' => false, 'replies' => 5],
            ['cat' => 'microbiota', 'title' => 'Mi experiencia mejorando mi digestión con kéfir casero', 'content' => 'Después de 3 semanas tomando kéfir todas las mañanas, noto menos inflamación. Comparto mi receta por si a alguien le sirve.', 'user' => false, 'store' => false, 'replies' => 8],
            ['cat' => 'microbiota', 'title' => '¿Los antibióticos afectan tanto a la microbiota como dicen?', 'content' => 'Tuve que tomar un tratamiento largo y ahora quiero recuperar mi flora intestinal. ¿Qué recomiendan?', 'user' => true, 'store' => false, 'replies' => 3],
            ['cat' => 'fitness', 'title' => 'Rutinas cortas para quienes no tienen tiempo de ir al gimnasio', 'content' => 'Trabajo muchas horas y quiero mantenerme activo. ¿Qué rutinas de 20-30 minutos recomiendan en casa?', 'user' => false, 'store' => false, 'replies' => 7],
            ['cat' => 'fitness', 'title' => 'Suplementación natural para la recuperación muscular', 'content' => 'Entreno 4 veces por semana y quiero evitar suplementos con muchos químicos. ¿Qué alternativas naturales funcionan?', 'user' => true, 'store' => true, 'replies' => 6],
            ['cat' => 'salud-mental', 'title' => 'Técnicas de respiración para reducir la ansiedad antes de dormir', 'content' => 'Últimamente me cuesta conciliar el sueño por el estrés del trabajo. ¿Qué técnicas les han funcionado?', 'user' => false, 'store' => false, 'replies' => 5],
            ['cat' => 'salud-mental', 'title' => 'La importancia de desconectarse de las redes sociales', 'content' => 'Hice una semana sin redes sociales y noté una mejora notable en mi ánimo. ¿Alguien más lo ha probado?', 'user' => true, 'store' => false, 'replies' => 4],
        ];

        $repliesPool = [
            'Totalmente de acuerdo, a mí también me ha funcionado algo similar.',
            'Interesante, no lo había considerado desde ese ángulo.',
            'Yo tuve una experiencia distinta, pero coincido en lo general.',
            '¡Gracias por compartir! Lo voy a probar esta semana.',
            'Recomiendo también consultar con un especialista antes de hacer cambios grandes.',
            'Esto me pasó exactamente igual, se siente bien no ser el único.',
            '¿Podrías compartir más detalles o alguna fuente sobre esto?',
            'Buen punto, lo aplicaré en mi rutina diaria.',
            'Depende mucho de cada organismo, pero vale la pena intentarlo.',
            'Excelente aporte, este tema no se discute lo suficiente.',
            'Yo lo complementé con más agua durante el día y mejoró bastante.',
            'Coincido, la constancia es lo que más ayuda en estos casos.',
        ];

        foreach ($topics as $t) {
            if (! isset($categories[$t['cat']])) {
                continue;
            }

            $isAnonymous = ! $t['user'] || empty($userIds);
            $topicUserId = $isAnonymous ? null : $userIds[array_rand($userIds)];
            $topicStoreId = ($t['store'] && ! empty($storeIds)) ? $storeIds[array_rand($storeIds)] : null;

            $likes = random_int(2, 45);
            $views = random_int(30, 600);

            $topic = ForumTopic::updateOrCreate(
                ['forum_category_id' => $categories[$t['cat']], 'title' => $t['title']],
                [
                    'store_id' => $topicStoreId,
                    'user_id' => $topicUserId,
                    'anonymous_name' => $isAnonymous ? self::ANONYMOUS_NAMES[array_rand(self::ANONYMOUS_NAMES)] : null,
                    'content' => $t['content'],
                    'status' => 'published',
                    'likes_count' => $likes,
                    'love_count' => random_int(0, 10),
                    'haha_count' => random_int(0, 5),
                    'wow_count' => random_int(0, 5),
                    'sad_count' => random_int(0, 3),
                    'angry_count' => random_int(0, 2),
                    'total_reactions' => $likes,
                    'reply_count' => 0,
                    'views' => $views,
                ],
            );

            $replyIds = [];
            $replyCount = $t['replies'];

            for ($i = 0; $i < $replyCount; $i++) {
                $replyIsAnonymous = empty($userIds) || random_int(0, 100) < 45;
                $replyUserId = $replyIsAnonymous ? null : $userIds[array_rand($userIds)];
                $replyToId = ($i >= 2 && random_int(0, 100) < 30 && ! empty($replyIds))
                    ? $replyIds[array_rand($replyIds)]
                    : null;

                $content = $repliesPool[$i % count($repliesPool)].' ('.($i + 1).')';

                $post = ForumPost::updateOrCreate(
                    ['forum_topic_id' => $topic->id, 'content' => $content],
                    [
                        'store_id' => (! $replyIsAnonymous && $t['store'] && ! empty($storeIds) && random_int(0, 100) < 20)
                            ? $storeIds[array_rand($storeIds)]
                            : null,
                        'user_id' => $replyUserId,
                        'anonymous_name' => $replyIsAnonymous ? self::ANONYMOUS_NAMES[array_rand(self::ANONYMOUS_NAMES)] : null,
                        'reply_to_id' => $replyToId,
                        'status' => 'active',
                        'likes_count' => random_int(0, 20),
                        'angry_count' => random_int(0, 3),
                    ],
                );

                $replyIds[] = $post->id;
            }

            $topic->update([
                'reply_count' => count($replyIds),
                'total_reactions' => $likes + ForumPost::where('forum_topic_id', $topic->id)->sum('likes_count'),
            ]);
        }

        $this->command?->info('ForumDemoSeeder: '.count($topics).' temas sembrados con respuestas.');
    }
}
