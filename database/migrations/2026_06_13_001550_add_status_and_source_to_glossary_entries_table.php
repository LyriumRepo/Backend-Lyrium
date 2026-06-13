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
        Schema::table('glossary_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('is_income');
            $table->string('source', 50)->nullable()->after('status');
            $table->foreignId('suggested_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('glossary_entries', function (Blueprint $table) {
            $table->dropColumn(['status', 'source', 'suggested_supplier_id']);
        });
    }
};
