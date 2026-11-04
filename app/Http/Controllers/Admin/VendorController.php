<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with(['user', 'documents']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $vendors = $query->latest()->paginate(20)->withQueryString();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('user', 'products', 'orders', 'payouts', 'documents.verifier');
        $categories = Category::whereIn('id', $vendor->product_categories ?? [])->orderBy('name')->pluck('name');

        return view('admin.vendors.show', compact('vendor', 'categories'));
    }

    /** Approve a seller. Applications that came with documents need the required ones verified first. */
    public function approve(Vendor $vendor)
    {
        $vendor->load('documents');
        if ($vendor->appliedWithDocuments() && $vendor->unverifiedRequiredDocuments()) {
            return redirect()->route('admin.vendors.show', $vendor)
                ->with('error', 'Verify the required documents first: ' . collect($vendor->unverifiedRequiredDocuments())->map(fn ($t) => str_replace('_', ' ', $t))->implode(', ') . '.');
        }

        $vendor->update([
            'status' => 'approved',
            'is_active' => true,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
        return back()->with('success', 'Vendor approved successfully.');
    }

    public function reject(Request $request, Vendor $vendor)
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $vendor->update([
            'status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $data['reason'],
        ]);
        return back()->with('success', 'Vendor rejected.');
    }

    /** The team has started checking this application; the seller sees that it is being reviewed. */
    public function underReview(Vendor $vendor)
    {
        abort_unless(in_array($vendor->status, ['pending', 'rejected'], true), 422);
        $vendor->update(['status' => 'under_review', 'rejection_reason' => null]);

        return back()->with('success', 'Marked as under review. The seller can see their application is being checked.');
    }

    public function suspend(Vendor $vendor)
    {
        $vendor->update([
            'status' => 'suspended',
            'is_active' => false,
        ]);
        return back()->with('success', 'Vendor suspended.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted.');
    }

    /** Open a seller's document in the browser. The file is on the private disk; this is the only way to it. */
    public function document(Vendor $vendor, VendorDocument $document)
    {
        abort_unless($document->vendor_id === $vendor->id && Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path), [
            'Content-Disposition' => 'inline; filename="' . addcslashes($document->original_name ?: basename($document->path), '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function verifyDocument(Request $request, Vendor $vendor, VendorDocument $document)
    {
        abort_unless($document->vendor_id === $vendor->id, 404);
        $document->update(['status' => 'verified', 'note' => null, 'verified_at' => now(), 'verified_by' => $request->user()->id]);
        // Checking has started, so the seller sees their application is being reviewed.
        if ($vendor->status === 'pending') {
            $vendor->update(['status' => 'under_review']);
        }

        return back()->with('success', $document->title() . ' marked as verified.');
    }

    public function rejectDocument(Request $request, Vendor $vendor, VendorDocument $document)
    {
        abort_unless($document->vendor_id === $vendor->id, 404);
        $data = $request->validate(['note' => 'required|string|max:500']);
        $document->update(['status' => 'rejected', 'note' => $data['note'], 'verified_at' => now(), 'verified_by' => $request->user()->id]);

        return back()->with('success', $document->title() . ' rejected. The seller is asked to upload it again.');
    }

    /** Set this seller's own commission %. Leave it blank to follow the default rate. */
    public function updateCommission(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'commission_rate.max' => __('Commission cannot be more than 100%.'),
            'commission_rate.min' => __('Commission cannot be negative.'),
        ]);

        $vendor->update(['commission_rate' => $data['commission_rate'] === null || $data['commission_rate'] === '' ? null : round((float) $data['commission_rate'], 2)]);

        return back()->with('success', __('Commission updated. It applies to new orders from now on.'));
    }
}
