<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de auditoría técnica (RF-13).
     * Registra TODAS las acciones sensibles del sistema con trazabilidad completa.
     * Tabla append-only: sin softDeletes, sin updates — sólo inserts.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Quién actuó (null = sistema/cron)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email')->nullable();   // Snapshot por si el user se elimina
            $table->string('user_role', 50)->nullable();

            // Qué hizo
            $table->string('event', 100);             // 'created', 'updated', 'deleted', 'login', 'ban'
            $table->string('module', 80);             // 'suppliers', 'expenses', 'operational_roles', 'users'
            $table->string('description');            // Texto legible: "Creó proveedor Juan Pérez"

            // Sobre qué entidad
            $table->string('auditable_type')->nullable();  // App\Models\Supplier
            $table->unsignedBigInteger('auditable_id')->nullable();

            // Cambios anteriores / nuevos (para eventos 'updated')
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Trazabilidad de red
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent(); // Sólo created_at — no updated_at

            // Índices para búsquedas en el log viewer
            $table->index(['module', 'event']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
