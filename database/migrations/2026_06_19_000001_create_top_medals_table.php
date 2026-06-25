<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_medals', function (Blueprint $table) {
            $table->id();
            $table->morphs('medalable');
            $table->string('entity_type'); // store, product, service
            $table->unsignedSmallInteger('rank_position')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, suspended
            $table->boolean('visible')->default(false);
            $table->string('medal_image_url')->nullable();

            $table->unsignedInteger('times_entered')->default(1);
            $table->unsignedInteger('times_exited')->default(0);

            $table->timestamp('detected_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['medalable_type', 'medalable_id'], 'uq_top_medals_entity');
            $table->index(['entity_type', 'status'], 'idx_top_medals_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_medals');
    }
};
