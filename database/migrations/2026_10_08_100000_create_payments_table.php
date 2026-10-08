<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Identifiers
            $table->string('payment_number', 50)->unique();

            // Relational associations
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // Payment specifics
            $table->string('payment_method', 30)->default(PaymentMethod::Cod->value);
            $table->string('status', 30)->default(PaymentTransactionStatus::Pending->value);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');

            // Audit & Gateway references
            $table->string('transaction_id', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Query indexes
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('payment_method');
            $table->index('status');
            $table->index('transaction_id');
            $table->index('paid_at');
            $table->index('created_at');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
