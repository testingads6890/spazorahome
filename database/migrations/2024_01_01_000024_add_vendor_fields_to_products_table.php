<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->enum('vendor_status', ['pending', 'approved', 'rejected'])->nullable()->after('is_featured');
            $table->text('vendor_rejection_reason')->nullable()->after('vendor_status');
            $table->index(['vendor_id', 'vendor_status']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'vendor_status', 'vendor_rejection_reason']);
        });
    }
};
