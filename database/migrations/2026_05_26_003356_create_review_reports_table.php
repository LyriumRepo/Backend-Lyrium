<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('review_id')
                ->constrained('reviews')
                ->cascadeOnDelete();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('reason', [
                'spam',
                'offensive',
                'fake',
                'irrelevant',
                'other',
            ])->default('other');

            $table->text('details')->nullable(); // descripción opcional del reporte

            $table->enum('status', [
                'pending',    // sin revisar
                'accepted',   // reporte válido → reseña eliminada o ocultada
                'dismissed',  // reporte rechazado → reseña se mantiene
            ])->default('pending');

            $table->foreignId('reviewed_by')  // admin que moderó
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Un usuario solo puede reportar la misma reseña una vez
            $table->unique(['review_id', 'reporter_id'], 'uq_review_reporter');

            // Índices para el panel de moderación
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
