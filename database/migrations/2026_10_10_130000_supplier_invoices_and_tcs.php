<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-supplier invoicing (SHOP_INVOICE_MODE=per_supplier): each vendor gets its own series, e.g. V12 and C12.
        // Some databases never got the series column (invoices and credit notes numbered separately), so it is added here if missing.
        if (Schema::hasColumn('invoice_sequences', 'series')) {
            Schema::table('invoice_sequences', fn (Blueprint $table) => $table->string('series', 12)->default('INV')->change());
        } else {
            Schema::table('invoice_sequences', fn (Blueprint $table) => $table->string('series', 12)->default('INV'));
        }
        $indexes = collect(Schema::getIndexes('invoice_sequences'));
        foreach ($indexes->filter(fn ($i) => $i['unique'] && ! $i['primary'] && $i['columns'] === ['financial_year']) as $index) {
            Schema::table('invoice_sequences', fn (Blueprint $table) => $table->dropUnique($index['name']));
        }
        if (! $indexes->contains(fn ($i) => $i['unique'] && $i['columns'] === ['series', 'financial_year'])) {
            Schema::table('invoice_sequences', fn (Blueprint $table) => $table->unique(['series', 'financial_year']));
        }

        // One invoice per supplier of an order. vendor_id null = the marketplace's own goods and the shipping charge.
        if (! Schema::hasTable('supplier_invoices')) {
            Schema::create('supplier_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
                $table->string('number', 16)->unique();
                $table->timestamp('issued_at');
                $table->timestamps();

                $table->index('order_id');
            });
        }

        $this->add('credit_notes', 'supplier_invoice_id', fn (Blueprint $t) => $t->foreignId('supplier_invoice_id')->nullable()->constrained()->nullOnDelete());
        // Tax collected at source, held back from the vendor's payout and paid to the government by the operator.
        $this->add('vendor_orders', 'tcs_amount', fn (Blueprint $t) => $t->decimal('tcs_amount', 12, 2)->default(0));
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
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropColumn('tcs_amount');
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_invoice_id');
        });
        Schema::dropIfExists('supplier_invoices');
    }
};
