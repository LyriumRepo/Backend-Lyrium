<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('download_url')->nullable()->after('expiration_date');
            $table->unsignedInteger('download_limit')->nullable()->after('download_url');
            $table->string('file_type', 100)->nullable()->after('download_limit');
            $table->unsignedInteger('file_size')->nullable()->after('file_type');
            $table->string('service_location')->nullable()->after('file_size');
            $table->unsignedInteger('service_duration')->nullable()->after('service_location');
            $table->string('service_modality', 50)->nullable()->after('service_duration');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'download_url',
                'download_limit',
                'file_type',
                'file_size',
                'service_location',
                'service_duration',
                'service_modality',
            ]);
        });
    }
};
