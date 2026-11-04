<?php

namespace App\Http\Controllers;

use App\Models\BulkEnquiry;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

/** Public bulk / trade enquiry form. A hidden "website" field catches spam bots. */
class BulkEnquiryController extends Controller
{
    public function create()
    {
        return view('bulk-enquiry.create');
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('bulk-enquiry.create')->with('success', __('Thank you! We will contact you soon.'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'other_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'subcategory_ids' => ['nullable', 'array'],
            'subcategory_ids.*' => ['integer', 'exists:categories,id'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['product_type'] = \App\Models\ProductType::whereKey($data['product_type_id'])->value('name');
        unset($data['product_type_id']);
        $data['category_ids'] = array_values(array_unique(array_map('intval', $data['category_ids'])));
        $data['subcategory_ids'] = array_values(array_unique(array_map('intval', $data['subcategory_ids'] ?? [])));
        BulkEnquiry::create($data + ['ip' => $request->ip()]);

        return redirect()->route('bulk-enquiry.create')->with('success', __('Thank you! We will contact you soon.'));
    }
}
