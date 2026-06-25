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
            $columns = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM invoices");
            $existing = array_column($columns, 'Field');

            if (!in_array('document_type', $existing)) $table->string('document_type')->nullable()->after('business_name');
            if (!in_array('series', $existing)) $table->string('series')->nullable()->after('document_type');
            if (!in_array('number', $existing)) $table->string('number')->nullable()->after('series');
            if (!in_array('customer_document_type', $existing)) $table->string('customer_document_type')->nullable()->after('number');
            if (!in_array('customer_address', $existing)) $table->string('customer_address')->nullable()->after('customer_document_type');
            if (!in_array('customer_email', $existing)) $table->string('customer_email')->nullable()->after('customer_address');
            if (!in_array('nubefact_response', $existing)) $table->json('nubefact_response')->nullable()->after('customer_email');
        });

        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'invoices_series_index'");
        if (empty($indexes)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('series');
            });
        }
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
