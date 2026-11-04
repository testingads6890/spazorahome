<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What an exporter needs to know about each product, beyond "may it leave India".
        $this->add('products', 'export_weight_kg', fn (Blueprint $t) => $t->decimal('export_weight_kg', 8, 2)->nullable()); // packed weight of one unit
        $this->add('products', 'country_of_origin', fn (Blueprint $t) => $t->string('country_of_origin', 100)->default('India'));
        $this->add('products', 'export_min_qty', fn (Blueprint $t) => $t->unsignedInteger('export_min_qty')->default(1));
        $this->add('products', 'export_blocked_countries', fn (Blueprint $t) => $t->json('export_blocked_countries')->nullable());

        // International freight is priced by weight: zone rate + rate_per_kg x packed weight of the order.
        $this->add('shipping_zones', 'rate_per_kg', fn (Blueprint $t) => $t->decimal('rate_per_kg', 10, 2)->nullable());

        // The packed weight the freight was worked out on, kept for the packing list.
        $this->add('orders', 'export_weight_kg', fn (Blueprint $t) => $t->decimal('export_weight_kg', 10, 2)->nullable());

        // Bulk / trade buyers abroad ask for a quote instead of checking out.
        if (! Schema::hasTable('export_enquiries')) {
            Schema::create('export_enquiries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 30)->nullable();
                $table->string('company')->nullable();
                $table->string('country', 100);
                $table->unsignedInteger('quantity')->default(1);
                $table->text('message')->nullable();
                $table->string('status', 20)->default('new'); // new, replied, closed
                $table->text('admin_notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }
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
        Schema::dropIfExists('export_enquiries');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('export_weight_kg');
        });
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn('rate_per_kg');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['export_weight_kg', 'country_of_origin', 'export_min_qty', 'export_blocked_countries']);
        });
    }
};
