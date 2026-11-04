@php $a = $address ?? null; @endphp
@csrf
@php
    $text = fn ($name, $label, $required = true, $col = null) => [$name, $label, $required];
    $val = fn ($name) => old($name, $a?->{$name});
    $exportOn = (bool) config('shop.export.enabled');
    $country = $exportOn ? (\App\Support\Countries::isIndia($val('country')) ? \App\Support\Countries::INDIA : $val('country')) : null;
    $abroad = $exportOn && $country !== \App\Support\Countries::INDIA;
@endphp

<div class="mb-3">
    <label class="form-label">{{ __('Address Type') }}</label>
    <select name="type" class="form-select">
        <option value="shipping" {{ $val('type') === 'shipping' ? 'selected' : '' }}>{{ __('Shipping') }}</option>
        <option value="billing" {{ $val('type') === 'billing' ? 'selected' : '' }}>{{ __('Billing') }}</option>
    </select>
    @error('type')<div class="text-danger">{{ $message }}</div>@enderror
</div>

<div class="row">
    @foreach([['first_name', __('First Name'), true], ['last_name', __('Last Name'), true]] as [$n, $l, $r])
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ $l }}</label>
            <input type="text" name="{{ $n }}" class="form-control" value="{{ $val($n) }}" {{ $r ? 'required' : '' }}>
            @error($n)<div class="text-danger">{{ $message }}</div>@enderror
        </div>
    @endforeach
</div>

@foreach([['company', 'Company (optional)', false], ['address_line_1', 'Address Line 1', true], ['address_line_2', 'Address Line 2 (optional)', false]] as [$n, $l, $r])
    <div class="mb-3">
        <label class="form-label">{{ $l }}</label>
        <input type="text" name="{{ $n }}" class="form-control" value="{{ $val($n) }}" {{ $r ? 'required' : '' }}>
        @error($n)<div class="text-danger">{{ $message }}</div>@enderror
    </div>
@endforeach

<div class="row">
    @foreach(array_filter([['city', __('City'), true], ['postal_code', __('Postal Code'), true], $exportOn ? null : ['country', __('Country'), true], ['phone', __('Phone (optional)'), false]]) as [$n, $l, $r])
        <div class="col-md-4 mb-3">
            <label class="form-label">{{ $l }}</label>
            <input type="text" name="{{ $n }}" class="form-control" value="{{ $val($n) }}" {{ $r ? 'required' : '' }}>
            @error($n)<div class="text-danger">{{ $message }}</div>@enderror
        </div>
    @endforeach
</div>

<div class="row">
    @if($exportOn)
        <div class="col-md-6 mb-3">
            <label class="form-label" for="country">{{ __('Country') }}</label>
            <select name="country" id="country" class="form-select" required>
                @foreach(\App\Support\Countries::all() as $name)
                    <option value="{{ $name }}" {{ $country === $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @error('country')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
    @endif
    <div class="col-md-6 mb-3">
        <label class="form-label" for="state_select">{{ __('State') }}</label>
        {{-- India: the GST state list. Abroad: the state or region as the customer writes it. Only one of the two is sent. --}}
        <select name="state" id="state_select" class="form-select" required @if($abroad) hidden disabled @endif>
            <option value="">{{ __('Select state') }}</option>
            @foreach(\App\Support\GstState::options() as $code => $name)
                <option value="{{ $name }}" {{ \App\Support\GstState::name($val('state')) === $name ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
            @endforeach
        </select>
        @if($exportOn)
            <input type="text" name="state" id="state_text" class="form-control" maxlength="100" value="{{ $abroad ? $val('state') : '' }}" placeholder="{{ __('State / region') }}" required @unless($abroad) hidden disabled @endunless>
        @endif
        @error('state')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
</div>
@if($exportOn)
    <script>
        (function () {
            var country = document.getElementById('country'), list = document.getElementById('state_select'), text = document.getElementById('state_text');
            function sync() {
                var india = country.value === @json(\App\Support\Countries::INDIA);
                list.hidden = list.disabled = !india;
                text.hidden = text.disabled = india;
            }
            country.addEventListener('change', sync);
        })();
    </script>
@endif

<div class="form-check mb-3">
    <input type="hidden" name="is_default" value="0">
    <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" {{ $val('is_default') ? 'checked' : '' }}>
    <label class="form-check-label" for="is_default">{{ __('Make this my default address') }}</label>
</div>

<button type="submit" class="btn btn-primary">{{ __('Save Address') }}</button>
<a href="{{ route('customer.addresses.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
