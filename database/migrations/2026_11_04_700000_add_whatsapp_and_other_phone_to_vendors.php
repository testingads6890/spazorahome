<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Seller WhatsApp and other phone number, from the seller application. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('vendors', 'other_phone')) {
                $table->string('other_phone', 20)->nullable()->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['whatsapp', 'other_phone'], fn ($c) => Schema::hasColumn('vendors', $c))));
        });
    }
};
