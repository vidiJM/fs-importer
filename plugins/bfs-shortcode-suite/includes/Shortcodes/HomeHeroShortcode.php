<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Home Hero section shortcode.
 *
 * Usage:
 * [bfs_home_hero]
 * [bfs_home_hero title="Mejores botas de fútbol sala según tu estilo de juego" subtitle="Analizamos..." button_text="Descubrir las mejores botas" button_url="/finder/" image_id="123" image_size="large" align="left"]
 */
final class HomeHeroShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_home_hero', [self::class, 'render']);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'title'       => __('Mejores botas de fútbol sala según tu estilo de juego', 'bfs-shortcodes'),
            'subtitle'    => __('Analizamos botas por tipo de jugador, superficie y nivel para ayudarte a elegir mejor.', 'bfs-shortcodes'),
            'button_text' => __('Descubrir las mejores botas', 'bfs-shortcodes'),
            'button_url'  => '',
            'image_url'   => '',
            'image_id'    => 0,
            'image_size'  => 'large',
            'align'       => 'left', // left|center
        ], (array) $atts, 'bfs_home_hero');

        $title       = sanitize_text_field((string) $atts['title']);
        $subtitle    = sanitize_text_field((string) $atts['subtitle']);
        $button_text = sanitize_text_field((string) $atts['button_text']);

        $button_url_raw = (string) $atts['button_url'];
        $button_url     = $button_url_raw !== '' ? esc_url_raw($button_url_raw) : '';

        $image_url_raw = (string) $atts['image_url'];
        $image_url     = $image_url_raw !== '' ? esc_url_raw($image_url_raw) : '';


        $image_id   = absint($atts['image_id'] ?? 0);
        $image_size = sanitize_key((string) ($atts['image_size'] ?? 'large'));

        $image_html = '';
        if ($image_id > 0) {
            $image_html = wp_get_attachment_image(
                $image_id,
                $image_size !== '' ? $image_size : 'large',
                false,
                [
                    'class'    => 'bfs-home-hero__img',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                    'fetchpriority' => 'high',
                ]
            );
        } elseif ($image_url !== '') {
            // Backward compatibility (legacy image_url).
            $image_html = sprintf(
                '<img class="bfs-home-hero__img" src="%s" alt="" loading="eager" fetchpriority="high" decoding="async">',
                esc_url($image_url)
            );
        }
        $align = sanitize_key((string) $atts['align']);
        if (!in_array($align, ['left', 'center'], true)) {
            $align = 'left';
        }

        // Performance: load assets only when shortcode is present.
        wp_enqueue_style('bfs-home-hero');

        $data = [
            'title'       => $title,
            'subtitle'    => $subtitle,
            'button_text' => $button_text,
            'button_url'  => $button_url,
            'image_url'   => $image_url,
            'image_id'    => (string) $image_id,
            'image_size'  => $image_size,
            'image_html'  => $image_html,
            'align'       => $align,
        ];

        ob_start();

        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-home-hero.php';
        if (file_exists($view)) {
            /** @var array<string, string> $data */
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista del hero no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }
}
