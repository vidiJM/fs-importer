<?php
declare(strict_types=1);

namespace BFS\Rest;

use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

final class SearchController
{
    private const NS = 'bfs/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route(self::NS, '/search', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'search'],
            'permission_callback' => '__return_true',
            'args'                => [
                'q'         => ['type' => 'string', 'required' => false],
                'page'      => ['type' => 'integer', 'required' => false],
                'per_page'  => ['type' => 'integer', 'required' => false],
                'price_min' => ['type' => 'number', 'required' => false],
                'price_max' => ['type' => 'number', 'required' => false],
                'in_stock'  => ['type' => 'boolean', 'required' => false],
                'marca'     => ['type' => 'array', 'required' => false],
                'categoria' => ['type' => 'array', 'required' => false],
                'superficie'=> ['type' => 'array', 'required' => false],
                'color'     => ['type' => 'array', 'required' => false],
                'genero'    => ['type' => 'array', 'required' => false],
                'talla'     => ['type' => 'array', 'required' => false],
            ],
        ]);

        register_rest_route(self::NS, '/filters', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'filters'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function filters(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        // Cache versioning (bumped on import)
        $cacheVer = (int) get_option('bfs_cache_version', 1);

        $cacheKey = 'bfs_filters_v1_' . $cacheVer;
        $cached = wp_cache_get($cacheKey, 'bfs_search');

        // Fallback cache for sites without a persistent object cache (Redis/Memcached).
        if (!is_array($cached) && function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            $transient = get_transient($cacheKey);
            if (is_array($transient)) {
                $cached = $transient;
            }
        }

        if (is_array($cached)) {
            return new WP_REST_Response(['success' => true, 'data' => $cached], 200);
        }

        $taxes = [
            'fs_marca'      => 'marca',
            'fs_categoria'  => 'categoria',
            'fs_superficie' => 'superficie',
            'fs_color'      => 'color',
            'fs_genero'     => 'genero',
        ];

        $out = [];

        foreach ($taxes as $tax => $key) {
            if (!taxonomy_exists($tax)) {
                $out[$key] = [];
                continue;
            }

            $terms = get_terms([
                'taxonomy'   => $tax,
                'hide_empty' => false,
                'number'     => 200,
            ]);

            if (is_wp_error($terms)) {
                $out[$key] = [];
                continue;
            }

            $out[$key] = array_map(static function ($t): array {
                return [
                    'id'   => (int) $t->term_id,
                    'name' => (string) $t->name,
                    'slug' => (string) $t->slug,
                ];
            }, $terms);
        }

        // Tallas (meta en ofertas): fs_size_eu (texto)
        $out['talla'] = self::getDistinctMetaValues($wpdb, 'fs_oferta', 'fs_size_eu', 200);

        wp_cache_set($cacheKey, $out, 'bfs_search', 3600);

        if (function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            set_transient($cacheKey, $out, 3600);
        }

        return new WP_REST_Response(['success' => true, 'data' => $out], 200);
    }

    public static function search(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        // Cache versioning (bumped on import)
        $cacheVer = (int) get_option('bfs_cache_version', 1);

        $q = (string) $request->get_param('q');
        $q = trim(wp_strip_all_tags($q));

        $page = max(1, (int) ($request->get_param('page') ?? 1));
        $perPage = (int) ($request->get_param('per_page') ?? 12);
        $perPage = min(24, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $priceMin = $request->get_param('price_min');
        $priceMax = $request->get_param('price_max');
        $inStock  = $request->get_param('in_stock');

        $filters = [
            'marca'      => self::sanitizeIntArray($request->get_param('marca')),
            'categoria'  => self::sanitizeIntArray($request->get_param('categoria')),
            'superficie' => self::sanitizeIntArray($request->get_param('superficie')),
            'color'      => self::sanitizeIntArray($request->get_param('color')),
            'genero'     => self::sanitizeIntArray($request->get_param('genero')),
            'talla'      => self::sanitizeStringArray($request->get_param('talla')),
        ];

        // Key de cache (respuestas calientes) + versionado
        $cachePayload = [
            'v' => $cacheVer,
            'q' => $q,
            'page' => $page,
            'perPage' => $perPage,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'inStock' => $inStock,
            'filters' => $filters,
        ];

        $cacheKey = 'bfs_search_' . $cacheVer . '_' . md5(wp_json_encode($cachePayload));
        $cached = wp_cache_get($cacheKey, 'bfs_search');

        // Fallback cache for sites without a persistent object cache (Redis/Memcached).
        if (!is_array($cached) && function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            $transient = get_transient($cacheKey);
            if (is_array($transient)) {
                $cached = $transient;
            }
        }

        if (is_array($cached)) {
            return new WP_REST_Response(['success' => true, 'data' => $cached], 200);
        }

        $posts = $wpdb->posts;
        $pm = $wpdb->postmeta;

        $relation = self::detectRelationMode($wpdb);
        $relationKeys = apply_filters('bfs_search_relation_keys', [
            'offer_to_variant'   => 'fs_variant_id',
            'variant_to_product' => 'fs_product_id',
            'variant_canonical'  => 'fs_variant_id',
            'product_canonical'  => 'fs_product_id',
        ]);
        $offerToVariantKey = is_array($relationKeys) && isset($relationKeys['offer_to_variant']) ? (string) $relationKeys['offer_to_variant'] : 'fs_variant_id';
        $variantToProductKey = is_array($relationKeys) && isset($relationKeys['variant_to_product']) ? (string) $relationKeys['variant_to_product'] : 'fs_product_id';
        $variantCanonicalKey = is_array($relationKeys) && isset($relationKeys['variant_canonical']) ? (string) $relationKeys['variant_canonical'] : 'fs_variant_id';
        $productCanonicalKey = is_array($relationKeys) && isset($relationKeys['product_canonical']) ? (string) $relationKeys['product_canonical'] : 'fs_product_id';

        // Base SQL: oferta -> variante -> producto (por post_parent o por meta)
        if ($relation === 'parent') {
            $sql  = "SELECT p.ID as product_id, p.post_title, MIN(CAST(price.meta_value AS DECIMAL(10,2))) as min_price
                   FROM {$posts} o
                        INNER JOIN {$posts} v ON v.ID = o.post_parent AND v.post_type='fs_variante' AND v.post_status='publish'
                        INNER JOIN {$posts} p ON p.ID = v.post_parent AND p.post_type='fs_producto' AND p.post_status='publish'
                        LEFT JOIN {$pm} price ON price.post_id = o.ID AND price.meta_key = 'fs_price' ";
        } else {
            // Relación por meta (string canonical ids): oferta -> variante -> producto
            $sql  = "SELECT p.ID as product_id, p.post_title, MIN(CAST(price.meta_value AS DECIMAL(10,2))) as min_price
                   FROM {$posts} o
                        INNER JOIN {$pm} ov ON ov.post_id = o.ID AND ov.meta_key = %s
                        INNER JOIN {$pm} vc ON vc.meta_key = %s AND vc.meta_value = ov.meta_value
                        INNER JOIN {$posts} v ON v.ID = vc.post_id AND v.post_type='fs_variante' AND v.post_status='publish'
                        INNER JOIN {$pm} vp ON vp.post_id = v.ID AND vp.meta_key = %s
                        INNER JOIN {$pm} pc ON pc.meta_key = %s AND pc.meta_value = vp.meta_value
                        INNER JOIN {$posts} p ON p.ID = pc.post_id AND p.post_type='fs_producto' AND p.post_status='publish'
                        LEFT JOIN {$pm} price ON price.post_id = o.ID AND price.meta_key = 'fs_price' ";
        }

        // Stock (si se solicita)
        if ($inStock !== null) {
            $sql .= " LEFT JOIN {$pm} stock ON stock.post_id = o.ID AND stock.meta_key = 'fs_stock_quantity' ";
            $sql .= " LEFT JOIN {$pm} instock ON instock.post_id = o.ID AND instock.meta_key = 'fs_in_stock' ";
        }

        $where = " WHERE o.post_type='fs_oferta' AND o.post_status='publish' ";

        $params = [];

        if ($relation === 'meta') {
            $params[] = $offerToVariantKey;
            $params[] = $variantCanonicalKey;
            $params[] = $variantToProductKey;
            $params[] = $productCanonicalKey;
        }

        // Búsqueda por texto (producto)
        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where .= " AND (p.post_title LIKE %s OR p.post_content LIKE %s) ";
            $params[] = $like;
            $params[] = $like;
        }

        // Rango de precio
        if ($priceMin !== null && $priceMin !== '') {
            $where .= " AND CAST(price.meta_value AS DECIMAL(10,2)) >= %f ";
            $params[] = (float) $priceMin;
        }
        if ($priceMax !== null && $priceMax !== '') {
            $where .= " AND CAST(price.meta_value AS DECIMAL(10,2)) <= %f ";
            $params[] = (float) $priceMax;
        }

        // Stock
        if ($inStock !== null) {
            $wantStock = filter_var($inStock, FILTER_VALIDATE_BOOLEAN);
            if ($wantStock) {
                $where .= " AND ( (stock.meta_value IS NOT NULL AND CAST(stock.meta_value AS SIGNED) > 0) OR (instock.meta_value IN ('1','true','yes','on')) ) ";
            }
        }

        // Tax filters (EXISTS subqueries) - producto
        $where .= self::existsTaxFilter('p.ID', 'fs_marca', $filters['marca'], $params);
        $where .= self::existsTaxFilter('p.ID', 'fs_categoria', $filters['categoria'], $params);

        // variante
        $where .= self::existsTaxFilter('v.ID', 'fs_superficie', $filters['superficie'], $params);
        $where .= self::existsTaxFilter('v.ID', 'fs_color', $filters['color'], $params);
        $where .= self::existsTaxFilter('v.ID', 'fs_genero', $filters['genero'], $params);

        // oferta (talla EU) - meta 'fs_size_eu' (texto)
        if (!empty($filters['talla'])) {
            $placeholders = implode(',', array_fill(0, count($filters['talla']), '%s'));
            $where .= " AND EXISTS (
                SELECT 1 FROM {$pm} tmeta
                WHERE tmeta.post_id = o.ID AND tmeta.meta_key = 'fs_size_eu' AND tmeta.meta_value IN ({$placeholders})
            ) ";
            foreach ($filters['talla'] as $val) {
                $params[] = (string) $val;
            }
        }

        $sql .= $where;
        $sql .= " GROUP BY p.ID ";
        $sql .= " ORDER BY p.post_title ASC ";
        $sql .= " LIMIT %d OFFSET %d ";

        $params[] = $perPage;
        $params[] = $offset;

        $prepared = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        $items = [];
        foreach ($rows as $row) {
            $pid = (int) $row['product_id'];
            $thumbId  = get_post_thumbnail_id($pid);
            $thumbUrl = $thumbId ? wp_get_attachment_image_url($thumbId, 'medium') : '';

            if ($thumbUrl === '' || $thumbUrl === false) {
                $metaUrl = get_post_meta($pid, 'fs_image_main_url', true);
                $metaUrl = is_string($metaUrl) ? trim($metaUrl) : '';

                if ($metaUrl !== '' && filter_var($metaUrl, FILTER_VALIDATE_URL)) {
                    $thumbUrl = $metaUrl;
                } else {
                    $thumbUrl = '';
                }
            }

            $items[] = [
                'id'        => $pid,
                'title'     => (string) $row['post_title'],
                'permalink' => get_permalink($pid),
                'image'     => $thumbUrl !== '' ? esc_url_raw($thumbUrl) : '',
                'min_price' => $row['min_price'] !== null ? (float) $row['min_price'] : null,
            ];
        }

        $data = [
            'query'   => $q,
            'page'    => $page,
            'perPage' => $perPage,
            'items'   => $items,
            'hasMore' => count($items) === $perPage,
        ];

        wp_cache_set($cacheKey, $data, 'bfs_search', 60);

        if (function_exists('wp_using_ext_object_cache') && !wp_using_ext_object_cache()) {
            set_transient($cacheKey, $data, 60);
        }

        return new WP_REST_Response(['success' => true, 'data' => $data], 200);
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    private static function sanitizeIntArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $v) {
            $i = (int) $v;
            if ($i > 0) {
                $out[] = $i;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private static function sanitizeStringArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $v) {
            $s = sanitize_text_field((string) $v);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    private static function detectRelationMode(\wpdb $wpdb): string
    {
        $posts = $wpdb->posts;

        $hasParent = (int) $wpdb->get_var(
            "SELECT 1 FROM {$posts} WHERE post_type='fs_oferta' AND post_status='publish' AND post_parent <> 0 LIMIT 1"
        );

        return $hasParent ? 'parent' : 'meta';
    }

    /**
     * Genera un EXISTS(...) para filtrar por taxonomía con term_id (no term_taxonomy_id).
     *
     * @param string $objectIdSql p.ej. 'p.ID'
     * @param string $taxonomy
     * @param int[]  $termIds
     * @param array<int, mixed> $params
     */
    private static function existsTaxFilter(string $objectIdSql, string $taxonomy, array $termIds, array &$params): string
    {
        global $wpdb;

        if (empty($termIds) || !taxonomy_exists($taxonomy)) {
            return '';
        }

        $tr = $wpdb->term_relationships;
        $tt = $wpdb->term_taxonomy;

        $placeholders = implode(',', array_fill(0, count($termIds), '%d'));

        $sql = " AND EXISTS (
            SELECT 1
            FROM {$tr} r
            INNER JOIN {$tt} tt ON tt.term_taxonomy_id = r.term_taxonomy_id
            WHERE r.object_id = {$objectIdSql}
              AND tt.taxonomy = %s
              AND tt.term_id IN ({$placeholders})
        ) ";

        $params[] = $taxonomy;
        foreach ($termIds as $id) {
            $params[] = (int) $id;
        }

        return $sql;
    }

    /**
     * @return string[]
     */
    private static function getDistinctMetaValues(\wpdb $wpdb, string $postType, string $metaKey, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));

        $posts = $wpdb->posts;
        $pm = $wpdb->postmeta;

        // DISTINCT meta values (simple + rápido)
        $sql = $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
             FROM {$pm} pm
             INNER JOIN {$posts} p ON p.ID = pm.post_id
             WHERE p.post_type = %s AND p.post_status = 'publish'
               AND pm.meta_key = %s
               AND pm.meta_value <> ''
             ORDER BY pm.meta_value ASC
             LIMIT %d",
            $postType,
            $metaKey,
            $limit
        );

        $vals = $wpdb->get_col($sql);
        $out = [];

        foreach ($vals as $v) {
            $s = sanitize_text_field((string) $v);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }
}
