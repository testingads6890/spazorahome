<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkEnquiry;
use Illuminate\Http\Request;

class BulkEnquiryController extends Controller
{
    public function index()
    {
        $enquiries = BulkEnquiry::latest('id')->paginate(25);

        return view('admin.bulk-enquiries.index', compact('enquiries'));
    }

    public function show(BulkEnquiry $enquiry)
    {
        return view('admin.bulk-enquiries.show', compact('enquiry'));
    }

    public function update(Request $request, BulkEnquiry $enquiry)
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(BulkEnquiry::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $enquiry->update($data);

        return redirect()->route('admin.bulk-enquiries.show', $enquiry)->with('success', 'Saved.');
    }
}
