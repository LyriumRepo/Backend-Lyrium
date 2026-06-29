<?php

declare(strict_types=1);

namespace App\Services;

final class ContentModerationService
{
    private array $blockedWords;

    private array $messages;

    public function __construct()
    {
        $this->blockedWords = config('content-moderation.blocked_words', []);
        $this->messages = config('content-moderation.messages', []);
    }

    public function check(string $content): ?array
    {
        $normalized = $this->normalize($content);

        foreach ($this->blockedWords as $category => $words) {
            foreach ($words as $word) {
                if ($this->matches($normalized, $word)) {
                    return [
                        'category' => $category,
                        'word' => $word,
                        'message' => $this->messages[$category] ?? 'Tu mensaje contiene contenido no permitido.',
                    ];
                }
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u'],
            $text
        );

        $text = str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['o', 'i', '', 'e', 'a', 's', 'g', 't', 'b', 'g'],
            $text
        );

        $text = preg_replace('/[^a-z\s]/', '', $text);

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function matches(string $text, string $word): bool
    {
        $word = mb_strtolower($word, 'UTF-8');

        $word = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $word
        );

        return preg_match('/\b'.preg_quote($word, '/').'\b/', $text) === 1;
    }
}
