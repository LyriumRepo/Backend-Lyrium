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
        Schema::table('service_slot_holds', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->constrained('service_schedules')->cascadeOnDelete();
            $table->text('customer_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_slot_holds', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn(['schedule_id', 'customer_notes']);
        });
    }
};
