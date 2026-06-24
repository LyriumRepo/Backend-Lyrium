<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\ScannedDocumentData;

interface ParsesDocument
{
    public function supports(string $text): bool;

    public function parse(string $text): ScannedDocumentData;
}
