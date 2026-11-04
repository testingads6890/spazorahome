<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 50)->nullable();
        });

        // The share of the coupon discount that landed on this line (GST is charged on the reduced amount).
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('coupon_code');
        });
    }
};
