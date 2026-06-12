<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->after('representative');
            $table->text('direccion')->nullable()->after('dni');
            $table->string('admin_name', 255)->nullable()->after('ruc');
            $table->string('admin_phone', 20)->nullable()->after('admin_name');
            $table->string('admin_email', 255)->nullable()->after('admin_phone');
            $table->string('plan', 100)->nullable()->after('modality');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['dni', 'direccion', 'admin_name', 'admin_phone', 'admin_email', 'plan']);
        });
    }
};
