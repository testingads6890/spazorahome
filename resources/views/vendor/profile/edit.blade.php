@extends('layouts.vendor')

@section('title', 'Business profile')

@php
    $v = fn (string $k, $d = null) => old($k, $vendor->{$k} ?? $d);
    $bank = (array) ($vendor->bank_details ?? []);
    $owner = auth()->user()->vendorCan('owner');
    $country = old('country', $vendor->country ?: 'India');
    $isIndia = \App\Support\Countries::isIndia($country);
    $gst = (bool) old('gst_registered', $vendor->gst_registered ?? filled($vendor->tax_number));
    $picked = array_map('intval', (array) old('product_categories', $vendor->product_categories ?? []));
    $acct = $bank['account_number'] ?? null;
    $statusBadge = ['approved' => 'success', 'pending' => 'warning', 'under_review' => 'info', 'rejected' => 'danger', 'suspended' => 'secondary'][$vendor->status] ?? 'secondary';
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="badge bg-{{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', (string) $vendor->status)) }}</span>
        @if($vendor->approved_at)<span class="small text-muted ms-2">Approved {{ $vendor->approved_at->format('d M Y') }}</span>@endif
        @if($vendor->commission_rate !== null)<span class="small text-muted ms-2">Commission {{ rtrim(rtrim(number_format((float) $vendor->commission_rate, 2), '0'), '.') }}%</span>@endif
    </div>
    @if(\Illuminate\Support\Facades\Route::has('stores.show') && $vendor->status === 'approved')
        <a href="{{ route('stores.show', $vendor) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-shop"></i> View my store</a>
    @endif
</div>

@unless($owner)<div class="alert alert-info">Only the shop owner can change these details.</div>@endunless
@if($errors->any())<div class="alert alert-danger">Please fix the highlighted fields.</div>@endif

<form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    <fieldset @disabled(! $owner)>

    <div class="card mb-3">
        <div class="card-header fw-semibold">1. Business</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="business_name">Business / store name <span class="text-danger">*</span></label>
                <input id="business_name" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ $v('business_name') }}" required>
                @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="business_type">Business type</label>
                <select id="business_type" name="business_type" class="form-select @error('business_type') is-invalid @enderror">
                    <option value="">Select</option>
                    @foreach($types as $k => $label)<option value="{{ $k }}" @selected($v('business_type') === $k)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="contact_name">Contact person</label>
                <input id="contact_name" name="contact_name" class="form-control" value="{{ $v('contact_name', auth()->user()->name) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="email">Business email</label>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ $v('email', auth()->user()->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Mobile / phone</label>
                <input id="phone" type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ $v('phone') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="website">Website</label>
                <input id="website" type="url" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ $v('website') }}" placeholder="https://www.yourbrand.com">
                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Product categories you sell</label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($categories as $cat)
                        <input type="checkbox" class="btn-check" name="product_categories[]" id="pc{{ $cat->id }}" value="{{ $cat->id }}" @checked(in_array($cat->id, $picked, true))>
                        <label class="btn btn-outline-secondary btn-sm" for="pc{{ $cat->id }}">{{ $cat->name }}</label>
                    @endforeach
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">About the business</label>
                <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror js-richtext">{{ $v('description') }}</textarea>
                <div class="form-text">Shown on your store page.</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">Store look: logo, cover and social links</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label" for="logo">Store logo</label>
                @if($vendor->logoUrl())
                    <div class="mb-2"><img src="{{ $vendor->logoUrl() }}" alt="Current logo" style="width:84px;height:84px;object-fit:cover;border-radius:12px;border:1px solid #dee2e6"></div>
                    <div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo"><label class="form-check-label" for="remove_logo">Remove logo</label></div>
                @endif
                <input type="file" id="logo" name="logo" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Square, at least 300 × 300 px. Up to 2 MB.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="cover">Cover banner</label>
                @if($vendor->coverUrl())
                    <div class="mb-2"><img src="{{ $vendor->coverUrl() }}" alt="Current cover" style="width:100%;max-height:120px;object-fit:cover;border-radius:12px;border:1px solid #dee2e6"></div>
                    <div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="remove_cover"><label class="form-check-label" for="remove_cover">Remove cover</label></div>
                @endif
                <input type="file" id="cover" name="cover" class="form-control @error('cover') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Wide picture for the top of your store page, about 1600 × 400 px. Up to 4 MB.</div>
            </div>
            @php $socials = (array) old('social', $vendor->social_links ?? []); @endphp
            @foreach(\App\Models\Vendor::SOCIAL as $key => [$label, $icon])
                <div class="col-md-4">
                    <label class="form-label small" for="social_{{ $key }}"><i class="bi {{ $icon }}"></i> {{ $label }}</label>
                    <input type="url" id="social_{{ $key }}" name="social[{{ $key }}]" class="form-control form-control-sm @error('social.' . $key) is-invalid @enderror" value="{{ $socials[$key] ?? '' }}" placeholder="https://">
                    @error('social.' . $key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">2. Address</div>
        <div class="card-body row g-3">
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ $v('address') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="country">Country <span class="text-danger">*</span></label>
                <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                    @foreach(\App\Support\Countries::all() as $c)<option value="{{ $c }}" @selected($country === $c)>{{ $c }}</option>@endforeach
                </select>
                <div class="form-text">Change and save to switch the state list.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="state">State <span class="text-danger">*</span></label>
                @if($isIndia)
                    <select id="state" name="state" class="form-select @error('state') is-invalid @enderror" required>
                        <option value="">Select state</option>
                        @foreach(\App\Support\GstState::options() as $code => $name)
                            <option value="{{ $name }}" @selected(\App\Support\GstState::name(old('state', $vendor->state ?? '')) === $name)>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                @else
                    <input id="state" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ $v('state') }}" required>
                @endif
                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="city">City</label>
                <input id="city" name="city" class="form-control" value="{{ $v('city') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="postal_code">{{ $isIndia ? 'PIN code' : 'Postal code' }}</label>
                <input id="postal_code" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" value="{{ $v('postal_code') }}" @if($isIndia) inputmode="numeric" maxlength="6" @endif>
                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">3. Registration and GST</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="registration_number">Company registration number</label>
                <input id="registration_number" name="registration_number" class="form-control" value="{{ $v('registration_number') }}" placeholder="CIN, LLPIN, Udyam, Shop Act...">
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Registered for GST?</label>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gst_registered" id="gst1" value="1" @checked($gst) data-gst><label class="form-check-label" for="gst1">Yes</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gst_registered" id="gst0" value="0" @checked(! $gst) data-gst><label class="form-check-label" for="gst0">No</label></div>
            </div>
            <div class="col-md-6" id="gstinBox" @unless($gst) hidden @endunless>
                <label class="form-label" for="tax_number">GSTIN</label>
                <input id="tax_number" name="tax_number" class="form-control text-uppercase @error('tax_number') is-invalid @enderror" maxlength="15" value="{{ $v('tax_number') }}" placeholder="27ABCDE1234F1Z5">
                @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">15 characters; the first two digits must match your state code. Printed on invoices for your products.</div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('vendor.application'))
                <div class="col-12 small"><a href="{{ route('vendor.application') }}">See your verification documents</a></div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">4. Bank account for payouts</div>
        <div class="card-body row g-3">
            @if($acct)<div class="col-12 small text-muted">Current account: {{ $bank['bank_name'] ?? 'Bank' }} ending <strong>{{ substr((string) $acct, -4) }}</strong>. Type a new number only if you want to change it.</div>@endif
            <div class="col-md-6">
                <label class="form-label" for="bank_holder">Account holder name</label>
                <input id="bank_holder" name="bank_holder" class="form-control" value="{{ old('bank_holder', $bank['account_holder'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bank_name">Bank name</label>
                <input id="bank_name" name="bank_name" class="form-control" value="{{ old('bank_name', $bank['bank_name'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bank_account">{{ $acct ? 'New account number' : 'Account number' }}</label>
                <input id="bank_account" name="bank_account" class="form-control @error('bank_account') is-invalid @enderror" inputmode="numeric" autocomplete="off" value="">
                @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bank_account_confirmation">Confirm account number</label>
                <input id="bank_account_confirmation" name="bank_account_confirmation" class="form-control" inputmode="numeric" autocomplete="off" value="">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bank_ifsc">{{ $isIndia ? 'IFSC' : 'SWIFT / bank code' }}</label>
                <input id="bank_ifsc" name="bank_ifsc" class="form-control text-uppercase @error('bank_ifsc') is-invalid @enderror" value="{{ old('bank_ifsc', $bank['ifsc'] ?? '') }}" maxlength="20">
                @error('bank_ifsc')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="upi">UPI ID <span class="text-muted">(optional)</span></label>
                <input id="upi" name="upi" class="form-control @error('upi') is-invalid @enderror" value="{{ old('upi', $bank['upi'] ?? '') }}" placeholder="name@bank">
                @error('upi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    @if($owner)<button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2"></i> Save profile</button>@endif
    </fieldset>
</form>

<script>
    document.querySelectorAll('[data-gst]').forEach(function (r) {
        r.addEventListener('change', function () { document.getElementById('gstinBox').hidden = this.value !== '1'; });
    });
</script>
@endsection
