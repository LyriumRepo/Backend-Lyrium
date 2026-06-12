<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('detailed_benefits');
            $table->string('timeline_icon', 50)->default('star')->after('is_active');
            $table->string('badge', 100)->nullable()->after('timeline_icon');
            $table->text('description')->nullable()->after('badge');
            $table->string('css_color', 20)->default('#3b82f6')->after('description');
            $table->string('accent_color', 20)->default('#2563eb')->after('css_color');
            $table->boolean('requires_payment')->default(true)->after('accent_color');
            $table->boolean('enable_claim_lock')->default(false)->after('requires_payment');
            $table->unsignedSmallInteger('claim_months')->default(1)->after('enable_claim_lock');
            $table->string('subscribe_button_text', 100)->default('Suscribirse')->after('claim_months');
            $table->string('currency', 10)->default('S/')->after('subscribe_button_text');
            $table->string('period', 20)->default('/mes')->after('currency');
            $table->decimal('price_annual', 10, 2)->nullable()->after('period');
            $table->string('price_text', 50)->nullable()->after('price_annual');
            $table->string('price_subtext', 50)->default('/mes')->after('price_text');
            $table->boolean('use_price_mode')->default(true)->after('price_subtext');
            $table->unsignedSmallInteger('compact_visible_count')->default(5)->after('use_price_mode');
            $table->text('trial_success_title')->nullable()->after('compact_visible_count');
            $table->text('trial_success_message')->nullable()->after('trial_success_title');
            $table->text('trial_wait_message')->nullable()->after('trial_success_message');
            $table->string('claimed_button_text', 100)->nullable()->after('trial_wait_message');
            $table->string('claimed_warning_text', 100)->nullable()->after('claimed_button_text');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'timeline_icon',
                'badge',
                'description',
                'css_color',
                'accent_color',
                'requires_payment',
                'enable_claim_lock',
                'claim_months',
                'subscribe_button_text',
                'currency',
                'period',
                'price_annual',
                'price_text',
                'price_subtext',
                'use_price_mode',
                'compact_visible_count',
                'trial_success_title',
                'trial_success_message',
                'trial_wait_message',
                'claimed_button_text',
                'claimed_warning_text',
            ]);
        });
    }
};
