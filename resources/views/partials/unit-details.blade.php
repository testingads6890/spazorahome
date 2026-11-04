{{-- "Sold per" unit for admin and seller product forms. Under the unit: length, width, height and the measuring unit (ft, m, cm...). --}}
@php
    $unitOptions = \App\Models\ProductUnit::options();
    $unitNow = old('unit', $product->unit ?? 'pcs');
    $unitSaved = (array) ($product->unit_details ?? []);
    $dimension = fn (string $name) => old('unit_details.' . $name, $unitSaved[$name] ?? '');
    // Measuring unit shown in the captions; it starts from the saved value or the chosen unit's usual one.
    $defaultMeasure = ['running_ft' => 'ft', 'rm' => 'm', 'sqft' => 'sq ft', 'sqm' => 'sq m'][$unitNow] ?? '';
    $measureNow = old('unit_details.measure', $unitSaved['measure'] ?? $defaultMeasure);
@endphp

<div class="card mb-3" id="unit-block">
    <div class="card-header fw-semibold">{{ __('Sold per (unit)') }}</div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="unit" class="form-label">{{ __('Unit') }}</label>
                <div class="input-group">
                    <select id="unit" name="unit" class="form-select @error('unit') is-invalid @enderror">
                        @foreach($unitOptions as $key => $label)
                            <option value="{{ $key }}" @selected($unitNow === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUnitModal">+ {{ __('Add new unit') }}</button>
                </div>
                @error('unit')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-6 col-md-3">
                <label for="ud-length" class="form-label"><span data-caption="Length">{{ __('Length') }}</span> <span class="text-muted" data-caption-unit>@if($measureNow !== '')({{ $measureNow }})@endif</span></label>
                <input id="ud-length" name="unit_details[length]" type="number" min="0" step="any" class="form-control @error('unit_details.length') is-invalid @enderror" value="{{ $dimension('length') }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="ud-width" class="form-label"><span data-caption="Width">{{ __('Width') }}</span> <span class="text-muted" data-caption-unit>@if($measureNow !== '')({{ $measureNow }})@endif</span></label>
                <input id="ud-width" name="unit_details[width]" type="number" min="0" step="any" class="form-control @error('unit_details.width') is-invalid @enderror" value="{{ $dimension('width') }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="ud-height" class="form-label"><span data-caption="Height">{{ __('Height') }}</span> <span class="text-muted" data-caption-unit>@if($measureNow !== '')({{ $measureNow }})@endif</span></label>
                <input id="ud-height" name="unit_details[height]" type="number" min="0" step="any" class="form-control @error('unit_details.height') is-invalid @enderror" value="{{ $dimension('height') }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="ud-measure" class="form-label">{{ __('Measured in') }}</label>
                <input id="ud-measure" name="unit_details[measure]" type="text" maxlength="20" class="form-control @error('unit_details.measure') is-invalid @enderror" placeholder="ft, m, cm, inch" value="{{ $measureNow }}">
            </div>
        </div>
        <div class="form-text mt-2">{{ __('Fill in what applies: for example 8 ft long and 2 ft wide, measured in ft.') }}</div>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="addUnitTitle">{{ __('Add a new unit') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <label for="new-unit-label" class="form-label">{{ __('Unit name') }}</label>
                <input id="new-unit-label" type="text" maxlength="60" class="form-control" placeholder="{{ __('e.g. Pack of 10, Sq yard') }}">
                <div class="text-danger small mt-2" id="new-unit-error" hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="new-unit-save">{{ __('Save unit') }}</button>
            </div>
        </div>
    </div>
</div>

@once
<script>
(function () {
    'use strict';
    var select = document.getElementById('unit');
    if (!select) { return; }
    var endpoint = @json(route('product-units.store'));
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // The captions under the unit show the measuring unit the seller typed (Length (ft), Width (ft)...).
    var measure = document.getElementById('ud-measure');
    function captions() {
        var m = measure ? measure.value.trim() : '';
        document.querySelectorAll('[data-caption-unit]').forEach(function (el) { el.textContent = m ? '(' + m + ')' : ''; });
    }
    if (measure) { measure.addEventListener('input', captions); }

    document.getElementById('new-unit-save').addEventListener('click', function () {
        var err = document.getElementById('new-unit-error');
        var label = document.getElementById('new-unit-label').value.trim();
        err.hidden = true;
        if (!label) { err.textContent = @json(__('Type a name for the unit.')); err.hidden = false; return; }

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify({ label: label }),
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
          .then(function (res) {
              if (!res.ok) {
                  var first = res.body && res.body.errors ? Object.values(res.body.errors)[0][0] : @json(__('Could not add the unit.'));
                  err.textContent = first; err.hidden = false; return;
              }
              var unit = res.body.unit;
              if (!select.querySelector('option[value="' + unit.key + '"]')) {
                  var opt = document.createElement('option');
                  opt.value = unit.key; opt.textContent = unit.label;
                  select.appendChild(opt);
              }
              select.value = unit.key;
              document.getElementById('new-unit-label').value = '';
              var modalEl = document.getElementById('addUnitModal');
              if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); }
          })
          .catch(function () { err.textContent = @json(__('Could not add the unit. Check your connection.')); err.hidden = false; });
    });
})();
</script>
@endonce
