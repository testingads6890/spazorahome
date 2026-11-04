<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->decimal('vendor_subtotal', 12, 2)->nullable()->after('unit_price');
            $table->decimal('vendor_commission', 12, 2)->nullable()->after('vendor_subtotal');
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'vendor_subtotal', 'vendor_commission']);
        });
    }
};
