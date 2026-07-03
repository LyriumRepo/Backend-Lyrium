<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('document_type')->nullable()->after('business_name');
            $table->string('series')->nullable()->after('document_type');
            $table->string('number')->nullable()->after('series');
            $table->string('customer_document_type')->nullable()->after('number');
            $table->string('customer_address')->nullable()->after('customer_document_type');
            $table->string('customer_email')->nullable()->after('customer_address');
            $table->json('nubefact_response')->nullable()->after('customer_email');

            $table->index('series');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'series',
                'number',
                'customer_document_type',
                'customer_address',
                'customer_email',
                'nubefact_response',
            ]);
        });
    }
};
