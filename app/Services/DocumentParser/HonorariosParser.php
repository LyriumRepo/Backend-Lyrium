<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\CustomerData;
use App\DTOs\IssuerData;
use App\DTOs\PaymentData;
use App\DTOs\ScannedDocumentData;

final class HonorariosParser implements ParsesDocument
{
    public function supports(string $text): bool
    {
        return (bool) preg_match('/recibo\s*(?:por|de)\s*honorarios/i', $text);
    }

    public function parse(string $text): ScannedDocumentData
    {
        $lines = $this->lines($text);
        $normalized = $this->normalize($text);

        return new ScannedDocumentData(
            rawText: $text,
            documentType: 'RECIBO_POR_HONORARIOS',
            documentNumber: $this->extractDocumentNumber($lines),
            issueDate: $this->extractIssueDate($lines),
            currency: 'PEN',
            issuer: $this->extractIssuer($normalized, $lines),
            customer: $this->extractCustomer($normalized, $lines),
            payment: $this->extractPayment($normalized, $lines),
            serviceDescription: $this->extractServiceDescription($lines),
            isScannedImage: false,
        );
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/', ' ', $text);
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(explode("\n", str_replace("\r", '', $text)), fn (string $l) => trim($l) !== ''));
    }

    private function extractDocumentNumber(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/Nro:\s*(.+)/i', $line, $m)) {
                return trim(preg_replace('/\s+/', ' ', $m[1]));
            }
        }

        return null;
    }

    private function extractIssueDate(array $lines): ?string
    {
        $months = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
        ];

        for ($i = 0; $i < count($lines) - 3; $i++) {
            if (str_contains($lines[$i], 'Fecha de emisión')
                && isset($lines[$i + 1], $lines[$i + 2], $lines[$i + 3])
                && is_numeric($lines[$i + 1])
                && $lines[$i + 2] === 'de'
            ) {
                $day = $lines[$i + 1];
                $monthStr = strtolower($lines[$i + 3]);
                $year = $lines[$i + 4] ?? '';
                $year = preg_replace('/^del\s*/', '', $year);

                if (isset($months[$monthStr]) && is_numeric($year)) {
                    return sprintf('%s-%s-%02s', $year, $months[$monthStr], $day);
                }
            }
        }

        return null;
    }

    private function extractIssuer(string $normalized, array $lines): IssuerData
    {
        $name = $lines[0] ?? null;
        $ruc = null;
        $address = null;

        if ($name && (str_contains($name, 'R.U.C.') || str_contains($name, 'RECIBO'))) {
            $name = null;
        }

        if (preg_match('/R\.U\.C\.\s*(\d{11})/', $normalized, $m)) {
            $ruc = $m[1];
        }

        // Address: lines between R.U.C. line and "Recibí de:"
        $capture = false;
        $addrLines = [];
        foreach ($lines as $line) {
            if (str_contains($line, 'R.U.C.')) {
                $capture = true;

                continue;
            }
            if ($capture) {
                if (str_contains($line, 'Recibí de') || str_contains($line, 'Recibi de')) {
                    break;
                }
                // Skip known non-address lines
                if (str_contains($line, 'RECIBO') || str_contains($line, 'Nro:') || str_contains($line, 'TELÉFONO') || $line === '-') {
                    continue;
                }
                $addrLines[] = $line;
            }
        }

        if ($addrLines) {
            $address = implode(', ', $addrLines);
        }

        return new IssuerData(name: $name, ruc: $ruc, address: $address);
    }

    private function extractCustomer(string $normalized, array $lines): CustomerData
    {
        $name = null;
        $ruc = null;
        $address = null;

        // Name: line after "Recibí de:"
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (str_contains($lines[$i], 'Recibí de') || str_contains($lines[$i], 'Recibi de')) {
                $name = $lines[$i + 1] ?? null;
                break;
            }
        }

        // RUC: the second RUC in the document
        preg_match_all('/\b(\d{11})\b/', $normalized, $matches);
        if (isset($matches[1][1])) {
            $ruc = $matches[1][1];
        }

        // Address: first line starting with AV. or MZA. after "Domiciliado en"
        $found = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'Domiciliado en')) {
                $found = true;

                continue;
            }
            if ($found && (str_starts_with($line, 'AV.') || str_starts_with($line, 'MZA.') || str_starts_with($line, 'CALLE') || str_starts_with($line, 'JR.'))) {
                $address = $line;
                break;
            }
        }

        return new CustomerData(name: $name, ruc: $ruc, address: $address);
    }

    private function extractPayment(string $normalized, array $lines): PaymentData
    {
        $paymentMethod = null;
        $amountWords = null;
        $grossAmount = null;
        $retention = null;
        $netAmount = null;
        $currency = null;

        if (preg_match('/AL\s+CONTADO/', $normalized)) {
            $paymentMethod = 'AL CONTADO';
        }

        // Amount words: line between blank lines before "Por concepto de"
        $found = false;
        foreach ($lines as $i => $line) {
            if (preg_match('/^CINCUENTA|^CIEN|^DOSCIENTOS|^TRESCIENTOS|^CUATROCIENTOS|^QUINIENTOS|^SEISCIENTOS|^SETECIENTOS|^OCHOCIENTOS|^NOVECIENTOS|^MIL|^UN\s/', $line)
                && str_contains($line, 'SOLES')) {
                $amountWords = $line;
                break;
            }
        }

        // Fallback: extract from "La suma de:" in normalized text, terminated by newline
        if ($amountWords === null) {
            if (preg_match('/La\s*suma\s*de\s*:\s*(.+?)(?:\n|$)/i', $normalized, $m)) {
                $amountWords = trim($m[1]);
            }
        }

        // Amounts from normalized text
        if (preg_match('/Total\s*por\s*honorarios\s*:?\s*([\d,]+\.\d{2})/', $normalized, $m)) {
            $grossAmount = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/Retenci[óo]n\s*\(?\s*8\s*%?\s*\)?\s*IR\s*:?\s*\(?\s*([\d,]+\.\d{2})\s*\)?/u', $normalized, $m)) {
            $retention = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/Total\s*Neto\s*Recibido\s*:?\s*([\d,]+\.\d{2})/', $normalized, $m)) {
            $netAmount = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/\b(SOLES|D[ÓO]LARES|USD|PEN)\b/u', $normalized, $m)) {
            $currency = strtoupper($m[1]);
        }

        return new PaymentData(
            paymentMethod: $paymentMethod,
            amountWords: $amountWords,
            grossAmount: $grossAmount,
            retentionIr: $retention,
            netAmount: $netAmount,
            currency: $currency,
        );
    }

    private function extractServiceDescription(array $lines): ?string
    {
        foreach ($lines as $i => $line) {
            if (str_starts_with($line, 'Por concepto de')) {
                return trim(substr($line, strlen('Por concepto de')));
            }
        }

        return null;
    }
}
