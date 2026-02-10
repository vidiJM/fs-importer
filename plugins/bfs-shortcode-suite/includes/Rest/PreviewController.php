<?php
declare(strict_types=1);

namespace BFS\Rest;

use BFS\Helpers\BfsPreviewBuilder;

defined('ABSPATH') || exit;

final class PreviewController
{
    private const NAMESPACE = 'bfs/v1';
    private const ROUTE = '/preview';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handle'],
            'permission_callback' => '__return_true',
            'args'                => [
                'genero'    => ['type' => 'string',  'required' => false],
                'ui'        => ['type' => 'string',  'required' => false, 'default' => 'adidas'],

                // paging
                'page'      => ['type' => 'integer', 'required' => false, 'default' => 1],
                'per_page'  => ['type' => 'integer', 'required' => false, 'default' => 12],
                'offset'    => ['type' => 'integer', 'required' => false],

                // ✅ extras (para que SSR == REST)
                'age_group' => ['type' => 'string',  'required' => false],
                'strict'    => ['type' => 'integer', 'required' => false],
                'order'     => ['type' => 'string',  'required' => false],
                'seed'      => ['type' => 'string',  'required' => false],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $req): \WP_REST_Response
    {
        $genero = sanitize_key((string) $req->get_param('genero'));

        $ui = sanitize_key((string) $req->get_param('ui'));
        if (!in_array($ui, ['adidas', 'nike'], true)) {
            $ui = 'adidas';
        }

        $page = max(1, (int) $req->get_param('page'));
        $page = min(500, $page);

        $perPage = (int) $req->get_param('per_page');
        $perPage = max(1, min(48, $perPage));

        // ✅ offset prioritario
        $rawOffset = $req->get_param('offset');
        $hasOffset = $rawOffset !== null && $rawOffset !== '';
        $offset = $hasOffset ? max(0, (int) $rawOffset) : (($page - 1) * $perPage);
        $offset = min(200000, $offset);

        // ✅ extras (alineados con shortcode + JS)
        $ageGroup = sanitize_key((string) $req->get_param('age_group'));
        if (!in_array($ageGroup, ['', 'adult', 'kids'], true)) {
            $ageGroup = '';
        }

        $strict = absint($req->get_param('strict')) === 1;

        $order = sanitize_key((string) $req->get_param('order'));
        $order = in_array($order, ['default', 'random'], true) ? $order : 'default';

        $seed = sanitize_text_field((string) $req->get_param('seed'));

        // cache versioning
        $cacheVer = (int) get_option('bfs_cache_version', 1);

        $cacheKeyPayload = [
            'v'        => $cacheVer,
            'genero'   => $genero,
            'ui'       => $ui,
            'per_page' => $perPage,
            'offset'   => $offset,

            // ✅ para que el cache no mezcle resultados
            'age_group'=> $ageGroup,
            'strict'   => $strict ? 1 : 0,
            'order'    => $order,
            'seed'     => $seed,
        ];

        $cacheKey = 'bfs_grid_' . md5(wp_json_encode($cacheKeyPayload));
        $cached = wp_cache_get($cacheKey, 'bfs_shortcodes');

        if (!is_array($cached) && function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            $transient = get_transient($cacheKey);
            if (is_array($transient)) {
                $cached = $transient;
            }
        }

        if (is_array($cached) && isset($cached['html'], $cached['has_more'])) {
            return rest_ensure_response(['success' => true, 'data' => $cached]);
        }

        // ✅ pedimos 1 extra para has_more exacto
        $productsPlus = BfsPreviewBuilder::buildPaged(
            $genero !== '' ? $genero : null,
            $offset,
            $perPage + 1,
            $ageGroup !== '' ? $ageGroup : null,
            $strict,
            [],          // excludeModels no aplica en REST (solo SSR multi-grids)
            $order,
            $seed
        );

        $hasMore  = count($productsPlus) > $perPage;
        $products = $hasMore ? array_slice($productsPlus, 0, $perPage) : $productsPlus;

        ob_start();
        $bfs_is_ssr = false;

        // el partial usa $products, $genero, $ui
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/partials/bfs-grid-cards.php';
        if (file_exists($view)) {
            include $view;
        }

        $html = (string) ob_get_clean();

        $payload = [
            'html'     => $html,
            'has_more' => $hasMore,
        ];

        wp_cache_set($cacheKey, $payload, 'bfs_shortcodes', 300);

        if (function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            set_transient($cacheKey, $payload, 300);
        }

        return rest_ensure_response(['success' => true, 'data' => $payload]);
    }
}
