<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeminiService
{
    private const TIMEOUT = 15;
    private const CONNECT_TIMEOUT = 5;
    private const MODEL = 'gemini-flash-lite-latest';

    public function ask(string $prompt, array $history = []): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent?key=' . $apiKey,
                    $this->buildPayload($prompt, $history)
                );

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Gemini request failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildPayload(string $prompt, array $history): array
    {
        $contents = [];

        foreach ($history as $entry) {
            $role = $entry['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $entry['content'] ?? ''],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt],
            ],
        ];

        return [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => implode("\n", [
                        'Eres un asistente virtual de soporte de Lyrium Biomarketplace, una plataforma peruana de comercio electrónico.',
                        '',
                        'REGLAS ESTRICTAS:',
                        '- Responde UNICAMENTE preguntas relacionadas con Lyrium, sus productos, servicios y funcionalidades.',
                        '- Si te preguntan sobre temas NO relacionados con Lyrium (política, religión, deportes, tecnología general, etc.), responde educadamente que solo puedes ayudar con temas relacionados a Lyrium Biomarketplace.',
                        '- No proporciones información falsa o especulativa sobre Lyrium.',
                        '- Sé amable, profesional y empático en tus respuestas.',
                        '- Responde en español (Perú).',
                        '- Usa un tono cálido y servicial.',
                        '- Si no sabes la respuesta, indicale al usuario que contacte a soporte@lyrium.pe o al WhatsApp 51937093420.',
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
                        '- Soporte al cliente via tickets, correo y chat en línea',
                        '- Notificaciones en tiempo real',
                    ])],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 500,
                'temperature' => 0.7,
            ],
        ];
    }
}
