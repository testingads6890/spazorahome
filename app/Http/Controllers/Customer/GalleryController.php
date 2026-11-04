<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Product $product)
    {
        $images = $product->images()->orderBy('sort_order')->get();
        return view('customer.products.gallery', compact('product', 'images'));
    }
}