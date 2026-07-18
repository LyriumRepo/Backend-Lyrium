<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protection_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30)->default('rate_limit');
            $table->string('pattern', 255)->nullable();
            $table->string('severity', 10)->default('warning');
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->unsignedInteger('trigger_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protection_rules');
    }
};
