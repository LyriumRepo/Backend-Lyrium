<?php

declare(strict_types=1);

namespace App\Services\DocumentScanner;

interface ExtractsText
{
    public function extract(string $filePath, ?string $password = null): ?string;
}
