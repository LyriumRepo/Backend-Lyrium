<?php

declare(strict_types=1);

namespace App\Services\DocumentScanner;

use Spatie\PdfToText\Pdf;

final readonly class SpatieTextExtractor implements ExtractsText
{
    public function __construct(
        private ?string $binPath = null,
    ) {}

    public function extract(string $filePath): ?string
    {
        $text = Pdf::getText($filePath, $this->binPath);

        if ($text === false || trim($text) === '') {
            return null;
        }

        return trim($text);
    }
}
