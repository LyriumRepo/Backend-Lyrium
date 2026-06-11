<?php

declare(strict_types=1);

namespace App\Services\DocumentScanner;

use Illuminate\Support\Facades\Log;

final readonly class OcrTextExtractor implements ExtractsText
{
    private const float DEFAULT_DPI = 300.0;

    public function __construct(
        private string $tesseractBin = 'tesseract',
        private string $language = 'spa',
    ) {}

    public function extract(string $filePath): ?string
    {
        if (! $this->isTesseractAvailable()) {
            Log::warning('Tesseract OCR no está disponible en el sistema.');

            return null;
        }

        $imagePath = $this->convertPdfToImage($filePath);

        if ($imagePath === null) {
            return null;
        }

        try {
            $outputPath = tempnam(sys_get_temp_dir(), 'ocr_');

            $escapedImage = escapeshellarg($imagePath);
            $escapedOutput = escapeshellarg($outputPath);
            $escapedLang = escapeshellarg($this->language);

            $command = "{$this->tesseractBin} {$escapedImage} {$escapedOutput} -l {$escapedLang} 2>/dev/null";

            exec($command, $output, $exitCode);

            if ($exitCode !== 0) {
                Log::warning("Tesseract falló con código {$exitCode}");

                return null;
            }

            $resultFile = "{$outputPath}.txt";

            if (! file_exists($resultFile)) {
                return null;
            }

            $text = trim(file_get_contents($resultFile));

            @unlink($resultFile);

            return $text !== '' ? $text : null;
        } finally {
            if (isset($imagePath)) {
                @unlink($imagePath);
            }
            if (isset($outputPath)) {
                @unlink($outputPath);
            }
        }
    }

    private function isTesseractAvailable(): bool
    {
        $command = escapeshellcmd($this->tesseractBin) . ' --version 2>/dev/null';
        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    private function convertPdfToImage(string $pdfPath): ?string
    {
        if (! extension_loaded('imagick')) {
            Log::warning('Imagick no está disponible para convertir PDF a imagen.');

            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(self::DEFAULT_DPI, self::DEFAULT_DPI);
            $imagick->readImage("{$pdfPath}[0]");
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(90);

            $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_page_') . '.png';
            $imagick->writeImage($tmpPath);
            $imagick->clear();

            return $tmpPath;
        } catch (\ImagickException $e) {
            Log::warning('Error al convertir PDF a imagen: ' . $e->getMessage());

            return null;
        }
    }
}
