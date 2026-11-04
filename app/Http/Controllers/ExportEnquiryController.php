<?php

namespace App\Http\Controllers;

use App\Models\ExportEnquiry;
use App\Models\Product;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Request an export quote" on a product page: for bulk and trade buyers abroad who want a price before ordering. */
class ExportEnquiryController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless(config('shop.export.enabled') && $product->is_active && $product->export_available, 404);

        $data = $request->validateWithBag('exportEnquiry', [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'country' => ['required', Rule::in(array_values(array_filter(Countries::all(), fn ($c) => ! Countries::isIndia($c))))],
            'quantity' => 'required|integer|min:1|max:1000000',
            'message' => 'nullable|string|max:2000',
        ]);

        ExportEnquiry::create($data + ['product_id' => $product->id, 'user_id' => auth()->id()]);

        return back()->with('success', __('pages.product.enquiry_sent'));
    }
}
