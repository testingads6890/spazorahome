<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller-panel extras (in the spirit of Martfury, without subscriptions): withdrawal requests, seller coupons,
 * replies to reviews, and a store logo, cover banner and social links. Every step checks first, so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_withdrawals')) {
            Schema::create('vendor_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->{$this->idType('vendors')}('vendor_id')->index();
                $table->decimal('amount', 12, 2);          // what the seller asked for (sum of the order lines it covers)
                $table->decimal('fee', 12, 2)->default(0);
                $table->string('status', 20)->default('pending'); // pending, approved, paid, rejected, cancelled
                $table->string('method', 30)->default('bank_transfer');
                $table->json('payout_details')->nullable(); // bank / UPI details at the time of the request
                $table->json('vendor_order_ids')->nullable(); // the order lines this request pays out
                $table->text('note')->nullable();          // from the seller
                $table->text('admin_note')->nullable();    // from the team (reason for rejection, transfer reference)
                $table->string('transaction_ref', 100)->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->{$this->idType('users')}('processed_by')->nullable()->index();
                $table->timestamps();
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_coupons')) {
            Schema::create('vendor_coupons', function (Blueprint $table) {
                $table->id();
                $table->{$this->idType('vendors')}('vendor_id')->index();
                $table->string('code', 40)->unique();
                $table->string('title')->nullable();
                $table->string('type', 10)->default('percent'); // percent or fixed
                $table->decimal('value', 12, 2);
                $table->decimal('min_order', 12, 2)->default(0); // on this seller's items in the cart
                $table->decimal('max_discount', 12, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Links are made last and never stop the update (a half-finished earlier run is repaired here too).
        $this->link('vendor_withdrawals', 'vendor_id', 'vendors');
        $this->link('vendor_withdrawals', 'processed_by', 'users', 'set null');
        $this->link('vendor_coupons', 'vendor_id', 'vendors');

        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (! Schema::hasColumn('reviews', 'vendor_reply')) {
                    $table->text('vendor_reply')->nullable();
                }
                if (! Schema::hasColumn('reviews', 'vendor_replied_at')) {
                    $table->timestamp('vendor_replied_at')->nullable();
                }
            });
        }

        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'logo')) {
                $table->string('logo')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'cover')) {
                $table->string('cover')->nullable();
            }
            if (! Schema::hasColumn('vendors', 'social_links')) {
                $table->json('social_links')->nullable();
            }
        });
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
        Schema::dropIfExists('vendor_withdrawals');
        Schema::dropIfExists('vendor_coupons');
    }
};
