<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Older copies of this table were made without these columns; the delivery options need them.
        $this->add('shipping_methods', 'type', fn (Blueprint $t) => $t->string('type')->default('flat_rate'));
        $this->add('shipping_methods', 'description', fn (Blueprint $t) => $t->text('description')->nullable());
        $this->add('shipping_methods', 'is_active', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        // Flat rate: extra charge on top of the address's normal shipping fee (0 = standard delivery).
        $this->add('shipping_methods', 'rate', fn (Blueprint $t) => $t->decimal('rate', 10, 2)->default(0));
        // Free shipping: only offered once the order value reaches this amount (empty = always).
        $this->add('shipping_methods', 'min_order', fn (Blueprint $t) => $t->decimal('min_order', 12, 2)->nullable());
        $this->add('shipping_methods', 'delivery_estimate', fn (Blueprint $t) => $t->string('delivery_estimate', 100)->nullable());
        $this->add('shipping_methods', 'sort_order', fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0));
        $this->add('orders', 'shipping_method', fn (Blueprint $t) => $t->string('shipping_method')->nullable());
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
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_method');
        });
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn(['rate', 'min_order', 'delivery_estimate', 'sort_order']);
        });
    }
};
