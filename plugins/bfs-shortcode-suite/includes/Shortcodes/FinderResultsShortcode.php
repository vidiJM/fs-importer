<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Shortcode: [bfs_finder_results]
 *
 * Results page renderer. It reads query params and fetches HTML via REST.
 *
 * Recommended URL params:
 * - genero, limit, pool, surface, style, priority, budget, guide_url
 */
final class FinderResultsShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_finder_results', [self::class, 'render']);
    }

    /**
     * @param array<string,mixed> $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts(
            [
                'genero'    => '',
                'limit'     => 3,
                'pool'      => 60,
                'guide_url' => '',
                'title'     => '',
                'subtitle'  => '',
            ],
            $atts
        );

        $genero   = sanitize_text_field((string) $atts['genero']);
        $limit    = max(1, min(6, (int) $atts['limit']));
        $pool     = max($limit, min(200, (int) $atts['pool']));
        $guideUrl = esc_url_raw((string) $atts['guide_url']);
        $title    = sanitize_text_field((string) $atts['title']);
        $subtitle = sanitize_text_field((string) $atts['subtitle']);

        wp_enqueue_style('bfs-finder');
        wp_enqueue_script('bfs-finder');

        $config = [
            'restUrl'  => esc_url_raw(rest_url('bfs/v1/finder')),
            'genero'   => $genero,
            'limit'    => $limit,
            'pool'     => $pool,
            'guideUrl' => $guideUrl,
            'strings'  => [
                'loading' => __('Buscando las mejores opciones…', 'bfs-shortcodes'),
                'error'   => __('No se han podido cargar recomendaciones. Inténtalo de nuevo.', 'bfs-shortcodes'),
            ],
        ];

        wp_localize_script('bfs-finder', 'bfsFinder', $config);

        ob_start();
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-finder-results-page.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista de resultados no encontrada.', 'bfs-shortcodes') . '</p>';
        }
        return (string) ob_get_clean();
    }
}
