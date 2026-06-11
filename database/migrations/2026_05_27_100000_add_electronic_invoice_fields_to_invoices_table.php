<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'series')) {
                $table->string('series')->nullable()->after('invoice_number');
            }
            if (!Schema::hasColumn('invoices', 'number')) {
                $table->string('number')->nullable()->after('series');
            }
            if (!Schema::hasColumn('invoices', 'type')) {
                $table->string('type')->default('FACTURA')->after('number');
            }
            if (!Schema::hasColumn('invoices', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('type');
            }
            if (!Schema::hasColumn('invoices', 'customer_ruc')) {
                $table->string('customer_ruc')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('invoices', 'emission_date')) {
                $table->timestamp('emission_date')->nullable()->after('customer_ruc');
            }
            if (!Schema::hasColumn('invoices', 'sunat_status')) {
                $table->string('sunat_status')->default('DRAFT')->after('status');
            }
            if (!Schema::hasColumn('invoices', 'xml_path')) {
                $table->string('xml_path')->nullable()->after('pdf_url');
            }
            if (!Schema::hasColumn('invoices', 'cdr_path')) {
                $table->string('cdr_path')->nullable()->after('xml_path');
            }
            if (!Schema::hasColumn('invoices', 'history')) {
                $table->json('history')->nullable()->after('cdr_path');
            }

            if (!Schema::hasIndex('invoices', 'invoices_series_index')) {
                $table->index('series');
            }
            if (!Schema::hasIndex('invoices', 'invoices_type_index')) {
                $table->index('type');
            }
            if (!Schema::hasIndex('invoices', 'invoices_sunat_status_index')) {
                $table->index('sunat_status');
            }
            if (!Schema::hasIndex('invoices', 'invoices_store_id_index')) {
                $table->index('store_id');
            }
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
