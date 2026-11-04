{{--
  Mobile OTP widget (Firebase Phone Auth).
  $mode: 'submit' = the widget sends the verified token by submitting its own form (sign-in / sign-up).
         'token'  = it only stores the verified token in the form, so the rest of the form is submitted as usual.
  $tokenName: name of the hidden field that receives the Firebase ID token.
  $title: optional heading. $prefill: name of a phone field in the same form to copy into the widget.
--}}
@php
    $fbConfig = \App\Support\FirebaseWeb::config();
    $mode = $mode ?? 'submit';
    $tokenName = $tokenName ?? 'id_token';
    $title = $title ?? null;
    $prefill = $prefill ?? null;
@endphp

@if($fbConfig)
<div class="otp-widget" data-phone-otp data-mode="{{ $mode }}" data-token-name="{{ $tokenName }}" data-prefill="{{ $prefill }}">
    @if($title)<div class="fw-semibold mb-2">{{ $title }}</div>@endif

    <div data-otp-step="phone">
        <label for="otp_phone_{{ $tokenName }}" class="form-label fw-semibold">{{ __('Mobile number') }}</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text">+91</span>
            <input type="tel" id="otp_phone_{{ $tokenName }}" class="form-control" inputmode="numeric" autocomplete="tel-national" maxlength="14" placeholder="{{ __('10-digit mobile number') }}" data-otp-phone>
        </div>
        <button type="button" class="btn btn-outline-primary btn-lg w-100 mt-3" data-otp-send>{{ __('Send OTP') }}</button>
    </div>

    <div data-otp-step="code" hidden>
        <p class="small text-muted mb-2">{{ __('Enter the 6-digit code sent to') }} <strong data-otp-sent-to></strong>.</p>
        <input type="text" class="form-control form-control-lg text-center fs-4" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" data-otp-code>
        <button type="button" class="btn btn-primary btn-lg w-100 mt-3" data-otp-verify>{{ $mode === 'token' ? __('Verify mobile') : __('Verify and continue') }}</button>
        <div class="d-flex justify-content-between mt-2 small">
            <button type="button" class="btn btn-link p-0" data-otp-change>{{ __('Change number') }}</button>
            <button type="button" class="btn btn-link p-0" data-otp-resend>{{ __('Resend OTP') }}</button>
        </div>
    </div>

    <div class="alert alert-danger mt-3 mb-0" role="alert" data-otp-error hidden></div>
    <div class="alert alert-success mt-3 mb-0" role="status" data-otp-done hidden>{{ __('Mobile number verified.') }}</div>
    <div id="otp-recaptcha-{{ $tokenName }}"></div>
</div>

@once
    <script>
        window.FIREBASE_CONFIG = {!! json_encode($fbConfig, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) !!};
        window.PHONE_OTP_I18N = {!! json_encode([
            'invalid' => __('Enter the 10-digit mobile number.'),
            'sending' => __('Sending…'),
            'sent' => __('We sent a code by SMS.'),
            'wrong_code' => __('That code is not right. Please check and try again.'),
            'expired' => __('That code has expired. Request a new one.'),
            'too_many' => __('Too many attempts. Please wait a few minutes and try again.'),
            'failed' => __('Could not send the code. Please try again.'),
            'not_ready' => __('Mobile sign-in is not available right now. Please use email and password.'),
            'verifying' => __('Verifying…'),
        ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
    </script>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js" defer></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-auth-compat.js" defer></script>
    <script src="{{ asset('js/phone-otp.js') }}?v={{ filemtime(public_path('js/phone-otp.js')) }}" defer></script>
@endonce
@else
<div class="alert alert-light border mb-0 small">{{ __('Mobile OTP sign-in is being set up. Please use your email and password for now.') }}</div>
@endif
