<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** WhatsApp number and other phone number on the customer profile (table customer_profiles). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_profiles')) {
            return;
        }
        Schema::table('customer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_profiles', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('customer_profiles', 'other_phone')) {
                $table->string('other_phone', 20)->nullable()->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_profiles')) {
            return;
        }
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['whatsapp', 'other_phone'], fn ($c) => Schema::hasColumn('customer_profiles', $c))));
        });
    }
};
