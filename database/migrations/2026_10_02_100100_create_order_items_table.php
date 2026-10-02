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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            // Product snapshot (historical integrity)
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku');

            // Pricing & Quantity
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('product_variant_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
