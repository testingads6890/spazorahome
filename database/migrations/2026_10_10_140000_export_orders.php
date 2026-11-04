<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders delivered outside India (SHOP_EXPORT_ENABLED). The books stay in rupees: total, tax and refunds are INR.
        // charge_currency / charge_total record what the customer's card was actually charged, at exchange_rate units per 1 INR.
        $this->add('orders', 'is_export', fn (Blueprint $t) => $t->boolean('is_export')->default(false));
        $this->add('orders', 'charge_currency', fn (Blueprint $t) => $t->string('charge_currency', 3)->nullable());
        $this->add('orders', 'charge_total', fn (Blueprint $t) => $t->decimal('charge_total', 14, 2)->nullable());
        $this->add('orders', 'exchange_rate', fn (Blueprint $t) => $t->decimal('exchange_rate', 14, 6)->nullable());

        // Only products ticked "available for export" can be delivered outside India.
        $this->add('products', 'export_available', fn (Blueprint $t) => $t->boolean('export_available')->default(false));

        // amount stays in rupees; charge_amount is the same payment in its own currency (payments.currency).
        $this->add('payments', 'charge_amount', fn (Blueprint $t) => $t->decimal('charge_amount', 14, 2)->nullable());
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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('charge_amount');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('export_available');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_export', 'charge_currency', 'charge_total', 'exchange_rate']);
        });
    }
};
