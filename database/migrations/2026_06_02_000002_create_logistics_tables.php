<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de logística:
 *   ✅ courier_operators     — cobertura por distrito (seeded desde operators_data.json)
 *   ✅ box_types             — tipos de caja (seeded desde LogisticsSeeder)
 *   ✅ courier_quotes_cache  — caché de cotizaciones (TTL 1h, por parámetros)
 *
 * IMPORTANTE: La caché NO es por usuario — es por parámetros de envío.
 * El mismo (courier + origen + destino + cajas) devuelve el mismo precio
 * a cualquier usuario, que es el comportamiento correcto.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. courier_operators ─────────────────────────────────────────────
        Schema::create('courier_operators', function (Blueprint $table) {
            $table->id();
            $table->string('departamento', 80);
            $table->string('provincia',    80);
            $table->string('distrito',     80);
            $table->boolean('tiene_shalom')->default(false);
            $table->boolean('tiene_olva'  )->default(false);
            $table->boolean('tiene_sharf' )->default(false);
            $table->boolean('tiene_urbano')->default(false);
            $table->timestamps();

            $table->unique(['departamento', 'provincia', 'distrito'], 'ux_courier_ops_geo');
            $table->index('departamento',                             'ix_co_dept');
            $table->index(['departamento', 'provincia'],              'ix_co_dept_prov');
        });

        // ── 2. box_types ─────────────────────────────────────────────────────
        Schema::create('box_types', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 10)->unique();        // XXS, XS, S, M, ML, L, XL
            $table->unsignedSmallInteger('largo');          // cm
            $table->unsignedSmallInteger('ancho');          // cm
            $table->unsignedSmallInteger('alto');           // cm
            $table->decimal('peso_max_kg', 5, 2);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('orden', 'ix_bt_orden');
        });

        // ── 3. courier_quotes_cache ──────────────────────────────────────────
        Schema::create('courier_quotes_cache', function (Blueprint $table) {
            $table->id();
            $table->string('courier',      20);
            $table->string('origen_dept',  80);
            $table->string('origen_prov',  80);
            $table->string('origen_dist',  80);
            $table->string('destino_dept', 80);
            $table->string('destino_prov', 80);
            $table->string('destino_dist', 80);
            $table->string('cajas_hash',   64); // SHA-256 del array de cajas
            $table->decimal('precio_domicilio', 8, 2)->nullable();
            $table->decimal('precio_agencia',   8, 2)->nullable();
            $table->json('respuesta_completa')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(
                ['courier','origen_dept','origen_prov','origen_dist',
                 'destino_dept','destino_prov','destino_dist','cajas_hash'],
                'ux_cqc_cotizacion'
            );
            $table->index('expires_at', 'ix_cqc_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_quotes_cache');
        Schema::dropIfExists('box_types');
        Schema::dropIfExists('courier_operators');
    }
};
