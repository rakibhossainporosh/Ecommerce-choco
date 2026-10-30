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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Storefront account linkage (nullable if guest/phone customer)
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            // Customer identity & contact
            $table->string('name');
            $table->string('phone', 30)->unique();
            $table->string('email')->nullable();

            // Status & CRM remarks
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();

            // Query indexes
            $table->index('name');
            $table->index('phone');
            $table->index('email');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
