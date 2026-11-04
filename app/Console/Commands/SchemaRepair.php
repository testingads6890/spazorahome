<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns the migration files expect but this database does not have. Nothing is dropped and no row is
 * changed: every column is added empty (or with its default). Tables that are missing altogether are left to
 * "php artisan migrate". Old columns the files no longer know are kept; if they would block new rows
 * (required, no default) they are made optional.
 *
 * The expected structure is built in a throwaway in-memory SQLite database, so column types come from there:
 * lengths and decimal places are generous approximations, which is fine for holding the data.
 */
class SchemaRepair extends Command
{
    protected $signature = 'shop:schema-repair {--dry-run : Only list what would be done}';

    protected $description = 'Add missing columns to this database without deleting anything';

    public function handle(): int
    {
        $real = config('database.default');
        config(['database.connections.schema_check' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);

        // Migrations that have not run here yet add their own columns, with exact types, when "php artisan migrate" runs.
        // They are taken back out of the expected structure so this command leaves their columns to them.
        $files = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'))->sort()->values();
        $ran = Schema::connection($real)->hasTable('migrations') ? DB::connection($real)->table('migrations')->pluck('migration') : collect();
        $pending = $files->diff($ran)->values();
        $pendingAreLast = $pending->isNotEmpty() && $pending->all() === $files->slice(-$pending->count())->values()->all();

        try {
            Artisan::call('migrate', ['--database' => 'schema_check', '--force' => true]);
            // Every column the files know, including those of pending migrations: none of these is an "old" column.
            $known = collect(Schema::connection('schema_check')->getTableListing(schemaQualified: false))
                ->mapWithKeys(fn ($t) => [$t => array_map('strtolower', Schema::connection('schema_check')->getColumnListing($t))]);
            if ($pendingAreLast) {
                Artisan::call('migrate:rollback', ['--database' => 'schema_check', '--force' => true, '--step' => $pending->count()]);
            }
        } catch (\Throwable $e) {
            $this->error('Could not build the expected structure: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            config(['database.default' => $real]);
            DB::setDefaultConnection($real);
        }

        $dry = (bool) $this->option('dry-run');
        $expected = Schema::connection('schema_check');
        $db = Schema::connection($real);
        $added = $relaxed = $failed = 0;

        foreach ($expected->getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, ['migrations', 'sqlite_sequence'], true) || ! $db->hasTable($table)) {
                continue; // missing tables are created by "php artisan migrate"
            }

            $have = collect($db->getColumns($table))->keyBy(fn ($c) => strtolower($c['name']));
            $want = collect($expected->getColumns($table))->keyBy(fn ($c) => strtolower($c['name']));

            foreach ($want->diffKeys($have) as $column) {
                $this->line(($dry ? 'WOULD ADD   ' : 'ADD         ') . "{$table}.{$column['name']}");
                if ($dry) {
                    continue;
                }
                try {
                    $db->table($table, fn (Blueprint $t) => $this->define($t, $column));
                    $added++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("  failed: " . $e->getMessage());
                }
            }

            // Columns from an older version of the shop: kept, but they must not stop new rows from being saved.
            foreach ($have->diffKeys($want)->except($known[$table] ?? []) as $column) {
                $blocks = ! $column['nullable'] && $column['default'] === null && ! $column['auto_increment'];
                $this->line(($blocks ? ($dry ? 'WOULD RELAX ' : 'RELAX       ') : 'OLD COLUMN  ') . "{$table}.{$column['name']} ({$column['type']})" . ($blocks ? ' required, no default: made optional' : ' kept as it is'));
                if ($blocks && ! $dry && DB::connection($real)->getDriverName() === 'mysql') {
                    try {
                        DB::connection($real)->statement("ALTER TABLE `{$table}` MODIFY `{$column['name']}` {$column['type']} NULL");
                        $relaxed++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->error("  failed: " . $e->getMessage());
                    }
                }
            }
        }

        $this->line('');
        if ($dry) {
            $this->info('Dry run: nothing was changed.');
        } else {
            $this->info("Added {$added} columns, made {$relaxed} old columns optional, {$failed} failed. Now run: php artisan migrate");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** One missing column, always optional so that existing rows stay valid. */
    private function define(Blueprint $t, array $column): void
    {
        $name = $column['name'];
        $type = strtolower($column['type_name']);

        $col = match (true) {
            $type === 'tinyint' => $t->boolean($name),
            str_contains($type, 'int') => str_ends_with($name, '_id') ? $t->unsignedBigInteger($name) : $t->bigInteger($name),
            in_array($type, ['numeric', 'decimal'], true) => str_contains($name, 'exchange_rate') ? $t->decimal($name, 14, 6) : $t->decimal($name, 14, 2),
            in_array($type, ['float', 'double', 'real'], true) => $t->double($name),
            $type === 'datetime' || $type === 'timestamp' => $t->dateTime($name),
            $type === 'date' => $t->date($name),
            $type === 'time' => $t->time($name),
            $type === 'text' => $t->text($name),
            default => $t->string($name),
        };
        $col->nullable();

        // Keep the default the files give the column (SQLite reports it as written in SQL, e.g. '0' or 'INV').
        $default = $column['default'];
        if ($default !== null && strtoupper((string) $default) !== 'NULL' && ! in_array($type, ['text', 'datetime', 'timestamp', 'date', 'time'], true)) {
            $col->default(trim((string) $default, "'\""));
        }
    }
}
