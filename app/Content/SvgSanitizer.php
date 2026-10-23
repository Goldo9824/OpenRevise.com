<?php

namespace App\Content;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Diagrams are often SVG. An SVG can carry scripts, so only drawing elements
 * and attributes are kept: no scripts, no event handlers, no outside links.
 */
class SvgSanitizer
{
    private const ELEMENTS = [
        'svg', 'g', 'defs', 'title', 'desc', 'symbol', 'use', 'path', 'rect', 'circle', 'ellipse',
        'line', 'polyline', 'polygon', 'text', 'tspan', 'textPath', 'marker', 'pattern', 'clipPath',
        'mask', 'linearGradient', 'radialGradient', 'stop', 'style', 'image',
    ];

    public function sanitize(string $svg): ?string
    {
        if (stripos($svg, '<!ENTITY') !== false) {
            return null;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NONET);
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return null;
        }

        $allowed = array_map('strtolower', self::ELEMENTS);
        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            /** @var DOMElement $node */
            if (! in_array(strtolower($node->localName), $allowed, true)) {
                $node->parentNode?->removeChild($node);

                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $value = strtolower(preg_replace('/\s+/', '', $attr->nodeValue));

                $isLink = in_array($name, ['href', 'xlink:href'], true);
                $badLink = $isLink && ! str_starts_with($value, '#') && ! str_starts_with($value, 'data:image/png') && ! str_starts_with($value, 'data:image/jpeg');

                if (str_starts_with($name, 'on') || $badLink || str_contains($value, 'javascript:') || str_contains($value, 'url(http')) {
                    $node->removeAttributeNode($attr);
                }
            }

            if (strtolower($node->localName) === 'style') {
                $node->textContent = preg_replace('/@import[^;]+;|url\(\s*[\'"]?\s*(?:https?:|javascript:)[^)]*\)/i', '', $node->textContent);
            }
        }

        return $dom->saveXML($dom->documentElement);
    }
}
