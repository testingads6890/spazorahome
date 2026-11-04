<footer class="site-footer">
    <div class="container">
        <div class="row g-4 footer-top">
            {{-- Brand and about --}}
            <div class="col-12 col-lg-3">
                <a class="brand" href="{{ route('home') }}">
                    <img src="{{ \App\Support\Theme::logoUrl() }}" alt="{{ \App\Support\Seo::get('site_title', 'SpazoraHome') }} logo" class="brand-logo">
                </a>
                <p class="footer-about mt-3 mb-0">{{ __('shop.footer.tagline') }}
                    <a href="{{ route('about-us') }}">{{ __('shop.footer.about_us') }}</a>
                </p>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h3 class="footer-heading">{{ __('shop.footer.shop') }}</h3>
                <ul>
                    <li><a href="{{ route('search') }}">{{ __('shop.footer.all_products') }}</a></li>
                    @foreach($navCategories->take(5) as $c)
                        <li><a href="{{ $c->shopUrl() }}">{{ $c->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h3 class="footer-heading">{{ __('shop.footer.company') }}</h3>
                <ul>
                    <li><a href="{{ route('about-us') }}">{{ __('shop.footer.about_us') }}</a></li>
                    <li><a href="{{ route('vendor.register') }}">{{ __('shop.footer.sell_with_us') }}</a></li>
                    <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                    @if(\App\Support\Stores::enabled())<li><a href="{{ route('stores.index') }}">{{ __('shop.footer.our_sellers') }}</a></li>@endif
                </ul>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h3 class="footer-heading">{{ __('Useful Links') }}</h3>
                <ul>
                    <li><a href="{{ route('customer.order-tracking.index') }}">{{ __('shop.footer.track_order') }}</a></li>
                    <li><a href="{{ route('faq') }}">{{ __('shop.footer.faq') }}</a></li>
                    <li><a href="{{ route('contact-us') }}">{{ __('shop.footer.contact') }}</a></li>
                    <li><a href="{{ route('customer.support-tickets.index') }}">{{ __('shop.footer.support') }}</a></li>
                </ul>
            </div>

            {{-- Address and social --}}
            <div class="col-12 col-md-4 col-lg-3">
                <h3 class="footer-heading">{{ __('Address') }}</h3>
                <div class="footer-address">
                    @if(config('shop.seller.name'))<div class="fw-semibold text-white">{{ config('shop.seller.name') }}</div>@endif
                    @if(config('shop.seller.address'))<div>{{ config('shop.seller.address') }}</div>@endif
                    @if(config('shop.contact.phone'))<div><a href="tel:{{ preg_replace('/\s+/', '', config('shop.contact.phone')) }}">{{ config('shop.contact.phone') }}</a></div>@endif
                    @if(config('shop.contact.email'))<div><a href="mailto:{{ config('shop.contact.email') }}">{{ config('shop.contact.email') }}</a></div>@endif
                    @if(config('shop.seller.gstin'))<div>GSTIN: {{ config('shop.seller.gstin') }}</div>@endif
                </div>
            </div>
        </div>

        {{-- Socials and payments --}}
        <div class="footer-bar">
            <div class="social" aria-label="{{ __('Follow us') }}">
                <a href="{{ config('shop.social.facebook', '#') }}" aria-label="Facebook" title="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="{{ config('shop.social.instagram', '#') }}" aria-label="Instagram" title="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="{{ config('shop.social.youtube', '#') }}" aria-label="YouTube" title="YouTube"><i class="bi bi-youtube"></i></a>
                <a href="{{ config('shop.social.pinterest', '#') }}" aria-label="Pinterest" title="Pinterest"><i class="bi bi-pinterest"></i></a>
                <a href="{{ config('shop.social.linkedin', '#') }}" aria-label="LinkedIn" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="{{ config('shop.social.x', '#') }}" aria-label="X (Twitter)" title="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                <a href="{{ config('shop.contact.whatsapp_link', '#') }}" aria-label="WhatsApp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            </div>
            <div class="payment-note"><i class="bi bi-credit-card" aria-hidden="true"></i> {{ __('shop.footer.cod') }} · {{ __('shop.footer.cards') }} · <i class="bi bi-shield-check" aria-hidden="true"></i> {{ __('shop.footer.secure') }}</div>
        </div>

        {{-- Policies and copyright --}}
        <div class="legal">
            <nav class="footer-policies" aria-label="{{ __('Policies') }}">
                <a href="{{ route('shipping') }}">{{ __('shop.footer.shipping_policy') }}</a>
                <a href="{{ route('returns') }}">{{ __('shop.footer.returns') }}</a>
                <a href="{{ route('privacy') }}">{{ __('shop.footer.privacy') }}</a>
                <a href="{{ route('terms') }}">{{ __('shop.footer.terms') }}</a>
                @if($footerLinks)
                    @foreach($footerLinks as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                @endif
            </nav>
            <span>&copy; {{ date('Y') }} {{ config('shop.seller.name') }}. {{ __('All rights reserved.') }}</span>
            <span>{{ __('Designed & Developed by') }} <a href="https://srkwebappinnovations.com" target="_blank" rel="noopener noreferrer">Srkwebapp Innovations LLP</a></span>
            <span>{{ __('shop.footer.gst_note') }}</span>
        </div>
    </div>
</footer>
