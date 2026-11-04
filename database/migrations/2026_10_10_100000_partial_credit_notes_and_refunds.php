<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every step checks first, so this can be run again if it stopped half way (MySQL keeps the steps that finished).
        // An order can now have several credit notes (partial returns), so order_id is no longer unique.
        // The plain index is added first because the foreign key needs an index to stay in place.
        $indexes = collect(Schema::getIndexes('credit_notes'));
        if (! $indexes->contains(fn ($i) => ! $i['unique'] && $i['columns'] === ['order_id'])) {
            Schema::table('credit_notes', fn (Blueprint $table) => $table->index('order_id', 'credit_notes_order_id_index'));
        }
        foreach ($indexes->filter(fn ($i) => $i['unique'] && ! $i['primary'] && $i['columns'] === ['order_id']) as $index) {
            Schema::table('credit_notes', fn (Blueprint $table) => $table->dropUnique($index['name']));
        }

        $this->add('credit_notes', 'is_full', fn (Blueprint $t) => $t->boolean('is_full')->default(true));
        $this->add('credit_notes', 'shipping', fn (Blueprint $t) => $t->decimal('shipping', 12, 2)->default(0));
        $this->add('credit_notes', 'shipping_tax', fn (Blueprint $t) => $t->decimal('shipping_tax', 12, 2)->default(0));
        $this->add('credit_notes', 'tax', fn (Blueprint $t) => $t->decimal('tax', 12, 2)->nullable());
        $this->add('credit_notes', 'total', fn (Blueprint $t) => $t->decimal('total', 12, 2)->nullable());

        if (! Schema::hasTable('credit_note_items')) {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('taxable', 12, 2);
                $table->decimal('tax', 12, 2);
                $table->timestamps();

                $table->index('order_item_id');
            });
        }

        // refunded_amount: money already sent back. refund_due: money owed back but not sent yet (retry or pay by hand).
        $this->add('payments', 'refunded_amount', fn (Blueprint $t) => $t->decimal('refunded_amount', 12, 2)->default(0));
        $this->add('payments', 'refund_due', fn (Blueprint $t) => $t->decimal('refund_due', 12, 2)->default(0));
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
            $table->dropColumn(['refunded_amount', 'refund_due']);
        });

        Schema::dropIfExists('credit_note_items');

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn(['is_full', 'shipping', 'shipping_tax', 'tax', 'total']);
            $table->unique('order_id');
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropIndex('credit_notes_order_id_index');
        });
    }
};
