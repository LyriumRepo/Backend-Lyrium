<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\CustomerData;
use App\DTOs\IssuerData;
use App\DTOs\ScannedDocumentData;
use App\DTOs\TotalsData;

final class BoletaParser implements ParsesDocument
{
    public function supports(string $text): bool
    {
        return (bool) preg_match('/boleta\s*(?:de\s*venta)?\s*electr[óo]nica/iu', $text)
            || (bool) preg_match('/\bBOLETA\b/', $text)
                && ! (bool) preg_match('/\bfactura\b/i', $text);
    }

    public function parse(string $text): ScannedDocumentData
    {
        $lines = $this->lines($text);
        $normalized = $this->normalize($text);

        return new ScannedDocumentData(
            rawText: $text,
            documentType: 'BOLETA',
            documentNumber: $this->extractDocumentNumber($text),
            issueDate: $this->extractDate($text),
            currency: 'PEN',
            issuer: $this->extractIssuer($normalized, $lines),
            customer: $this->extractCustomer($normalized, $lines),
            totals: $this->extractTotals($text),
            isScannedImage: false,
        );
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/', ' ', $text);
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(explode("\n", str_replace("\r", '', $text)), fn(string $l) => trim($l) !== ''));
    }

    private function extractDocumentNumber(string $text): ?string
    {
        if (preg_match('/\b(B\d{3}-\d{1,8})\b/', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/Nro\.?\s*([A-Z0-9-]+)/', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/fecha\s*(?:de\s*emisi[óo]n)?\s*:?\s*(\d{2})\/(\d{2})\/(\d{4})/iu', $text, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('/\b(\d{2})\/(\d{2})\/(\d{4})\b/', $text, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    private function extractIssuer(string $text, array $lines): IssuerData
    {
        $name = null;
        $ruc = null;

        if (preg_match('/ruc\s*:?\s*(\d{11})/i', $text, $m)) {
            $ruc = $m[1];
        }

        foreach ($lines as $i => $line) {
            if (preg_match('/BOLETA/', $line) && isset($lines[$i + 1])) {
                $candidate = trim($lines[$i + 1]);
                if ($candidate !== '' && ! preg_match('/^(Nro|RUC)/', $candidate)) {
                    $name = $candidate;
                }
                break;
            }
        }

        if ($name === null && preg_match('/raz[óo]n\s*social\s*:?\s*([^\n]+)/iu', $text, $m)) {
            $name = trim($m[1]);
        }

        return new IssuerData(name: $name, ruc: $ruc);
    }

    private function extractCustomer(string $text, array $lines): CustomerData
    {
        $name = null;
        $ruc = null;

        foreach ($lines as $i => $line) {
            if (preg_match('/Señor\(es\)/i', $line) || preg_match('/CLIENTE/i', $line)) {
                if (isset($lines[$i + 1])) {
                    $candidate = trim($lines[$i + 1]);
                    if ($candidate !== '' && ! preg_match('/^(Dirección|RUC|Dni)/iu', $candidate)) {
                        $name = $candidate;
                    }
                }
                break;
            }
        }

        preg_match_all('/RUC\s*:?\s*(\d{11})/i', $text, $matches);
        if (! empty($matches[1]) && count($matches[1]) > 1) {
            $ruc = end($matches[1]);
        } elseif (preg_match('/DNI\s*:?\s*(\d{8})/i', $text, $m)) {
            $ruc = $m[1];
        }

        return new CustomerData(name: $name, ruc: $ruc);
    }

    private function extractTotals(string $text): ?TotalsData
    {
        $igv = $this->extractTotalField($text, ['I[Gg][Vv]']);
        $grandTotal = $this->extractTotalField($text, ['Importe\s*Total', 'Total\s*(?:General)?\s*(?:a\s*pagar)?', 'Monto\s*Total']);

        if ($igv === null && $grandTotal === null) {
            return null;
        }

        return new TotalsData(igv: $igv, grandTotal: $grandTotal);
    }

    private function extractTotalField(string $text, array $labels): ?float
    {
        foreach ($labels as $label) {
            if (preg_match('/' . $label . '\s*:?\s*(?:S\/|s\/)?\s*([\d,]+\.\d{2})\b/i', $text, $m)) {
                return (float) str_replace(',', '', $m[1]);
            }
        }
        foreach ($labels as $label) {
            if (preg_match('/' . $label . '\s*\n\s*([\d,]+\.\d{2})\b/i', $text, $m)) {
                return (float) str_replace(',', '', $m[1]);
            }
        }

        return null;
    }
}
