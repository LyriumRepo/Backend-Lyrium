<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('customer_validated_at')->nullable()->after('completed_at');
            $table->string('validation_source')->nullable()->after('customer_validated_at'); // manual | email | auto_expired
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'customer_validated_at', 'validation_source']);
        });
    }
};
