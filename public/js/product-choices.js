/*
 * Product form helpers: (1) a typed "Other" box appears only when "Other" is picked in its dropdown;
 * (2) the mandatory dispatch-location dropdowns fill each other, country > state > district > city > PIN.
 */
(function () {
    'use strict';

    function otherBoxes() {
        document.querySelectorAll('select[data-other-for]').forEach(function (sel) {
            var box = document.getElementById(sel.getAttribute('data-other-for'));
            if (!box) return;
            var sync = function () {
                var on = sel.value === '__other';
                box.hidden = !on;
                if (!on) box.value = box.value; // keep the typed text if they switch back and forth
            };
            sel.addEventListener('change', sync);
            sync();
        });
    }

    function fillLevel(select, options, placeholder) {
        var keep = select.value;
        select.innerHTML = '';
        var first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        select.appendChild(first);
        options.forEach(function (o) {
            var opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.name;
            if (String(o.id) === keep) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function locationChain(root) {
        var order = ['countries', 'states', 'districts', 'cities', 'pincodes'];
        var selects = {};
        root.querySelectorAll('select[data-location-level]').forEach(function (s) { selects[s.getAttribute('data-location-level')] = s; });

        function note(level, show) {
            var s = selects[level];
            if (!s) return;
            var n = s.parentNode.querySelector('[data-location-empty]');
            if (n) n.classList.toggle('d-none', !show);
        }

        function load(level, parentId, done) {
            var url = '/location-options/' + level + (parentId ? '/' + parentId : '');
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (list) { done(list); })
                .catch(function () { done([]); });
        }

        function cascadeFrom(index) {
            var parentLevel = order[index];
            var childLevel = order[index + 1];
            if (!childLevel || !selects[childLevel]) return;
            var parent = selects[parentLevel];
            var child = selects[childLevel];
            var placeholder = child.options[0] ? child.options[0].textContent : 'Choose';
            if (!parent.value) { fillLevel(child, [], placeholder); note(childLevel, false); cascadeFrom(index + 1); return; }
            load(childLevel, parent.value, function (list) {
                fillLevel(child, list, placeholder);
                note(childLevel, list.length === 0);
                cascadeFrom(index + 1);
            });
        }

        order.forEach(function (level, i) {
            if (!selects[level]) return;
            selects[level].addEventListener('change', function () {
                // Changing a level resets everything below it.
                for (var j = i + 1; j < order.length; j++) {
                    if (selects[order[j]]) fillLevel(selects[order[j]], [], selects[order[j]].options[0].textContent);
                }
                cascadeFrom(i);
            });
        });
    }

    function init() {
        otherBoxes();
        document.querySelectorAll('[data-location-select]').forEach(locationChain);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
