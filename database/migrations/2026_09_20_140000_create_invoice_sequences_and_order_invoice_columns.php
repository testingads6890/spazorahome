<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One counter row per financial year, locked while a number is allocated so the series has no gaps or duplicates.
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('financial_year', 5)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('invoice_number', 16)->nullable()->unique();
            $table->timestamp('invoice_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'invoice_date']);
        });
        Schema::dropIfExists('invoice_sequences');
    }
};
