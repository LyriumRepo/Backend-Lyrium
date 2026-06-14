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

        $faqResponse = $this->faqService->find($message, $history);

        if ($faqResponse !== null) {
            $this->trackRequest($sessionId);

            return $this->success([
                'reply' => $faqResponse,
                'source' => 'faq',
            ]);
        }

        $aiResponse = $this->gemini->ask($message, $history);

        if ($aiResponse === null) {
            $this->trackRequest($sessionId);

            return $this->success([
                'reply'  => "👩🏻 No pude resolver tu consulta en este momento. Un asesor de Lyrium puede ayudarte ahora mismo:\n\n📱 WhatsApp: https://wa.me/51937093420\n\n¡Escríbenos y te atendemos de inmediato! 🌱",
                'source' => 'fallback',
            ]);
        }

        $this->trackRequest($sessionId);

        return $this->success([
            'reply' => $aiResponse,
            'source' => 'ai',
        ]);
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
