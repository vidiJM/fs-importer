<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Single feature item for [bfs_home_features].
 *
 * Usage:
 * [bfs_home_feature icon_id="123" title="Especialistas" text="..." url="/especialistas/"]
 */
final class HomeFeatureItemShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_home_feature', [self::class, 'render']);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'icon_id'   => 0,
            'icon_size' => 'bfs_icon_128',
            'title'     => '',
            'text'      => '',
            'url'       => '',
        ], (array) $atts, 'bfs_home_feature');

        $icon_id   = absint($atts['icon_id'] ?? 0);
        $icon_size = sanitize_key((string) ($atts['icon_size'] ?? 'bfs_icon_128'));
        if ($icon_size === '') {
            $icon_size = 'bfs_icon_128';
        }

        // Avoid requesting a hard-cropped size by accident.
        // If an unknown size is provided, fall back to 'full' (never cropped).
        $registered_sizes = array_merge(\get_intermediate_image_sizes(), ['full']);
        if (!in_array($icon_size, $registered_sizes, true)) {
            $icon_size = 'full';
        }

        $title = sanitize_text_field((string) $atts['title']);
        $text  = sanitize_text_field((string) $atts['text']);

        $url_raw = (string) $atts['url'];
        $url     = $url_raw !== '' ? esc_url_raw($url_raw) : '';

        $icon_html = '';
        if ($icon_id > 0) {
            $icon_html = wp_get_attachment_image(
                $icon_id,
                $icon_size,
                false,
                [
                    'class'    => 'bfs-home-feature__icon',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'alt'      => '',
                ]
            );
        }

        // Enqueue stylesheet even if used outside wrapper (idempotent).
        wp_enqueue_style('bfs-home-features');

        $data = [
            'icon_html' => $icon_html,
            'title'     => $title,
            'text'      => $text,
            'url'       => $url,
        ];

        ob_start();

        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-home-feature.php';
        if (file_exists($view)) {
            /** @var array<string, string> $data */
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista del item no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }
}
