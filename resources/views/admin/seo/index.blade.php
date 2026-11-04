@extends('layouts.admin')

@section('title', 'SEO Settings')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">SEO Settings</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Search Engine Optimization</div>
        <div class="card-body">
            <form action="{{ route('admin.seo.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="site_title" class="form-label">Site Title</label>
                    <input type="text" id="site_title" name="site_title" class="form-control" 
                           value="{{ old('site_title', $settings['site_title']) }}" required>
                    @error('site_title')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="site_description" class="form-label">Site Description</label>
                    <textarea id="site_description" name="site_description" class="form-control" rows="3" required>{{ old('site_description', $settings['site_description']) }}</textarea>
                    @error('site_description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="site_keywords" class="form-label">Site Keywords</label>
                    <input type="text" id="site_keywords" name="site_keywords" class="form-control" 
                           value="{{ old('site_keywords', $settings['site_keywords']) }}">
                    <small class="text-muted">Comma-separated keywords</small>
                    @error('site_keywords')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="og_image" class="form-label">OG Image URL</label>
                    <input type="text" id="og_image" name="og_image" class="form-control" 
                           value="{{ old('og_image', $settings['og_image']) }}">
                    @error('og_image')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <hr>
                <h5>Analytics & Verification</h5>

                <div class="mb-3">
                    <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id" class="form-control" 
                           value="{{ old('google_analytics_id', $settings['google_analytics_id']) }}">
                    @error('google_analytics_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="google_site_verification" class="form-label">Google Site Verification</label>
                    <input type="text" id="google_site_verification" name="google_site_verification" class="form-control" 
                           value="{{ old('google_site_verification', $settings['google_site_verification']) }}">
                    @error('google_site_verification')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="bing_site_verification" class="form-label">Bing Site Verification</label>
                    <input type="text" id="bing_site_verification" name="bing_site_verification" class="form-control" 
                           value="{{ old('bing_site_verification', $settings['bing_site_verification']) }}">
                    @error('bing_site_verification')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <hr>
                <h5>Search engine access (robots.txt and sitemap)</h5>
                <p class="text-muted small">Your sitemap is at <a href="{{ route('sitemap') }}" target="_blank" rel="noopener">{{ route('sitemap') }}</a> and robots.txt at <a href="{{ route('robots') }}" target="_blank" rel="noopener">{{ route('robots') }}</a>. Submit the sitemap in Google Search Console and Bing Webmaster Tools. Admin, seller, account, cart and checkout pages are always kept out of search results.</p>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="seo_noindex" name="seo_noindex" value="1" {{ old('seo_noindex', $settings['seo_noindex']) == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="seo_noindex">Hide the whole site from search engines <small class="text-muted">(only for testing; switch off before launch)</small></label>
                </div>
                <div class="mb-4">
                    <label for="robots_extra" class="form-label">Extra robots.txt rules <small class="text-muted">(optional, one per line: Disallow:, Allow:, Crawl-delay:)</small></label>
                    <textarea id="robots_extra" name="robots_extra" class="form-control font-monospace" rows="3">{{ old('robots_extra', $settings['robots_extra']) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update SEO Settings</button>
            </form>
        </div>
    </div>
</div>
@endsection