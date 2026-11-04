<?php

namespace App\Http\Controllers;

use App\Models\ProductUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admins and sellers add a new unit (for example "Pack of 10") straight from the product form. */
class ProductUnitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'vendor']), 403);

        $data = $request->validate(['label' => ['required', 'string', 'max:60']]);

        $label = trim($data['label']);
        $existing = ProductUnit::whereRaw('LOWER(label) = ?', [mb_strtolower($label)])->first();
        if ($existing) {
            return response()->json(['unit' => ['key' => $existing->key, 'label' => $existing->label, 'fields' => $existing->fields]]);
        }

        $base = Str::slug($label, '_') ?: 'unit';
        $key = $base;
        for ($i = 2; ProductUnit::where('key', $key)->exists(); $i++) {
            $key = $base . '_' . $i;
        }

        // Every unit uses the same inputs (length, width, height, measured in), so none is stored per unit.
        $fields = [];

        $unit = ProductUnit::create([
            'key' => $key, 'label' => $label, 'fields' => $fields, 'is_active' => true, 'created_by' => $request->user()->id,
        ]);

        return response()->json(['unit' => ['key' => $unit->key, 'label' => $unit->label, 'fields' => $unit->fields]], 201);
    }
}
