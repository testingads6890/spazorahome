{{-- Mandatory dispatch location: country > state > district > city > PIN code, each one a dropdown that fills the next. --}}
@php
    $loc = [
        'country_id' => old('country_id', $product->country_id),
        'state_id' => old('state_id', $product->state_id),
        'district_id' => old('district_id', $product->district_id),
        'city_id' => old('city_id', $product->city_id),
        'pincode_id' => old('pincode_id', $product->pincode_id),
    ];
    $locCountries = App\Models\Country::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    $locStates = $loc['country_id'] ? App\Models\State::where('country_id', $loc['country_id'])->orderBy('name')->get(['id', 'name']) : collect();
    $locDistricts = $loc['state_id'] ? App\Models\District::where('state_id', $loc['state_id'])->orderBy('name')->get(['id', 'name']) : collect();
    $locCities = $loc['district_id'] ? App\Models\City::where('district_id', $loc['district_id'])->orderBy('name')->get(['id', 'name']) : collect();
    $locPins = $loc['city_id'] ? App\Models\Pincode::where('city_id', $loc['city_id'])->orderBy('code')->get(['id', 'code']) : collect();
    $pinSelected = array_map('strval', (array) old('pincode_ids', \App\Support\DeliveryArea::pincodeIds($product)));
    $locLevels = [
        ['country_id', 'Country', 'countries', $locCountries, 'name'],
        ['state_id', 'State / UT', 'states', $locStates, 'name'],
        ['district_id', 'District', 'districts', $locDistricts, 'name'],
        ['city_id', 'City', 'cities', $locCities, 'name'],
        ['pincode_id', 'PIN code', 'pincodes', $locPins, 'code'],
    ];
@endphp

<div class="row g-3" data-location-select>
    @foreach($locLevels as [$field, $label, $level, $options, $labelKey])
        <div class="col-md-6 col-xl-4">
            <label for="{{ $field }}" class="form-label">{{ __($label) }} @if($field === 'country_id')<span class="text-danger">*</span>@else<span class="text-muted small">({{ __('optional') }})</span>@endif</label>
            @if($field === 'pincode_id')
                <select id="{{ $field }}" name="pincode_ids[]" multiple size="4" class="form-select @error($field) is-invalid @enderror" data-location-level="{{ $level }}">
                    @foreach($options as $opt)
                        <option value="{{ $opt->id }}" @selected(in_array((string) $opt->id, $pinSelected, true))>{{ $opt->{$labelKey} }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="pincode_ids_sent" value="1">
                <div class="form-text">{{ __('Hold Ctrl (or Cmd) to pick several PIN codes.') }}</div>
            @else
                <select id="{{ $field }}" name="{{ $field }}" class="form-select @error($field) is-invalid @enderror" @if($field === 'country_id') required @endif data-location-level="{{ $level }}">
                    <option value="">{{ __('Choose') }}</option>
                    @foreach($options as $opt)
                        <option value="{{ $opt->id }}" @selected((string) $loc[$field] === (string) $opt->id)>{{ $opt->{$labelKey} }}</option>
                    @endforeach
                </select>
            @endif
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text text-warning d-none" data-location-empty>{{ __('No entries yet for this area. Ask the marketplace team to add them.') }}</div>
        </div>
    @endforeach
</div>
<div class="form-text">{{ __('Where the product is dispatched from. Shown to buyers for delivery estimates.') }}</div>
