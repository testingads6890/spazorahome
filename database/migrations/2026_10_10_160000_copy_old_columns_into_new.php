<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Databases made by an older version of the shop keep some data under old column names (found by
 * "php artisan shop:schema-repair"). This copies it into the columns the shop reads now. It only fills
 * columns that are still empty, never overwrites, and does nothing on a database without the old columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['banners', 'image', 'image_url'], ['banners', 'link', 'link_url'],
            ['deals', 'name', 'title'],
            ['product_images', 'path', 'image_url'],
            ['professional_profiles', 'specialization', 'expertise'], ['professional_profiles', 'description', 'bio'],
            ['room_ideas', 'image', 'image_url'],
            ['shipping_methods', 'rate', 'cost'],
            ['sliders', 'name', 'title'],
            ['swatches', 'value', 'name'],
        ] as [$table, $new, $old]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $new) && Schema::hasColumn($table, $old)) {
                DB::table($table)->whereNotNull($old)->where(fn ($q) => $q->whereNull($new)->orWhere($new, ''))->update([$new => DB::raw($old)]);
            }
        }

        // A delivery option that already cost something keeps that cost as its extra charge.
        if (Schema::hasColumn('shipping_methods', 'cost') && Schema::hasColumn('shipping_methods', 'rate')) {
            DB::table('shipping_methods')->where('rate', 0)->where('cost', '>', 0)->update(['rate' => DB::raw('cost')]);
        }

        // Values the shop needs on every row.
        $this->fillEmpty('banners', 'position', 'homepage');
        $this->fillEmpty('sliders', 'location', 'home');
        $this->fillEmpty('currencies', 'position', 'before');
        if (Schema::hasColumn('currencies', 'name') && Schema::hasColumn('currencies', 'code')) {
            DB::table('currencies')->whereNull('name')->update(['name' => DB::raw('code')]);
        }

        // An old slider row was one picture: it becomes a slider with that one slide.
        if (Schema::hasColumn('sliders', 'image_url') && Schema::hasColumn('sliders', 'slides')) {
            foreach (DB::table('sliders')->whereNull('slides')->whereNotNull('image_url')->get() as $row) {
                DB::table('sliders')->where('id', $row->id)->update([
                    'slides' => json_encode([['image' => $row->image_url, 'title' => $row->title ?? null]]),
                    'name' => $row->name ?? ($row->title ?? 'Slider ' . $row->id),
                ]);
            }
        }

        // Room ideas need a slug of their own.
        if (Schema::hasColumn('room_ideas', 'slug') && Schema::hasColumn('room_ideas', 'title')) {
            foreach (DB::table('room_ideas')->whereNull('slug')->get() as $row) {
                DB::table('room_ideas')->where('id', $row->id)->update(['slug' => (Str::slug((string) $row->title) ?: 'room-idea') . '-' . $row->id]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the old columns were never changed.
    }

    private function fillEmpty(string $table, string $column, string $value): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->whereNull($column)->update([$column => $value]);
        }
    }
};
