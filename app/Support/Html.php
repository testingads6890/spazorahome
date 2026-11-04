<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Rich text (CKEditor) safety net. Text typed in the editors is cleaned to a short list of
 * harmless tags before it is saved and again before it is shown, so no script, style or
 * event handler can ever reach a visitor — whatever the browser sent.
 */
class Html
{
    /** tag => allowed attributes */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [], 'mark' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'code' => [], 'pre' => [],
        'ul' => [], 'ol' => ['start', 'type'], 'li' => [],
        'a' => ['href', 'target', 'rel', 'title'],
        'figure' => ['class'], 'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    /** Tags removed together with everything inside them. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'svg', 'math', 'noscript', 'template'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        if (! preg_match('/<[a-z!\/]/i', $html)) {
            return $html; // plain text stays plain; render() turns it into paragraphs
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }
        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim(preg_replace('/(<p>(\s|&nbsp;|<br>)*<\/p>)+\s*$/i', '', $out));
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, self::DROP, true)) {
                    $node->removeChild($child);
                    continue;
                }
                self::walk($child);
                if (! array_key_exists($tag, self::ALLOWED)) {
                    // Unknown tag (div, span, font...): keep the text, lose the wrapper.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                self::cleanAttributes($child, $tag);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            $keep = in_array($name, self::ALLOWED[$tag], true);
            if ($keep && in_array($name, ['href', 'src'], true)) {
                $keep = self::safeUrl($value, $name === 'src');
            }
            if ($keep && $tag === 'figure' && $name === 'class') {
                $keep = preg_match('/^(table|image)(\s+[\w-]+)*$/', $value) === 1;
            }
            if (! $keep) {
                $el->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener noreferrer nofollow');
            if ($el->getAttribute('target') && $el->getAttribute('target') !== '_blank') {
                $el->removeAttribute('target');
            }
        }
        if ($tag === 'img' && ! $el->hasAttribute('src')) {
            $el->parentNode?->removeChild($el);
        }
    }

    private static function safeUrl(string $url, bool $image): bool
    {
        $u = preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode($url));
        if ($u === '') {
            return false;
        }
        if ($image) {
            return (bool) preg_match('#^(https?:)?//#i', $u) || str_starts_with($u, '/');
        }

        return (bool) preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $u);
    }

    /** Safe HTML ready to print with {!! !!}. Old plain-text descriptions become paragraphs. */
    public static function render(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        if (! preg_match('/<[a-z!\/]/i', $text)) {
            return collect(preg_split('/\R{2,}/', $text))->map(fn ($p) => '<p>' . nl2br(e(trim($p))) . '</p>')->implode("\n");
        }

        return self::clean($text);
    }

    /** Plain text (no tags, entities decoded, spaces collapsed): meta tags, cards, search. */
    public static function text(?string $html, ?int $limit = null): string
    {
        $t = preg_replace('/<(br|\/p|\/li|\/h[1-6]|\/tr|\/blockquote)\b[^>]*>/i', ' ', (string) $html);
        $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $limit ? Str::limit($t, $limit) : $t;
    }
}
