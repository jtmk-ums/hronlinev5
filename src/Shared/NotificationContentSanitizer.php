<?php

declare(strict_types=1);

namespace App\Shared;

final class NotificationContentSanitizer
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'a' => ['href', 'class', 'title', 'target', 'rel'],
        'br' => [],
        'strong' => [],
        'b' => [],
        'i' => ['class'],
        'small' => [],
        'span' => ['class'],
        'p' => ['class'],
    ];

    public function toPlainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = is_string($text) ? trim($text) : '';

        return $text === '' ? '-' : $text;
    }

    public function toSafeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '-';
        }

        $html = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/isu', '', $html);
        $html = is_string($html) ? $html : '';

        if (!class_exists(\DOMDocument::class)) {
            return nl2br(htmlspecialchars($this->toPlainText($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="notification-content-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('notification-content-root');
        if (!$root instanceof \DOMElement) {
            return nl2br(htmlspecialchars($this->toPlainText($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        $this->cleanChildren($root);

        $safe = '';
        foreach ($root->childNodes as $child) {
            $safe .= $document->saveHTML($child);
        }

        $safe = trim($safe);

        return $safe === '' ? '-' : $safe;
    }

    private function cleanChildren(\DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;

            if ($child instanceof \DOMElement) {
                $name = strtolower($child->nodeName);
                if (!array_key_exists($name, self::ALLOWED)) {
                    $this->cleanChildren($child);
                    $this->unwrap($child);
                    $child = $next;
                    continue;
                }

                $this->cleanAttributes($child, self::ALLOWED[$name]);
                $this->cleanChildren($child);
            }

            $child = $next;
        }
    }

    /**
     * @param list<string> $allowed
     */
    private function cleanAttributes(\DOMElement $element, array $allowed): void
    {
        $remove = [];
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (!in_array($name, $allowed, true) || str_starts_with($name, 'on') || $name === 'style') {
                $remove[] = $attribute->name;
                continue;
            }

            if ($name === 'href' && !$this->isSafeHref($attribute->value)) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $name) {
            $element->removeAttribute($name);
        }

        if (strtolower($element->nodeName) === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }

        if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
            return true;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        if ($scheme === null) {
            return !str_starts_with($href, '#');
        }

        return in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }

    private function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
