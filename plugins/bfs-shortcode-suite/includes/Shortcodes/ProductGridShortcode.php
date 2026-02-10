<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

use BFS\Helpers\BfsPreviewBuilder;

defined('ABSPATH') || exit;

final class ProductGridShortcode
{
    /** @var array<string,true> */
    private static array $wpa_seen_models = [];

    public static function register(): void
    {
        add_shortcode('bfs_grid', [self::class, 'render']);
    }

    /**
     * [bfs_grid genero="hombre" age_group="adult" ui="adidas" strict="0" order="default" seed=""]
     *
     * Infinite behavior:
     * - SSR: 4 items
     * - Mobile: +3 on scroll, then CTA
     * - Desktop: +4 on scroll, +3 on scroll, then CTA
     *
     * @param array<string,mixed> $atts
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts(
            [
                'genero'      => '',
                'age_group'   => '',
                'strict'      => '0',
                'ui'          => 'adidas',
                'order'       => 'default',
                'seed'        => '',
                'cta_url'     => '', // opcional: URL del buscador
                'cta_text'    => '', // opcional: texto CTA
                'infinite'    => 1,  // default ON para grid
            ],
            $atts
        );

        $genero = sanitize_key((string) $atts['genero']);

        $ageGroup = sanitize_key((string) $atts['age_group']);
        if ($ageGroup === '' && $genero !== '') {
            $ageGroup = 'adult';
        }
        if (!in_array($ageGroup, ['', 'adult', 'kids'], true)) {
            $ageGroup = '';
        }

        $strict = absint($atts['strict']) === 1 ? 1 : 0;

        $ui = sanitize_key((string) $atts['ui']);
        if (!in_array($ui, ['adidas', 'nike'], true)) {
            $ui = 'adidas';
        }

        $order = sanitize_key((string) $atts['order']);
        $order = in_array($order, ['default', 'random'], true) ? $order : 'default';

        $seed = sanitize_text_field((string) $atts['seed']);
        if ($seed === '') {
            $seed = gmdate('Y-m-d');
        }

        $infinite = absint($atts['infinite']) === 1 ? 1 : 0;

        // CTA
        $ctaUrl = esc_url_raw((string) $atts['cta_url']);
        if ($ctaUrl === '') {
            // fallback razonable: homepage + /botas/ (ajústalo si tu slug es otro)
            $ctaUrl = home_url('/botas/');
        }

        $ctaText = sanitize_text_field((string) $atts['cta_text']);
        if ($ctaText === '') {
            $ctaText = __('Ver más botas', 'bfs-shortcodes');
        }

        // Assets solo aquí
        wp_enqueue_style('bfs-grid');
        wp_enqueue_script('bfs-grid');

        // SSR inicial: SIEMPRE 4 (mejor LCP/CLS)
        $ssrCount = 4;

        // Excluir modelos ya pintados si hay varios grids en la misma página
        $exclude = array_keys(self::$wpa_seen_models);

        // SSR: build legacy a 4. El “load more” va por REST /preview.
        $products = BfsPreviewBuilder::build(
            $genero !== '' ? $genero : null,
            $ssrCount,
            $ageGroup !== '' ? $ageGroup : null,
            (bool) $strict,
            $exclude,
            $order,
            $seed
        );

        foreach ($products as $p) {
            $key = trim((string) ($p->title ?? ''));
            if ($key !== '') {
                self::$wpa_seen_models[$key] = true;
            }
        }

        // Evitar conflictos si hay varios grids: id único por instancia
        $gridId = 'bfs-grid-' . wp_generate_uuid4();

        // Config para JS (por instancia, se lee desde el DOM; evita colisiones de wp_localize_script)
        $gridConfig = [
            'gridId'     => $gridId,
            'restUrl'    => esc_url_raw(rest_url('bfs/v1/preview')),
            'ui'         => $ui,
            'genero'     => $genero,
            // ✅ usar snake_case para coincidir con REST
            'age_group'  => $ageGroup,
            'strict'     => $strict,
            'order'      => $order,
            'seed'       => $seed,

            // “Plan” de carga: SSR ya fue 4. Aquí definimos lo que se añade.
            'batches' => [
                'desktop' => [4, 3],
                'mobile'  => [3],
            ],

            // CTA
            'ctaUrl'     => $ctaUrl,
            'ctaText'    => $ctaText,

            // UX
            'infinite'   => $infinite,

            // Info útil para JS
            'ssrCount'   => $ssrCount,
        ];

        // Variables para la vista
        $bfs_grid_id          = $gridId;
        $bfs_ui               = $ui;
        $bfs_genero           = $genero;
        $bfs_cta_url          = $ctaUrl;
        $bfs_cta_text         = $ctaText;
        $bfs_infinite         = $infinite;
        $bfs_grid_config_json = wp_json_encode($gridConfig, JSON_UNESCAPED_SLASHES);

        ob_start();
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-product-grid.php';

        if (file_exists($view)) {
            include $view;
        } else {
            // fallback mínimo para no romper si falta la vista
            echo '<div id="' . esc_attr($gridId) . '" class="bfs-grid bfs-grid--ui-' . esc_attr($ui) . '" data-bfs-grid data-bfs-grid-config="' . esc_attr((string) $bfs_grid_config_json) . '">';
            echo '<div class="bfs-grid__items"></div>';
            echo '</div>';
        }

        return (string) ob_get_clean();
    }
}
