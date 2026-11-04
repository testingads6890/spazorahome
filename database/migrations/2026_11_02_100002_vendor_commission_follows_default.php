<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A seller's commission is now empty unless admin sets one, so the site-wide default (10% out of the box) applies.
     * Rows still holding the old column default of 10 were never chosen by anyone, so they go back to "use default".
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->default(null)->change();
        });

        DB::table('vendors')->where('commission_rate', 10)->update(['commission_rate' => null]);
    }

    public function down(): void
    {
        DB::table('vendors')->whereNull('commission_rate')->update(['commission_rate' => 10]);

        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(10.00)->change();
        });
    }
};
