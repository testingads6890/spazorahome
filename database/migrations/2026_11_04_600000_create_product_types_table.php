<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Product types (Furniture, Decor, Lighting...). A type holds categories, a category holds subcategories. */
return new class extends Migration
{
    private const TYPES = ['Furniture', 'Decor', 'Lighting', 'Textiles', 'Outdoor', 'Kids', 'Kitchen & Dining'];

    public function up(): void
    {
        if (! Schema::hasTable('product_types')) {
            Schema::create('product_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->unique();
                $table->string('slug', 140)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('categories', 'product_type_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('product_type_id')->nullable()->after('parent_id')->constrained('product_types')->nullOnDelete();
            });
        }

        // Starter types, and every top-level category goes under the type it belongs to (admin can change it).
        if (DB::table('product_types')->count() === 0) {
            foreach (self::TYPES as $name) {
                DB::table('product_types')->insert(['name' => $name, 'slug' => Str::slug($name), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            $map = ['Lighting' => 'Lighting', 'Textiles & Rugs' => 'Textiles', 'Decoration' => 'Decor', 'Outdoor' => 'Outdoor', 'Kids & Baby' => 'Kids', 'Kitchen & Dining' => 'Kitchen & Dining', 'Dining' => 'Kitchen & Dining'];
            foreach (DB::table('categories')->whereNull('parent_id')->get(['id', 'name']) as $cat) {
                $typeName = $map[$cat->name] ?? 'Furniture';
                $typeId = DB::table('product_types')->where('name', $typeName)->value('id');
                DB::table('categories')->where('id', $cat->id)->update(['product_type_id' => $typeId]);
            }
        }
    }

    public function down(): void
    {
        // Kept on purpose: the types and their links are data.
    }
};
