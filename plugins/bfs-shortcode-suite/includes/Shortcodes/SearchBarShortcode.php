<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

final class SearchBarShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_search_bar', [self::class, 'render']);
    }

    /**
     * Render del buscador premium.
     *
     * Uso:
     * [bfs_search_bar placeholder="Buscar productos…" min_chars="2" max_results="12" history_limit="8"]
     *
     * @param array|string $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'placeholder'   => __('Buscar…', 'bfs-shortcodes'),
            'min_chars'     => 2,
            'max_results'   => 12,
            'history_limit' => 8,
        ], $atts);

        $placeholder   = sanitize_text_field((string) $atts['placeholder']);
        $minChars      = max(1, (int) $atts['min_chars']);
        $maxResults    = min(24, max(1, (int) $atts['max_results']));
        $historyLimit  = min(20, max(1, (int) $atts['history_limit']));

        // Encolar assets solo si se usa el shortcode
        wp_enqueue_style('bfs-search');
        wp_enqueue_script('bfs-search');

        // Pasar config al JS (REST + strings + límites)
        $data = [
            'restUrl'      => esc_url_raw(rest_url('bfs/v1')),
            'nonce'        => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'minChars'     => $minChars,
            'maxResults'   => $maxResults,
            'historyLimit' => $historyLimit,
            'i18n'         => [
                'search'        => __('Buscar', 'bfs-shortcodes'),
                'cancel'        => __('Cancelar', 'bfs-shortcodes'),
                'recent'        => __('Búsquedas recientes', 'bfs-shortcodes'),
                'clearAll'      => __('Borrar todo', 'bfs-shortcodes'),
                'suggestions'   => __('Sugerencias', 'bfs-shortcodes'),
                'noResults'     => __('Sin resultados', 'bfs-shortcodes'),
                'minCharsHint'  => sprintf(__('Escribe al menos %d caracteres…', 'bfs-shortcodes'), $minChars),
                'price'         => __('Precio', 'bfs-shortcodes'),
                'brand'         => __('Marca', 'bfs-shortcodes'),
                'size'          => __('Talla', 'bfs-shortcodes'),
                'color'         => __('Color', 'bfs-shortcodes'),
                'moreFilters'   => __('Más filtros', 'bfs-shortcodes'),
                'apply'         => __('Aplicar', 'bfs-shortcodes'),
                'reset'         => __('Reset', 'bfs-shortcodes'),
                'min'           => __('Min', 'bfs-shortcodes'),
                'max'           => __('Max', 'bfs-shortcodes'),
                'inStock'       => __('En stock', 'bfs-shortcodes'),
            ],
        ];

        wp_localize_script('bfs-search', 'BFS_SEARCH', $data);

        ob_start();

        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-search-bar.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista del buscador no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }
}
