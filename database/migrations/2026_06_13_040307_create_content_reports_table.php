<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 50);
            $table->unsignedBigInteger('content_id');
            $table->enum('reason', ['spam', 'inappropriate', 'offensive', 'other']);
            $table->text('description')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['content_type', 'content_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};
