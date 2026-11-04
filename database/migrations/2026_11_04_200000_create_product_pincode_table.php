<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several dispatch PIN codes per product. On the live site this table already exists (created by hand),
 * so the migration does nothing there; it only builds the table on a fresh database such as the test one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_pincode')) {
            return;
        }

        Schema::create('product_pincode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pincode_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'pincode_id']);
            $table->index('pincode_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pincode');
    }
};
