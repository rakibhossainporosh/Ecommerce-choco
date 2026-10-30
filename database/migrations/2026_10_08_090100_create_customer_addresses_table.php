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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();

            // Owning customer
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            // Address classification ('shipping', 'billing')
            $table->string('type', 20)->default('shipping');

            // Optional recipient contact override
            $table->string('name')->nullable();
            $table->string('phone', 30)->nullable();

            // Geographic location
            $table->string('address_line');
            $table->string('area', 100);
            $table->string('city', 100);
            $table->string('postcode', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');

            // Default flag for rapid checkout selection
            $table->boolean('is_default')->default(false);

            // Timestamps
            $table->timestamps();

            // Query indexes
            $table->index('customer_id');
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
