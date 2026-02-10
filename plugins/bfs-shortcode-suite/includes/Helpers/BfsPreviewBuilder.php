<?php
declare(strict_types=1);

namespace BFS\Helpers;

use WP_Query;

defined('ABSPATH') || exit;

final class BfsPreviewBuilder
{
    private const COLOR_ORDER = [
        'NEGRO' => 1, 'BLANCO' => 2, 'AZUL' => 3, 'ROJO' => 4, 'VERDE' => 5,
        'GRIS' => 6, 'AMARILLO' => 7, 'NARANJA' => 8, 'ROSA' => 9,
        'MORADO' => 10, 'MULTICOLOR' => 99,
    ];

    /**
     * API legacy (compat): construye hasta $limit productos y luego corta.
     * Para paginación real usa buildPaged().
     */
    public static function build(
        ?string $genero = null,
        int $limit = 12,
        ?string $ageGroup = null,
        ?bool $strict = false,
        array $excludeModels = [],
        string $order = 'default',
        string $seed = ''
    ): array {
        return self::buildPaged(
            $genero,
            0,
            $limit,
            $ageGroup,
            $strict,
            $excludeModels,
            $order,
            $seed
        );
    }

    /**
     * ✅ Paginación real: devuelve EXACTAMENTE $limit productos (si hay) desde $offset.
     *
     * Estrategia:
     * - Query paginada de variantes (overfetch) para reunir suficientes productos únicos.
     * - Bulk mapping fs_product_id -> post_id con una query SQL.
     * - Bulk ofertas por variant_id con una query SQL (evita N+1).
     */
    public static function buildPaged(
        ?string $genero,
        int $offset,
        int $limit,
        ?string $ageGroup = null,
        ?bool $strict = false,
        array $excludeModels = [],
        string $order = 'default',
        string $seed = ''
    ): array {
        global $wpdb;

        $offset = max(0, $offset);
        $limit  = max(1, min(200, $limit)); // guardrail

        // Excludes
        $excludeMap = [];
        foreach ($excludeModels as $m) {
            $m = trim((string) $m);
            if ($m !== '') {
                $excludeMap[$m] = true;
            }
        }

        // Overfetch: como hay varias variantes por producto, necesitamos pedir más variantes.
        $variantBatch = max(200, $limit * 12);
        $variantPage  = 0;

        $allProducts = [];
        $productMap  = [];

        // Queremos llegar a offset+limit productos únicos en memoria, pero no recorrer todo.
        $target = $offset + $limit;

        while (count($allProducts) < $target) {
            $args = [
                'post_type'              => 'fs_variante',
                'post_status'            => 'publish',
                'posts_per_page'         => $variantBatch,
                'offset'                 => $variantPage * $variantBatch,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query'             => [
                    ['key' => 'fs_variant_id', 'compare' => 'EXISTS'],
                ],
                'tax_query'              => [],
            ];

            if (!empty($genero)) {
                $args['tax_query'][] = [
                    'taxonomy' => 'fs_genero',
                    'field'    => 'slug',
                    'terms'    => [sanitize_key((string) $genero)],
                ];
            }

            if ((bool) $strict && !empty($genero) && in_array($genero, ['hombre', 'mujer'], true)) {
                $other = $genero === 'hombre' ? 'mujer' : 'hombre';
                $args['tax_query'][] = [
                    'taxonomy' => 'fs_genero',
                    'field'    => 'slug',
                    'terms'    => [$other],
                    'operator' => 'NOT IN',
                ];
            }

            if (!empty($ageGroup)) {
                $args['tax_query'][] = [
                    'taxonomy' => 'fs_age_group',
                    'field'    => 'slug',
                    'terms'    => [sanitize_key((string) $ageGroup)],
                ];
            }

            if (empty($args['tax_query'])) {
                unset($args['tax_query']);
            }

            $variantIds = (new WP_Query($args))->posts;
            if (empty($variantIds)) {
                break; // no hay más variantes
            }

            // 1) Leer metas mínimas de variantes (sin objetos WP_Post)
            $variantCanonicalIds = [];
            $variantMeta = [];
            $productIdsNeeded = [];

            foreach ($variantIds as $variantPostId) {
                $variantPostId = (int) $variantPostId;

                $productId = (string) get_post_meta($variantPostId, 'fs_product_id', true);
                if ($productId === '') {
                    continue;
                }

                $variantId = (string) get_post_meta($variantPostId, 'fs_variant_id', true);
                if ($variantId === '') {
                    continue;
                }

                $color = strtoupper(trim((string) get_post_meta($variantPostId, 'fs_colour_raw', true)));

                $rawImages = (string) get_post_meta($variantPostId, 'fs_images', true);
                $urls = self::extractUrls($rawImages);

                $variantMeta[$variantPostId] = [
                    'variant_id' => $variantId,
                    'product_id' => $productId,
                    'color'      => $color,
                    'img1'       => $urls[0] ?? '',
                    'img2'       => $urls[1] ?? '',
                ];

                $variantCanonicalIds[] = $variantId;
                $productIdsNeeded[$productId] = true;
            }

            if (!$variantMeta) {
                $variantPage++;
                continue;
            }

            // 2) Bulk map: fs_product_id -> wp post_id
            $productWpMap = self::bulkMapProductIds(array_keys($productIdsNeeded));

            // 3) Bulk ofertas por variant canonical id
            $offersByVariant = self::bulkOffersByVariantIds($variantCanonicalIds);

            // 4) Construcción en memoria (agrupando por productId)
            foreach ($variantMeta as $variantPostId => $vm) {
                $productId = $vm['product_id'];

                if (!isset($productMap[$productId])) {
                    $product = new BfsPreviewProduct();
                    $product->productId = $productId;

                    $wpId = $productWpMap[$productId] ?? null;
                    $product->postId = $wpId;

                    // ✅ IMPORTANTE: permalink siempre string (nunca null)
                    $product->permalink = $wpId ? (string) get_permalink($wpId) : '';

                    $product->title = $wpId
                        ? (string) get_post_meta($wpId, 'fs_model_signature', true)
                        : '';

                    $modelKey = trim((string) ($product->title ?? ''));
                    if ($modelKey !== '' && isset($excludeMap[$modelKey])) {
                        $productMap[$productId] = null;
                        continue;
                    }

                    $product->image = $vm['img1'];
                    $product->imageHover = (string) ($vm['img2'] ?? '');
                    $product->minPrice = 0.0;
                    $product->variants = [];

                    $productMap[$productId] = $product;
                    $allProducts[] = $productId;
                }

                if (!isset($productMap[$productId]) || $productMap[$productId] === null) {
                    continue;
                }

                /** @var BfsPreviewProduct $product */
                $product = $productMap[$productId];

                $variant = new BfsPreviewVariant();
                $variant->variantId   = $vm['variant_id'];
                $variant->color       = $vm['color'] !== '' ? $vm['color'] : 'SIN_COLOR';
                // --- buildPaged(): asignación de imágenes variante
                $variant->imageMain  = (string) ($vm['img1'] ?? '');
                $variant->imageHover = (string) ($vm['img2'] ?? '');
                $variant->offers      = [];
                $variant->sizes       = [];
                $variant->minPrice    = 0.0;

                $offers = $offersByVariant[$variant->variantId] ?? [];
                foreach ($offers as $offer) {
                    $variant->offers[] = $offer;

                    if ($offer->inStock && $offer->size !== '') {
                        $variant->sizes[$offer->size] = true;
                    }
                    if ($offer->inStock && $offer->price > 0) {
                        $variant->minPrice = ($variant->minPrice === 0.0)
                            ? (float) $offer->price
                            : min($variant->minPrice, (float) $offer->price);
                    }
                }

                // Añadir/merge por color
                $colorKey = $variant->color;
                if (!isset($product->variants[$colorKey])) {
                    $product->variants[$colorKey] = $variant;
                } else {
                    /** @var BfsPreviewVariant $existing */
                    $existing = $product->variants[$colorKey];

                    if ($existing->imageMain === '' && $variant->imageMain !== '') {
                        $existing->imageMain = $variant->imageMain;
                    }
                    // --- buildPaged(): merge de hover
                    if ($existing->imageHover === '' && $variant->imageHover !== '') {
                        $existing->imageHover = $variant->imageHover;
                    }

                    foreach ($variant->offers as $offer) {
                        $existing->offers[] = $offer;
                        if ($offer->inStock && $offer->size !== '') {
                            $existing->sizes[$offer->size] = true;
                        }
                    }

                    if ($variant->minPrice > 0) {
                        $existing->minPrice = ($existing->minPrice === 0.0)
                            ? (float) $variant->minPrice
                            : min((float) $existing->minPrice, (float) $variant->minPrice);
                    }

                    $product->variants[$colorKey] = $existing;
                }

                // Precio global
                if ($variant->minPrice > 0) {
                    $product->minPrice = ($product->minPrice === 0.0)
                        ? (float) $variant->minPrice
                        : min($product->minPrice, (float) $variant->minPrice);
                }
            }

            $variantPage++;

            if ($variantPage > 50) {
                break;
            }
        }

        // 5) Materializar productos en orden de aparición
        $products = [];
        foreach ($allProducts as $productId) {
            if (!isset($productMap[$productId]) || $productMap[$productId] === null) {
                continue;
            }
            $p = $productMap[$productId];
            self::sortVariants($p);
            $products[] = $p;
        }

        // Orden random determinista si se pide (solo sobre el slice final)
        if ($order === 'random') {
            $seed = $seed !== '' ? $seed : 'seed';
            usort(
                $products,
                static function ($a, $b) use ($seed): int {
                    $ka = crc32($seed . '|' . (string) ($a->title ?? ''));
                    $kb = crc32($seed . '|' . (string) ($b->title ?? ''));
                    return $ka <=> $kb;
                }
            );
        }

        // Slice final real
        return array_slice($products, $offset, $limit);
    }

    /**
     * Bulk map fs_product_id -> post_id sin WP_Query por producto.
     *
     * @param string[] $productIds
     * @return array<string, int>
     */
    private static function bulkMapProductIds(array $productIds): array
    {
        global $wpdb;

        $productIds = array_values(array_filter(array_map('strval', $productIds), static fn($v) => $v !== ''));
        if (!$productIds) {
            return [];
        }

        $posts = $wpdb->posts;
        $pm    = $wpdb->postmeta;

        $placeholders = implode(',', array_fill(0, count($productIds), '%s'));

        $sql = $wpdb->prepare(
            "SELECT pm.meta_value AS product_id, p.ID AS post_id
             FROM {$pm} pm
             INNER JOIN {$posts} p ON p.ID = pm.post_id
             WHERE p.post_type = 'fs_producto'
               AND p.post_status = 'publish'
               AND pm.meta_key = 'fs_product_id'
               AND pm.meta_value IN ({$placeholders})",
            $productIds
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $out  = [];

        foreach ($rows as $r) {
            $pid = (string) ($r['product_id'] ?? '');
            $wpId = (int) ($r['post_id'] ?? 0);
            if ($pid !== '' && $wpId > 0) {
                $out[$pid] = $wpId;
            }
        }

        return $out;
    }

    /**
     * Bulk ofertas por variant canonical id (meta fs_variant_id), evita N+1.
     *
     * @param string[] $variantCanonicalIds
     * @return array<string, BfsPreviewOffer[]>
     */
    private static function bulkOffersByVariantIds(array $variantCanonicalIds): array
    {
        global $wpdb;

        $variantCanonicalIds = array_values(array_filter(array_map('strval', $variantCanonicalIds), static fn($v) => $v !== ''));
        if (!$variantCanonicalIds) {
            return [];
        }

        $posts = $wpdb->posts;
        $pm    = $wpdb->postmeta;

        $placeholders = implode(',', array_fill(0, count($variantCanonicalIds), '%s'));

        $sql = $wpdb->prepare(
            "SELECT
                o.ID AS offer_id,
                vmeta.meta_value AS variant_id,
                price.meta_value AS price,
                instock.meta_value AS in_stock,
                mname.meta_value AS merchant_name,
                mid.meta_value AS merchant_id,
                size.meta_value AS size_eu
             FROM {$posts} o
             INNER JOIN {$pm} vmeta ON vmeta.post_id = o.ID AND vmeta.meta_key = 'fs_variant_id'
             LEFT JOIN {$pm} price ON price.post_id = o.ID AND price.meta_key = 'fs_price'
             LEFT JOIN {$pm} instock ON instock.post_id = o.ID AND instock.meta_key = 'fs_in_stock'
             LEFT JOIN {$pm} mname ON mname.post_id = o.ID AND mname.meta_key = 'fs_merchant_name'
             LEFT JOIN {$pm} mid ON mid.post_id = o.ID AND mid.meta_key = 'fs_merchant_id'
             LEFT JOIN {$pm} size ON size.post_id = o.ID AND size.meta_key = 'fs_size_eu'
             WHERE o.post_type = 'fs_oferta'
               AND o.post_status = 'publish'
               AND vmeta.meta_value IN ({$placeholders})
             ORDER BY o.ID ASC",
            $variantCanonicalIds
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $out  = [];

        foreach ($rows as $r) {
            $variantId = (string) ($r['variant_id'] ?? '');
            if ($variantId === '') {
                continue;
            }

            $offer = new BfsPreviewOffer();
            $offer->merchantName = (string) ($r['merchant_name'] ?? '');
            $offer->merchantId   = (int) ($r['merchant_id'] ?? 0);
            $offer->price        = (float) ($r['price'] ?? 0);

            $offer->inStock = filter_var($r['in_stock'] ?? null, FILTER_VALIDATE_BOOLEAN);
            $offer->size = trim((string) ($r['size_eu'] ?? ''));

            $out[$variantId][] = $offer;
        }

        return $out;
    }

    /**
     * Extraer URLs válidas (incluso concatenadas)
     */
    private static function extractUrls(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        preg_match_all('/https?:\/\/[^\s",]+/i', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Ordenar variantes por prioridad de color
     */
    private static function sortVariants(BfsPreviewProduct $product): void
    {
        uasort($product->variants, static function ($a, $b): int {
            $pa = self::COLOR_ORDER[$a->color] ?? 100;
            $pb = self::COLOR_ORDER[$b->color] ?? 100;
            return $pa <=> $pb;
        });
    }
}
