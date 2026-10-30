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
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Statuses (aligned with OrderStatus enum)
            $table->string('from_status');
            $table->string('to_status');

            // Optional note/cancellation reason
            $table->string('reason')->nullable();

            // Immutable audit timestamp (no updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast chronological lookups and user audits
            $table->index('order_id');
            $table->index('changed_by');
            $table->index('created_at');
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
