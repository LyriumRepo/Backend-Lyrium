<?php

declare(strict_types=1);

namespace App\Services\DocumentScanner;

use App\DTOs\ScannedDocumentData;
use App\Services\DocumentParser\DocumentParserService;

final class DocumentScannerService
{
    public function __construct(
        private readonly SpatieTextExtractor $spatieExtractor = new SpatieTextExtractor,
        private readonly OcrTextExtractor $ocrExtractor = new OcrTextExtractor,
        private readonly DocumentParserService $parser = new DocumentParserService,
    ) {}

    public function scan(string $filePath, ?string $password = null): ScannedDocumentData
    {
        $text = $this->spatieExtractor->extract($filePath, $password);
        $isScanned = false;

        if ($text === null) {
            $text = $this->ocrExtractor->extract($filePath, $password);
            $isScanned = true;
        }

        if ($text === null) {
            return new ScannedDocumentData(
                rawText: '',
                isScannedImage: false,
            );
        }

        $result = $this->parser->parse($text);

        return new ScannedDocumentData(
            rawText: $result->rawText,
            documentType: $result->documentType,
            documentNumber: $result->documentNumber,
            issueDate: $result->issueDate,
            dueDate: $result->dueDate,
            currency: $result->currency,
            issuer: $result->issuer,
            customer: $result->customer,
            payment: $result->payment,
            totals: $result->totals,
            amountInWords: $result->amountInWords,
            items: $result->items,
            serviceDescription: $result->serviceDescription,
            bankStatementLines: $result->bankStatementLines,
            period: $result->period,
            openingBalance: $result->openingBalance,
            closingBalance: $result->closingBalance,
            isScannedImage: $isScanned,
            authorizationDate: $result->authorizationDate,
            source: $isScanned ? 'OCR' : 'PDF_TEXT',
        );
    }
}
