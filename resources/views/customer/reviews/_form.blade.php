@csrf
@php $r = $review ?? null; @endphp

@isset($products)
    <div class="mb-3">
        <label class="form-label fw-semibold" for="product_id">{{ __('Product') }}</label>
        <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
            <option value="">{{ __('Select a product you bought') }}</option>
            @foreach($products as $p)<option value="{{ $p->id }}" {{ (int) old('product_id') === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
        </select>
        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($products->isEmpty())<div class="form-text">{{ __('You can review a product after buying it.') }}</div>@endif
    </div>
@endisset

<div class="mb-3">
    <label class="form-label fw-semibold d-block">{{ __('Rating') }}</label>
    <div class="d-flex gap-3 flex-wrap">
        @foreach([5, 4, 3, 2, 1] as $n)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="rating" id="rating{{ $n }}" value="{{ $n }}" {{ (int) old('rating', $r?->rating) === $n ? 'checked' : '' }} required>
                <label class="form-check-label" for="rating{{ $n }}">{{ $n }} <i class="bi bi-star-fill" style="color:var(--star)"></i></label>
            </div>
        @endforeach
    </div>
    @error('rating')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold" for="title">{{ __('Headline') }} <span class="text-muted fw-normal">{{ __('(optional)') }}</span></label>
    <input type="text" id="title" name="title" maxlength="255" value="{{ old('title', $r?->title) }}" class="form-control @error('title') is-invalid @enderror">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold" for="comment">{{ __('Your review') }}</label>
    <textarea id="comment" name="comment" rows="5" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $r?->comment) }}</textarea>
    @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<button class="btn btn-primary">{{ __('Submit review') }}</button>
<a href="{{ route('customer.reviews.index') }}" class="btn btn-outline-primary">{{ __('Cancel') }}</a>
