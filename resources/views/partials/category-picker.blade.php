{{-- Product type → categories → subcategories. Each step is filled from the one before, and every list allows several ticks.
     Used by the admin and seller product forms and the bulk enquiry page (with $pickerTypeField / $pickerCategoryField set by the page). --}}
@php
    $pickerData = \App\Support\CategoryPicker::data();
    $savedCats = old('category_ids', \App\Support\CategoryPicker::savedIds($product ?? null));
    $savedSubs = old('subcategory_ids', []);
    $savedType = old('product_type_id', $product?->category?->product_type_id ?? null);
    $pickerTypeField = $pickerTypeField ?? 'product_type_id';
@endphp

<div class="category-picker mb-3" data-category-picker>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="cp-type" class="form-label fw-semibold">{{ __('Product type') }} <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
                <select id="cp-type" name="{{ $pickerTypeField }}" class="form-select @error($pickerTypeField) is-invalid @enderror">
                    <option value="">{{ __('Choose a type') }}</option>
                    @foreach($pickerData['types'] as $t)<option value="{{ $t['id'] }}" @selected((string) $savedType === (string) $t['id'])>{{ $t['name'] }}</option>@endforeach
                </select>
                <button type="button" class="btn btn-outline-primary text-nowrap" data-add-type>+ {{ __('Add') }}</button>
            </div>
            @error($pickerTypeField)<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-8">
            <span class="form-label fw-semibold d-block">{{ __('Category') }} <span class="text-danger">*</span> <span class="text-muted small">({{ __('tick one or more') }})</span></span>
            <div class="border rounded p-2 d-flex flex-wrap gap-2 min-h-cp" id="cp-cats">
                <span class="text-muted small align-self-center">{{ __('Choose a product type first.') }}</span>
            </div>
            @error('category_ids')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-12" id="cp-sub-block" hidden>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="form-label fw-semibold">{{ __('Subcategory') }} <span class="text-muted small">({{ __('tick what applies') }})</span></span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-sub>+ {{ __('Add subcategory') }}</button>
            </div>
            <div class="border rounded p-2 d-flex flex-wrap gap-2" id="cp-subs"></div>
        </div>
    </div>
    {{-- The main category (first one ticked) is also kept on the product, for older screens and lists. --}}
    <input type="hidden" name="category_id" id="cp-main" value="{{ $savedCats[0] ?? '' }}">
    <input type="hidden" name="category_ids_sent" value="1">
</div>

@once
    @push('scripts')
    <script>
    (function () {
        'use strict';
        var data = @json($pickerData);
        var savedCats = @json(array_map('intval', (array) $savedCats));
        var savedSubs = @json(array_map('intval', (array) $savedSubs));
        var root = document.querySelector('[data-category-picker]');
        if (!root) { return; }
        var typeSel = document.getElementById('cp-type');
        var catBox = document.getElementById('cp-cats');
        var subBox = document.getElementById('cp-subs');
        var subBlock = document.getElementById('cp-sub-block');
        var main = document.getElementById('cp-main');

        function ticked(box) {
            return Array.prototype.map.call(box.querySelectorAll('input:checked'), function (i) { return Number(i.value); });
        }
        function box(parent, value, label, name, checked, cls) {
            var wrap = document.createElement('label');
            wrap.className = 'form-check d-inline-flex align-items-center gap-2 border rounded px-2 py-1 ' + (cls || '');
            var input = document.createElement('input');
            input.type = 'checkbox'; input.name = name; input.value = value; input.className = 'form-check-input m-0';
            input.checked = checked;
            var text = document.createElement('span'); text.textContent = label;
            wrap.appendChild(input); wrap.appendChild(text); parent.appendChild(wrap);
            return input;
        }
        function renderSubs() {
            var cats = ticked(catBox);
            var keep = ticked(subBox);
            subBox.innerHTML = '';
            var seen = {};
            data.categories.forEach(function (c) {
                if (cats.indexOf(c.id) === -1) { return; }
                c.subs.forEach(function (s) {
                    if (seen[s.id]) { return; }
                    seen[s.id] = true;
                    box(subBox, s.id, s.name, 'subcategory_ids[]', keep.indexOf(s.id) !== -1 || savedSubs.indexOf(s.id) !== -1, '');
                });
            });
            // The subcategory section appears as soon as a category is ticked, even when that category has none yet.
            subBlock.hidden = cats.length === 0;
            if (subBox.childNodes.length === 0 && cats.length) {
                var empty = document.createElement('span');
                empty.className = 'text-muted small align-self-center';
                empty.textContent = @json(__('No subcategories yet. Use "+ Add subcategory" to create one.'));
                subBox.appendChild(empty);
            }
            syncMain();
        }
        function renderCats() {
            var type = Number(typeSel.value) || 0;
            var keep = ticked(catBox);
            catBox.innerHTML = '';
            var list = data.categories.filter(function (c) { return !type || c.type === type; });
            if (!type) {
                catBox.innerHTML = '<span class="text-muted small align-self-center">{{ __('Choose a product type first.') }}</span>';
            } else if (!list.length) {
                catBox.innerHTML = '<span class="text-muted small align-self-center">{{ __('No categories for this type yet. Add one with the + button below.') }}</span>';
            }
            list.forEach(function (c) {
                var inp = box(catBox, c.id, c.name, 'category_ids[]', keep.indexOf(c.id) !== -1 || savedCats.indexOf(c.id) !== -1, '');
                inp.addEventListener('change', renderSubs);
            });
            renderSubs();
        }
        function syncMain() {
            var first = ticked(catBox)[0] || ticked(subBox)[0] || '';
            main.value = first;
        }
        typeSel.addEventListener('change', function () { savedCats = []; savedSubs = []; renderCats(); });
        // "+ Add": a product type that is not in the list is saved and selected straight away.
        var addBtn = root.querySelector('[data-add-type]');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var name = window.prompt(@json(__('Type the new product type')));
                if (!name || !name.trim()) { return; }
                var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                fetch(@json(route('product-types.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ name: name.trim() }),
                }).then(function (r) { return r.json(); }).then(function (j) {
                    if (!j.type) { window.alert(@json(__('Could not add the type.'))); return; }
                    if (!typeSel.querySelector('option[value="' + j.type.id + '"]')) {
                        data.types.push({ id: j.type.id, name: j.type.name });
                        var opt = document.createElement('option'); opt.value = j.type.id; opt.textContent = j.type.name; typeSel.appendChild(opt);
                    }
                    typeSel.value = j.type.id;
                    savedCats = []; savedSubs = [];
                    renderCats();
                    window.alert(@json(__('Saved. Now tick the categories for it.')));
                }).catch(function () { window.alert(@json(__('Could not add the type.'))); });
            });
        }
        subBox.addEventListener('change', syncMain);
        // "+ Add subcategory": goes under the first ticked category, appears in the list straight away.
        var addSub = root.querySelector('[data-add-sub]');
        if (addSub) {
            addSub.addEventListener('click', function () {
                var parent = ticked(catBox)[0];
                if (!parent) { window.alert(@json(__('Tick a category first, then add its subcategory.'))); return; }
                // Added under the first category ticked; tick another category to add its own subcategories.
                var name = window.prompt(@json(__('Type the new subcategory')));
                if (!name || !name.trim()) { return; }
                var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                fetch(@json(route('subcategories.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ name: name.trim(), parent_id: parent }),
                }).then(function (r) { return r.json(); }).then(function (j) {
                    if (!j.sub) { window.alert(@json(__('Could not add the subcategory.'))); return; }
                    data.categories.forEach(function (c) {
                        if (c.id === j.sub.parent_id && !c.subs.some(function (x) { return x.id === j.sub.id; })) { c.subs.push({ id: j.sub.id, name: j.sub.name }); }
                    });
                    var keep = ticked(subBox); keep.push(j.sub.id); savedSubs = savedSubs.concat([j.sub.id]);
                    renderSubs();
                }).catch(function () { window.alert(@json(__('Could not add the subcategory.'))); });
            });
        }
        renderCats();
    })();
    </script>
    @endpush
@endonce
