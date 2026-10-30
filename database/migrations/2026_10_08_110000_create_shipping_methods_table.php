<?php

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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('provider', 30)->default(ShippingProvider::InHouse->value);
            $table->decimal('charge', 10, 2)->default(0.00);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->unsignedSmallInteger('estimated_days_min')->default(1);
            $table->unsignedSmallInteger('estimated_days_max')->default(3);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
