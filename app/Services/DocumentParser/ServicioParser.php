<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\IssuerData;
use App\DTOs\ScannedDocumentData;
use App\DTOs\TotalsData;

final class ServicioParser implements ParsesDocument
{
    public function supports(string $text): bool
    {
        return (bool) preg_match('/\bservicio\b/iu', $text)
            || (bool) preg_match('/\bprestaci[óo]n\s+de\s+servicios?\b/iu', $text)
            || (bool) preg_match('/\bservicio\s+t[eé]cnico\b/iu', $text);
    }

    public function parse(string $text): ScannedDocumentData
    {
        $lines = $this->lines($text);

        return new ScannedDocumentData(
            rawText: $text,
            documentType: 'SERVICIO',
            documentNumber: $this->extractDocumentNumber($text),
            issueDate: $this->extractDate($text),
            issuer: $this->extractIssuer($text),
            totals: $this->extractTotals($text),
            isScannedImage: false,
        );
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(explode("\n", str_replace("\r", '', $text)), fn(string $l) => trim($l) !== ''));
    }

    private function extractDocumentNumber(string $text): ?string
    {
        $patterns = [
            '/\b(E\d{3}-\d{1,8})\b/',
            '/n[uú]mero\s*(?:de\s*documento)?\s*:?\s*([^\n]+)/iu',
            '/documento\s*:?\s*([^\n]+)/i',
        ];

        return $this->matchPattern($patterns, $text);
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/fecha\s*(?:de\s*emisi[óo]n|del\s*servicio)?\s*:?\s*(\d{2})\/(\d{2})\/(\d{4})/iu', $text, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('/\b(\d{2})\/(\d{2})\/(\d{4})\b/', $text, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    private function extractIssuer(string $text): IssuerData
    {
        $name = null;
        $ruc = null;

        $patterns = [
            '/proveedor\s*:?\s*([^\n]+)/i',
            '/prestador\s*:?\s*([^\n]+)/i',
            '/nombre\s*:?\s*([^\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $name = trim($m[1]);
                break;
            }
        }

        if (preg_match('/ruc\s*:?\s*(\d{11})/i', $text, $m)) {
            $ruc = $m[1];
        }

        return new IssuerData(name: $name, ruc: $ruc);
    }

    private function extractTotals(string $text): ?TotalsData
    {
        $patterns = [
            '/total\s*(?:a\s*pagar|del\s*servicio)?\s*:?\s*(?:S\/|s\/)?\s*([\d,]+\.\d{2})\b/i',
            '/costo\s*(?:del\s*servicio)?\s*:?\s*(?:S\/|s\/)?\s*([\d,]+\.\d{2})\b/i',
            '/monto\s*:?\s*(?:S\/|s\/)?\s*([\d,]+\.\d{2})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return new TotalsData(grandTotal: (float) str_replace(',', '', $m[1]));
            }
        }

        if (preg_match('/\b(?:S\/|s\/)\s*([\d,]+\.\d{2})\b/', $text, $m)) {
            return new TotalsData(grandTotal: (float) str_replace(',', '', $m[1]));
        }

        return null;
    }

    private function matchPattern(array $patterns, string $text): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
