<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\BankStatementLineData;
use App\DTOs\ScannedDocumentData;
use App\Models\GlossaryEntry;
use App\Models\PendingGlossaryTerm;
use Illuminate\Support\Facades\Log;

final class EstadoCuentaBcpParser implements ParsesDocument
{
    private const KNOWN_MED = ['BPI', 'CAJ', 'INT', 'VEN', 'POS', 'TLC', 'BPT'];

    public function supports(string $text): bool
    {
        $hasEstadoCuenta = (bool) preg_match('/estado\s*de\s*cuenta/i', $text);
        $hasBcp = (bool) preg_match('/\bBCP\b|\bbanco\s*de\s*cr[eé]dito\b/i', $text);

        return $hasEstadoCuenta && $hasBcp;
    }

    public function parse(string $text): ScannedDocumentData
    {
        $lines = $this->nonEmptyLines($text);
        $periodFull = $this->extractPeriodFull($lines);
        $period = $this->extractPeriod($lines);

        $resumen = $this->extractResumenDelMes($lines);

        $tableLines = $this->locateTransactionTable($lines);
        $parsedLines = $this->parseTransactionLines($tableLines);
        $glossaryEntries = GlossaryEntry::all()->keyBy('key');

        $bankLines = [];
        foreach ($parsedLines as $parsed) {
            $match = $this->matchGlossary($parsed['description'], $glossaryEntries);

            if ($match === null) {
                $this->recordUnmatchedTerm($parsed['description']);
            }

            $isCharge = $parsed['is_charge'] ?? true;

            $bankLines[] = new BankStatementLineData(
                date: $parsed['date'],
                description: $parsed['description'],
                amount: $parsed['amount'],
                balance: $parsed['balance'] ?? null,
                reference: $parsed['reference'],
                charge: $isCharge ? $parsed['amount'] : null,
                deposit: $isCharge ? null : $parsed['amount'],
                glossaryKey: $match['key'] ?? null,
                glossaryDescription: $match['description'] ?? null,
                hour: $parsed['hour'] ?? null,
                med: $parsed['med'] ?? null,
                tipo: $parsed['tipo'] ?? null,
                place: $parsed['place'] ?? null,
                origen: $parsed['origen'] ?? null,
                numOp: $parsed['numOp'] ?? null,
                sucAge: $parsed['sucAge'] ?? null,
            );
        }

        return new ScannedDocumentData(
            rawText: $text,
            documentType: 'ESTADO_CUENTA_BCP',
            documentNumber: $this->extractAccountNumber($lines),
            issueDate: $period,
            period: $periodFull,
            openingBalance: $resumen['openingBalance'] ?? null,
            closingBalance: $resumen['closingBalance'] ?? null,
            bankStatementLines: $bankLines,
            isScannedImage: false,
        );
    }

    private function nonEmptyLines(string $text): array
    {
        return array_values(array_filter(
            explode("\n", str_replace("\r", '', $text)),
            fn (string $l) => trim($l) !== '',
        ));
    }

    private function extractPeriodFull(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/DEL\s+(\d{2}\/\d{2}\/\d{4})\s*AL\s*(\d{2}\/\d{2}\/\d{4}|\d{2}\/\d{2}\/\d{2})/iu', $line, $m)) {
                return trim($m[0]);
            }
        }

        return null;
    }

    private function extractPeriod(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/DEL\s+(\d{2}\/\d{2}\/\d{4})/i', $line, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    private function extractAccountNumber(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/cuenta\s+([\d\-]+)/iu', $line, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    private function extractResumenDelMes(array $lines): array
    {
        $openingBalance = null;
        $closingBalance = null;

        $idx = null;
        foreach ($lines as $i => $line) {
            if (str_contains($line, 'RESUMEN DEL MES')) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return [];
        }

        $resumenArea = array_slice($lines, $idx, 15);

        // Find the data line containing balances (the line after the one with date headers)
        $dataLine = null;
        $foundDateHeader = false;

        foreach ($resumenArea as $line) {
            if (preg_match('/^\s*\d{2}\/\d{2}\/\d{4}\b/', $line)) {
                $foundDateHeader = true;

                continue;
            }

            if ($foundDateHeader && preg_match('/^\s{4,}/', $line)) {
                $dataLine = $line;
                break;
            }

            // Also check if this line itself has both a date AND a decimal
            if (preg_match('/^\s*\d{2}\/\d{2}\/\d{4}\s+/', $line)) {
                if (preg_match('/\b(\d{1,3}(?:,\d{3})*\.\d{2})\b/', $line)) {
                    $dataLine = $line;
                    break;
                }
            }
        }

        if ($dataLine !== null) {
            if (preg_match_all('/\b(\d{1,3}(?:,\d{3})*\.\d{2})\b/', $dataLine, $mBal)) {
                $numbers = array_map(fn ($v) => (float) str_replace(',', '', $v), $mBal[1]);
                $count = count($numbers);
                if (isset($numbers[0])) {
                    $openingBalance = $numbers[0];
                }
                // BCP layout: columns are opening, deposits(2), charges(2), interests(2), closing, avg
                // closing balance is second-to-last when there are 9 numbers
                if ($count >= 9) {
                    $closingBalance = $numbers[7];
                } elseif ($count >= 2) {
                    $closingBalance = end($numbers);
                }
            }
        }

        return [
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ];
    }

    private function locateTransactionTable(array $lines): array
    {
        $headerIdx = null;

        foreach ($lines as $i => $line) {
            if (str_contains($line, 'ACTIVIDADES')) {
                $headerIdx = $i;
                break;
            }
        }

        if ($headerIdx === null) {
            return [];
        }

        $dataLines = array_slice($lines, $headerIdx + 1);

        $result = [];
        foreach ($dataLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || preg_match('/^\-{3,}/', $trimmed)) {
                break;
            }

            if (preg_match('/^\d{2}-\d{2}\s+/', $trimmed)) {
                $result[] = $trimmed;
            }
        }

        return $result;
    }

    private function parseTransactionLines(array $lines): array
    {
        $result = [];
        foreach ($lines as $line) {
            $parsed = $this->parseLine($line);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    private function parseLine(string $line): ?array
    {
        // Overall structure: date + details + amount[-] + balance
        if (! preg_match('/^(\d{2})-(\d{2})\s+(.+)\s+(\d[\d,]*\.\d{2})\s*(\-?)\s+(\d[\d,]*\.\d{2})\s*$/', $line, $m)) {
            return null;
        }

        $day = $m[1];
        $month = $m[2];
        $details = trim($m[3]);
        $amount = (float) str_replace(',', '', $m[4]);
        $isCharge = $m[5] === '-';
        $balance = (float) str_replace(',', '', $m[6]);

        // Extract structured fields from the details section
        $hora = null;
        $med = null;
        $tipo = null;
        $place = null;
        $origen = null;
        $numOp = null;
        $sucAge = null;

        // HORA: HH:MM pattern
        if (preg_match('/\b(\d{2}:\d{2})\b/', $details, $mH)) {
            $hora = $mH[1];
        }

        // MED: known medium codes
        if (preg_match('/\b(BPI|CAJ|INT|VEN|POS|TLC|BPT)\b/i', $details, $mM)) {
            $med = strtoupper($mM[1]);
            // Extract place: text after MED until next known field pattern
            $medPos = strpos($details, $mM[0]);
            if ($medPos !== false) {
                $afterMed = substr($details, $medPos + strlen($mM[0]));
                $afterMed = trim($afterMed);
                // Everything up to the next 2+ spaces or end is the place
                if (preg_match('/^(.+?)(?:\s{2,}|$)/', $afterMed, $mP)) {
                    $placeText = trim($mP[1]);
                    if ($placeText !== '' && ! preg_match('/^\d/', $placeText)) {
                        $place = $placeText;
                    }
                }
            }
        }

        // TIPO: 3-4 digits at/near the end of details
        if (preg_match('/\b(\d{3,4})\s*$/', $details, $mT)) {
            $tipo = $mT[1];
        }

        // SUC-AGE: XXX-XXX pattern
        if (preg_match('/\b(\d{3}-\d{3})\b/', $details, $mS)) {
            $sucAge = $mS[1];
        }

        // NUM OP: 6+ digit number (not a date or year)
        if (preg_match('/\b(\d{6,})\b/', $details, $mN)) {
            $numOp = $mN[1];
        }

        // ORIGEN: alphanumeric code (5-8 chars) after HORA and before TIPO
        if (preg_match('/\b([A-Z0-9]{5,8})\b/', $details, $mO)) {
            $origen = $mO[1];
        }

        $reference = $this->extractReference($details);

        return [
            'date' => "{$day}/{$month}",
            'description' => $details,
            'reference' => $reference,
            'amount' => $amount,
            'is_charge' => $isCharge,
            'balance' => $balance,
            'hour' => $hora,
            'med' => $med,
            'tipo' => $tipo,
            'place' => $place,
            'origen' => $origen,
            'numOp' => $numOp,
            'sucAge' => $sucAge,
        ];
    }

    private function extractReference(string $description): ?string
    {
        if (preg_match('/\b(\d{6,})\b/', $description, $m)) {
            return $m[1];
        }

        return null;
    }

    private function matchGlossary(string $description, iterable $entries): ?array
    {
        foreach ($entries as $entry) {
            foreach ($entry->search_patterns as $pattern) {
                if (@preg_match("#{$pattern}#iu", '') !== false) {
                    if (preg_match("#{$pattern}#iu", $description)) {
                        return [
                            'key' => $entry->key,
                            'description' => $entry->description,
                            'is_income' => $entry->is_income,
                        ];
                    }
                } elseif (str_contains(mb_strtoupper($description), mb_strtoupper($pattern))) {
                    return [
                        'key' => $entry->key,
                        'description' => $entry->description,
                        'is_income' => $entry->is_income,
                    ];
                }
            }
        }

        return null;
    }

    private function recordUnmatchedTerm(string $description): void
    {
        $normalized = trim(preg_replace('/[^A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s.]+/', ' ', $description));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = mb_strtoupper(trim($normalized));

        if (mb_strlen($normalized) < 3) {
            return;
        }

        try {
            PendingGlossaryTerm::firstOrCreate(
                ['term' => $normalized, 'status' => 'pending'],
                [
                    'term' => $normalized,
                    'document_type' => 'ESTADO_CUENTA_BCP',
                    'source_field' => 'description',
                    'status' => 'pending',
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to record pending glossary term', [
                'term' => $normalized,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
