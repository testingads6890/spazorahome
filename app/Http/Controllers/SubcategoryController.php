<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admins and sellers add a subcategory under a category. It shows in the picker straight away. */
class SubcategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'vendor']), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $parent = Category::whereNull('parent_id')->where('status', true)->find($data['parent_id']);
        abort_unless($parent, 422);

        $name = trim($data['name']);
        $existing = Category::where('parent_id', $parent->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json(['sub' => ['id' => $existing->id, 'name' => $existing->name, 'parent_id' => $parent->id]]);
        }

        $base = Str::slug($name) ?: 'subcategory';
        $slug = $base;
        for ($i = 2; Category::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        $sub = Category::create([
            'name' => mb_substr($name, 0, 120), 'slug' => $slug, 'parent_id' => $parent->id,
            'product_type_id' => $parent->product_type_id, 'status' => true,
        ]);

        return response()->json(['sub' => ['id' => $sub->id, 'name' => $sub->name, 'parent_id' => $parent->id]], 201);
    }
}
