<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('xml_path', 'xml_url');
            $table->renameColumn('cdr_path', 'cdr_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('xml_url', 'xml_path');
            $table->renameColumn('cdr_url', 'cdr_path');
        });
    }
};
