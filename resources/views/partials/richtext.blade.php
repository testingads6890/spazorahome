{{-- CKEditor 5 for every <textarea class="js-richtext">. Loaded only on pages that have one. Text is cleaned on the server too (App\Support\Html). --}}
<script>
(function () {
    var areas = document.querySelectorAll('textarea.js-richtext');
    if (!areas.length) return;

    var VERSION = '49.0.0';
    var base = 'https://cdn.jsdelivr.net/npm/ckeditor5@' + VERSION + '/dist/browser/';
    var css = document.createElement('link');
    css.rel = 'stylesheet'; css.href = base + 'ckeditor5.css';
    document.head.appendChild(css);

    var style = document.createElement('style');
    style.textContent = '.ck-editor__editable_inline{min-height:140px}.ck-editor__editable_inline.rt-tall{min-height:320px}.ck.ck-editor{max-width:100%}.rt-box.is-invalid .ck-editor__editable{border-color:#dc3545!important}';
    document.head.appendChild(style);

    var s = document.createElement('script');
    s.src = base + 'ckeditor5.umd.js';
    s.onload = start;
    s.onerror = function () { /* offline: the plain textarea keeps working */ };
    document.head.appendChild(s);

    function start() {
        var C = window.CKEDITOR;
        if (!C || !C.ClassicEditor) return;
        var plugins = ['Essentials', 'Paragraph', 'Heading', 'Bold', 'Italic', 'Underline', 'Strikethrough', 'Link', 'AutoLink', 'List', 'BlockQuote', 'Table', 'TableToolbar', 'Undo', 'RemoveFormat', 'HorizontalLine', 'PasteFromOffice', 'Autoformat', 'SourceEditing']
            .map(function (n) { return C[n]; }).filter(Boolean);

        areas.forEach(function (ta) {
            var tall = ta.dataset.size === 'tall';
            var required = ta.hasAttribute('required');
            ta.removeAttribute('required'); // a hidden field cannot be focused, so the browser would block the form

            C.ClassicEditor.create(ta, {
                licenseKey: 'GPL',
                plugins: plugins,
                toolbar: { items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'horizontalLine', '|', 'removeFormat', 'sourceEditing'], shouldNotGroupWhenFull: false },
                heading: { options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
                ] },
                link: { addTargetToExternalLinks: true, defaultProtocol: 'https://' },
                placeholder: ta.getAttribute('placeholder') || ''
            }).then(function (editor) {
                if (tall) editor.ui.view.editable.element.classList.add('rt-tall');
                var form = ta.form;
                editor.model.document.on('change:data', function () { editor.updateSourceElement(); });
                if (form) {
                    form.addEventListener('submit', function (e) {
                        editor.updateSourceElement();
                        var empty = editor.getData().replace(/<[^>]*>|&nbsp;|\s/g, '') === '';
                        if (required && empty) {
                            e.preventDefault();
                            editor.ui.view.element.classList.add('is-invalid');
                            e.stopImmediatePropagation();
                            editor.editing.view.focus();
                        }
                    }, true);
                }
            }).catch(function (err) { console.error('CKEditor', err); ta.style.display = ''; });
        });
    }
})();
</script>
