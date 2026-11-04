<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema for the features finished on 12 Oct 2026: vendor "under review", product "changes requested",
 * richer vendor listings, extra order stages, ticket follow-ups and staff roles.
 * Every step checks first, so it is safe on a database where some of this was already applied by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addEnumValues('vendors', 'status', ['under_review']);
        $this->addEnumValues('products', 'vendor_status', ['changes_requested']);
        $this->addEnumValues('vendor_products', 'status', ['changes_requested']);
        $this->addEnumValues('orders', 'status', ['ready_to_ship', 'out_for_delivery']);
        $this->addEnumValues('support_tickets', 'status', ['in_progress', 'escalated']);
        $this->addEnumValues('support_tickets', 'priority', ['high']);
        $this->addEnumValues('ticket_messages', 'type', ['note', 'chat']);

        // Call outcomes are a free list in the app, so the column only needs to hold short text.
        if ($this->enumValues('ticket_call_records', 'call_status') !== null) {
            DB::statement('ALTER TABLE `ticket_call_records` MODIFY `call_status` VARCHAR(30) NULL');
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'video_url')) {
                $table->string('video_url', 500)->nullable();
            }
            if (! Schema::hasColumn('products', 'min_order_qty')) {
                $table->unsignedInteger('min_order_qty')->default(1);
            }
            if (! Schema::hasColumn('products', 'specifications')) {
                $table->text('specifications')->nullable();
            }
            if (! Schema::hasColumn('products', 'bulk_pricing')) {
                $table->json('bulk_pricing')->nullable();
            }
        });

        if (Schema::hasTable('support_tickets') && ! Schema::hasColumn('support_tickets', 'follow_up_date')) {
            Schema::table('support_tickets', fn (Blueprint $table) => $table->date('follow_up_date')->nullable());
        }

        if (Schema::hasTable('roles')) {
            foreach (['admin', 'vendor', 'customer', 'vendor_manager', 'product_manager', 'order_manager', 'support_manager', 'support_executive'] as $role) {
                DB::table('roles')->insertOrIgnore(['name' => $role, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
            app()['cache']->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }

    public function down(): void
    {
        // Values and columns stay: removing them could drop data that now uses them.
    }

    /** The values of an ENUM column, or null when the column is missing or not an enum. */
    private function enumValues(string $table, string $column): ?array
    {
        // ENUM lists only exist on MySQL / MariaDB (SQLite, used by the test suite, stores them as plain text).
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }
        $type = DB::selectOne('SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column])?->t;
        if (! $type || ! str_starts_with(strtolower($type), 'enum(')) {
            return null;
        }
        preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $type, $m);

        return $m[1];
    }

    private function addEnumValues(string $table, string $column, array $add): void
    {
        $values = $this->enumValues($table, $column);
        if ($values === null || ! array_diff($add, $values)) {
            return;
        }
        $col = DB::selectOne('SELECT IS_NULLABLE AS n, COLUMN_DEFAULT AS d FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
        $list = implode(',', array_map(fn ($v) => DB::getPdo()->quote($v), array_values(array_unique(array_merge($values, $add)))));
        $null = $col->n === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col->d !== null ? ' DEFAULT ' . DB::getPdo()->quote(trim($col->d, "'")) : '';
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$list}) {$null}{$default}");
    }
};
