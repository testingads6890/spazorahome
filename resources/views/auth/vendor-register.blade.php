@extends('layouts.auth')

@section('title', __('Apply to sell'))
@section('pitch_title', __('Sell to customers across India.'))
@section('pitch_text', __('List your furniture, get orders and receive payouts. Every order comes with GST-ready invoicing.'))
@section('pitch_points')
    <li><i class="bi bi-shop"></i> {{ __('Your own seller dashboard') }}</li>
    <li><i class="bi bi-cash-coin"></i> {{ __('Transparent commission and payouts') }}</li>
    <li><i class="bi bi-patch-check"></i> {{ __('Applications reviewed by our team') }}</li>
    <li><i class="bi bi-file-earmark-lock2"></i> {{ __('Your documents stay private') }}</li>
@endsection

@php
    $country = old('country', 'India');
    $india = \App\Support\Countries::isIndia($country);
    $gst = (string) old('gst_registered', '1') === '1';
    $accept = '.' . str_replace(',', ',.', \App\Models\VendorDocument::MIMES);

    // One text-like field. $opt: type, autocomplete, column class, hint, optional flag, extra attributes.
    $field = function (string $name, string $label, array $opt = []) use ($errors) {
        $opt += ['type' => 'text', 'auto' => 'off', 'col' => 'col-12', 'hint' => null, 'optional' => false, 'attrs' => '', 'value' => old($name)];
        return compact('name', 'label') + $opt;
    };
@endphp

@section('content')
<div class="vform">
    <h1 class="auth-title">{{ __('Apply to sell') }}</h1>
    <p class="text-muted mb-3">{{ __('Tell us about your business. We verify every application and its documents before a store goes live.') }}</p>

    <ol class="vform-steps" aria-label="{{ __('What we ask for') }}">
        <li>{{ __('Account') }}</li><li>{{ __('Business') }}</li><li>{{ __('Address') }}</li><li>{{ __('GST & documents') }}</li><li>{{ __('Bank') }}</li>
    </ol>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>{{ __('Please fix the highlighted fields.') }}</strong>
            <span class="d-block small mt-1">{{ __('For your security, files and passwords have to be chosen again.') }}</span>
        </div>
    @endif

    @php $signedIn = auth()->check(); @endphp
    <form action="{{ $signedIn ? route('customer.become-seller.store') : route('vendor.register.post') }}" method="POST" enctype="multipart/form-data" data-auth-form novalidate id="vendorForm">
        @csrf

        @if($signedIn)
        {{-- 1. Account: the customer's own sign-in is used --}}
        <fieldset class="vform-section">
            <legend><span>1</span> {{ __('Your account') }}</legend>
            <p class="mb-2">{{ __('You are applying with your account') }} <strong>{{ auth()->user()->email }}</strong>. {{ __('You keep shopping with it too, and sign in to the seller panel with the same password.') }}</p>
            <div class="row g-3">
                @include('auth._vendor-field', ['x' => $field('phone', __('Mobile number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"'])])
                @include('auth._vendor-field', ['x' => $field('whatsapp', __('WhatsApp number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"'])])
                @include('auth._vendor-field', ['x' => $field('other_phone', __('Other phone number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"'])])
            </div>
        </fieldset>
        @else
        {{-- 1. Account --}}
        <fieldset class="vform-section">
            <legend><span>1</span> {{ __('Your account') }}</legend>
            <div class="row g-3">
                @foreach([
                    $field('name', __('Your full name'), ['auto' => 'name', 'col' => 'col-sm-6']),
                    $field('phone', __('Mobile number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"']),
                    $field('whatsapp', __('WhatsApp number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"']),
                    $field('other_phone', __('Other phone number'), ['type' => 'tel', 'auto' => 'tel', 'col' => 'col-sm-6', 'attrs' => 'inputmode="tel"']),
                    $field('email', __('Business email'), ['type' => 'email', 'auto' => 'email', 'hint' => __('You sign in with this email.')]),
                ] as $x)
                    @include('auth._vendor-field', ['x' => $x])
                @endforeach
                <div class="col-sm-6">
                    <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                    <div class="input-group has-validation">
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
            </div>
            <div class="mt-3">
                @include('partials.firebase-phone', ['mode' => 'token', 'tokenName' => 'phone_token', 'prefill' => 'phone', 'title' => __('Verify your mobile number (recommended)')])
            </div>
        </fieldset>
        @endif

        {{-- 2. Business --}}
        <fieldset class="vform-section">
            <legend><span>2</span> {{ __('Your business') }}</legend>
            <div class="row g-3">
                <div class="col-12">
                    <span class="form-label fw-semibold d-block">{{ __('What you are') }} <span class="vform-req">{{ __('Required') }}</span></span>
                    <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="{{ __('What you are') }}">
                        @foreach(\App\Models\Vendor::SELLER_TYPES as $val => $lbl)
                            <input type="radio" class="btn-check" name="seller_type" id="seller_{{ $val }}" value="{{ $val }}" autocomplete="off" @checked(old('seller_type') === $val) required>
                            <label class="btn btn-outline-secondary" for="seller_{{ $val }}">{{ __($lbl) }}</label>
                        @endforeach
                    </div>
                    @error('seller_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                @include('auth._vendor-field', ['x' => $field('company_name', __('Company / business name'), ['auto' => 'organization', 'col' => 'col-sm-7'])])
                <div class="col-sm-5">
                    <label for="business_type" class="form-label fw-semibold">{{ __('Business type') }}</label>
                    <select id="business_type" name="business_type" class="form-select @error('business_type') is-invalid @enderror" required>
                        <option value="">{{ __('Select') }}</option>
                        @foreach(\App\Models\Vendor::BUSINESS_TYPES as $k => $l)<option value="{{ $k }}" {{ old('business_type') === $k ? 'selected' : '' }}>{{ __($l) }}</option>@endforeach
                    </select>
                    @error('business_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    @include('partials.category-picker', ['product' => null, 'pickerTypeField' => 'product_type_id', 'pickerCatName' => 'categories', 'pickerSubName' => 'subcategories'])
                </div>
                @include('auth._vendor-field', ['x' => $field('website', __('Website'), ['type' => 'url', 'auto' => 'url', 'optional' => true, 'hint' => __('If you have one. Example: www.yourbrand.com'), 'attrs' => 'inputmode="url" placeholder="https://"'])])
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">{{ __('About the business') }}</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" class="form-control @error('description') is-invalid @enderror" required aria-describedby="descHint">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="descHint">{{ __('What you make or sell, how long you have been in business, and where your workshop or warehouse is.') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- 3. Address --}}
        <fieldset class="vform-section">
            <legend><span>3</span> {{ __('Business address') }}</legend>
            <div class="row g-3">
                @include('auth._vendor-field', ['x' => $field('address', __('Address'), ['auto' => 'street-address', 'hint' => __('Building, street and area.')])])
                <div class="col-sm-6">
                    <label for="country" class="form-label fw-semibold">{{ __('Country') }}</label>
                    <select id="country" name="country" class="form-select @error('country') is-invalid @enderror" autocomplete="country-name" required>
                        @foreach(\App\Support\Countries::all() as $n)<option value="{{ $n }}" {{ $country === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label for="state" class="form-label fw-semibold">{{ __('State') }}</label>
                    {{-- India picks from the GST state list; other countries type the state or province. Only the visible one is sent. --}}
                    <select id="state" name="state" class="form-select @error('state') is-invalid @enderror" autocomplete="address-level1" data-for-india @unless($india) hidden disabled @endunless required>
                        <option value="">{{ __('Select state') }}</option>
                        @foreach(\App\Support\GstState::names() as $s)<option value="{{ $s }}" {{ old('state') === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
                    </select>
                    <input type="text" id="stateText" name="state" value="{{ $india ? '' : old('state') }}" class="form-control @error('state') is-invalid @enderror" autocomplete="address-level1" aria-label="{{ __('State or province') }}" data-for-abroad @if($india) hidden disabled @endif required>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @include('auth._vendor-field', ['x' => $field('city', __('City'), ['auto' => 'address-level2', 'col' => 'col-sm-6'])])
                @include('auth._vendor-field', ['x' => $field('postal_code', __('PIN code / ZIP code'), ['auto' => 'postal-code', 'col' => 'col-sm-6', 'attrs' => 'maxlength="12"'])])
            </div>
        </fieldset>

        {{-- 4. Registration, GST and documents --}}
        <fieldset class="vform-section">
            <legend><span>4</span> {{ __('Registration, GST and documents') }}</legend>
            <p class="vform-note"><i class="bi bi-shield-lock"></i> {{ __('PDF, JPG, PNG or WEBP, up to 5 MB each. Only our verification team can open these files.') }}</p>
            <div class="row g-3">
                @include('auth._vendor-field', ['x' => $field('registration_number', __('Company registration number'), ['col' => 'col-sm-6', 'hint' => __('CIN, LLPIN, Udyam, Shop Act or partnership registration number.'), 'attrs' => 'maxlength="100"'])])
                <div class="col-sm-6">
                    <label for="doc_registration" class="form-label fw-semibold">{{ __('Registration certificate') }} <span class="vform-req">{{ __('Required') }}</span></label>
                    <input type="file" id="doc_registration" name="doc_registration" accept="{{ $accept }}" class="form-control @error('doc_registration') is-invalid @enderror" required>
                    @error('doc_registration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <span class="form-label fw-semibold d-block" id="gstLabel">{{ __('Is the business registered for GST?') }}</span>
                    <div class="vform-toggle" role="radiogroup" aria-labelledby="gstLabel">
                        <input type="radio" class="btn-check" name="gst_registered" id="gstYes" value="1" {{ $gst ? 'checked' : '' }} autocomplete="off">
                        <label for="gstYes">{{ __('Yes, we have a GSTIN') }}</label>
                        <input type="radio" class="btn-check" name="gst_registered" id="gstNo" value="0" {{ $gst ? '' : 'checked' }} autocomplete="off">
                        <label for="gstNo">{{ __('No, not registered') }}</label>
                    </div>
                    @error('gst_registered')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6" data-gst @unless($gst) hidden @endunless>
                    <label for="gstin" class="form-label fw-semibold">{{ __('GSTIN') }} <span class="vform-req">{{ __('Required') }}</span></label>
                    <input type="text" id="gstin" name="gstin" value="{{ old('gstin') }}" maxlength="15" class="form-control text-uppercase @error('gstin') is-invalid @enderror" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="22AAAAA0000A1Z5" @unless($gst) disabled @endunless>
                    @error('gstin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ __('15 characters, as printed on your GST certificate.') }}</div>
                </div>
                <div class="col-sm-6" data-gst @unless($gst) hidden @endunless>
                    <label for="doc_gst" class="form-label fw-semibold">{{ __('GST certificate') }} <span class="vform-req">{{ __('Required') }}</span></label>
                    <input type="file" id="doc_gst" name="doc_gst" accept="{{ $accept }}" class="form-control @error('doc_gst') is-invalid @enderror" @unless($gst) disabled @endunless>
                    @error('doc_gst')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12" data-no-gst @if($gst) hidden @endif>
                    <p class="vform-note mb-0"><i class="bi bi-info-circle"></i> {{ __('No problem. GST details are optional for unregistered businesses; you can add a GSTIN later from your seller profile.') }}</p>
                </div>

                <div class="col-sm-6">
                    <label for="doc_aadhaar" class="form-label fw-semibold">{{ __('Aadhaar card') }} <span class="vform-req">{{ __('Required') }}</span></label>
                    <input type="file" id="doc_aadhaar" name="doc_aadhaar" accept="{{ $accept }}" class="form-control @error('doc_aadhaar') is-invalid @enderror" required>
                    @error('doc_aadhaar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ __('Of the owner or authorised signatory. A clear copy is enough; you may mask the number.') }}</div>
                </div>
                <div class="col-sm-5">
                    <label for="doc_other_type" class="form-label fw-semibold">{{ __('One more document') }} <span class="vform-opt">{{ __('Optional') }}</span></label>
                    <select id="doc_other_type" name="doc_other_type" class="form-select @error('doc_other_type') is-invalid @enderror">
                        <option value="">{{ __('Choose the type') }}</option>
                        @foreach(\App\Models\VendorDocument::ADDITIONAL_KINDS as $k => $l)<option value="{{ $k }}" {{ old('doc_other_type') === $k ? 'selected' : '' }}>{{ __($l) }}</option>@endforeach
                    </select>
                    @error('doc_other_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-7">
                    <label for="doc_other" class="form-label fw-semibold">{{ __('File') }} <span class="vform-opt">{{ __('Optional') }}</span></label>
                    <input type="file" id="doc_other" name="doc_other" accept="{{ $accept }}" class="form-control @error('doc_other') is-invalid @enderror">
                    @error('doc_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ __('A PAN card, address proof or cancelled cheque speeds up verification.') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- 5. Bank --}}
        <fieldset class="vform-section">
            <legend><span>5</span> {{ __('Bank details for payouts') }}</legend>
            <div class="row g-3">
                @include('auth._vendor-field', ['x' => $field('bank_holder', __('Account holder name'), ['col' => 'col-sm-6', 'hint' => __('Exactly as it appears in the bank.')])])
                @include('auth._vendor-field', ['x' => $field('bank_name', __('Bank name'), ['col' => 'col-sm-6'])])
                @include('auth._vendor-field', ['x' => $field('bank_account', __('Account number'), ['col' => 'col-sm-6', 'attrs' => 'inputmode="numeric" maxlength="34" spellcheck="false"', 'value' => ''])])
                @include('auth._vendor-field', ['x' => $field('bank_account_confirmation', __('Confirm account number'), ['col' => 'col-sm-6', 'attrs' => 'inputmode="numeric" maxlength="34" spellcheck="false"', 'value' => ''])])
                <div class="col-sm-6">
                    <label for="bank_ifsc" class="form-label fw-semibold"><span data-for-india-label @unless($india) hidden @endunless>{{ __('IFSC') }}</span><span data-for-abroad-label @if($india) hidden @endif>{{ __('SWIFT / bank code') }}</span></label>
                    <input type="text" id="bank_ifsc" name="bank_ifsc" value="{{ old('bank_ifsc') }}" maxlength="20" class="form-control text-uppercase @error('bank_ifsc') is-invalid @enderror" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
                    @error('bank_ifsc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @include('auth._vendor-field', ['x' => $field('upi', __('UPI ID'), ['col' => 'col-sm-6', 'optional' => true, 'attrs' => 'placeholder="name@bank" spellcheck="false"'])])
            </div>
        </fieldset>

        <div class="form-check mt-4">
            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
            <label class="form-check-label" for="terms">{{ __('I confirm these details and documents are genuine, and I agree to the') }} <a href="{{ route('terms') }}" target="_blank" rel="noopener">{{ __('seller terms') }}</a> {{ __('and') }} <a href="{{ route('privacy') }}" target="_blank" rel="noopener">{{ __('privacy policy') }}</a>.</label>
            @error('terms')<div class="invalid-feedback d-block">{{ __('Please accept the terms to continue.') }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">{{ __('Submit application') }}</button>
        <p class="small text-muted text-center mt-3 mb-0">{{ __('We usually review applications within a few working days and email you the result.') }}</p>
    </form>

    <p class="text-center mt-4 mb-0">{{ __('Already a seller?') }} <a href="{{ route('vendor.login') }}" class="fw-semibold">{{ __('Sign in') }}</a></p>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('vendorForm'); if (!form) return;
        var maxBytes = {{ \App\Models\VendorDocument::MAX_KB }} * 1024;

        // GST yes / no: show the GSTIN and certificate only when registered, and only send them then.
        function syncGst() {
            var yes = document.getElementById('gstYes').checked;
            form.querySelectorAll('[data-gst]').forEach(function (el) { el.hidden = !yes; el.querySelectorAll('input').forEach(function (i) { i.disabled = !yes; i.required = yes; }); });
            form.querySelectorAll('[data-no-gst]').forEach(function (el) { el.hidden = yes; });
        }
        form.querySelectorAll('[name=gst_registered]').forEach(function (r) { r.addEventListener('change', syncGst); });
        syncGst();

        // Country: Indian states come from a list, other countries type theirs; the bank code label follows.
        var country = document.getElementById('country');
        function syncCountry() {
            var india = country.value === 'India';
            var list = form.querySelector('[data-for-india]'), text = form.querySelector('[data-for-abroad]');
            list.hidden = !india; list.disabled = !india; text.hidden = india; text.disabled = india;
            form.querySelector('[data-for-india-label]').hidden = !india; form.querySelector('[data-for-abroad-label]').hidden = india;
            document.getElementById('postal_code').setAttribute('inputmode', india ? 'numeric' : 'text');
        }
        country.addEventListener('change', syncCountry); syncCountry();

        // Files: say so at once when a file is too large, instead of after the whole form is sent.
        form.querySelectorAll('input[type=file]').forEach(function (input) {
            input.addEventListener('change', function () {
                var f = input.files[0], big = f && f.size > maxBytes;
                input.setCustomValidity(big ? @json(__('This file is larger than 5 MB.')) : '');
                input.classList.toggle('is-invalid', !!big);
                var note = input.parentNode.querySelector('.vform-file-note') || input.parentNode.appendChild(Object.assign(document.createElement('div'), { className: 'vform-file-note form-text' }));
                note.textContent = f ? (big ? @json(__('This file is larger than 5 MB. Please choose a smaller one.')) : f.name + ' · ' + (f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB')) : '';
                note.classList.toggle('text-danger', !!big);
            });
        });
        // The extra document needs its type, and the type needs a file.
        var otherFile = document.getElementById('doc_other'), otherType = document.getElementById('doc_other_type');
        function syncOther() { otherType.required = otherFile.files.length > 0; }
        otherFile.addEventListener('change', syncOther);

        // Check in the browser first and take the seller to the first problem.
        form.addEventListener('submit', function (e) {
            var boxes = form.querySelectorAll('[name="categories[]"]'), any = Array.prototype.some.call(boxes, function (b) { return b.checked; });
            if (boxes.length) boxes[0].setCustomValidity(any ? '' : @json(__('Choose at least one product category.')));
            var typeBox = form.querySelector('[name="product_type_id"]');
            if (typeBox) { typeBox.setCustomValidity(typeBox.value ? '' : @json(__('Choose a product type.'))); }
            var a = document.getElementById('bank_account'), b = document.getElementById('bank_account_confirmation');
            b.setCustomValidity(a.value.replace(/\s+/g, '') === b.value.replace(/\s+/g, '') ? '' : @json(__('The two account numbers do not match.')));
            if (!form.checkValidity()) {
                e.preventDefault(); e.stopImmediatePropagation();
                form.classList.add('was-validated');
                var bad = form.querySelector(':invalid:not(fieldset)');
                if (bad) { (bad.closest('.vform-chips') || bad).scrollIntoView({ behavior: 'smooth', block: 'center' }); if (bad.type !== 'checkbox') bad.focus({ preventScroll: true }); }
            }
        }, true);
    })();
</script>
@endpush
