<?php

namespace Ernestdefoe\Festoon\Style;

use DOMDocument;
use DOMElement;

/**
 * An administrator's own SVG picture, made safe to draw.
 *
 * The picture is only ever drawn as a CSS background image, and browsers run
 * no script and load nothing external from an SVG used as an image. That is
 * the real protection. The clean-up here is a second layer: it keeps a short
 * list of drawing elements and attributes, drops everything else (script,
 * foreignObject, event handlers, links and url() to anything but the picture
 * itself), and refuses a DOCTYPE so no entity is ever expanded.
 *
 * An image cannot inherit the page's colors, so currentColor is replaced
 * with the look's main color instead.
 */
final class Svg
{
    public const MAX = 20000;

    private const NS = 'http://www.w3.org/2000/svg';

    private const ELEMENTS = [
        'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'text', 'tspan',
        'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern', 'symbol', 'use', 'title', 'desc',
    ];

    private const ATTRIBUTES = [
        'id', 'class', 'viewbox', 'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points',
        'fill', 'fill-opacity', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit', 'opacity', 'transform', 'style',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'spreadmethod', 'fx', 'fy',
        'clip-path', 'mask', 'patternunits', 'patterntransform', 'preserveaspectratio', 'href', 'font-size', 'font-family',
        'font-weight', 'text-anchor', 'dominant-baseline', 'dx', 'dy', 'xmlns', 'version', 'color',
    ];

    /** The picture as a data: URI, or null if there is none or it is not a usable SVG. */
    public static function uri(string $svg, string $color): ?string
    {
        $clean = self::clean($svg);

        return $clean === null ? null : 'data:image/svg+xml,'.rawurlencode(preg_replace('/currentcolor/i', $color, $clean) ?? '');
    }

    public static function clean(string $svg): ?string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > self::MAX || stripos($svg, '<!DOCTYPE') !== false || stripos($svg, '<!ENTITY') !== false) {
            return null;
        }

        $doc = new DOMDocument();
        $internal = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($internal);

        $root = $doc->documentElement;
        if (! $loaded || $doc->doctype !== null || ! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            return null;
        }

        self::walk($root);

        // Pasted pictures often leave the namespace off; an image needs it.
        if ($root->namespaceURI !== self::NS) {
            $out = new DOMDocument();
            $out->appendChild(self::copy($out, $root));
            $doc = $out;
        }

        $xml = $doc->saveXML($doc->documentElement);

        return $xml === false ? null : $xml;
    }

    private static function walk(DOMElement $el): void
    {
        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $name = strtolower($attr->localName);
            $value = $attr->value;
            $safe = in_array($name, self::ATTRIBUTES, true)
                && ! preg_match('/javascript:|data:|expression\s*\(|@import/i', preg_replace('/[\s\x00-\x1f]/', '', $value) ?? '')
                // url() only to something inside the picture, like a gradient
                && ! preg_match('/url\s*\(\s*[\'"]?\s*[^\'"\s#)]/i', $value)
                && ($name !== 'href' || str_starts_with(trim($value), '#'));

            if (! $safe) {
                $el->removeAttributeNode($attr);
            }
        }

        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if (in_array(strtolower($child->localName), self::ELEMENTS, true) && in_array($child->namespaceURI, [null, self::NS], true)) {
                    self::walk($child);
                } else {
                    $el->removeChild($child);
                }
            } elseif (! in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                $el->removeChild($child); // comments, processing instructions
            }
        }
    }

    /** The same tree, rebuilt in the SVG namespace. */
    private static function copy(DOMDocument $doc, DOMElement $from): DOMElement
    {
        $to = $doc->createElementNS(self::NS, $from->localName);
        foreach ($from->attributes ?? [] as $attr) {
            if ($attr->localName !== 'xmlns') {
                $to->setAttribute($attr->localName, $attr->value);
            }
        }
        foreach ($from->childNodes as $child) {
            $to->appendChild($child instanceof DOMElement ? self::copy($doc, $child) : $doc->createTextNode($child->textContent));
        }

        return $to;
    }
}
