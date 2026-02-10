<?php
declare(strict_types=1);

namespace BFS\Helpers;

defined('ABSPATH') || exit;

/**
 * Sanitiza y normaliza descripciones HTML provenientes de feeds (a menudo escapadas con &lt;...&gt;).
 * Seguridad primero: decodifica entidades y aplica wp_kses con allowlist.
 */
final class BfsHtmlSanitizer
{
    public static function sanitizeDescription(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Viene frecuentemente escapado: &lt;p&gt;... => decodificar
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalizar títulos frecuentes escritos como <b>...</b>
        $html = preg_replace('/<\s*b\s*>\s*(Características|Caracteristicas)\s*<\s*\/b\s*>/iu', '<h3>$1</h3>', $html) ?? $html;
        $html = preg_replace('/<\s*b\s*>\s*(Cómo cuidar[^<]*)\s*<\s*\/b\s*>/iu', '<h3>$1</h3>', $html) ?? $html;
        $html = preg_replace('/<\s*b\s*>\s*Composición\s*<\s*\/b\s*>\s*:\s*/iu', '<h3>Composición</h3><p>', $html) ?? $html;

        // Allowlist segura
        $allowed = [
            'p'      => ['class' => true],
            'br'     => [],
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'i'      => [],
            'ul'     => ['class' => true],
            'ol'     => ['class' => true],
            'li'     => ['class' => true],
            'h2'     => ['class' => true],
            'h3'     => ['class' => true],
            'h4'     => ['class' => true],
            'a'      => ['href' => true, 'rel' => true, 'target' => true],
            'div'    => ['class' => true],
            'span'   => ['class' => true],
            'table'  => ['class' => true],
            'thead'  => [],
            'tbody'  => [],
            'tr'     => [],
            'th'     => [],
            'td'     => [],
        ];

        $clean = wp_kses($html, $allowed);

        // Hardening links: rel si falta
        $clean = preg_replace_callback('/<a\b[^>]*>/i', static function (array $m): string {
            $tag = (string) $m[0];
            if (stripos($tag, 'rel=') === false) {
                $tag = rtrim($tag, '>') . ' rel="nofollow noopener">';
            }
            return $tag;
        }, $clean) ?? $clean;

        return $clean;
    }
}
