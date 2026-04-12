<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('supplier_store');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'email', 'phone', 'address', 'city', 'notes']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('especialidad')->nullable()->after('type');
            $table->date('fecha_renovacion')->nullable()->after('status');
            $table->json('proyectos')->nullable()->after('fecha_renovacion');
            $table->json('certificaciones')->nullable()->after('proyectos');
            $table->decimal('total_gastado', 12, 2)->default(0)->after('certificaciones');
            $table->unsignedInteger('total_recibos')->default(0)->after('total_gastado');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['especialidad', 'fecha_renovacion', 'proyectos', 'certificaciones', 'total_gastado', 'total_recibos']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
        });

        Schema::create('supplier_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['supplier_id', 'store_id']);
        });
    }
};
