<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            // category_id apunta a la categoría de NIVEL 2 del especialista
            // (ej: id de "Dermatología", cuyo parent_id es el id de "Servicios médicos")
            $table->foreignId('category_id')
                ->nullable()
                ->after('store_id')
                ->constrained('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
