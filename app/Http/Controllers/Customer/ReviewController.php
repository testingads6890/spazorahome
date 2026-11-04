<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Http\Requests\Customer\ReviewRequest;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = auth()->user()->reviews()->with('product')->latest()->paginate(10);
        return view('customer.reviews.index', compact('reviews'));
    }

    public function create()
    {
        $products = $this->purchasedProducts()->get();

        return view('customer.reviews.create', compact('products'));
    }

    public function store(ReviewRequest $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', \Illuminate\Validation\Rule::in($this->purchasedProducts()->pluck('products.id'))],
        ]);

        Review::create(array_merge($request->validated(), [
            'user_id' => auth()->id(),
            'product_id' => $data['product_id'],
        ]));

        return redirect()->route('customer.reviews.index')->with('success', __('Review submitted successfully.'));
    }

    public function edit(Review $review)
    {
        $this->authorizeOwner($review);

        return view('customer.reviews.edit', compact('review'));
    }

    public function update(ReviewRequest $request, Review $review)
    {
        $this->authorizeOwner($review);
        $review->update($request->validated());

        return redirect()->route('customer.reviews.index')->with('success', __('Review updated successfully.'));
    }

    public function destroy(Review $review)
    {
        $this->authorizeOwner($review);
        $review->delete();

        return back()->with('success', __('Review deleted successfully.'));
    }

    /** Only products the customer has actually ordered can be reviewed. */
    private function purchasedProducts()
    {
        return Product::whereHas('orderItems.order', fn ($q) => $q->where('user_id', auth()->id()));
    }

    private function authorizeOwner(Review $review): void
    {
        abort_unless($review->user_id === auth()->id(), 403);
    }
}
