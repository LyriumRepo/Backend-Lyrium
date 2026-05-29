<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('series')->nullable()->after('invoice_number');
            $table->string('number')->nullable()->after('series');
            $table->string('type')->default('FACTURA')->after('number');
            $table->string('customer_name')->nullable()->after('type');
            $table->string('customer_ruc')->nullable()->after('customer_name');
            $table->timestamp('emission_date')->nullable()->after('customer_ruc');
            $table->string('sunat_status')->default('DRAFT')->after('status');
            $table->string('xml_path')->nullable()->after('pdf_url');
            $table->string('cdr_path')->nullable()->after('xml_path');
            $table->json('history')->nullable()->after('cdr_path');

            $table->index('series');
            $table->index('type');
            $table->index('sunat_status');
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn([
                'series', 'number', 'type', 'customer_name', 'customer_ruc',
                'emission_date', 'sunat_status', 'xml_path', 'cdr_path', 'history',
            ]);
        });
    }
};
