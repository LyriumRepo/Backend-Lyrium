<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('points_per_currency', 8, 2)->default(1);
            $table->decimal('currency_per_point', 8, 2)->default(1);
            $table->integer('min_points_to_redeem')->default(100);
            $table->timestamps();
        });

        Schema::create('loyalty_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();
            $table->string('name');
            $table->integer('min_points')->default(0);
            $table->decimal('bonus_rate', 5, 2)->default(0);
            $table->text('benefits')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('program_id');
            $table->unique(['program_id', 'name']);
        });

        Schema::create('user_loyalty_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();
            $table->foreignId('tier_id')
                ->nullable()
                ->constrained('loyalty_tiers')
                ->nullOnDelete();
            $table->integer('points_balance')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('tier_id');
        });

        Schema::create('loyalty_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('user_loyalty_accounts')
                ->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted']);
            $table->integer('points');
            $table->integer('points_balance_after')->default(0);
            $table->string('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('order_id');
            $table->index('type');
        });

        Schema::create('loyalty_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('reward_type', ['discount_percentage', 'discount_fixed', 'free_shipping', 'free_product']);
            $table->decimal('value', 10, 2);
            $table->integer('points_required');
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index('program_id');
        });

        Schema::create('user_redeemed_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('reward_id')
                ->constrained('loyalty_rewards')
                ->cascadeOnDelete();
            $table->string('code')->unique();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_redeemed_rewards');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('user_loyalty_accounts');
        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('loyalty_programs');
    }
};
