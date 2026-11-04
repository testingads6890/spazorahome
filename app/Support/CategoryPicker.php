<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The product type → category → subcategory choice used on the admin and seller product forms and on the
 * bulk enquiry page. One list, so the three places always agree.
 */
class CategoryPicker
{
    /** Everything the picker needs, as plain data for the page script. */
    public static function data(): array
    {
        $types = Schema::hasTable('product_types') ? ProductType::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();
        $cats = Category::where('status', true)->orderBy('name')->get(['id', 'name', 'parent_id', 'product_type_id']);

        return [
            'types' => $types->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->all(),
            // Top-level categories, each with its type and its subcategories.
            'categories' => $cats->whereNull('parent_id')->values()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->product_type_id,
                'subs' => $cats->where('parent_id', $c->id)->values()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all(),
            ])->all(),
        ];
    }

    /** Category and subcategory ids saved on a product (its links in category_product). */
    public static function savedIds(?Product $product): array
    {
        if (! $product || ! $product->exists || ! Schema::hasTable('category_product')) {
            return [];
        }

        return DB::table('category_product')->where('product_id', $product->id)->pluck('category_id')->map(fn ($id) => (int) $id)->all();
    }

    /** Save every ticked category and subcategory for the product, keeping the first one as the main category. */
    public static function sync(Product $product, \Illuminate\Http\Request $request): void
    {
        if (! $request->has('category_ids_sent') || ! Schema::hasTable('category_product')) {
            return;
        }
        $ids = collect(array_merge((array) $request->input('category_ids', []), (array) $request->input('subcategory_ids', [])))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $valid = Category::whereIn('id', $ids)->pluck('id')->all();

        DB::table('category_product')->where('product_id', $product->id)->delete();
        $now = now();
        foreach ($valid as $id) {
            DB::table('category_product')->insert(['category_id' => $id, 'product_id' => $product->id, 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($valid && (int) $product->category_id !== (int) $valid[0]) {
            $product->forceFill(['category_id' => $valid[0]])->saveQuietly();
        }
    }
}
