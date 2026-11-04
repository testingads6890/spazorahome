<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Support\Countries;
use App\Support\FirebaseIdToken;
use App\Support\IndianPhone;
use App\Support\InvalidFirebaseToken;
use App\Support\GstState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register', ['action' => route('register.post'), 'phoneRequired' => false]);
    }

    public function showCustomerRegistrationForm()
    {
        return view('auth.register', ['action' => route('customer.register.post'), 'phoneRequired' => true]);
    }

    public function showVendorRegistrationForm()
    {
        return view('auth.vendor-register', [
            'categories' => collect(),
        ]);
    }

    public function register(Request $request)
    {
        return $this->registerCustomer($request, false, '/dashboard');
    }

    public function customerRegister(Request $request)
    {
        return $this->registerCustomer($request, true, '/customer/dashboard');
    }

    /**
     * Seller application. Two things are always required besides the business details: the company registration
     * number with its certificate, and (for GST-registered businesses) the GSTIN with the GST certificate.
     * A third document is optional. Nothing goes live until an admin has verified the documents and approved.
     */
    public function vendorRegister(Request $request)
    {
        // A signed-in customer can apply with their own account ("Become a seller"): no new sign-in is created.
        $existing = $request->user();
        if ($existing) {
            abort_if($existing->isVendorOwner() || $existing->vendorMembership()->exists() || $existing->hasRole('admin'), 403, 'This account already sells or works for a shop.');
            $request->merge(['name' => $existing->name, 'email' => $existing->email]);
        }

        // Tidy what people commonly type before it is checked.
        $website = trim((string) $request->input('website'));
        $request->merge([
            'website' => $website === '' ? null : (preg_match('~^https?://~i', $website) ? $website : 'https://' . $website),
            'gstin' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('gstin'))) ?: null,
            'bank_ifsc' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('bank_ifsc'))) ?: null,
            'bank_account' => preg_replace('/\s+/', '', (string) $request->input('bank_account')) ?: null,
            'bank_account_confirmation' => preg_replace('/\s+/', '', (string) $request->input('bank_account_confirmation')) ?: null,
        ]);

        $india = Countries::isIndia($request->input('country'));
        $gst = $request->boolean('gst_registered');
        $file = ['file', 'mimes:' . VendorDocument::MIMES, 'max:' . VendorDocument::MAX_KB];

        $data = $request->validate([
            // Account
            'name' => $existing ? ['nullable'] : ['required', 'string', 'max:255'],
            'email' => $existing ? ['nullable'] : ['required', 'string', 'email', 'max:255', 'unique:users', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'other_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'password' => $existing ? ['nullable'] : ['required', 'confirmed', Rules\Password::defaults()],
            // Business
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(array_keys(Vendor::BUSINESS_TYPES))],
            'seller_type' => ['required', Rule::in(array_keys(Vendor::SELLER_TYPES))],
            // Optional: a mobile number verified with Firebase OTP. It is attached to the account only if the token checks out.
            'phone_token' => ['nullable', 'string', 'max:4096'],
            'website' => ['nullable', 'url', 'max:255'],
            'product_type_id' => ['required', 'integer', Rule::exists('product_types', 'id')],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'subcategories' => ['nullable', 'array'],
            'subcategories.*' => ['integer', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'min:30', 'max:2000'],
            // Address
            'address' => ['required', 'string', 'max:500'],
            'country' => ['required', Rule::in(Countries::all())],
            'state' => $india ? ['required', Rule::in(GstState::names())] : ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => $india ? ['required', 'digits:6'] : ['required', 'string', 'max:12'],
            // Registration and GST
            'registration_number' => ['required', 'string', 'max:100'],
            'gst_registered' => ['required', 'boolean'],
            'gstin' => $gst
                ? ['required', 'string', Rule::unique('vendors', 'tax_number'), fn ($attribute, $value, $fail) => GstState::isValidGstin($value) || $fail(__('Enter a valid 15-character GSTIN.'))]
                : ['nullable'],
            // Documents
            'doc_registration' => array_merge(['required'], $file),
            'doc_gst' => array_merge([$gst ? 'required' : 'nullable'], $file),
            // Mandatory for every seller: the Aadhaar card of the owner or authorised signatory.
            'doc_aadhaar' => array_merge(['required'], $file),
            'doc_other' => array_merge(['nullable'], $file),
            'doc_other_type' => ['nullable', 'required_with:doc_other', Rule::in(array_keys(VendorDocument::ADDITIONAL_KINDS))],
            // Bank
            'bank_holder' => ['required', 'string', 'max:150'],
            'bank_name' => ['required', 'string', 'max:150'],
            'bank_account' => ['required', 'string', 'regex:/^[0-9A-Za-z]{6,34}$/', 'confirmed'],
            'bank_ifsc' => $india ? ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'] : ['required', 'string', 'max:20'],
            'upi' => ['nullable', 'string', 'max:100', 'regex:/^[\w.\-]{2,}@[A-Za-z]{2,}$/'],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => __('Enter a valid mobile number.'),
            'categories.required' => __('Choose at least one product category.'),
            'description.min' => __('Tell us a little more about the business (at least :min characters).'),
            'postal_code.digits' => __('Enter a 6-digit PIN code.'),
            'doc_registration.required' => __('Upload your company registration certificate.'),
            'doc_aadhaar.required' => __('Upload the Aadhaar card of the owner or authorised signatory.'),
            'seller_type.required' => __('Choose what you are: architect, interior designer or manufacturer.'),
            'doc_gst.required' => __('Upload your GST certificate.'),
            'gstin.required' => __('Enter your GSTIN.'),
            'doc_other_type.required_with' => __('Choose what kind of document this is.'),
            'bank_account.regex' => __('Enter a valid account number.'),
            'bank_account.confirmed' => __('The two account numbers do not match.'),
            'bank_ifsc.regex' => __('Enter a valid 11-character IFSC.'),
            'upi.regex' => __('Enter a valid UPI ID, like name@bank.'),
            '*.mimes' => __('Upload a PDF, JPG, PNG or WEBP file.'),
            '*.max' => __('Each file can be up to 5 MB.'),
        ]);

        // A verified mobile is optional here, but if sent it must be the very number on the application.
        $verifiedPhone = null;
        if (filled($data['phone_token'] ?? null)) {
            try {
                $verifiedPhone = app(FirebaseIdToken::class)->verify($data['phone_token'])['phone'];
            } catch (InvalidFirebaseToken $e) {
                throw ValidationException::withMessages(['phone_token' => $e->getMessage()]);
            }
            if ($verifiedPhone !== IndianPhone::normalize($data['phone'])) {
                throw ValidationException::withMessages(['phone_token' => __('The verified mobile number must be the one on this application.')]);
            }
            if (! $existing && User::where('phone', $verifiedPhone)->exists()) {
                throw ValidationException::withMessages(['phone_token' => __('This mobile number is already linked to another account.')]);
            }
        }

        $stored = [];
        try {
            DB::transaction(function () use ($data, $request, $gst, $existing, $verifiedPhone, &$stored) {
                $user = $existing ?? User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'account_type' => 'customer', // the vendor role (not this column) marks a seller
                    'company_name' => $data['company_name'],
                ]);
                $user->assignRole(Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']));
                if ($verifiedPhone !== null) {
                    $user->update(['phone' => $verifiedPhone, 'phone_verified_at' => now()]);
                }

                // Not active until an admin has verified the documents and approved the application.
                $vendor = Vendor::create([
                    'user_id' => $user->id,
                    'business_name' => $data['company_name'],
                    'contact_name' => $data['name'],
                    'business_type' => $data['business_type'],
                    'seller_type' => $data['seller_type'],
                    'website' => $data['website'] ?? null,
                    'description' => $data['description'],
                    'product_categories' => array_values(array_unique(array_map('intval', array_merge($data['categories'], $data['subcategories'] ?? [])))),
                    'slug' => $this->uniqueVendorSlug($data['company_name']),
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'other_phone' => $data['other_phone'] ?? null,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => $data['country'],
                    'postal_code' => $data['postal_code'],
                    'registration_number' => $data['registration_number'],
                    'gst_registered' => $gst,
                    'tax_number' => $gst ? $data['gstin'] : null,
                    'bank_details' => array_filter([
                        'account_holder' => $data['bank_holder'],
                        'bank_name' => $data['bank_name'],
                        'account_number' => $data['bank_account'],
                        'ifsc' => $data['bank_ifsc'],
                        'upi' => $data['upi'] ?? null,
                    ]),
                    'terms_accepted_at' => now(),
                    'status' => 'pending',
                    'is_active' => false,
                ]);

                $uploads = [
                    ['doc_registration', VendorDocument::COMPANY_REGISTRATION, null],
                    ['doc_aadhaar', VendorDocument::AADHAAR, null],
                    ['doc_gst', VendorDocument::GST_CERTIFICATE, null],
                    ['doc_other', VendorDocument::ADDITIONAL, VendorDocument::ADDITIONAL_KINDS[$data['doc_other_type'] ?? ''] ?? null],
                ];
                foreach ($uploads as [$field, $type, $label]) {
                    $upload = $request->file($field);
                    if (! $upload || ($type === VendorDocument::GST_CERTIFICATE && ! $gst)) {
                        continue;
                    }
                    // Private disk: these files are only ever served through a signed-in admin or the seller.
                    $path = $upload->store('vendor-documents/' . $vendor->id, 'local');
                    $stored[] = $path;
                    $vendor->documents()->create([
                        'type' => $type,
                        'label' => $label,
                        'path' => $path,
                        'original_name' => Str::limit($upload->getClientOriginalName(), 200, ''),
                        'mime' => $upload->getMimeType(),
                        'size' => $upload->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Nothing was saved, so do not leave the uploaded files behind either.
            Storage::disk('local')->delete($stored);
            throw $e;
        }

        if ($existing) {
            return redirect()->route('vendor.application')->with('status', __('Thanks! Your seller application and documents are with our team. You get your seller dashboard once it is approved.'));
        }

        return redirect()->route('vendor.login')->with('status', __('Thanks! Your seller application and documents are with our team. You can sign in to see its status, and you get your dashboard once it is approved.'));
    }

    private function registerCustomer(Request $request, bool $phoneRequired, string $redirect)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => [$phoneRequired ? 'required' : 'nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'other_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'account_type' => ['nullable', Rule::in(['homeowner', 'interior_designer', 'architect'])],
            'company_name' => ['nullable', 'required_if:account_type,interior_designer,architect', 'string', 'max:255'],
        ], [
            'company_name.required_if' => __('Enter your studio or company name.'),
        ]);
        $professional = in_array($data['account_type'] ?? 'homeowner', ['interior_designer', 'architect'], true);

        $user = DB::transaction(function () use ($data, $professional) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'account_type' => 'customer',
            ]);
            // Without this role the new account is locked out of every customer page.
            $user->assignRole(Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));
            if (! empty($data['phone']) || ! empty($data['whatsapp']) || ! empty($data['other_phone'])) {
                $user->profile()->create([
                    'phone' => $data['phone'] ?? null,
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'other_phone' => $data['other_phone'] ?? null,
                ]);
            }
            // Designers and architects get a professional profile that the team verifies before trade benefits apply.
            if ($professional) {
                \App\Models\ProfessionalProfile::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'designation' => $data['account_type'] === 'architect' ? 'Architect' : 'Interior designer',
                    'is_verified' => false,
                ]);
            }

            return $user;
        });

        // Welcome email with the verification link. A mail problem must never stop the sign-up itself.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($professional) {
            return redirect()->route('customer.professional-profile.edit')->with('success', __('Welcome! Add a few details about your practice so we can verify your professional account.'));
        }

        return redirect()->intended($redirect);
    }

    private function uniqueVendorSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'vendor';
        $slug = $base;
        for ($i = 2; Vendor::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
