<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('order_number')->unique();

            // Customer
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_note')->nullable();

            // Order status & payment
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->string('payment_status')->default(PaymentStatus::Unpaid->value);
            $table->string('payment_method')->default(PaymentMethod::Cod->value);
            $table->string('currency', 3)->default('BDT');

            // Amounts
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2);

            // Shipping address snapshot
            $table->string('shipping_address_line');
            $table->string('shipping_area');
            $table->string('shipping_city');
            $table->string('shipping_postcode')->nullable();
            $table->string('shipping_country')->default('Bangladesh');

            // Billing address snapshot
            $table->boolean('billing_same_as_shipping')->default(true);
            $table->string('billing_address_line')->nullable();
            $table->string('billing_area')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_postcode')->nullable();
            $table->string('billing_country')->nullable();

            // Lifecycle
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('cancellation_reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Query indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('placed_at');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
