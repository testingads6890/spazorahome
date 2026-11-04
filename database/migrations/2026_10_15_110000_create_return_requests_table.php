<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customers ask to return items from a delivered order; the team approves (credit note + refund) or declines. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('return_requests')) {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->{$this->idType('orders')}('order_id')->index();
            $table->{$this->idType('users')}('user_id')->index();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->json('items');                            // {order_item_id: quantity}
            $table->string('reason', 60);
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('credit_notes')->nullable();       // numbers issued when approved
            $table->timestamp('resolved_at')->nullable();
            $table->{$this->idType('users')}('resolved_by')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        }

        $this->link('return_requests', 'order_id', 'orders');
        $this->link('return_requests', 'user_id', 'users');
        $this->link('return_requests', 'resolved_by', 'users', 'set null');
    }

    /** Column type that matches the referenced table's id (live servers differ), so a link can be made to it. */
    private function idType(string $refTable): string
    {
        foreach (Schema::getColumns($refTable) as $c) {
            if ($c['name'] === 'id') {
                $big = str_contains(strtolower($c['type_name']), 'big');
                $unsigned = str_contains(strtolower($c['type']), 'unsigned');

                return ($unsigned ? 'unsigned' : '') . ($big ? ($unsigned ? 'BigInteger' : 'bigInteger') : ($unsigned ? 'Integer' : 'integer'));
            }
        }

        return 'unsignedBigInteger';
    }

    /** Links the column to its parent table. If the server cannot (different engine or type) the column simply stays indexed, and the migration carries on. */
    private function link(string $table, string $column, string $refTable, string $onDelete = 'cascade'): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column, $refTable, $onDelete) {
                $t->foreign($column)->references('id')->on($refTable)->onDelete($onDelete);
            });
        } catch (\Throwable $e) {
            // already linked, or not possible on this server: not worth stopping the update
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
