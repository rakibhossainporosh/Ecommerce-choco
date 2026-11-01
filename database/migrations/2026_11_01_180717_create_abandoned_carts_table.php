<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->json('items');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('open');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->unsignedTinyInteger('remind_attempts')->default(0);
            $table->timestamps();

            $table->index(['status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
