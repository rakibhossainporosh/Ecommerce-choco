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
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('attribute_id')
                ->constrained('attributes')
                ->restrictOnDelete();
            $table->foreignId('attribute_value_id')
                ->nullable()
                ->constrained('attribute_values')
                ->nullOnDelete();
            $table->text('text_value')->nullable();
            $table->decimal('number_value', 16, 4)->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();

            // Indexes for common query lookup paths
            $table->index(['product_id', 'attribute_id'], 'prod_attr_val_prod_attr_idx');
            $table->index(['attribute_id', 'product_id'], 'prod_attr_val_attr_prod_idx');
            $table->index('attribute_value_id');

            // Unique constraint to prevent duplicate value assignment for multiselect and predefined values
            $table->unique(['product_id', 'attribute_id', 'attribute_value_id'], 'prod_attr_val_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
