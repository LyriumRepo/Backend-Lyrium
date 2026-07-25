<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class GenericExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private const HEADER_BG    = '123C26';
    private const HEADER_FONT  = 'A3E635';
    private const BODY_BG      = '0C2316';
    private const ALT_BG       = '143420';
    private const BODY_FONT    = 'D7EBCD';
    private const BORDER_COLOR = '265A37';

    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Header row
        $styles[1] = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => self::HEADER_FONT],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => self::HEADER_BG],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => self::BORDER_COLOR],
                ],
            ],
        ];

        // Body rows with alternating colors
        $rowCount = count($this->rows);
        for ($i = 0; $i < $rowCount; $i++) {
            $rowNum = $i + 2;
            $isEven = $i % 2 === 1;

            $styles[$rowNum] = [
                'font' => [
                    'color' => ['rgb' => self::BODY_FONT],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => $isEven ? self::ALT_BG : self::BODY_BG],
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => 'hair',
                        'color' => ['rgb' => self::BORDER_COLOR],
                    ],
                ],
            ];
        }

        return $styles;
    }
}
