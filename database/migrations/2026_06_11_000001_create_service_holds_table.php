<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_holds', function (Blueprint $table) {
            $table->id();
            $table->string('cart_token');
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('service_name');
            $table->decimal('service_price', 10, 2);
            $table->string('service_image')->nullable();
            $table->foreignId('specialist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('specialist_name');
            $table->foreignId('schedule_id')->nullable()->constrained('service_schedules')->nullOnDelete();
            $table->date('appointment_date');
            $table->string('start_time', 10);
            $table->text('customer_notes')->nullable();
            $table->string('service_address')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('cart_token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_holds');
    }
};
