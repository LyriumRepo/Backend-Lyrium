<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izipay_booking_transactions', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->change();
            $table->dateTime('appointment_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('izipay_booking_transactions', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable(false)->change();
            $table->dateTime('appointment_date')->nullable(false)->change();
        });
    }
};
