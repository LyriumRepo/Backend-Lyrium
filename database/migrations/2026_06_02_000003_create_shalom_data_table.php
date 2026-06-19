<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * shalom_data — tabla unificada que reemplaza los 3 JSON del scraper:
 *   ❌ shalom-ids-reales.json
 *   ❌ distritos_con_reparto_shalom.json
 *   ❌ todos_los_distritos_shalom.json
 *
 * ✅ UNA sola tabla con todo lo que necesita shalom.js:
 *   ter_id          → ID de terminal para la API /tarifa/mostrar
 *   departamento/provincia/zona → para buscarTerminal()
 *   tiene_reparto + ids + tarifas → para buscarReparto()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shalom_data', function (Blueprint $table) {
            $table->id();

            // ── Terminal / Agencia ────────────────────────────────────────────
            $table->unsignedInteger('ter_id')->nullable()->unique()
                ->comment('ID terminal Shalom — usado en /tarifa/mostrar');
            $table->string('nombre', 150)
                ->comment('Nombre del terminal o agencia');
            $table->string('departamento', 80);
            $table->string('provincia', 80);
            $table->string('zona', 100)->nullable()
                ->comment('Barrio/zona de la agencia');
            $table->string('direccion', 250)->nullable();
            $table->string('abreviatura', 20)->nullable()
                ->comment('Ej: LIM.M, TRU.1');
            $table->string('ubigeo', 10)->nullable()
                ->comment('Código INEI (4 o 6 dígitos en Shalom)');
            $table->string('latitud',  20)->nullable();
            $table->string('longitud', 20)->nullable();

            // ── Reparto a domicilio ────────────────────────────────────────────
            $table->boolean('tiene_reparto')->default(false)
                ->comment('true = tiene entrega a domicilio');
            $table->unsignedSmallInteger('departamento_id')->nullable()
                ->comment('ID interno Shalom del departamento');
            $table->unsignedSmallInteger('provincia_id')->nullable()
                ->comment('ID interno Shalom de la provincia');
            $table->unsignedSmallInteger('distrito_id')->nullable()
                ->comment('ID interno Shalom del distrito');
            $table->unsignedInteger('ubi_id')->nullable()
                ->comment('ubigeo Shalom del distrito (5 dígitos)');
            $table->json('tarifas_reparto')->nullable()
                ->comment('[{cs_costo, des_nombre}] — tarifas por tipo de paquete');

            $table->timestamps();

            // ── Índices ───────────────────────────────────────────────────────
            $table->index('departamento',                         'ix_sd_dept');
            $table->index('provincia',                            'ix_sd_prov');
            $table->index('zona',                                 'ix_sd_zona');
            $table->index('nombre',                               'ix_sd_nombre');
            $table->index('ubigeo',                               'ix_sd_ubigeo');
            $table->index('tiene_reparto',                        'ix_sd_reparto');
            $table->index(['departamento', 'provincia'],          'ix_sd_dept_prov');
            $table->index(['departamento_id', 'provincia_id'],    'ix_sd_ids_reparto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shalom_data');
    }
};
