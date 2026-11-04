/*
 * Mobile OTP widget (Firebase Phone Auth). Sends an SMS code, checks it, then hands the Firebase ID token to the
 * server: either by submitting the form (sign-in / sign-up) or by storing it in a hidden field (seller application).
 * The Firebase session is signed out right after, so the server session is the only one that matters.
 */
(function () {
    'use strict';

    var I = window.PHONE_OTP_I18N || {};
    var app = null, auth = null;
    var verifiers = {};

    function ensureFirebase() {
        if (!window.FIREBASE_CONFIG || !window.firebase) return false;
        if (!app) {
            app = firebase.apps.length ? firebase.app() : firebase.initializeApp(window.FIREBASE_CONFIG);
            auth = app.auth();
        }
        return true;
    }

    function digitsOnly(v) { return (v || '').replace(/\D/g, ''); }

    /** Indian mobile typed in any usual way -> +91XXXXXXXXXX, or null. */
    function toE164(v) {
        var d = digitsOnly(v);
        if (d.length === 12 && d.indexOf('91') === 0) d = d.slice(2);
        if (d.length === 11 && d.charAt(0) === '0') d = d.slice(1);
        return /^[6-9]\d{9}$/.test(d) ? '+91' + d : null;
    }

    function friendly(err) {
        var code = err && err.code ? err.code : '';
        if (code === 'auth/invalid-phone-number') return I.invalid;
        if (code === 'auth/too-many-requests' || code === 'auth/quota-exceeded') return I.too_many;
        if (code === 'auth/invalid-verification-code') return I.wrong_code;
        if (code === 'auth/code-expired' || code === 'auth/session-expired') return I.expired;
        return I.failed;
    }

    function init(root) {
        var mode = root.dataset.mode || 'submit';
        var tokenName = root.dataset.tokenName || 'id_token';
        var form = root.closest('form');
        var phoneInput = root.querySelector('[data-otp-phone]');
        var codeInput = root.querySelector('[data-otp-code]');
        var sendBtn = root.querySelector('[data-otp-send]');
        var verifyBtn = root.querySelector('[data-otp-verify]');
        var resendBtn = root.querySelector('[data-otp-resend]');
        var changeBtn = root.querySelector('[data-otp-change]');
        var errBox = root.querySelector('[data-otp-error]');
        var doneBox = root.querySelector('[data-otp-done]');
        var stepPhone = root.querySelector('[data-otp-step="phone"]');
        var stepCode = root.querySelector('[data-otp-step="code"]');
        var sentTo = root.querySelector('[data-otp-sent-to]');
        var recaptchaId = 'otp-recaptcha-' + tokenName;
        var confirmation = null;
        var sendLabel = sendBtn.textContent, verifyLabel = verifyBtn.textContent;

        // Seller application: copy the phone the person already typed in the form.
        if (root.dataset.prefill && form) {
            var src = form.querySelector('[name="' + root.dataset.prefill + '"]');
            if (src && src.value) phoneInput.value = digitsOnly(src.value).slice(-10);
        }

        function tokenField() {
            var f = form.querySelector('input[name="' + tokenName + '"]');
            if (!f) {
                f = document.createElement('input');
                f.type = 'hidden';
                f.name = tokenName;
                form.appendChild(f);
            }
            return f;
        }

        function clearVerified() {
            if (form) { var f = form.querySelector('input[name="' + tokenName + '"]'); if (f) f.value = ''; }
            doneBox.hidden = true;
            root.dataset.verified = '';
        }

        function showError(msg) { errBox.textContent = msg; errBox.hidden = false; }
        function clearError() { errBox.hidden = true; }
        function busy(btn, on, label) { btn.disabled = on; btn.textContent = on ? label : btn.dataset.label; }
        sendBtn.dataset.label = sendLabel;
        verifyBtn.dataset.label = verifyLabel;
        resendBtn.dataset.label = resendBtn.textContent;

        function verifier() {
            if (!verifiers[recaptchaId]) {
                verifiers[recaptchaId] = new firebase.auth.RecaptchaVerifier(recaptchaId, { size: 'invisible' });
            }
            return verifiers[recaptchaId];
        }

        function resetVerifier() {
            try { if (verifiers[recaptchaId]) verifiers[recaptchaId].clear(); } catch (e) { /* already cleared */ }
            delete verifiers[recaptchaId];
        }

        function sendCode() {
            clearError();
            if (!ensureFirebase()) { showError(I.not_ready); return; }
            var e164 = toE164(phoneInput.value);
            if (!e164) { showError(I.invalid); return; }
            busy(sendBtn, true, I.sending);
            auth.signInWithPhoneNumber(e164, verifier())
                .then(function (c) {
                    confirmation = c;
                    sentTo.textContent = '+91 ' + e164.slice(3);
                    stepPhone.hidden = true;
                    stepCode.hidden = false;
                    codeInput.value = '';
                    codeInput.focus();
                })
                .catch(function (err) { resetVerifier(); showError(friendly(err)); })
                .then(function () { busy(sendBtn, false); });
        }

        function verifyCode() {
            clearError();
            if (mode === 'submit' && form && !form.reportValidity()) return;
            var code = digitsOnly(codeInput.value);
            if (code.length !== 6) { showError(I.wrong_code); return; }
            if (!confirmation) { showError(I.expired); return; }
            busy(verifyBtn, true, I.verifying);

            confirmation.confirm(code)
                .then(function (cred) { return cred.user.getIdToken(); })
                .then(function (idToken) {
                    // Only the server session should remain: sign the Firebase user out again.
                    return auth.signOut().catch(function () {}).then(function () { return idToken; });
                })
                .then(function (idToken) {
                    if (!form) throw new Error('no form');
                    tokenField().value = idToken;
                    if (mode === 'submit') { form.submit(); return; }
                    stepCode.hidden = true;
                    stepPhone.hidden = true;
                    doneBox.hidden = false;
                    root.dataset.verified = '1';
                })
                .catch(function (err) { showError(friendly(err)); })
                .then(function () { busy(verifyBtn, false); });
        }

        sendBtn.addEventListener('click', sendCode);
        verifyBtn.addEventListener('click', verifyCode);
        resendBtn.addEventListener('click', sendCode);
        codeInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); verifyCode(); } });
        changeBtn.addEventListener('click', function () {
            confirmation = null;
            clearVerified();
            clearError();
            stepCode.hidden = true;
            stepPhone.hidden = false;
            phoneInput.focus();
        });
        // Enter in the number box sends the code; it must not submit the surrounding form.
        phoneInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendCode(); } });
        // Changing the number after verifying invalidates the verified token.
        phoneInput.addEventListener('input', function () { if (root.dataset.verified === '1') clearVerified(); });
    }

    /** Email/password vs mobile OTP tabs on the sign-in and sign-up pages. */
    function tabs() {
        document.querySelectorAll('[data-auth-tabs]').forEach(function (bar) {
            var scope = bar.parentNode;
            bar.querySelectorAll('[data-auth-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    bar.querySelectorAll('[data-auth-tab]').forEach(function (b) {
                        var on = b === btn;
                        b.classList.toggle('active', on);
                        b.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    scope.querySelectorAll('[data-auth-pane]').forEach(function (p) {
                        p.hidden = p.dataset.authPane !== btn.dataset.authTab;
                    });
                });
            });
        });
    }

    function start() {
        tabs();
        document.querySelectorAll('[data-phone-otp]').forEach(init);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
