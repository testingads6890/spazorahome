/*
 * Storefront search helpers:
 *  1. Voice search: a microphone button that uses the browser's speech recognition (Chrome, Edge, Android, Safari 14.1+).
 *  2. Recent searches: every submitted search is remembered in this browser and offered again on focus,
 *     and filtered as the shopper types. Clicking a suggestion fills the box and searches.
 * Works on every search box with role="search" and an input named "q". Degrades silently where unsupported.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'shop_recent_searches';
    var MAX_RECENT = 8;
    var i18n = window.SEARCH_I18N || {};
    var t = function (key, fallback) { return i18n[key] || fallback; };
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function readRecent() {
        try {
            var list = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(list) ? list.filter(function (x) { return typeof x === 'string' && x.trim() !== ''; }) : [];
        } catch (e) {
            return [];
        }
    }

    function writeRecent(list) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, MAX_RECENT))); } catch (e) { /* storage blocked */ }
    }

    function remember(query) {
        var q = (query || '').trim();
        if (!q) return;
        var list = readRecent().filter(function (x) { return x.toLowerCase() !== q.toLowerCase(); });
        list.unshift(q);
        writeRecent(list);
    }

    function forgetAll() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
    }

    function el(tag, attrs, text) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function setupForm(form) {
        var input = form.querySelector('input[name="q"]');
        if (!input) return;

        form.classList.add('search-assist');

        // ---- Suggestion panel -------------------------------------------------
        var panel = el('div', { class: 'search-suggest', role: 'listbox', hidden: 'hidden' });
        form.appendChild(panel);

        // Live product and category names from the server, asked for after a short pause while typing.
        var suggestUrl = (form.action || '').replace(/\/(search|shop)(\/.*)?(\?.*)?$/, '/search/suggest');
        var liveTimer = null;
        var liveSeq = 0;

        function fetchLive(typed) {
            clearTimeout(liveTimer);
            if (typed.length < 2 || !suggestUrl) { return; }
            var seq = ++liveSeq;
            liveTimer = setTimeout(function () {
                fetch(suggestUrl + '?q=' + encodeURIComponent(typed), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        // Ignore answers for something the shopper has already changed.
                        if (!data || seq !== liveSeq || input.value.trim().toLowerCase() !== typed) { return; }
                        showLive(data);
                    })
                    .catch(function () { /* offline or blocked: recent searches still work */ });
            }, 200);
        }

        function showLive(data) {
            var groups = [
                [t('products', 'Products'), data.products || [], 'bi-box-seam'],
                [t('categories', 'Categories'), data.categories || [], 'bi-grid'],
            ];
            groups.forEach(function (g) {
                if (g[1].length === 0) { return; }
                panel.appendChild(el('div', { class: 'search-suggest-head' }, g[0]));
                g[1].forEach(function (row) {
                    var link = el('a', { class: 'search-suggest-item', role: 'option', href: row.url });
                    link.appendChild(el('i', { class: 'bi ' + g[2], 'aria-hidden': 'true' }));
                    link.appendChild(el('span', {}, row.name));
                    // mousedown keeps the box focused until the link is followed
                    link.addEventListener('mousedown', function (e) { e.preventDefault(); });
                    link.addEventListener('click', function () { remember(input.value); });
                    panel.appendChild(link);
                });
            });
            if (panel.childNodes.length) { panel.hidden = false; }
        }

        function renderSuggestions() {
            var typed = input.value.trim().toLowerCase();
            var list = readRecent().filter(function (x) {
                return typed === '' || x.toLowerCase().indexOf(typed) !== -1;
            });
            panel.innerHTML = '';
            fetchLive(typed);
            if (list.length === 0) { hide(); return; }

            var head = el('div', { class: 'search-suggest-head' });
            head.appendChild(el('span', {}, typed === '' ? t('recent_searches', 'Recent searches') : t('matching', 'Matching your searches')));
            var clear = el('button', { type: 'button', class: 'search-suggest-clear' }, t('clear', 'Clear'));
            clear.addEventListener('mousedown', function (e) { e.preventDefault(); forgetAll(); hide(); });
            head.appendChild(clear);
            panel.appendChild(head);

            list.forEach(function (q) {
                var item = el('button', { type: 'button', class: 'search-suggest-item', role: 'option' });
                item.appendChild(el('i', { class: 'bi bi-clock-history', 'aria-hidden': 'true' }));
                item.appendChild(el('span', {}, q));
                // mousedown so the choice lands before the input loses focus
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    input.value = q;
                    remember(q); // form.submit() skips the submit event, so save it here
                    form.submit();
                });
                panel.appendChild(item);
            });
            panel.hidden = false;
        }

        function hide() { panel.hidden = true; }

        input.setAttribute('autocomplete', 'off');
        input.addEventListener('focus', renderSuggestions);
        input.addEventListener('click', renderSuggestions);
        input.addEventListener('input', renderSuggestions);
        input.addEventListener('blur', hide);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hide();
        });

        form.addEventListener('submit', function () {
            remember(input.value);
        });

        // ---- Voice search -----------------------------------------------------
        if (SpeechRecognition) {
            var submit = form.querySelector('button[type="submit"]');
            var mic = el('button', {
                type: 'button',
                class: 'voice-btn',
                'aria-label': t('voice_search', 'Search by voice'),
                title: t('voice_search', 'Search by voice')
            });
            mic.appendChild(el('i', { class: 'bi bi-mic', 'aria-hidden': 'true' }));
            var box = form.querySelector('.search-box') || form;
            box.insertBefore(mic, submit || null);

            var recognition = new SpeechRecognition();
            recognition.lang = document.documentElement.lang === 'hi' ? 'hi-IN' : 'en-IN';
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;
            recognition.continuous = false;

            var listening = false;
            var finalText = '';

            recognition.onstart = function () {
                listening = true;
                mic.classList.add('is-listening');
                mic.setAttribute('aria-pressed', 'true');
                input.placeholder = t('listening', 'Listening…');
            };

            recognition.onresult = function (event) {
                var interim = '';
                for (var i = event.resultIndex; i < event.results.length; i++) {
                    var transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) finalText += transcript;
                    else interim += transcript;
                }
                input.value = (finalText + interim).trim();
            };

            recognition.onerror = function (event) {
                var message = event.error === 'not-allowed' || event.error === 'service-not-allowed'
                    ? t('mic_blocked', 'Microphone access is blocked. Allow it in your browser settings to use voice search.')
                    : event.error === 'no-speech'
                        ? t('no_speech', 'We could not hear anything. Please try again.')
                        : t('voice_failed', 'Voice search is not available right now. Please type your search.');
                input.placeholder = message;
                setTimeout(function () { input.placeholder = input.dataset.defaultPlaceholder || ''; }, 4000);
            };

            recognition.onend = function () {
                listening = false;
                mic.classList.remove('is-listening');
                mic.setAttribute('aria-pressed', 'false');
                input.placeholder = input.dataset.defaultPlaceholder || input.placeholder;
                if (finalText.trim() !== '') {
                    input.value = finalText.trim();
                    finalText = '';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            };

            mic.addEventListener('click', function () {
                if (listening) { recognition.stop(); return; }
                finalText = '';
                try {
                    input.dataset.defaultPlaceholder = input.dataset.defaultPlaceholder || input.placeholder;
                    recognition.start();
                } catch (e) {
                    input.placeholder = t('voice_failed', 'Voice search is not available right now. Please type your search.');
                }
            });
        } else {
            // Browser has no speech recognition: no microphone, typed search and history still work.
        }
    }

    function init() {
        document.querySelectorAll('form[role="search"]').forEach(setupForm);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
