<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatBotFaqService;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ChatBotController extends Controller
{
    private const MAX_MESSAGE_LENGTH = 2000;

    private const RATE_LIMIT_MAX = 10;

    private const RATE_LIMIT_WINDOW = 60;

    private const WHATSAPP_NUMBER = '51937093420';

    private ChatBotFaqService $faqService;
    private GeminiService $gemini;

    public function __construct(ChatBotFaqService $faqService, GeminiService $gemini)
    {
        $this->faqService = $faqService;
        $this->gemini = $gemini;
    }

    public function ask(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id');

        if (empty($sessionId) || strlen($sessionId) > 64) {
            return $this->error('Sesión inválida.', 400);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:' . self::MAX_MESSAGE_LENGTH,
            'history' => 'sometimes|array',
            'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:' . self::MAX_MESSAGE_LENGTH,
        ]);

        if ($this->isRateLimited($sessionId)) {
            return $this->errorWithCode(
                'RATE_LIMITED',
                'Demasiadas solicitudes. Intenta de nuevo en un minuto.',
                429
            );
        }

        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];

        $user = $request->user('sanctum');
        $role = $user?->getRoleNames()->first();

        $faqResponse = $this->faqService->find($message, $history, $role);

        if ($faqResponse !== null) {
            $this->trackRequest($sessionId);

            if ($this->faqService->isHandoffResponse($faqResponse)) {
                return $this->success([
                    'reply'         => $faqResponse,
                    'source'        => 'handoff',
                    'whatsapp_url'  => $this->buildWhatsAppUrl(),
                ]);
            }

            return $this->success([
                'reply' => $faqResponse,
                'source' => 'faq',
            ]);
        }

        $aiResponse = $this->gemini->ask($message, $history, $role);

        if ($aiResponse === null) {
            $this->trackRequest($sessionId);

            return $this->success([
                'reply'        => "Para esta consulta, uno de nuestros asesores podrá ayudarte mejor. 🌿\n\nHaz clic en el botón de abajo para continuar por WhatsApp — ya tendrán el contexto de tu pregunta.\n\n🕘 Atención: lun–vie 9:00–18:00 | sáb 9:00–13:00",
                'source'       => 'handoff',
                'whatsapp_url' => $this->buildWhatsAppUrl(),
            ]);
        }

        $this->trackRequest($sessionId);

        // Si Gemini incluyó el link de WhatsApp en su respuesta, ya decidió
        // que el usuario necesita atención humana — activar handoff con contexto.
        if (str_contains($aiResponse, 'wa.me/')) {
            return $this->success([
                'reply'        => $aiResponse,
                'source'       => 'handoff',
                'whatsapp_url' => $this->buildWhatsAppUrl(),
            ]);
        }

        return $this->success([
            'reply' => $aiResponse,
            'source' => 'ai',
        ]);
    }

    private function buildWhatsAppUrl(): string
    {
        return 'https://wa.me/' . self::WHATSAPP_NUMBER;
    }

    private function isRateLimited(string $sessionId): bool
    {
        $key = 'chatbot_rate_' . $sessionId;
        $data = cache()->get($key, ['count' => 0, 'window' => time()]);

        if ($data['window'] < time()) {
            return false;
        }

        return $data['count'] >= self::RATE_LIMIT_MAX;
    }

    private function trackRequest(string $sessionId): void
    {
        $key = 'chatbot_rate_' . $sessionId;
        $data = cache()->get($key, ['count' => 0, 'window' => time() + self::RATE_LIMIT_WINDOW]);

        $data['count']++;
        $data['window'] = max($data['window'], time() + self::RATE_LIMIT_WINDOW);

        cache()->put($key, $data, now()->addMinutes(2));
    }
}
