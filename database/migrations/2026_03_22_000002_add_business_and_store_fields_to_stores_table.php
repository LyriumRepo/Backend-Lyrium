<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('razon_social', 255)->nullable()->after('trade_name');
            $table->string('nombre_comercial', 255)->nullable()->after('razon_social');
            $table->string('rep_legal_nombre', 255)->nullable()->after('nombre_comercial');
            $table->string('rep_legal_dni', 20)->nullable()->after('rep_legal_nombre');
            $table->string('rep_legal_foto')->nullable()->after('rep_legal_dni');
            $table->unsignedTinyInteger('experience_years')->nullable()->after('rep_legal_foto');
            $table->enum('tax_condition', ['RUC', 'DNI', 'CE', 'PAS'])->nullable()->after('experience_years');
            $table->text('direccion_fiscal')->nullable()->after('tax_condition');
            $table->string('cuenta_bcp', 50)->nullable()->after('direccion_fiscal');
            $table->string('cci', 50)->nullable()->after('cuenta_bcp');
            $table->json('bank_secondary')->nullable()->after('cci');
            $table->string('store_name', 255)->nullable()->after('banner');
            $table->foreignId('category_id')->nullable()->after('store_name')->constrained('categories')->nullOnDelete();
            $table->text('address')->nullable()->after('category_id');
            $table->string('instagram', 255)->nullable()->after('address');
            $table->string('facebook', 255)->nullable()->after('instagram');
            $table->string('tiktok', 255)->nullable()->after('facebook');
            $table->text('policies')->nullable()->after('tiktok');
            $table->json('gallery')->nullable()->after('policies');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'razon_social',
                'nombre_comercial',
                'rep_legal_nombre',
                'rep_legal_dni',
                'rep_legal_foto',
                'experience_years',
                'tax_condition',
                'direccion_fiscal',
                'cuenta_bcp',
                'cci',
                'bank_secondary',
                'store_name',
                'category_id',
                'address',
                'instagram',
                'facebook',
                'tiktok',
                'policies',
                'gallery',
            ]);
        });
    }
};
