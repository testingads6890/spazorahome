<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\Swatch;
use Illuminate\Http\Request;

class SwatchController extends Controller
{
    public function index(ProductAttribute $productAttribute)
    {
        $swatches = $productAttribute->swatches()->orderBy('sort_order')->get();
        return view('admin.swatches.index', compact('productAttribute', 'swatches'));
    }

    public function create(ProductAttribute $productAttribute)
    {
        return view('admin.swatches.create', compact('productAttribute'));
    }

    public function store(Request $request, ProductAttribute $productAttribute)
    {
        $request->validate([
            'value' => 'required|string|max:255',
            'type' => 'required|in:color,label,image',
            'image' => 'nullable|string',
            'color_code' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Swatch::create([
            'product_attribute_id' => $productAttribute->id,
            'value' => $request->value,
            'type' => $request->type,
            'image' => $request->image,
            'color_code' => $request->color_code,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.swatches.index', $productAttribute)
            ->with('success', 'Swatch created successfully.');
    }

    public function edit(ProductAttribute $productAttribute, Swatch $swatch)
    {
        return view('admin.swatches.edit', compact('productAttribute', 'swatch'));
    }

    public function update(Request $request, ProductAttribute $productAttribute, Swatch $swatch)
    {
        $request->validate([
            'value' => 'required|string|max:255',
            'type' => 'required|in:color,label,image',
            'image' => 'nullable|string',
            'color_code' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $swatch->update([
            'value' => $request->value,
            'type' => $request->type,
            'image' => $request->image,
            'color_code' => $request->color_code,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.swatches.index', $productAttribute)
            ->with('success', 'Swatch updated successfully.');
    }

    public function destroy(ProductAttribute $productAttribute, Swatch $swatch)
    {
        $swatch->delete();
        return redirect()->route('admin.swatches.index', $productAttribute)
            ->with('success', 'Swatch deleted successfully.');
    }
}