<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'carrier')) {
                $table->string('carrier', 50)->nullable()->after('shipping_type')
                    ->comment('Courier seleccionado en checkout: shalom, olva, sharf, urbano');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'carrier')) {
                $table->dropColumn('carrier');
            }
        });
    }
};
