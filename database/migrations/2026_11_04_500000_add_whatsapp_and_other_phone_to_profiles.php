<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customers give a WhatsApp number and another phone number, at sign-up and on their profile. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('profiles', 'other_phone')) {
                $table->string('other_phone', 20)->nullable()->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['whatsapp', 'other_phone'], fn ($c) => Schema::hasColumn('profiles', $c))));
        });
    }
};
