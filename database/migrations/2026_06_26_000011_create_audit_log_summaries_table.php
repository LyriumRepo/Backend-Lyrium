<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('module', 80);
            $table->string('severity', 10);
            $table->unsignedInteger('total')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['date', 'module', 'severity'], 'idx_date_module_severity');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_summaries');
    }
};
