<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** The seller's side of verification: see the application's status, open their own files, replace a rejected one. */
class DocumentController extends Controller
{
    public function status(Request $request)
    {
        $vendor = $request->user()->vendor;
        if ($vendor && $vendor->is_active) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.pending', ['vendor' => $vendor]);
    }

    public function show(Request $request, VendorDocument $document)
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor && $document->vendor_id === $vendor->id && Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path), [
            'Content-Disposition' => 'inline; filename="' . addcslashes($document->original_name ?: basename($document->path), '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Upload a missing document or replace one that is not verified. A verified document cannot be swapped. */
    public function store(Request $request)
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor, 403);

        $data = $request->validate([
            'type' => ['required', Rule::in([VendorDocument::COMPANY_REGISTRATION, VendorDocument::GST_CERTIFICATE, VendorDocument::AADHAAR, VendorDocument::ADDITIONAL])],
            'kind' => ['nullable', 'required_if:type,' . VendorDocument::ADDITIONAL, Rule::in(array_keys(VendorDocument::ADDITIONAL_KINDS))],
            'file' => ['required', 'file', 'mimes:' . VendorDocument::MIMES, 'max:' . VendorDocument::MAX_KB],
        ], [
            'file.mimes' => __('Upload a PDF, JPG, PNG or WEBP file.'),
            'file.max' => __('Each file can be up to 5 MB.'),
        ]);

        $existing = $vendor->documents()->where('type', $data['type'])->latest('id')->first();
        if ($existing && $existing->status === 'verified') {
            return back()->withErrors(['file' => __('This document is already verified.')]);
        }

        $upload = $request->file('file');
        $path = $upload->store('vendor-documents/' . $vendor->id, 'local');
        $fields = [
            'label' => $data['type'] === VendorDocument::ADDITIONAL ? VendorDocument::ADDITIONAL_KINDS[$data['kind']] : null,
            'path' => $path,
            'original_name' => \Illuminate\Support\Str::limit($upload->getClientOriginalName(), 200, ''),
            'mime' => $upload->getMimeType(),
            'size' => $upload->getSize(),
            'status' => 'pending',
            'note' => null,
            'verified_at' => null,
            'verified_by' => null,
        ];

        if ($existing) {
            $old = $existing->path;
            $existing->update($fields);
            Storage::disk('local')->delete($old);
        } else {
            $vendor->documents()->create(['type' => $data['type']] + $fields);
        }

        // A rejected application goes back into the review queue once the seller has sent a new file.
        if ($vendor->status === 'rejected') {
            $vendor->update(['status' => 'pending']);
        }

        return back()->with('status', __('Thanks. Your document has been sent for verification.'));
    }
}
