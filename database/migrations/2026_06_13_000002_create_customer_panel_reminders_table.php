<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_panel_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('reminder_day'); // 7, 30 or 90
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'reminder_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_panel_reminders');
    }
};
