<?php

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            // Identifiers
            $table->string('shipment_number', 50)->unique();

            // Foreign associations
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained('shipping_methods')
                ->nullOnDelete();

            // Shipping specifics
            $table->string('provider', 30)->default(ShippingProvider::InHouse->value);
            $table->string('status', 30)->default(ShipmentStatus::Pending->value);
            $table->string('tracking_code', 100)->nullable();
            $table->decimal('shipping_charge', 10, 2)->default(0.00);
            $table->decimal('weight_kg', 8, 2)->nullable();

            // Recipient address snapshot
            $table->string('recipient_name', 255);
            $table->string('recipient_phone', 50);
            $table->string('shipping_address_line', 255);
            $table->string('shipping_area', 100)->nullable();
            $table->string('shipping_city', 100)->default('Dhaka');
            $table->string('shipping_postcode', 20)->nullable();
            $table->string('shipping_country', 50)->default('Bangladesh');

            // Audit timestamps
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Audit relations & metadata
            $table->foreignId('dispatched_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for fast operational querying
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('provider');
            $table->index('tracking_code');
            $table->index('shipped_at');
            $table->index('delivered_at');
            $table->index('created_at');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
