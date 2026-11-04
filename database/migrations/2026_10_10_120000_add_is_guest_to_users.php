<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // True for an account made by guest checkout that has no password of its own yet.
        $this->add('users', 'is_guest', fn (Blueprint $t) => $t->boolean('is_guest')->default(false));
    }

    /** Add a column only if it is missing, so the migration can be run again after stopping half way (MySQL keeps the finished steps). */
    private function add(string $table, string $column, \Closure $definition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, fn (Blueprint $t) => $definition($t));
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_guest');
        });
    }
};
