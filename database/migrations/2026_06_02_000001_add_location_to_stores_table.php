<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'department')) {
                $table->string('department', 80)->nullable()->after('address')
                    ->comment('Departamento de despacho — ej: LA LIBERTAD');
            }
            if (!Schema::hasColumn('stores', 'province')) {
                $table->string('province', 80)->nullable()->after('department')
                    ->comment('Provincia de despacho — ej: TRUJILLO');
            }
            if (!Schema::hasColumn('stores', 'district')) {
                $table->string('district', 80)->nullable()->after('province')
                    ->comment('Distrito de despacho — ej: TRUJILLO');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $cols = array_filter(
                ['department', 'province', 'district'],
                fn ($c) => Schema::hasColumn('stores', $c)
            );
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
