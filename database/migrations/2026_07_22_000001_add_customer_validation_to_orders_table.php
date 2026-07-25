<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('status');
            $table->timestamp('customer_validated_at')->nullable()->after('delivered_at');
            $table->string('validation_source')->nullable()->after('customer_validated_at'); // manual | email | auto_expired
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'customer_validated_at', 'validation_source']);
        });
    }
};
