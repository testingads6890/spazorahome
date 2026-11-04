<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index()
    {
        $deals = Deal::with('product')->paginate(20);
        return view('admin.deals.index', compact('deals'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->get();
        return view('admin.deals.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'required|exists:products,id',
            'original_price' => 'required|numeric|min:0',
            'deal_price' => 'required|numeric|min:0|lte:original_price',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        Deal::create($request->all());

        return redirect()->route('admin.deals.index')
            ->with('success', 'Deal created successfully.');
    }

    public function edit(Deal $deal)
    {
        $products = Product::where('is_active', true)->get();
        return view('admin.deals.edit', compact('deal', 'products'));
    }

    public function update(Request $request, Deal $deal)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'required|exists:products,id',
            'original_price' => 'required|numeric|min:0',
            'deal_price' => 'required|numeric|min:0|lte:original_price',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $deal->update($request->all());

        return redirect()->route('admin.deals.index')
            ->with('success', 'Deal updated successfully.');
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return redirect()->route('admin.deals.index')
            ->with('success', 'Deal deleted successfully.');
    }
}