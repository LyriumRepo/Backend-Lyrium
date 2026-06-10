<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAIService
{
    private const TIMEOUT = 15;
    private const CONNECT_TIMEOUT = 5;
    private const MODEL = 'gpt-4o-mini';

    public function ask(string $prompt, array $history = []): ?string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::warning('OpenAI API key not configured');
            return null;
        }

        $messages = $this->buildMessages($prompt, $history);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'messages' => $messages,
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildMessages(string $prompt, array $history): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'Eres un asistente virtual de soporte de Lyrium Biomarketplace, una plataforma peruana de comercio electrónico.',
                    '',
                    'REGLAS ESTRICTAS:',
                    '- Responde UNICAMENTE preguntas relacionadas con Lyrium, sus productos, servicios y funcionalidades.',
                    '- Si te preguntan sobre temas NO relacionados con Lyrium (política, religión, deportes, tecnología general, etc.), responde educadamente que solo puedes ayudar con temas relacionados a Lyrium Biomarketplace.',
                    '- No proporciones información falsa o especulativa sobre Lyrium.',
                    '- Sé amable, profesional y empático en tus respuestas.',
                    '- Responde en español (Perú).',
                    '- Usa un tono cálido y servicial.',
                    '- Si no sabes la respuesta, indicale al usuario que contacte a soporte@lyrium.pe.',
                    '',
                    'Lyrium es un Biomarketplace peruano donde vendedores ofrecen productos y servicios especializados en bienestar, salud natural y estilo de vida sostenible.',
                    '',
                    'Funcionalidades clave de Lyrium:',
                    '- Compra y venta de productos con múltiples métodos de pago',
                    '- Sistema de chat Cliente-Vendedor para consultas',
                    '- Facturación electrónica con validación SUNAT',
                    '- Seguimiento de pedidos y envíos',
                    '- Registro y gestión de tiendas para vendedores',
                    '- Sistema de reseñas y valoraciones',
                    '- Planes de suscripción con diferentes comisiones',
                    '- Soporte al cliente via tickets, correo y WhatsApp',
                    '- Notificaciones en tiempo real',
                ]),
            ],
        ];

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry['role'] ?? 'user',
                'content' => $entry['content'] ?? '',
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        return $messages;
    }
}
