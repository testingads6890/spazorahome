<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Compares this database with what the migration files describe and lists tables and columns that are missing.
 * It builds the expected structure in a throwaway in-memory SQLite database; the real database is only read.
 */
class SchemaCheck extends Command
{
    protected $signature = 'shop:schema-check';

    protected $description = 'List tables and columns the migrations expect but this database does not have';

    public function handle(): int
    {
        $real = config('database.default');
        config(['database.connections.schema_check' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);

        try {
            Artisan::call('migrate', ['--database' => 'schema_check', '--force' => true]);
        } catch (\Throwable $e) {
            $this->error('Could not build the expected structure: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            config(['database.default' => $real]);
            \Illuminate\Support\Facades\DB::setDefaultConnection($real);
        }

        $missing = 0;
        foreach (Schema::connection('schema_check')->getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, ['migrations', 'sqlite_sequence'], true)) {
                continue;
            }
            if (! Schema::connection($real)->hasTable($table)) {
                $this->line("MISSING TABLE   {$table}");
                $missing++;

                continue;
            }
            $have = array_map('strtolower', Schema::connection($real)->getColumnListing($table));
            foreach (Schema::connection('schema_check')->getColumnListing($table) as $column) {
                if (! in_array(strtolower($column), $have, true)) {
                    $this->line("MISSING COLUMN  {$table}.{$column}");
                    $missing++;
                }
            }
        }

        $pending = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'))
            ->diff(Schema::connection($real)->hasTable('migrations') ? \Illuminate\Support\Facades\DB::connection($real)->table('migrations')->pluck('migration') : []);
        if ($pending->isNotEmpty()) {
            $this->line('');
            $this->line('Migrations not run yet (their tables and columns are listed above until you run them):');
            $pending->each(fn ($m) => $this->line("  {$m}"));
        }

        $this->line('');
        $missing ? $this->warn("{$missing} missing. Copy this whole output when asking for a fix.") : $this->info('Nothing missing: the database matches the migrations.');

        return self::SUCCESS;
    }
}
