<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Portion of orders.tax that was charged on shipping (orders.tax is the total GST).
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_tax', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_tax');
        });
    }
};
