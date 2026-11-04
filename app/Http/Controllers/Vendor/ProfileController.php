<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\Countries;
use App\Support\GstState;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The shop owner's business profile: contact details, address, GST and the bank account for payouts. */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($vendor, 403);
        $categories = Category::whereNull('parent_id')->where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('vendor.profile.edit', compact('vendor', 'categories') + ['types' => Vendor::BUSINESS_TYPES]);
    }

    public function update(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($vendor && $request->user()->vendorCan('owner'), 403, 'Only the shop owner can change the business profile.');

        // Older forms (and API clients) send no country or GST switch: keep the shop's country, and treat a GSTIN as "registered".
        if (! $request->filled('country')) {
            $request->merge(['country' => $vendor->country ?: 'India']);
        }
        $india = Countries::isIndia($request->input('country'));
        $gst = $request->has('gst_registered') ? $request->boolean('gst_registered') : $request->filled('tax_number');
        $bank = (array) ($vendor->bank_details ?? []);

        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:150',
            'business_type' => ['nullable', Rule::in(array_keys(Vendor::BUSINESS_TYPES))],
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:20000',
            'product_categories' => 'nullable|array',
            'product_categories.*' => 'integer|exists:categories,id',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => ['required', Rule::in(array_merge(Countries::all(), ['India']))],
            'state' => $india ? ['required', Rule::in(GstState::names())] : ['required', 'string', 'max:100'],
            'postal_code' => $india ? ['nullable', 'digits:6'] : ['nullable', 'string', 'max:12'],
            'registration_number' => 'nullable|string|max:100',
            'gst_registered' => 'nullable|boolean',
            'tax_number' => [$gst ? 'required' : 'nullable', 'string', 'max:15'],
            'bank_holder' => 'nullable|string|max:150',
            'bank_name' => 'nullable|string|max:150',
            'bank_account' => ['nullable', 'string', 'regex:/^[0-9A-Za-z]{6,34}$/', 'confirmed'],
            'bank_ifsc' => $india ? ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'] : ['nullable', 'string', 'max:20'],
            'upi' => ['nullable', 'string', 'max:100', 'regex:/^[\w.\-]{2,}@[A-Za-z]{2,}$/'],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
            'remove_cover' => 'nullable|boolean',
            'social' => 'nullable|array',
            'social.*' => 'nullable|url|max:255',
        ], [
            'logo.max' => 'The logo can be up to 2 MB.',
            'cover.max' => 'The cover banner can be up to 4 MB.',
            'social.*.url' => 'Enter full links starting with https://',
            'phone.regex' => 'Enter a valid phone number.',
            'postal_code.digits' => 'Enter a 6-digit PIN code.',
            'tax_number.required' => 'Enter your GSTIN, or choose "Not registered for GST".',
            'bank_account.regex' => 'Enter a valid account number.',
            'bank_account.confirmed' => 'The two account numbers do not match.',
            'bank_ifsc.regex' => 'Enter a valid 11-character IFSC (e.g. HDFC0001234).',
            'upi.regex' => 'Enter a valid UPI ID, like name@bank.',
        ]);

        // A GSTIN must be well-formed and start with the state code of the registered state.
        $gstin = $gst ? strtoupper(trim((string) $data['tax_number'])) : null;
        if ($gstin && $india) {
            $expected = GstState::code($data['state']);
            if (! GstState::isValidGstin($gstin) || GstState::codeFromGstin($gstin) !== $expected) {
                return back()->withInput()->withErrors(['tax_number' => "Enter a valid 15-character GSTIN starting with {$expected} (the code for {$data['state']})."]);
            }
        }

        // The account number is only replaced when a new one is typed; the rest of the bank details always save.
        $bank = array_filter([
            'account_holder' => $data['bank_holder'] ?? ($bank['account_holder'] ?? null),
            'bank_name' => $data['bank_name'] ?? ($bank['bank_name'] ?? null),
            'account_number' => filled($data['bank_account'] ?? null) ? $data['bank_account'] : ($bank['account_number'] ?? null),
            'ifsc' => isset($data['bank_ifsc']) ? strtoupper($data['bank_ifsc']) : ($bank['ifsc'] ?? null),
            'upi' => $data['upi'] ?? null,
        ]);

        $values = [
            'business_name' => $data['business_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'description' => $data['description'] ?? null,
            'product_categories' => array_values(array_map('intval', $data['product_categories'] ?? [])) ?: null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'gst_registered' => $gst,
            'tax_number' => $gstin,
            'bank_details' => $bank ?: null,
            'social_links' => array_filter(array_intersect_key((array) ($data['social'] ?? []), Vendor::SOCIAL)) ?: null,
        ];
        // Store logo and cover banner: a new upload replaces the old file; "remove" clears it.
        foreach (['logo' => 'vendors/logos', 'cover' => 'vendors/covers'] as $field => $dir) {
            if ($request->hasFile($field) || $request->boolean('remove_' . $field)) {
                if ($vendor->{$field}) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($vendor->{$field});
                }
                $values[$field] = $request->hasFile($field) ? $request->file($field)->store($dir, 'public') : null;
            }
        }
        // Fields the form did not send keep their saved value; the shop's email is never blanked.
        foreach (['contact_name', 'business_type', 'email', 'phone', 'website', 'description', 'product_categories', 'address', 'city', 'postal_code', 'registration_number'] as $field) {
            if (! $request->has($field)) {
                unset($values[$field]);
            }
        }
        if (blank($values['email'] ?? 'kept')) {
            unset($values['email']);
        }
        if (! $request->hasAny(['bank_holder', 'bank_name', 'bank_account', 'bank_ifsc', 'upi'])) {
            unset($values['bank_details']);
        }
        if (! $request->has('social')) {
            unset($values['social_links']);
        }
        // Only columns this database has (older installs may not have the newer seller fields yet).
        $vendor->update(array_intersect_key($values, array_flip(\Illuminate\Support\Facades\Schema::getColumnListing($vendor->getTable()))));

        return back()->with('success', 'Business profile saved.');
    }
}
