<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class FixMojibakeEncoding extends Command
{
    protected $signature = 'mojibake:fix
        {--dry-run : Muestra qué corregiría sin modificar la base de datos}
        {--table=* : Reparar solo tablas específicas (ej: --table=categories)}
        {--column=* : Reparar solo columnas específicas (ej: --column=name)}';

    protected $description = 'Corrige texto mojibake CP850 (├ ® │ ┬) en columnas de texto de la base de datos';

    private array $defaultColumns = [
        'categories' => ['name', 'description'],
        'services' => ['name', 'description'],
        'specialists' => ['nombres', 'apellidos', 'especialidad'],
        'stores' => ['store_name', 'trade_name', 'description', 'razon_social', 'nombre_comercial', 'direccion_fiscal', 'address', 'policies'],
        'products' => ['name', 'short_description', 'description', 'service_location', 'service_modality', 'rejection_reason', 'sticker', 'sku'],
    ];

    public function handle(): int
    {
        $tables = $this->option('table');
        $columns = $this->option('column');
        $dryRun = (bool) $this->option('dry-run');

        $tables = $tables !== [] ? $tables : array_keys($this->defaultColumns);
        $columns = $columns !== [] ? $columns : null;

        $totalFixed = 0;

        foreach ($tables as $table) {
            $tableColumns = $columns ?? $this->defaultColumns[$table] ?? null;
            if ($tableColumns === null) {
                $this->warn("Tabla [{$table}] sin columnas por defecto. Usa --column=col1,col2");

                continue;
            }

            foreach ($tableColumns as $column) {
                $fixed = $this->fixColumn($table, $column, $dryRun);
                $totalFixed += $fixed;
                $verb = $dryRun ? 'detectadas' : 'corregidas';
                $this->line(sprintf('  [%s.%s] %d filas %s', $table, $column, $fixed, $verb));
            }
        }

        $this->newLine();
        $verb = $dryRun ? 'AFECTADAS (dry-run, sin cambios)' : 'CORREGIDAS';
        $this->info("Total filas {$verb}: {$totalFixed}");

        return self::SUCCESS;
    }

    private function fixColumn(string $table, string $column, bool $dryRun): int
    {
        $rows = DB::table($table)->select('id', $column)->whereNotNull($column)->get();
        $fixed = 0;

        foreach ($rows as $row) {
            $original = $row->{$column};
            $corrected = $this->repair($original);

            if ($corrected === null) {
                continue;
            }

            $fixed++;

            if ($dryRun) {
                $this->line(sprintf('    [%s.%s id=%s] %s => %s', $table, $column, $row->id, $this->truncate($original), $this->truncate($corrected)));
            } else {
                DB::table($table)->where('id', $row->id)->update([$column => $corrected]);
            }
        }

        return $fixed;
    }

    /**
     * Reversa el mojibake CP850. Devuelve null si no hay corrupción.
     *
     * El texto se corrompió cuando bytes UTF-8 (p.ej. C3 A9 de "é") fueron
     * leídos como página de códigos CP850 y re-guardados como UTF-8 (├ = 0xC3,
     * ® = 0xA9). Para revertir: re-codificar cada carácter del texto corrupto
     * a su byte CP850 produce la secuencia UTF-8 original.
     */
    private function repair(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Firma de corrupción: caracteres de caja CP437/CP850 (├ ┬ │ ─ ┐ └ ┘)
        // no aparecen en texto legítimo en español.
        if (! preg_match('/[\x{2500}-\x{257F}]/u', $value)) {
            return null;
        }

        $bytes = @iconv('UTF-8', 'CP850', $value);
        if ($bytes === false || ! mb_check_encoding($bytes, 'UTF-8')) {
            return null;
        }

        // Verificación de ida y vuelta: re-codificar el resultado a CP850
        // debe reproducir el texto corrupto original.
        $roundTrip = @iconv('CP850', 'UTF-8', $bytes);
        if ($roundTrip === false || $roundTrip !== $value) {
            return null;
        }

        if ($bytes === $value) {
            return null;
        }

        return $bytes;
    }

    private function truncate(string $value): string
    {
        $trimmed = mb_substr($value, 0, 60);

        return $trimmed.(mb_strlen($value) > 60 ? '…' : '');
    }
}
