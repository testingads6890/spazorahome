{{-- Shared product fields for the admin and seller forms: colour, material and grade, dispatch time, installation, max quantity. --}}
@php
    [$colourSel, $colourOther] = \App\Support\ProductChoices::split($product->color, \App\Support\ProductChoices::COLOURS);
    [$matSel, $matOther] = \App\Support\ProductChoices::split($product->material, \App\Support\ProductChoices::MATERIALS);
    [$secSel, $secOther] = \App\Support\ProductChoices::split($product->secondary_material, \App\Support\ProductChoices::MATERIALS);
    $colourSel = old('color', $colourSel); $colourOther = old('color_other', $colourOther);
    $matSel = old('material', $matSel); $matOther = old('material_other', $matOther);
    $secSel = old('secondary_material', $secSel); $secOther = old('secondary_material_other', $secOther);
    $serviceSel = old('service_type', $product->service_type ?? ($product->assembly_required && $product->installation_included ? 'assembly_installation' : ($product->installation_included ? 'installation' : ($product->assembly_required ? 'assembly' : 'none'))));
    $inputClass = fn (string $name) => 'form-control' . ($errors->has($name) ? ' is-invalid' : '');
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="color" class="form-label">Colour</label>
        <select id="color" name="color" class="form-select @error('color') is-invalid @enderror" data-other-for="color_other">
            <option value="">Choose a colour</option>
            @foreach(\App\Support\ProductChoices::COLOURS as $c)<option value="{{ $c }}" @selected($colourSel === $c)>{{ $c }}</option>@endforeach
            <option value="{{ \App\Support\ProductChoices::OTHER }}" @selected($colourSel === \App\Support\ProductChoices::OTHER)>Other (type it)</option>
        </select>
        <input type="text" id="color_other" name="color_other" value="{{ $colourOther }}" class="{{ $inputClass('color_other') }} mt-2" placeholder="Type the colour" maxlength="150" @unless($colourSel === \App\Support\ProductChoices::OTHER) hidden @endunless>
        @error('color')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('color_other')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="material" class="form-label">Primary material</label>
        <select id="material" name="material" class="form-select @error('material') is-invalid @enderror" data-other-for="material_other">
            <option value="">Choose a material</option>
            @foreach(\App\Support\ProductChoices::MATERIALS as $m)<option value="{{ $m }}" @selected($matSel === $m)>{{ $m }}</option>@endforeach
            <option value="{{ \App\Support\ProductChoices::OTHER }}" @selected($matSel === \App\Support\ProductChoices::OTHER)>Other (type it)</option>
        </select>
        <input type="text" id="material_other" name="material_other" value="{{ $matOther }}" class="{{ $inputClass('material_other') }} mt-2" placeholder="Type the material" maxlength="150" @unless($matSel === \App\Support\ProductChoices::OTHER) hidden @endunless>
        @error('material')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('material_other')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="material_grade" class="form-label">Primary material grade / quality</label>
        <input type="text" id="material_grade" name="material_grade" value="{{ old('material_grade', $product->material_grade) }}" class="{{ $inputClass('material_grade') }}" maxlength="150" placeholder="e.g. Teak, grade A, 18 mm">
        @error('material_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="secondary_material" class="form-label">Secondary material</label>
        <select id="secondary_material" name="secondary_material" class="form-select @error('secondary_material') is-invalid @enderror" data-other-for="secondary_material_other">
            <option value="">None / choose</option>
            @foreach(\App\Support\ProductChoices::MATERIALS as $m)<option value="{{ $m }}" @selected($secSel === $m)>{{ $m }}</option>@endforeach
            <option value="{{ \App\Support\ProductChoices::OTHER }}" @selected($secSel === \App\Support\ProductChoices::OTHER)>Other (type it)</option>
        </select>
        <input type="text" id="secondary_material_other" name="secondary_material_other" value="{{ $secOther }}" class="{{ $inputClass('secondary_material_other') }} mt-2" placeholder="Type the material" maxlength="150" @unless($secSel === \App\Support\ProductChoices::OTHER) hidden @endunless>
        @error('secondary_material')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('secondary_material_other')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="secondary_material_grade" class="form-label">Secondary material grade / quality</label>
        <input type="text" id="secondary_material_grade" name="secondary_material_grade" value="{{ old('secondary_material_grade', $product->secondary_material_grade) }}" class="{{ $inputClass('secondary_material_grade') }}" maxlength="150">
        @error('secondary_material_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="lead_time_days" class="form-label">Dispatch time (days)</label>
        <input type="number" min="0" max="365" step="1" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days', $product->lead_time_days) }}" class="{{ $inputClass('lead_time_days') }}">
        <div class="form-text">Days from order to dispatch.</div>
        @error('lead_time_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="service_type" class="form-label">Assembly / Installation</label>
        <select id="service_type" name="service_type" class="form-select @error('service_type') is-invalid @enderror">
            @foreach(\App\Support\ProductChoices::SERVICE_TYPES as $val => $lbl)<option value="{{ $val }}" @selected($serviceSel === $val)>{{ $lbl }}</option>@endforeach
        </select>
        @error('service_type')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
  <div class="col-md-4">
        <label for="min_order_qty" class="form-label">Minimum order quantity</label>
        <input type="number" min="1" id="max_order_qty" name="min_order_qty" value="{{ old('min_order_qty', $product->min_order_qty) }}" class="{{ $inputClass('min_order_qty') }}" placeholder="minimum 1">
        <div class="form-text">Most units one buyer can order at once. Leave empty for no limit.</div>
        @error('max_order_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="max_order_qty" class="form-label">Maximum order quantity</label>
        <input type="number" min="1" id="max_order_qty" name="max_order_qty" value="{{ old('max_order_qty', $product->max_order_qty) }}" class="{{ $inputClass('max_order_qty') }}" placeholder="No limit">
        <div class="form-text">Most units one buyer can order at once. Leave empty for no limit.</div>
        @error('max_order_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<script src="{{ asset('js/product-choices.js') }}?v={{ filemtime(public_path('js/product-choices.js')) }}" defer></script>
