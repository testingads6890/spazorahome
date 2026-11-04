<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

/** A signed-in customer reviews a product straight from its page. The team approves it before it shows. */
class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active && ($product->vendor_id === null || $product->vendor_status === 'approved'), 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // One review per customer per product: a second one replaces the first (and waits for approval again).
        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            $data + ['is_approved' => false],
        );

        return back()->with('success', __('Thanks! Your review will appear once our team has approved it.'));
    }
}
