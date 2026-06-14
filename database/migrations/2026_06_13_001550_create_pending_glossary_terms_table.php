<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('document_type', 50)->nullable();
            $table->string('source_field', 30)->default('description');
            $table->enum('status', ['pending', 'approved', 'dismissed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_glossary_terms');
    }
};
