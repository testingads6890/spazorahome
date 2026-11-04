<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['blogs', 'categories'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'meta_title')) {
                    $t->string('meta_title')->nullable();
                }
                if (! Schema::hasColumn($table, 'meta_description')) {
                    $t->text('meta_description')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['blogs', 'categories'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    foreach (['meta_title', 'meta_description'] as $c) {
                        if (Schema::hasColumn($table, $c)) {
                            $t->dropColumn($c);
                        }
                    }
                });
            }
        }
    }
};
