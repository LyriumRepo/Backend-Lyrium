<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal_sin_igv', 10, 2)->default(0)->after('total');
            $table->decimal('igv_amount', 10, 2)->default(0)->after('subtotal_sin_igv');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal_sin_igv', 'igv_amount']);
        });
    }
};
