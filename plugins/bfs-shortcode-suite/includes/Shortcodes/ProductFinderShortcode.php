<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Shortcode: [bfs_finder]
 */
final class ProductFinderShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_finder', [self::class, 'render']);
    }

    /**
     * @param array<string,mixed> $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts(
            [
                'genero'      => '',
                'limit'       => 3,
                'pool'        => 60,
                'guide_url'   => '',
                'title'       => '',
                'subtitle'    => '',
                'results_url' => '',
                'layout'      => 'modal',
            ],
            $atts
        );

        $genero   = sanitize_text_field((string) $atts['genero']);
        $limit    = max(1, min(6, (int) $atts['limit']));
        $pool     = max($limit, min(200, (int) $atts['pool']));
        $guideUrl = esc_url_raw((string) $atts['guide_url']);
        $title    = sanitize_text_field((string) $atts['title']);
        $subtitle = sanitize_text_field((string) $atts['subtitle']);

        $layout = sanitize_key((string) $atts['layout']);
        if (!in_array($layout, ['modal', 'inline'], true)) {
            $layout = 'modal';
        }

        // results_url: solo permitimos relativo o misma origin (evita open-redirect UX)
        $resultsUrlRaw = trim((string) $atts['results_url']);
        $resultsUrl = '';
        if ($resultsUrlRaw !== '') {
            // Permite rutas relativas tipo /finder/ o ?bfs_results=1
            if (str_starts_with($resultsUrlRaw, '/') || str_starts_with($resultsUrlRaw, '?') || str_starts_with($resultsUrlRaw, '#')) {
                $resultsUrl = $resultsUrlRaw;
            } else {
                $candidate = esc_url_raw($resultsUrlRaw);
                if ($candidate !== '') {
                    $siteHost = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
                    $candHost = (string) wp_parse_url($candidate, PHP_URL_HOST);
                    if ($siteHost !== '' && $candHost !== '' && strtolower($siteHost) === strtolower($candHost)) {
                        $resultsUrl = $candidate;
                    }
                }
            }
        }

        wp_enqueue_style('bfs-finder');
        wp_enqueue_script('bfs-finder');

        $config = [
            'restUrl'     => esc_url_raw(rest_url('bfs/v1/finder')),
            'genero'      => $genero,
            'limit'       => $limit,
            'pool'        => $pool,
            'guideUrl'    => $guideUrl,
            'resultsUrl'  => $resultsUrl,
            'layout'      => $layout,
            'strings'     => [
                'loading' => __('Buscando las mejores opciones…', 'bfs-shortcodes'),
                'error'   => __('No se han podido cargar recomendaciones. Inténtalo de nuevo.', 'bfs-shortcodes'),
                'reset'   => __('Reiniciar', 'bfs-shortcodes'),
                'back'    => __('Atrás', 'bfs-shortcodes'),
                'results' => __('Top 3 recomendadas', 'bfs-shortcodes'),
            ],
            'steps'       => [
                [
                    'id'       => 'surface',
                    'kicker'   => __('Paso 1/4', 'bfs-shortcodes'),
                    'title'    => __('¿Cómo juegas?', 'bfs-shortcodes'),
                    'subtitle' => __('Selecciona la superficie', 'bfs-shortcodes'),
                    'options'  => [
                        ['value' => 'indoor', 'label' => __('Indoor', 'bfs-shortcodes')],
                        ['value' => 'outdoor', 'label' => __('Outdoor', 'bfs-shortcodes')],
                        ['value' => 'artificial_grass', 'label' => __('Césped artificial', 'bfs-shortcodes')],
                        ['value' => 'hard', 'label' => __('Pista dura', 'bfs-shortcodes')],
                    ],
                ],
                [
                    'id'       => 'style',
                    'kicker'   => __('Paso 2/4', 'bfs-shortcodes'),
                    'title'    => __('Estilo de juego', 'bfs-shortcodes'),
                    'subtitle' => __('Elige la sensación principal', 'bfs-shortcodes'),
                    'options'  => [
                        ['value' => 'control', 'label' => __('Control', 'bfs-shortcodes')],
                        ['value' => 'speed', 'label' => __('Velocidad', 'bfs-shortcodes')],
                        ['value' => 'power', 'label' => __('Potencia', 'bfs-shortcodes')],
                        ['value' => 'all', 'label' => __('Equilibrado', 'bfs-shortcodes')],
                    ],
                ],
                [
                    'id'       => 'priority',
                    'kicker'   => __('Paso 3/4', 'bfs-shortcodes'),
                    'title'    => __('Prioridad', 'bfs-shortcodes'),
                    'subtitle' => __('¿Qué valoras más?', 'bfs-shortcodes'),
                    'options'  => [
                        ['value' => 'grip', 'label' => __('Agarre', 'bfs-shortcodes')],
                        ['value' => 'cushion', 'label' => __('Amortiguación', 'bfs-shortcodes')],
                        ['value' => 'durability', 'label' => __('Durabilidad', 'bfs-shortcodes')],
                        ['value' => 'light', 'label' => __('Ligereza', 'bfs-shortcodes')],
                    ],
                ],
                [
                    'id'       => 'budget',
                    'kicker'   => __('Paso 4/4', 'bfs-shortcodes'),
                    'title'    => __('Presupuesto', 'bfs-shortcodes'),
                    'subtitle' => __('Elige un rango', 'bfs-shortcodes'),
                    'options'  => [
                        ['value' => 'low', 'label' => __('< 60€', 'bfs-shortcodes')],
                        ['value' => 'mid', 'label' => __('60–120€', 'bfs-shortcodes')],
                        ['value' => 'high', 'label' => __('> 120€', 'bfs-shortcodes')],
                    ],
                ],
            ],
        ];

        wp_localize_script('bfs-finder', 'bfsFinder', $config);

        ob_start();
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/' . ($layout === 'inline' ? 'bfs-product-finder.php' : 'bfs-product-finder-modal.php');
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista del finder no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }
}
