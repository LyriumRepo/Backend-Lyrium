<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->text('reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->string('reason', 255)->nullable()->change();
        });
    }
};
