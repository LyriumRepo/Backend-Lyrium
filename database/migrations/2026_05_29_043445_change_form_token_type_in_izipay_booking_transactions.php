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
        Schema::table('izipay_booking_transactions', function (Blueprint $table) {
            $table->text('form_token')->nullable()->change();

            $table->text('izipay_response')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izipay_booking_transactions', function (Blueprint $table) {
            $table->string('form_token', 255)->change();
            $table->string('izipay_response', 255)->nullable()->change();
        });
    }
};
