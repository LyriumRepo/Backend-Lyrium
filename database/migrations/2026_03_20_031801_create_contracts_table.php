<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company');
            $table->string('ruc', 11)->nullable();
            $table->string('representative')->nullable();
            $table->string('type'); // Comision Mercantil V2, Distribucion Exclusiva, etc.
            $table->string('modality')->default('VIRTUAL'); // VIRTUAL, PHYSICAL
            $table->string('status')->default('PENDING'); // ACTIVE, PENDING, EXPIRED
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
