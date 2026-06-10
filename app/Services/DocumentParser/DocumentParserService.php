<?php

declare(strict_types=1);

namespace App\Services\DocumentParser;

use App\DTOs\ScannedDocumentData;

final class DocumentParserService
{
    /** @var ParsesDocument[] */
    private readonly array $parsers;

    public function __construct(
        ?FacturaParser $facturaParser = null,
        ?HonorariosParser $honorariosParser = null,
        ?BoletaParser $boletaParser = null,
        ?ServicioParser $servicioParser = null,
    ) {
        $this->parsers = array_filter([
            $facturaParser ?? new FacturaParser(),
            $honorariosParser ?? new HonorariosParser(),
            $boletaParser ?? new BoletaParser(),
            $servicioParser ?? new ServicioParser(),
        ]);
    }

    public function parse(string $text): ScannedDocumentData
    {
        $text = trim($text);

        if ($text === '') {
            return new ScannedDocumentData(rawText: '');
        }

        foreach ($this->parsers as $parser) {
            if ($parser->supports($text)) {
                return $parser->parse($text);
            }
        }

        return new ScannedDocumentData(rawText: $text);
    }
}
