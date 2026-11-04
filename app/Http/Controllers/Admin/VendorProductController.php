<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorProduct;
use Illuminate\Http\Request;

/**
 * Review queue for seller listings. Nothing a seller adds is public until it is approved here.
 * Approve: goes live. Reject: stays hidden. Request changes: sent back to the seller with a note,
 * and returns to this queue when they save it.
 */
class VendorProductController extends Controller
{
    public const STATUSES = ['pending', 'changes_requested', 'approved', 'rejected'];

    public function index(Request $request)
    {
        $query = VendorProduct::with(['vendor', 'product.images', 'product.category:id,name'])->whereHas('product');
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;
        if ($status) {
            $query->where('status', $status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->vendor_id);
        }
        $items = $query->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'changes_requested' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END")->latest('id')->paginate(20)->withQueryString();
        $counts = VendorProduct::whereHas('product')->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.vendor-products.index', ['vendorProducts' => $items, 'counts' => $counts, 'status' => $status]);
    }

    public function approve(VendorProduct $vendorProduct)
    {
        $vendorProduct->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        $vendorProduct->product()->update(['vendor_status' => 'approved', 'vendor_rejection_reason' => null]);
        return back()->with('success', 'Vendor product approved.');
    }

    public function reject(Request $request, VendorProduct $vendorProduct)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        $vendorProduct->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);
        $vendorProduct->product()->update(['vendor_status' => 'rejected', 'vendor_rejection_reason' => $request->reason]);
        return back()->with('success', 'Vendor product rejected.');
    }

    /** Send the listing back to the seller with a note. It is not public while changes are pending. */
    public function requestChanges(Request $request, VendorProduct $vendorProduct)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        $vendorProduct->update([
            'status' => 'changes_requested',
            'rejection_reason' => $request->reason,
        ]);
        $vendorProduct->product()->update(['vendor_status' => 'changes_requested', 'vendor_rejection_reason' => $request->reason]);
        return back()->with('success', 'Changes requested. The seller sees your note and can resubmit.');
    }

    /** Publish or unpublish an approved listing without changing its approval. */
    public function togglePublish(VendorProduct $vendorProduct)
    {
        $product = $vendorProduct->product;
        abort_unless($product && $vendorProduct->status === 'approved', 404);
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Product published.' : 'Product unpublished.');
    }

    public function toggleFeatured(VendorProduct $vendorProduct)
    {
        $product = $vendorProduct->product;
        abort_unless($product && $vendorProduct->status === 'approved', 404);
        $product->update(['is_featured' => ! $product->is_featured]);

        return back()->with('success', $product->is_featured ? 'Marked as featured.' : 'Removed from featured.');
    }
}
