{{-- The product form shared by "Add product" and "Edit product". Expects $product (new or existing) and $categories. --}}
@php
    $editing = $product->exists;
    $locked = $editing && $product->vendor_status === 'approved'; // SKU and tax were reviewed at approval
    $val = fn (string $name, $default = null) => old($name, $product->{$name} ?? $default);
    $tags = old('tags', is_array($product->tags) ? implode(', ', $product->tags) : (string) $product->tags);
    $max = \App\Http\Controllers\Vendor\ProductController::MAX_IMAGES;
    // One plain text input. [name, label, column class, attributes, hint]
    $text = fn (string $name, string $label, string $col = 'col-md-4', string $attrs = '', ?string $hint = null) => compact('name', 'label', 'col', 'attrs', 'hint');
@endphp

@if($errors->any())
    <div class="alert alert-danger"><strong>Please fix the highlighted fields.</strong> Pictures have to be chosen again.</div>
@endif

<div class="card mb-3">
    <div class="card-header fw-semibold">1. Basics</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="name" class="form-label">Product name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ $val('name') }}" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                <input type="text" id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ $val('sku') }}" maxlength="100" required @if($locked) readonly @endif>
                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($locked)<div class="form-text">Fixed once the listing is approved.</div>@endif
            </div>
            <div class="col-md-6">
                @include('partials.category-picker', ['product' => $product ?? null])
                <input type="text" name="custom_category" class="form-control mt-2 @error('custom_category') is-invalid @enderror" maxlength="120" value="{{ old('custom_category') }}" placeholder="Or type a new category name">
                <div class="form-text">Not in the list? Type it. Our team reviews new categories before they go live.</div>
                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('custom_category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            @foreach([$text('brand', 'Brand', 'col-md-3', 'maxlength="100"'), $text('product_type', 'Product type', 'col-md-3', 'maxlength="100" placeholder="Sofa, pendant lamp..."')] as $x)
                <div class="{{ $x['col'] }}">
                    <label for="{{ $x['name'] }}" class="form-label">{{ $x['label'] }}</label>
                    <input type="text" id="{{ $x['name'] }}" name="{{ $x['name'] }}" class="form-control @error($x['name']) is-invalid @enderror" value="{{ $val($x['name']) }}" {!! $x['attrs'] !!}>
                    @error($x['name'])<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
            <div class="col-12">
                <label for="short_description" class="form-label">Short description</label>
                <textarea id="short_description" name="short_description" class="form-control @error('short_description') is-invalid @enderror js-richtext" rows="2">{{ $val('short_description') }}</textarea>
                @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">One or two lines shown on listing pages.</div>
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea data-size="tall" id="description" name="description" class="form-control @error('description') is-invalid @enderror js-richtext" rows="6" required>{{ $val('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">2. Pictures and video</div>
    <div class="card-body">
        @include('partials.product-images-fields')
        <div class="row g-3 mt-1">
            <div class="col-md-5">
                <label for="video_url" class="form-label">Video link</label>
                <input type="url" id="video_url" name="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ $val('video_url') }}" maxlength="255" placeholder="https://www.youtube.com/watch?v=...">
                @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Optional. A YouTube or Vimeo link to a product video.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">3. Price, tax and stock</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label for="price" class="form-label">Price (before GST) <span class="text-danger">*</span></label>
                <div class="input-group has-validation"><span class="input-group-text">₹</span><input type="number" step="0.01" min="0" id="price" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ $val('price') }}" required>@error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="col-6 col-md-3">
                <label for="sale_price" class="form-label">Sale price</label>
                <div class="input-group has-validation"><span class="input-group-text">₹</span><input type="number" step="0.01" min="0" id="sale_price" name="sale_price" class="form-control @error('sale_price') is-invalid @enderror" value="{{ $val('sale_price') }}">@error('sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="col-6 col-md-3">
                <label for="mrp" class="form-label">Suggested MRP</label>
                <div class="input-group has-validation"><span class="input-group-text">₹</span><input type="number" step="0.01" min="0" id="mrp" name="mrp" class="form-control @error('mrp') is-invalid @enderror" value="{{ $val('mrp') }}">@error('mrp')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-text">Printed retail price, including GST. Optional.</div>
            </div>
            <div class="col-6 col-md-3">
                <label for="tax_rate" class="form-label">GST rate <span class="text-danger">*</span></label>
                @if($editing)
                    <input type="text" class="form-control" value="{{ rtrim(rtrim(number_format($product->taxRate(), 2), '0'), '.') }}%" readonly>
                    <input type="hidden" name="tax_rate" value="{{ (float) $product->tax_rate }}">
                @else
                    <select id="tax_rate" name="tax_rate" class="form-select @error('tax_rate') is-invalid @enderror" required>
                        <option value="">Select</option>
                        @foreach(config('shop.gst_slabs') as $slab)
                            <option value="{{ $slab }}" {{ $val('tax_rate') !== null && (float) $val('tax_rate') === (float) $slab ? 'selected' : '' }}>{{ $slab }}%</option>
                        @endforeach
                    </select>
                    @error('tax_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>
            <div class="col-6 col-md-3">
                <label for="hsn_code" class="form-label">HSN code <span class="text-danger">*</span></label>
                <input type="text" id="hsn_code" name="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" maxlength="8" inputmode="numeric" value="{{ $val('hsn_code') }}" required @if($locked) readonly @endif>
                @error('hsn_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">4, 6 or 8 digits.</div>
            </div>
            <div class="col-6 col-md-3">
                <label for="stock_quantity" class="form-label">Stock <span class="text-danger">*</span></label>
                <input type="number" min="0" id="stock_quantity" name="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror" value="{{ $val('stock_quantity') }}" required>
                @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6 col-md-3">
                <label for="low_stock_threshold" class="form-label">Low-stock alert at</label>
                <input type="number" min="0" id="low_stock_threshold" name="low_stock_threshold" class="form-control @error('low_stock_threshold') is-invalid @enderror" value="{{ $val('low_stock_threshold', 5) }}">
                @error('low_stock_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6 col-md-3">
                <label for="min_order_qty" class="form-label">Minimum order quantity</label>
                <input type="number" min="1" id="min_order_qty" name="min_order_qty" class="form-control @error('min_order_qty') is-invalid @enderror" value="{{ $val('min_order_qty', 1) }}">
                @error('min_order_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Leave at 1 unless you only sell in sets.</div>
            </div>
            @include('partials.bulk-pricing-fields')
            @if($editing)
                <div class="col-6 col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Available for sale</label>
                    </div>
                </div>
            @endif
        </div>
        @if($locked)<p class="form-text mb-0 mt-2">GST rate and HSN code were reviewed at approval. Contact the marketplace team to change them.</p>@endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Dispatch location</div>
    <div class="card-body">
        @include('partials.location-select')
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">4. Details and specifications</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                @include('partials.product-choices')
            </div>
            @foreach([
                $text('finish', 'Finish', 'col-6 col-md-4', 'maxlength="100" placeholder="Matte, polished..."'),
                $text('size', 'Size', 'col-6 col-md-3', 'maxlength="100" placeholder="3-seater, king..."'),
                $text('style', 'Style', 'col-6 col-md-3', 'maxlength="100" placeholder="Modern, classic..."'),
                $text('dimensions', 'Dimensions (W x D x H)', 'col-6 col-md-3', 'maxlength="100" placeholder="180 x 90 x 75 cm"'),
                $text('weight', 'Weight', 'col-6 col-md-3', 'maxlength="50" placeholder="42 kg"'),
            ] as $x)
                <div class="{{ $x['col'] }}">
                    <label for="{{ $x['name'] }}" class="form-label">{{ $x['label'] }}</label>
                    <input type="text" id="{{ $x['name'] }}" name="{{ $x['name'] }}" class="form-control @error($x['name']) is-invalid @enderror" value="{{ $val($x['name']) }}" {!! $x['attrs'] !!}>
                    @error($x['name'])<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
            @foreach([
                ['specifications', 'Specifications', 'One per line, like "Seat height: 45 cm".', 4],
                ['customization_info', 'Customisation', 'Sizes, colours or finishes you can make to order, and how long it takes.', 3],
                ['shipping_info', 'Shipping', 'Packing, dispatch time and anything the customer should know about delivery.', 3],
                ['warranty', 'Warranty', 'Length and what it covers.', 2],
                ['care_instructions', 'Care instructions', 'How to clean and look after the product.', 2],
            ] as [$name, $label, $hint, $rows])
                <div class="col-md-6">
                    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
                    <textarea id="{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" rows="{{ $rows }}">{{ $val($name) }}</textarea>
                    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ $hint }}</div>
                </div>
            @endforeach
            <div class="col-md-6">
                <label for="tags" class="form-label">Tags</label>
                <input type="text" id="tags" name="tags" class="form-control @error('tags') is-invalid @enderror" value="{{ $tags }}" maxlength="500" placeholder="solid wood, handmade, mid-century">
                @error('tags')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Separate with commas. Up to 15.</div>
            </div>
        </div>
    </div>
</div>

@include('partials.unit-details')
@include('partials.vendor-product-extra-fields', ['startAt' => 5])

<div class="card mb-3">
    <div class="card-header fw-semibold">12. Search engine listing <span class="text-muted fw-normal">(optional)</span></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="meta_title" class="form-label">Page title</label>
                <input type="text" id="meta_title" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror" value="{{ $val('meta_title') }}" maxlength="255">
                @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Left empty, the product name is used.</div>
            </div>
            <div class="col-md-6">
                <label for="meta_description" class="form-label">Page description</label>
                <textarea id="meta_description" name="meta_description" class="form-control @error('meta_description') is-invalid @enderror" rows="2" maxlength="500">{{ $val('meta_description') }}</textarea>
                @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">About 150 characters. Left empty, the short description is used.</div>
            </div>
        </div>
    </div>
</div>
