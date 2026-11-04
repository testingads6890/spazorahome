{{-- Extra product details from the vendor data sheet. Drawn from App\Support\ProductExtraFields (one list for the form and the checks). Expects $product; optional $startAt (first section number). --}}
@php
    $n = $startAt ?? 5;
    $extraVal = fn (string $name, $default = null) => old($name, $product->{$name} ?? $default);
    $groups = \App\Support\ProductExtraFields::groupsForForm();
    $saved = (array) ($product->category_attributes ?? []);
    $chosen = old('detail_group', $saved['group'] ?? '');
    $values = old('attr', $saved ? [($saved['group'] ?? '') => (array) ($saved['values'] ?? [])] : []);
    $attrErrors = collect($errors->keys())->contains(fn ($k) => $k === 'detail_group' || str_starts_with($k, 'attr'));
@endphp

@foreach(\App\Support\ProductExtraFields::sections() as $section)
    <div class="card mb-3">
        <div class="card-header fw-semibold">{{ $n++ }}. {{ $section['title'] }} <span class="text-muted fw-normal">(optional)</span></div>
        <div class="card-body">
            @if($section['hint'])<p class="text-muted small mb-3">{{ $section['hint'] }}</p>@endif
            <div class="row g-3">
                @foreach($section['fields'] as $f)
                    @continue($f['name'] === 'unit') {{-- the unit has its own block (partials.unit-details) --}}
                    @php $name = $f['name']; @endphp
                    <div class="{{ $f['col'] }}">
                        @if($f['type'] === 'bool')
                            <div class="form-check form-switch mt-md-4">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input type="checkbox" id="{{ $name }}" name="{{ $name }}" class="form-check-input @error($name) is-invalid @enderror" value="1" @checked((bool) $extraVal($name, $f['default'] ?? false))>
                                <label class="form-check-label" for="{{ $name }}">{{ $f['label'] }}</label>
                                @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @else
                            <label for="{{ $name }}" class="form-label">{{ $f['label'] }}</label>
                            @if($f['type'] === 'textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" rows="{{ $f['rows'] ?? 2 }}" maxlength="{{ $f['max'] }}">{{ $extraVal($name) }}</textarea>
                            @elseif($f['type'] === 'select')
                                <select id="{{ $name }}" name="{{ $name }}" class="form-select @error($name) is-invalid @enderror">
                                    @if(isset($f['blank']))<option value="">{{ $f['blank'] }}</option>@endif
                                    @foreach($f['options'] as $value => $label)
                                        <option value="{{ $value }}" @selected((string) $extraVal($name, $name === 'unit' ? 'pcs' : null) === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif($f['type'] === 'number')
                                <input type="number" id="{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" min="{{ $f['min'] }}" max="{{ $f['max'] }}" step="{{ $f['step'] }}" value="{{ $extraVal($name) }}">
                            @else
                                <input type="{{ $f['type'] === 'url' ? 'url' : ($f['type'] === 'date' ? 'date' : 'text') }}" id="{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" @if($f['type'] !== 'date') maxlength="{{ $f['max'] }}" @endif value="{{ $f['type'] === 'date' && $extraVal($name) instanceof \DateTimeInterface ? $extraVal($name)->format('Y-m-d') : $extraVal($name) }}" @if(isset($f['placeholder']))placeholder="{{ $f['placeholder'] }}"@endif>
                            @endif
                            @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(isset($f['hint']))<div class="form-text">{{ $f['hint'] }}</div>@endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach

<div class="card mb-3">
    <div class="card-header fw-semibold">{{ $n++ }}. Details for this kind of product <span class="text-muted fw-normal">(optional)</span></div>
    <div class="card-body">
        @if($attrErrors)<div class="alert alert-danger py-2">Please check the details below: Yes / No answers must be Yes or No, and each answer is at most 500 characters.</div>@endif
        <div class="row g-3">
            <div class="col-md-5">
                <label for="detail_group" class="form-label">Kind of product</label>
                <select id="detail_group" name="detail_group" class="form-select">
                    <option value="">Not needed</option>
                    @foreach($groups as $key => $group)
                        <option value="{{ $key }}" @selected((string) $chosen === (string) $key)>{{ $group['label'] }}</option>
                    @endforeach
                </select>
                <div class="form-text">Pick the closest match to fill in the specifics buyers and designers look for (wood species, lumens, GSM...).</div>
            </div>
        </div>
        <div id="detail-fields" class="row g-3 mt-1"></div>
    </div>
</div>

<script>
(function () {
    var groups = {!! \Illuminate\Support\Js::from($groups) !!};
    var values = {!! \Illuminate\Support\Js::from($values) !!};
    var select = document.getElementById('detail_group');
    var box = document.getElementById('detail-fields');
    var current = select.value;

    // Keep what was typed when the vendor switches to another kind and back.
    function remember() {
        if (!current) { return; }
        values[current] = {};
        box.querySelectorAll('[data-key]').forEach(function (el) { values[current][el.getAttribute('data-key')] = el.value; });
    }

    function render() {
        box.textContent = '';
        var group = groups[select.value];
        if (!group) { return; }
        var saved = values[select.value] || {};
        group.fields.forEach(function (f) {
            var id = 'attr_' + select.value + '_' + f.key;
            var col = document.createElement('div');
            col.className = 'col-6 col-md-4';
            var label = document.createElement('label');
            label.className = 'form-label';
            label.htmlFor = id;
            label.textContent = f.label;
            var input;
            if (f.type === 'bool') {
                input = document.createElement('select');
                input.className = 'form-select';
                ['', 'Yes', 'No'].forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o;
                    opt.textContent = o || 'Not set';
                    input.appendChild(opt);
                });
            } else {
                input = document.createElement('input');
                input.type = 'text';
                input.maxLength = 500;
                input.className = 'form-control';
            }
            input.id = id;
            input.name = 'attr[' + select.value + '][' + f.key + ']';
            input.setAttribute('data-key', f.key);
            input.value = saved[f.key] || '';
            col.appendChild(label);
            col.appendChild(input);
            box.appendChild(col);
        });
    }

    select.addEventListener('change', function () { remember(); current = select.value; render(); });
    render();
})();
</script>
