@if($errors->any())
    <div class="alert alert-danger"><strong>Please fix these:</strong><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
                    <div class="mb-3">
                        @include('partials.category-picker', ['product' => $product])
                        <input type="text" name="custom_category" class="form-control mt-2" maxlength="120" value="{{ old('custom_category') }}" placeholder="Or type a new category name">
                        <small class="text-muted">Pick one from the list, or type a new category. Typing a name that already exists reuses it.</small>
                        @error('category_id')<div class="text-danger">{{ $message }}</div>@enderror
                        @error('custom_category')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (web address)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $product->exists ? $product->slug : '') }}" placeholder="e.g. oak-dining-table" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea data-size="tall" name="description" class="form-control js-richtext" rows="4">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" class="form-control js-richtext" rows="2">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sale Price</label>
                            <input type="number" step="0.01" name="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Stock Qty</label>
                            <input type="number" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        @include('partials.bulk-pricing-fields')
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold mb-2">Thumbnail and pictures</div>
                        @include('partials.product-images-fields')
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $product->meta_title) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HSN code</label>
                        <input type="text" name="hsn_code" class="form-control" maxlength="8" inputmode="numeric" value="{{ old('hsn_code', $product->hsn_code) }}" placeholder="e.g. 94036000">
                        <small class="text-muted">4, 6 or 8 digits. Printed on the GST invoice.</small>
                        @error('hsn_code')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST rate (percent)</label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="form-control" list="gst-rates" value="{{ old('tax_rate', $product->tax_rate) }}" placeholder="Default ({{ config('shop.tax_rate') }})">
                        <datalist id="gst-rates"><option value="0"><option value="5"><option value="12"><option value="18"><option value="28"></datalist>
                        <small class="text-muted">Leave empty to use the store default.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand', $product->brand) }}">
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold mb-2">Material, colour, dispatch and installation</div>
                        @include('partials.product-choices')
                        @include('partials.unit-details')
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold mb-2">Dispatch location <span class="text-danger">*</span></div>
                        @include('partials.location-select')
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <input type="text" name="size" class="form-control" value="{{ old('size', $product->size) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Style</label>
                        <input type="text" name="style" class="form-control" value="{{ old('style', $product->style) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Low stock threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}">
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_featured">Featured</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="hidden" name="export_available" value="0">
                        <input type="checkbox" class="form-check-input" id="export_available" name="export_available" value="1" {{ old('export_available', $product->export_available) ? 'checked' : '' }}>
                        <label class="form-check-label" for="export_available">Available for export (can be delivered outside India)</label>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold mb-2">Export details</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="form-label small mb-1" for="export_weight_kg">Packed weight (kg)</label>
                                <input type="number" step="0.01" min="0" id="export_weight_kg" name="export_weight_kg" class="form-control" value="{{ old('export_weight_kg', $product->export_weight_kg) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small mb-1" for="export_min_qty">Minimum export quantity</label>
                                <input type="number" min="1" id="export_min_qty" name="export_min_qty" class="form-control" value="{{ old('export_min_qty', $product->export_min_qty) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1" for="country_of_origin">Country of origin</label>
                                <input type="text" id="country_of_origin" name="country_of_origin" class="form-control" value="{{ old('country_of_origin', $product->country_of_origin) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1" for="export_blocked_countries">Do not deliver to</label>
                                <input type="hidden" name="export_blocked_countries[]" value="">
                                <select id="export_blocked_countries" name="export_blocked_countries[]" class="form-select" multiple size="4">
                                    @foreach(\App\Support\Countries::all() as $country)
                                        @continue(\App\Support\Countries::isIndia($country))
                                        <option value="{{ $country }}" @selected(in_array($country, old('export_blocked_countries', $product->export_blocked_countries ?? [])))>{{ $country }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Weight is one unit packed for shipping; it prices freight in zones with a per-kg rate and prints on the packing list. Hold Ctrl to pick several countries.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ old('is_active', $product->exists ? (int) $product->is_active : 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $product->exists ? (int) $product->is_active : 1) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Role (Target Audience)</label>
                        <select name="user_role" class="form-control">
                            <option value="customer" {{ $product->user_role === 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="interior" {{ $product->user_role === 'interior' ? 'selected' : '' }}>Interior</option>
                            <option value="architecture" {{ $product->user_role === 'architecture' ? 'selected' : '' }}>Architecture</option>
                            <option value="designer" {{ $product->user_role === 'designer' ? 'selected' : '' }}>Designer</option>
                            <option value="other" {{ $product->user_role === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
