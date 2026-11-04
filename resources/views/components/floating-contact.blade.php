{{-- Sticky buttons on the right, middle of the screen: WhatsApp, Instagram, Facebook, Email, Call us. Always shown; the links come from settings with safe fallbacks. --}}
@php
    $wa = preg_replace('/\D/', '', (string) (config('shop.contact.whatsapp') ?: '919660533307'));
    // Placeholder links until the real pages are set in .env (CONTACT_INSTAGRAM, CONTACT_FACEBOOK, SHOP_CONTACT_EMAIL).
    $ig = config('shop.social.instagram') ?: 'https://instagram.com/spazorahome';
    $fb = config('shop.social.facebook') ?: 'https://facebook.com/spazorahome';
    $mail = config('shop.contact.email') ?: 'info@spazorahome.com';
    $tel = preg_replace('/[^0-9+]/', '', (string) (config('shop.contact.phone') ?: '+919660533307'));
@endphp
<div class="floating-contact" aria-label="{{ __('Contact us') }}">
    <a class="fc-btn fc-wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp"><i class="bi bi-whatsapp"></i><span>WhatsApp</span></a>
    <a class="fc-btn fc-ig" href="{{ $ig }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><i class="bi bi-instagram"></i><span>Instagram</span></a>
    <a class="fc-btn fc-fb" href="{{ $fb }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><i class="bi bi-facebook"></i><span>Facebook</span></a>
    <a class="fc-btn fc-mail" href="mailto:{{ $mail }}" aria-label="Email" title="Email"><i class="bi bi-envelope-fill"></i><span>Email</span></a>
    <a class="fc-btn fc-call" href="tel:{{ $tel }}" aria-label="{{ __('Call us') }}" title="{{ __('Call us') }}"><i class="bi bi-telephone-fill"></i><span>{{ __('Call us') }}</span></a>
</div>
