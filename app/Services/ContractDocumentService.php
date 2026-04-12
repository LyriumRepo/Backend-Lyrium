<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Store;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\TemplateProcessor;

final class ContractDocumentService
{
    /** Path relativo al disco 'local' donde se guarda el template subido por el admin */
    public const TEMPLATE_PATH = 'templates/convenio_template.docx';

    /**
     * Genera el documento Word del convenio y lo guarda en storage.
     * Si existe un template subido por el admin, lo usa con TemplateProcessor.
     * Si no, genera el documento por código (fallback).
     * Retorna el path relativo para guardar en Contract::file_path.
     */
    public function generate(Store $store, string $contractNumber): string
    {
        $store->load('subscription.plan');

        $templateAbs = storage_path('app/private/' . self::TEMPLATE_PATH);

        if (file_exists($templateAbs)) {
            return $this->generateFromTemplate($store, $contractNumber, $templateAbs);
        }

        return $this->generateFromCode($store, $contractNumber);
    }

    /**
     * Genera el documento rellenando el template Word con TemplateProcessor.
     */
    private function generateFromTemplate(Store $store, string $contractNumber, string $templatePath): string
    {
        $vars = $this->buildVars($store, $contractNumber);

        $processor = new TemplateProcessor($templatePath);

        foreach ($vars as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value));
        }

        [$absDir, $relDir, $filename] = $this->resolvePaths($store, $contractNumber);

        if (! is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $processor->saveAs("{$absDir}/{$filename}");

        return "{$relDir}/{$filename}";
    }

    /**
     * Genera el documento por código (fallback cuando no hay template).
     */
    private function generateFromCode(Store $store, string $contractNumber): string
    {
        $store->load('subscription.plan');

        $planName   = $store->subscription?->plan?->name ?? 'EMPRENDE';
        $commission = $store->subscription?->plan?->commission_rate
            ? number_format((float) $store->subscription->plan->commission_rate * 100, 0) . '%'
            : '5%';

        $company      = $store->razon_social ?? $store->trade_name ?? 'Sin razón social';
        $ruc          = $store->ruc ?? '—';
        $repNombre    = $store->rep_legal_nombre ?? '—';
        $repDni       = $store->rep_legal_dni ?? '—';
        $direccion    = $store->direccion_fiscal ?? $store->address ?? '—';
        $email        = $store->corporate_email ?? '—';
        $fechaInicio  = now()->format('d/m/Y');
        $ciudad       = 'Lima';

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        // ── Estilos ──────────────────────────────────────────────────────────
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 14, 'color' => '1a5276'], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 11, 'color' => '1a5276'], ['spaceAfter' => 60]);

        $boldStyle   = ['bold' => true];
        $normalStyle = ['size' => 11];
        $paraStyle   = ['spaceAfter' => 120, 'lineHeight' => 1.5];
        $centerPara  = ['alignment' => Jc::CENTER, 'spaceAfter' => 80];

        $section = $phpWord->addSection([
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
            'marginTop'    => 1134,
            'marginBottom' => 1134,
        ]);

        // ── Encabezado ───────────────────────────────────────────────────────
        $section->addText('LYRIUM', ['bold' => true, 'size' => 20, 'color' => '1a5276'], $centerPara);
        $section->addText('CONVENIO DIGITAL DE PARTICIPACIÓN EN PLATAFORMA', ['bold' => true, 'size' => 13, 'color' => '1a5276'], $centerPara);
        $section->addText("N° {$contractNumber}", ['bold' => true, 'size' => 11], $centerPara);
        $section->addLine(['weight' => 1, 'color' => '1a5276', 'width' => 5000]);
        $section->addTextBreak(1);

        // ── PARTES ───────────────────────────────────────────────────────────
        $section->addTitle('PARTES DEL CONVENIO', 2);

        $textRun = $section->addTextRun($paraStyle);
        $textRun->addText('LYRIUM S.A.C.', $boldStyle);
        $textRun->addText(', empresa debidamente constituida bajo las leyes de la República del Perú, con RUC N° 20612345678, con domicilio en Av. La Marina 1234, Lima — Perú, en adelante denominada ', $normalStyle);
        $textRun->addText('"LYRIUM"', $boldStyle);
        $textRun->addText('.', $normalStyle);

        $textRun2 = $section->addTextRun($paraStyle);
        $textRun2->addText($company, $boldStyle);
        $textRun2->addText(", con RUC N° {$ruc}, representada por ", $normalStyle);
        $textRun2->addText($repNombre, $boldStyle);
        $textRun2->addText(", identificado/a con DNI N° {$repDni}, con domicilio fiscal en {$direccion}, correo corporativo: {$email}; en adelante denominado/a ", $normalStyle);
        $textRun2->addText('"EL VENDEDOR"', $boldStyle);
        $textRun2->addText('.', $normalStyle);

        $section->addTextBreak(1);

        // ── CLÁUSULA 1 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA PRIMERA — OBJETO DEL CONVENIO', 2);
        $section->addText(
            'El presente convenio regula las condiciones de participación de EL VENDEDOR en la plataforma digital LYRIUM, '.
            'un marketplace especializado en productos y servicios biosaludables y orgánicos, operado en la República del Perú. '.
            'EL VENDEDOR se compromete a utilizar la plataforma exclusivamente para la comercialización de productos y/o servicios '.
            'que cumplan con los estándares de calidad y autenticidad establecidos por LYRIUM.',
            $normalStyle, $paraStyle
        );

        // ── CLÁUSULA 2 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA SEGUNDA — OBLIGACIONES DE EL VENDEDOR', 2);

        $obligations = [
            'Mantener el inventario actualizado y disponible para los pedidos recibidos a través de la plataforma.',
            'Entregar los productos o servicios en las condiciones descritas, en los plazos acordados y con embalaje adecuado.',
            'Respetar los precios publicados en la plataforma y no realizar cobros adicionales no autorizados al comprador.',
            'Atender los reclamos, devoluciones y disputas de manera oportuna, dentro de los plazos establecidos por LYRIUM.',
            'Mantener la confidencialidad de los datos personales de los compradores, conforme a la Ley N° 29733.',
            'No comercializar productos falsificados, adulterados o que incumplan la normativa peruana vigente.',
            'Informar a LYRIUM sobre cualquier cambio en su razón social, RUC o datos de contacto dentro de los 5 días hábiles.',
        ];

        foreach ($obligations as $i => $text) {
            $section->addListItem($text, 0, $normalStyle, 'multilevel', $paraStyle);
        }

        $section->addTextBreak(1);

        // ── CLÁUSULA 3 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA TERCERA — OBLIGACIONES DE LYRIUM', 2);

        $lyriumObs = [
            'Proveer la infraestructura tecnológica de la plataforma con una disponibilidad mínima del 99% mensual.',
            'Procesar los pagos de los compradores y transferir las liquidaciones a EL VENDEDOR según el calendario de pagos acordado.',
            'Brindar soporte técnico para el uso de la plataforma durante los horarios establecidos.',
            'Mantener la confidencialidad de la información comercial de EL VENDEDOR.',
            'Notificar con al menos 15 días de anticipación cualquier cambio en las condiciones del presente convenio.',
        ];

        foreach ($lyriumObs as $text) {
            $section->addListItem($text, 0, $normalStyle, 'multilevel', $paraStyle);
        }

        $section->addTextBreak(1);

        // ── CLÁUSULA 4 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA CUARTA — COMISIONES Y PLAN DE SUSCRIPCIÓN', 2);

        $textRun3 = $section->addTextRun($paraStyle);
        $textRun3->addText('EL VENDEDOR se suscribe al plan ', $normalStyle);
        $textRun3->addText($planName, $boldStyle);
        $textRun3->addText(', por el cual LYRIUM retendrá una comisión del ', $normalStyle);
        $textRun3->addText($commission, $boldStyle);
        $textRun3->addText(' sobre el valor neto de cada venta procesada a través de la plataforma. Dicha comisión será descontada automáticamente al momento de la liquidación.', $normalStyle);

        $section->addText(
            'Las comisiones vigentes por plan son: Plan EMPRENDE — 5%, Plan CRECE — 10%, Plan ESPECIAL — 15%. '.
            'En caso de cambio de plan, la nueva tasa de comisión aplicará desde la fecha de activación del nuevo plan.',
            $normalStyle, $paraStyle
        );

        // ── CLÁUSULA 5 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA QUINTA — VIGENCIA Y RENOVACIÓN', 2);
        $section->addText(
            "El presente convenio entra en vigencia el {$fechaInicio} y tendrá una duración vinculada al plan de suscripción activo de EL VENDEDOR. ".
            'La renovación del convenio se producirá automáticamente al renovar o actualizar la suscripción en la plataforma. '.
            'Cualquiera de las partes podrá resolver el convenio con un preaviso de 30 días calendario mediante comunicación escrita.',
            $normalStyle, $paraStyle
        );

        // ── CLÁUSULA 6 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA SEXTA — SISTEMA DE FALTAS Y RESOLUCIÓN', 2);
        $section->addText(
            'LYRIUM aplicará un sistema de advertencias (strikes) ante incumplimientos de las obligaciones del presente convenio. '.
            'Al acumular tres (3) strikes, LYRIUM podrá resolver el presente convenio de forma inmediata y proceder con el bloqueo permanente '.
            'de la cuenta de EL VENDEDOR. Las causales de strike incluyen, sin limitarse a: '.
            'ventas de productos no conformes, fraude al comprador, incumplimiento reiterado de plazos de entrega, y '.
            'calificaciones promedio inferiores a 2.0 sostenidas por más de 30 días.',
            $normalStyle, $paraStyle
        );

        // ── CLÁUSULA 7 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA SÉPTIMA — PROTECCIÓN DE DATOS PERSONALES', 2);
        $section->addText(
            'Ambas partes se comprometen a tratar los datos personales que intercambien en el marco del presente convenio '.
            'de conformidad con la Ley N° 29733 — Ley de Protección de Datos Personales del Perú — y su Reglamento aprobado '.
            'por Decreto Supremo N° 003-2013-JUS. Los datos serán utilizados exclusivamente para los fines del presente convenio '.
            'y no serán cedidos a terceros sin consentimiento previo y expreso.',
            $normalStyle, $paraStyle
        );

        // ── CLÁUSULA 8 ───────────────────────────────────────────────────────
        $section->addTitle('CLÁUSULA OCTAVA — JURISDICCIÓN Y LEY APLICABLE', 2);
        $section->addText(
            'El presente convenio se rige por las leyes de la República del Perú. Para cualquier controversia derivada '.
            'de su interpretación, cumplimiento o resolución, las partes se someten a la jurisdicción de los Juzgados y '.
            'Tribunales de la ciudad de Lima, con expresa renuncia a cualquier otro fuero que pudiera corresponderles.',
            $normalStyle, $paraStyle
        );

        // ── FIRMAS ───────────────────────────────────────────────────────────
        $section->addTextBreak(2);
        $section->addTitle('FIRMAS', 2);

        $section->addText(
            "En {$ciudad}, a los {$fechaInicio}, las partes suscriben el presente convenio en señal de conformidad.",
            $normalStyle, $paraStyle
        );

        $section->addTextBreak(2);

        $tableStyle = ['borderSize' => 0, 'cellMargin' => 80];
        $table = $section->addTable($tableStyle);
        $table->addRow();

        $cellLeft = $table->addCell(4000);
        $cellLeft->addText('____________________________', $normalStyle, ['alignment' => Jc::CENTER]);
        $cellLeft->addText('LYRIUM S.A.C.', $boldStyle, ['alignment' => Jc::CENTER]);
        $cellLeft->addText('Representante Autorizado', $normalStyle, ['alignment' => Jc::CENTER]);

        $table->addCell(500); // espacio

        $cellRight = $table->addCell(4000);
        $cellRight->addText('____________________________', $normalStyle, ['alignment' => Jc::CENTER]);
        $cellRight->addText($repNombre !== '—' ? $repNombre : 'Representante Legal', $boldStyle, ['alignment' => Jc::CENTER]);
        $cellRight->addText('EL VENDEDOR — RUC: ' . $ruc, $normalStyle, ['alignment' => Jc::CENTER]);

        [$absDir, $relDir, $filename] = $this->resolvePaths($store, $contractNumber);

        if (! is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save("{$absDir}/{$filename}");

        return "{$relDir}/{$filename}";
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Variables dinámicas disponibles para el template.
     * Claves = nombre del placeholder en el Word (sin ${ }).
     */
    private function buildVars(Store $store, string $contractNumber): array
    {
        $store->loadMissing('subscription.plan');

        $planName   = $store->subscription?->plan?->name ?? 'EMPRENDE';
        $commission = $store->subscription?->plan?->commission_rate
            ? number_format((float) $store->subscription->plan->commission_rate * 100, 0) . '%'
            : '5%';

        return [
            'contract_number' => $contractNumber,
            'company'         => $store->razon_social ?? $store->trade_name ?? 'Sin razón social',
            'ruc'             => $store->ruc ?? '—',
            'rep_nombre'      => $store->rep_legal_nombre ?? '—',
            'rep_dni'         => $store->rep_legal_dni ?? '—',
            'direccion'       => $store->direccion_fiscal ?? $store->address ?? '—',
            'email'           => $store->corporate_email ?? '—',
            'plan'            => $planName,
            'commission'      => $commission,
            'fecha_inicio'    => now()->format('d/m/Y'),
            'ciudad'          => 'Lima',
            'year'            => (string) now()->year,
        ];
    }

    /**
     * Resuelve directorios y nombre del archivo de salida.
     * Retorna [absDir, relDir, filename].
     */
    private function resolvePaths(Store $store, string $contractNumber): array
    {
        $company     = $store->razon_social ?? $store->trade_name ?? 'empresa';
        $companySlug = preg_replace('/[^a-zA-Z0-9_]/', '_', $company);
        $year        = now()->year;
        $relDir      = "contracts/{$companySlug}/{$year}";
        $absDir      = storage_path("app/private/{$relDir}");
        $filename    = "convenio_{$contractNumber}.docx";

        return [$absDir, $relDir, $filename];
    }

    /**
     * Genera el siguiente número de contrato con formato CTR-{year}-{seq}.
     */
    public static function generateContractNumber(): string
    {
        $year         = now()->year;
        $lastContract = Contract::where('contract_number', 'like', "CTR-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastContract) {
            $parts      = explode('-', $lastContract->contract_number);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return sprintf('CTR-%d-%03d', $year, $nextNumber);
    }
}
