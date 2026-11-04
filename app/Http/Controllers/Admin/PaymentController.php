<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::paginate(20);
        return view('admin.payments.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('admin.payments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:payment_methods',
            'type' => 'required|in:automatic,manual',
            'description' => 'nullable|string',
            'credentials' => 'nullable|array',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        PaymentMethod::create($request->all());

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payments.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:payment_methods,slug,' . $paymentMethod->id,
            'type' => 'required|in:automatic,manual',
            'description' => 'nullable|string',
            'credentials' => 'nullable|array',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $paymentMethod->update($request->all());

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment method deleted successfully.');
    }
}