<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Which kind of professional or maker the seller is: architect, interior designer or manufacturer. */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('seller_type', 30)->nullable()->after('business_type');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('seller_type');
        });
    }
};
