<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->text('admin_note')->nullable()->after('customer_note');
            $table->text('gift_message')->nullable()->after('admin_note');
            $table->json('tags')->nullable()->after('gift_message');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['admin_note', 'gift_message', 'tags']);
        });
    }
};
