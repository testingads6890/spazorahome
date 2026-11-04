<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Units a product is sold per (Running ft, Sq ft, Box...). Admins and sellers can add more from the product form. */
return new class extends Migration
{
    private const DEFAULTS = [
        ['running_ft', 'Running ft', [['length', 'Length (ft)', 'number'], ['height', 'Height (ft)', 'number']]],
        ['rm', 'Running metre', [['length', 'Length (m)', 'number'], ['height', 'Height (m)', 'number']]],
        ['sqft', 'Sq ft', [['area', 'Area (sq ft)', 'number']]],
        ['sqm', 'Sq m', [['area', 'Area (sq m)', 'number']]],
        ['roll', 'Roll', [['length', 'Roll length (m)', 'number'], ['width', 'Roll width (m)', 'number']]],
        ['box', 'Box', [['pieces', 'Pieces in one box', 'number'], ['coverage', 'Coverage (e.g. 2 sq ft)', 'text']]],
        ['pcs', 'Piece', [['count', 'Number of pieces', 'number']]],
        ['set', 'Set', [['count', 'Pieces in one set', 'number']]],
        ['pair', 'Pair', [['count', 'Pieces in one pair', 'number']]],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->string('label', 60);
                $table->json('fields');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (DB::table('product_units')->count() === 0) {
            foreach (self::DEFAULTS as [$key, $label, $fields]) {
                $fieldsJson = json_encode(array_map(fn ($f) => ['name' => $f[0], 'label' => $f[1], 'type' => $f[2]], $fields));
                DB::table('product_units')->insert(['key' => $key, 'label' => $label, 'fields' => $fieldsJson, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        if (! Schema::hasColumn('products', 'unit_details')) {
            Schema::table('products', function (Blueprint $table) {
                $table->longText('unit_details')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Kept on purpose: the units and the saved numbers are data.
    }
};
