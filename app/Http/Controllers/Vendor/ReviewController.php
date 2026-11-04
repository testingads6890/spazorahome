<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

/** Reviews customers left on the seller's products, with a public reply from the seller. */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        $base = fn () => Review::whereHas('product', fn ($q) => $q->where('vendor_id', $vendor->id));

        $reviews = $base()->with(['product:id,name,slug', 'user:id,name'])
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->rating))
            ->when($request->query('show') === 'unanswered', fn ($q) => $q->whereNull('vendor_reply'))
            ->latest()->paginate(15)->withQueryString();

        $stats = [
            'count' => $base()->count(),
            'average' => round((float) $base()->avg('rating'), 1),
            'unanswered' => $base()->whereNull('vendor_reply')->count(),
            'by_star' => $base()->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating'),
        ];

        return view('vendor.reviews.index', compact('reviews', 'stats'));
    }

    public function reply(Request $request, Review $review)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($review->product && (int) $review->product->vendor_id === (int) $vendor->id, 404);
        $data = $request->validate(['vendor_reply' => 'nullable|string|max:2000']);

        $text = trim((string) ($data['vendor_reply'] ?? ''));
        $review->forceFill(['vendor_reply' => $text ?: null, 'vendor_replied_at' => $text ? now() : null])->save();

        return back()->with('success', $text ? 'Reply posted. Customers see it under the review.' : 'Reply removed.');
    }
}
