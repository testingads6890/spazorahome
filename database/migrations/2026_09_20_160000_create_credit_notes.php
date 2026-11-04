<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoices and credit notes each get their own gap-free series per financial year.
        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique(['financial_year']);
            $table->string('series', 3)->default('INV')->after('id');
            $table->unique(['series', 'financial_year']);
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number', 16)->unique();
            $table->timestamp('issued_at');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique(['series', 'financial_year']);
            $table->dropColumn('series');
            $table->unique('financial_year');
        });
    }
};
