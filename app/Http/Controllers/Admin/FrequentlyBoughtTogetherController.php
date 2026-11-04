<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrequentlyBoughtTogether;
use App\Models\Product;
use Illuminate\Http\Request;

class FrequentlyBoughtTogetherController extends Controller
{
    public function index()
    {
        $items = FrequentlyBoughtTogether::with(['product', 'relatedProduct'])->paginate(20);
        return view('admin.frequently-bought.index', compact('items'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->get();
        return view('admin.frequently-bought.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'related_product_id' => 'required|exists:products,id|different:product_id',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        FrequentlyBoughtTogether::create($request->all());

        return redirect()->route('admin.frequently-bought.index')
            ->with('success', 'Frequently bought together item created successfully.');
    }

    public function edit(FrequentlyBoughtTogether $frequentlyBoughtTogether)
    {
        $products = Product::where('is_active', true)->get();
        return view('admin.frequently-bought.edit', compact('frequentlyBoughtTogether', 'products'));
    }

    public function update(Request $request, FrequentlyBoughtTogether $frequentlyBoughtTogether)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'related_product_id' => 'required|exists:products,id|different:product_id',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $frequentlyBoughtTogether->update($request->all());

        return redirect()->route('admin.frequently-bought.index')
            ->with('success', 'Frequently bought together item updated successfully.');
    }

    public function destroy(FrequentlyBoughtTogether $frequentlyBoughtTogether)
    {
        $frequentlyBoughtTogether->delete();
        return redirect()->route('admin.frequently-bought.index')
            ->with('success', 'Frequently bought together item deleted successfully.');
    }
}