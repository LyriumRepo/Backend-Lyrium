<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\CustomerData;
use App\DTOs\IssuerData;
use App\DTOs\ItemData;
use App\DTOs\ScannedDocumentData;
use App\DTOs\TotalsData;

final class FacturaParser implements ParsesDocument
{
    public function supports(string $text): bool
    {
        return (bool) preg_match('/factura\s*electr[óo]nica/iu', $text)
            || (bool) preg_match('/\bFACTURA\b/', $text)
                && (bool) preg_match('/\bRUC\s*:?\s*\d{11}/i', $text);
    }

    public function parse(string $text): ScannedDocumentData
    {
        $lines = $this->lines($text);

        return new ScannedDocumentData(
            rawText: $text,
            documentType: 'FACTURA',
            documentNumber: $this->extractDocumentNumber($text),
            issueDate: $this->extractDate($text, 'Emisión'),
            dueDate: $this->extractDate($text, 'Vencimiento'),
            currency: $this->extractCurrency($text),
            issuer: $this->extractIssuer($text, $lines),
            customer: $this->extractCustomer($text, $lines),
            items: $this->extractItems($text, $lines),
            totals: $this->extractTotals($text),
            amountInWords: $this->extractAmountInWords($text),
            authorizationDate: $this->extractAuthorizationDate($text),
            isScannedImage: false,
        );
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(explode("\n", str_replace("\r", '', $text)), fn(string $l) => trim($l) !== ''));
    }

    private function extractDocumentNumber(string $text): ?string
    {
        if (preg_match('/Nro\.?\s*([A-Z0-9]{2,4}-\d{1,8})/', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/\b(F\d{3}-\d{1,8})\b/', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractDate(string $text, string $label): ?string
    {
        if (preg_match('/Fecha\s*de\s*' . $label . '\s*:?\s*(\d{2})\/(\d{2})\/(\d{4})/i', $text, $m)) {
            try {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            } catch (\Exception) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }
        }

        return null;
    }

    private function extractCurrency(string $text): ?string
    {
        if (preg_match('/Moneda\s*:?\s*(PEN|USD|SOLES|D[ÓO]LARES)/iu', $text, $m)) {
            $c = strtoupper($m[1]);

            return match ($c) {
                'SOLES' => 'PEN',
                'DÓLARES', 'DOLARES' => 'USD',
                default => $c,
            };
        }

        return 'PEN';
    }

    private function extractIssuer(string $text, array $lines): IssuerData
    {
        $ruc = null;
        $name = null;
        $address = null;

        // RUC is usually the first RUC in the text
        if (preg_match('/RUC\s*:?\s*(\d{11})/i', $text, $m)) {
            $ruc = $m[1];
        }

        // Name is on line after "FACTURA ELECTRÓNICA" or similar
        $idx = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/FACTURA/', $line)) {
                $idx = $i;
                break;
            }
        }
        if ($idx !== null && isset($lines[$idx + 1])) {
            $candidate = trim($lines[$idx + 1]);
            if ($candidate !== '' && ! preg_match('/^(Nro|RUC|CALLE|AV\.|MZA\.)/', $candidate)) {
                $name = $candidate;
            }
        }

        // If no name found by above, try line after RUC
        if ($name === null) {
            foreach ($lines as $i => $line) {
                if (preg_match('/RUC\s*:?\s*\d{11}/i', $line)) {
                    if (isset($lines[$i + 1])) {
                        $candidate = trim($lines[$i + 1]);
                        if ($candidate !== '' && ! preg_match('/^(FACTURA|Nro)/', $candidate)) {
                            $name = $candidate;
                        }
                    }
                    break;
                }
            }
        }

        // Address: lines between issuer name and "Señor(es)"
        $capture = false;
        $addrLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($name !== null && str_contains($trimmed, $name)) {
                $capture = true;
                continue;
            }
            if ($capture) {
                if (str_contains($trimmed, 'Señor(es)') || str_contains($trimmed, 'Señor') || str_contains($trimmed, 'Cliente')) {
                    break;
                }
                if ($trimmed !== '' && ! preg_match('/^(Nro|RUC)/', $trimmed)) {
                    $addrLines[] = $trimmed;
                }
            }
        }

        // Alternative: address after RUC line before "Señor(es)"
        if (empty($addrLines)) {
            $capture = false;
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (preg_match('/RUC\s*:?\s*\d{11}/i', $trimmed)) {
                    $capture = true;
                    continue;
                }
                if ($capture) {
                    if (str_contains($trimmed, 'Señor(es)') || str_contains($trimmed, 'Señor')) {
                        break;
                    }
                    if ($trimmed !== '' && ! preg_match('/^(FACTURA|Nro)/', $trimmed)) {
                        $addrLines[] = $trimmed;
                    }
                }
            }
        }

        if ($addrLines) {
            $address = implode(', ', $addrLines);
        }

        return new IssuerData(
            name: $name,
            ruc: $ruc,
            address: $address ?: null,
        );
    }

    private function extractCustomer(string $text, array $lines): CustomerData
    {
        $name = null;
        $ruc = null;
        $address = null;

        // Name after "Señor(es):"
        foreach ($lines as $i => $line) {
            if (preg_match('/Señor\(es\)\s*:/iu', $line) || str_contains($line, 'Señor(es)')) {
                if (isset($lines[$i + 1])) {
                    $candidate = trim($lines[$i + 1]);
                    if ($candidate !== '' && ! preg_match('/^(Dirección|Direccion|RUC)/iu', $candidate)) {
                        $name = $candidate;
                    }
                }
                break;
            }
        }

        // Address after "Dirección:" 
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (preg_match('/^Direcci[óo]n\s*:/iu', $trimmed) || preg_match('/^Direcci[óo]n$/iu', $trimmed)) {
                if (isset($lines[$i + 1])) {
                    $candidate = trim($lines[$i + 1]);
                    if ($candidate !== '' && ! preg_match('/^(RUC|Moneda)/i', $candidate)) {
                        $address = $candidate;
                    }
                }
                break;
            }
        }

        // RUC: find the second RUC in the document (customer RUC)
        preg_match_all('/RUC\s*:?\s*(\d{11})/i', $text, $matches);
        if (! empty($matches[1])) {
            $ruc = end($matches[1]);
        }

        return new CustomerData(
            name: $name,
            ruc: $ruc,
            address: $address,
        );
    }

    private function extractItems(string $text, array $lines): array
    {
        $items = [];
        $i = 0;
        $lineCount = count($lines);

        // Find where items start: look for "---" after the table header
        $foundHeader = false;
        while ($i < $lineCount) {
            if (str_contains($lines[$i], 'CÓDIGO') || str_contains($lines[$i], 'CODIGO')) {
                $foundHeader = true;
                $i++;
                break;
            }
            $i++;
        }
        if (! $foundHeader) {
            return [];
        }

        // Skip remaining header lines until we hit "---"
        while ($i < $lineCount && ! str_starts_with(trim($lines[$i]), '---')) {
            $i++;
        }

        // Parse item blocks: each starts with "---" followed by data lines
        while ($i < $lineCount) {
            $current = trim($lines[$i]);

            if (str_starts_with($current, 'Son:') || str_starts_with($current, 'Son ')) {
                break;
            }

            // Skip to next item marker or stop at totals lines
            if (str_starts_with($current, '---')) {
                // Collect data lines for this item
                $parts = [];
                $i++;
                while ($i < $lineCount) {
                    $next = trim($lines[$i]);
                    if (str_starts_with($next, '---')) {
                        break;
                    }
                    if (str_starts_with($next, 'Son:') || str_starts_with($next, 'Son ')) {
                        break;
                    }
                    if (preg_match('/^(Descuentos|Cargos|Total|I\.S\.C\.|I\.G\.V\.|ICBPER|Otros|Importe|Fecha)/', $next)) {
                        break;
                    }
                    $parts[] = $next;
                    $i++;
                }

                if (count($parts) >= 4) {
                    $description = $parts[0];
                    $quantity = (float) str_replace(',', '', $parts[1]);
                    $unitPrice = isset($parts[3]) ? (float) str_replace(',', '', $parts[3]) : null;

                    // total is typically at position 6 (PRECIO UNITARIO) or position 7 (VALOR VENTA TOTAL)
                    $total = null;
                    if (isset($parts[6])) {
                        $total = (float) str_replace(',', '', $parts[6]);
                    } elseif (isset($parts[4])) {
                        $total = (float) str_replace(',', '', $parts[4]);
                    }

                    $items[] = new ItemData(
                        description: $description,
                        quantity: $quantity,
                        unitPrice: $unitPrice,
                        total: $total,
                    );
                }
                continue;
            }

            $i++;
        }

        return $items;
    }

    private function extractTotals(string $text): ?TotalsData
    {
        $taxable = $this->extractTotalField($text, ['Valor\s*de\s*Venta\s*(?:\(?\s*Operaciones\s*Gravadas\s*\)?)?', 'Sub\s*Total', 'Gravadas?']);
        $inafect = $this->extractTotalField($text, ['Inafect[ao]s?', 'Inafecto']);
        $exempt = $this->extractTotalField($text, ['Exonerad[ao]s?', 'Exonerado']);
        $free = $this->extractTotalField($text, ['Gratuit[ao]s?', 'Gratuito']);
        $igv = $this->extractTotalField($text, ['I[Gg][Vv]', 'I\.G\.V\.']);
        $isc = $this->extractTotalField($text, ['I[Ss][Cc]', 'I\.S\.C\.']);
        $grandTotal = $this->extractTotalField($text, ['Importe\s*Total', 'Total\s*(?:General)?\s*(?:a\s*pagar)?', 'Monto\s*Total']);

        if ($taxable === null && $inafect === null && $exempt === null && $free === null && $igv === null && $grandTotal === null) {
            return null;
        }

        return new TotalsData(
            taxableAmount: $taxable,
            inafectAmount: $inafect,
            exemptAmount: $exempt,
            freeAmount: $free,
            igv: $igv,
            isc: $isc,
            grandTotal: $grandTotal,
        );
    }

    private function extractTotalField(string $text, array $labels): ?float
    {
        // Try "Label : X.XX" on same line
        foreach ($labels as $label) {
            if (preg_match('/' . $label . '\s*:?\s*(?:S\/|s\/)?\s*([\d,]+\.\d{2})\b/i', $text, $m)) {
                return (float) str_replace(',', '', $m[1]);
            }
        }

        // Try "Label" on one line, then "X.XX" on next line
        foreach ($labels as $label) {
            if (preg_match('/' . $label . '\s*\n\s*([\d,]+\.\d{2})\b/i', $text, $m)) {
                return (float) str_replace(',', '', $m[1]);
            }
        }

        return null;
    }

    private function extractAmountInWords(string $text): ?string
    {
        if (preg_match('/Son\s*:\s*(.+?)(?:\.\s|\n|$)/i', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractAuthorizationDate(string $text): ?string
    {
        if (preg_match('/Fecha\s*y\s*Hora\s*de\s*Autorizaci[óo]n\s*:?\s*(\d{2}\/\d{2}\/\d{4}\s*\d{2}:\d{2}(?::\d{2})?)/iu', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
