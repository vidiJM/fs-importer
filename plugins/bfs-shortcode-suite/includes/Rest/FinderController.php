<?php
declare(strict_types=1);

namespace BFS\Rest;

use BFS\Helpers\BfsPreviewBuilder;

defined('ABSPATH') || exit;

/**
 * REST endpoint to return TOP recommendations for the finder/quiz.
 *
 * GET /wp-json/bfs/v1/finder?genero=hombre&limit=3&pool=60&budget=mid&guide_url=...
 */
final class FinderController
{
    private const NAMESPACE = 'bfs/v1';
    private const ROUTE = '/finder';

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
                'genero'    => ['type' => 'string', 'required' => false],
                'limit'     => ['type' => 'integer', 'required' => false, 'default' => 3],
                'pool'      => ['type' => 'integer', 'required' => false, 'default' => 60],
                'surface'   => ['type' => 'string', 'required' => false],
                'style'     => ['type' => 'string', 'required' => false],
                'priority'  => ['type' => 'string', 'required' => false],
                'budget'    => ['type' => 'string', 'required' => false],
                'guide_url' => ['type' => 'string', 'required' => false],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $req): \WP_REST_Response
    {
        $genero = sanitize_text_field((string) $req->get_param('genero'));
        $limit  = max(1, min(6, (int) $req->get_param('limit')));
        $pool   = max($limit, min(200, (int) $req->get_param('pool')));

        $surface  = sanitize_key((string) $req->get_param('surface'));
        $style    = sanitize_key((string) $req->get_param('style'));
        $priority = sanitize_key((string) $req->get_param('priority'));
        $budget   = sanitize_key((string) $req->get_param('budget'));
        $guideUrl = esc_url_raw((string) $req->get_param('guide_url'));

        // Cache versioning (bumped on import)
        $cacheVer = (int) get_option('bfs_cache_version', 1);

        $cacheKey = 'bfs_finder_' . $cacheVer . '_' . md5(
            $genero . '|' . $limit . '|' . $pool . '|' . $surface . '|' . $style . '|' . $priority . '|' . $budget . '|' . $guideUrl
        );

        $cached = wp_cache_get($cacheKey, 'bfs_shortcodes');

        // Fallback cache for sites without a persistent object cache (Redis/Memcached).
        if (!is_array($cached) && function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            $transient = get_transient($cacheKey);
            if (is_array($transient)) {
                $cached = $transient;
            }
        }

        if (is_array($cached) && isset($cached['html'])) {
            return rest_ensure_response([
                'success' => true,
                'data'    => $cached,
            ]);
        }

        // Build a small pool and pick TOP N.
        // Con el builder optimizado, esto ya es paginado internamente (offset 0).
        $products = BfsPreviewBuilder::build($genero, $pool);

        // Budget filter (primary reliable signal).
        $products = self::filterByBudget($products, $budget);

        // Pick first N after filter (builder order is stable).
        $products = array_slice($products, 0, $limit);

        ob_start();
        $productsVar = $products;
        $guide_url = $guideUrl;

        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/partials/bfs-finder-results.php';
        if (file_exists($view)) {
            $products = $productsVar;
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista de resultados no encontrada.', 'bfs-shortcodes') . '</p>';
        }
        $html = (string) ob_get_clean();

        $payload = ['html' => $html];

        wp_cache_set($cacheKey, $payload, 'bfs_shortcodes', 300);

        if (function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            set_transient($cacheKey, $payload, 300);
        }

        return rest_ensure_response([
            'success' => true,
            'data'    => $payload,
        ]);
    }

    /**
     * @param array<int, \BFS\Helpers\BfsPreviewProduct> $products
     * @return array<int, \BFS\Helpers\BfsPreviewProduct>
     */
    private static function filterByBudget(array $products, string $budget): array
    {
        if ($budget === '') {
            return $products;
        }

        $min = 0.0;
        $max = 0.0;

        if ($budget === 'low') {
            $min = 0.0;
            $max = 60.0;
        } elseif ($budget === 'mid') {
            $min = 60.0;
            $max = 120.0;
        } elseif ($budget === 'high') {
            $min = 120.01;
            $max = 1000000.0;
        } else {
            return $products;
        }

        return array_values(array_filter($products, static function ($p) use ($min, $max): bool {
            $price = isset($p->minPrice) ? (float) $p->minPrice : 0.0;
            if ($max >= 1000000.0) {
                return $price >= $min;
            }
            return $price >= $min && $price <= $max;
        }));
    }
}
