<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los roles de Spatie (administrator, seller, customer, logistics_operator) cubren
     * el acceso general al sistema. Esta tabla gestiona "Roles Operativos" internos
     * (Administrador Global, Auditor Financiero, Moderador de Catálogo, etc.) con
     * granularidad de módulos, tal como muestra el frontend en la pestaña
     * "Roles y Permisos" de Gestión Operativa.
     */
    public function up(): void
    {
        Schema::create('operational_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // "Administrador Global"
            $table->string('code')->unique();         // "admin_global"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Módulos asignados: ['finanzas', 'gestion_operativa', 'vendedores', ...]
            $table->json('modules')->nullable();

            // Requiere 2FA para modificaciones
            $table->boolean('requires_2fa')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        // Tabla pivot: qué usuarios tienen qué rol operativo
        Schema::create('operational_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_role_id')
                ->constrained('operational_roles')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['operational_role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_role_user');
        Schema::dropIfExists('operational_roles');
    }
};
