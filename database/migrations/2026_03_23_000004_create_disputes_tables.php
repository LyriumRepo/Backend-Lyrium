<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('dispute_number')->unique();
            $table->enum('type', ['product_not_received', 'product_damaged', 'product_not_as_described', 'seller_fraud', 'payment_issue', 'other'])
                ->nullable();
            $table->enum('status', ['open', 'under_review', 'pending_resolution', 'resolved', 'closed', 'cancelled'])
                ->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                ->default('medium');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->enum('resolution', ['favor_buyer', 'favor_seller', 'partial_refund', 'other'])->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('store_id');
            $table->index('user_id');
        });

        Schema::create('dispute_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispute_id')
                ->constrained('disputes')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_system')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('dispute_id');
        });

        Schema::create('dispute_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispute_id')
                ->constrained('disputes')
                ->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()
                ->constrained('dispute_messages')
                ->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->timestamps();

            $table->index('dispute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_attachments');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
    }
};
