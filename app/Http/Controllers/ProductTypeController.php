<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admins and sellers add a product type that is missing from the list. */
class ProductTypeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'vendor']), 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $name = trim($data['name']);
        $type = ProductType::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if (! $type) {
            $base = Str::slug($name) ?: 'type';
            $slug = $base;
            for ($i = 2; ProductType::where('slug', $slug)->exists(); $i++) {
                $slug = $base . '-' . $i;
            }
            $type = ProductType::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        }

        return response()->json(['type' => ['id' => $type->id, 'name' => $type->name]], 201);
    }
}
