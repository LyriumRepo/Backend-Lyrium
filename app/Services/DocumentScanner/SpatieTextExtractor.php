<?php

declare(strict_types=1);

namespace App\Services\DocumentScanner;

use Illuminate\Support\Facades\Log;
use Spatie\PdfToText\Pdf;

final class SpatieTextExtractor implements ExtractsText
{
    public function __construct(
        private ?string $binPath = null,
    ) {}

    public function extract(string $filePath, ?string $password = null): ?string
    {
        $text = $this->extractViaSpatie($filePath, $password);

        if ($text !== null) {
            return $text;
        }

        $text = $this->extractViaExec($filePath, $password);

        if ($text !== null) {
            return $text;
        }

        $text = $this->extractViaShellExec($filePath, $password);

        return $text;
    }

    private function extractViaSpatie(string $filePath, ?string $password = null): ?string
    {
        $options = ['-layout', '-enc UTF-8'];

        if ($password !== null && $password !== '') {
            $options[] = "-upw {$password}";
        }

        try {
            $text = Pdf::getText($filePath, $this->binPath, $options);
        } catch (\Throwable $e) {
            Log::debug('Spatie PdfToText failed', ['error' => $e->getMessage()]);

            return null;
        }

        if ($text === false || trim($text) === '') {
            Log::debug('Spatie PdfToText returned empty text');

            return null;
        }

        return trim($text);
    }

    private function extractViaExec(string $filePath, ?string $password = null): ?string
    {
        if (! function_exists('exec')) {
            return null;
        }

        $bin = $this->binPath ?? 'pdftotext';

        $parts = [
            escapeshellcmd($bin),
            '-layout',
            '-enc',
            'UTF-8',
        ];

        if ($password !== null && $password !== '') {
            $parts[] = '-upw';
            $parts[] = escapeshellarg($password);
        }

        $parts[] = escapeshellarg($filePath);
        $parts[] = '-';

        $command = implode(' ', $parts);

        $output = null;
        $exitCode = -1;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            Log::debug('exec pdftotext failed', ['exitCode' => $exitCode, 'output_count' => count($output ?? [])]);

            return null;
        }

        $text = implode("\n", $output);

        if (trim($text) === '') {
            return null;
        }

        return trim($text);
    }

    private function extractViaShellExec(string $filePath, ?string $password = null): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $bin = $this->binPath ?? 'pdftotext';

        $parts = [
            escapeshellcmd($bin),
            '-layout',
            '-enc',
            'UTF-8',
        ];

        if ($password !== null && $password !== '') {
            $parts[] = '-upw';
            $parts[] = escapeshellarg($password);
        }

        $parts[] = escapeshellarg($filePath);
        $parts[] = '-';

        $command = implode(' ', $parts);

        $output = shell_exec($command);

        if ($output === null || $output === false || trim($output) === '') {
            Log::debug('shell_exec pdftotext failed', ['output' => $output]);

            return null;
        }

        return trim($output);
    }
}
