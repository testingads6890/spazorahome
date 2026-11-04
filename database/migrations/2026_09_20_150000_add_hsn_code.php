<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('hsn_code', 8)->nullable();
        });

        // Snapshot at purchase time so an invoice never changes after the product is edited.
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('hsn_code', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('hsn_code');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('hsn_code');
        });
    }
};
