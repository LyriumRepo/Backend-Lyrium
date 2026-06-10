<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('card_token')->nullable()->after('detalle_extra');
            $table->string('card_last4', 4)->nullable()->after('card_token');
            $table->string('card_brand', 50)->nullable()->after('card_last4');
            $table->string('card_exp_month', 2)->nullable()->after('card_brand');
            $table->string('card_exp_year', 4)->nullable()->after('card_exp_month');
            $table->string('token_status', 20)->default('active')->after('card_exp_year');

            $table->string('documento', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn([
                'card_token',
                'card_last4',
                'card_brand',
                'card_exp_month',
                'card_exp_year',
                'token_status',
            ]);

            $table->string('documento', 255)->nullable(false)->change();
        });
    }
};
