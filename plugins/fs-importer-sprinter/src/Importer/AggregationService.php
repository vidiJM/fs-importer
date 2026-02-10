<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

final class AggregationService
{
    /**
     * Agrega métricas por producto.
     *
     * @param array<int> $productIds Si se pasa, solo agrega esos productos.
     * @param int $batchSize Tamaño de lote de productos (evita cargas masivas).
     */
    public static function run(array $productIds = [], int $batchSize = 200): void
    {
        $batchSize = $batchSize > 0 ? $batchSize : 200;

        if ($productIds) {
            foreach ($productIds as $pid) {
                $pid = (int) $pid;
                if ($pid > 0) {
                    self::aggregateProduct($pid);
                }
            }
            return;
        }

        // Procesar productos por lotes para no reventar memoria/tiempo
        $paged = 1;
        do {
            $q = new \WP_Query([
                'post_type'      => 'fs_producto',
                'post_status'    => 'publish',
                'posts_per_page' => $batchSize,
                'paged'          => $paged,
                'fields'         => 'ids',
                'no_found_rows'  => false,
                'orderby'        => 'ID',
                'order'          => 'ASC',
            ]);

            if (!$q->have_posts()) {
                break;
            }

            foreach ($q->posts as $productPostId) {
                self::aggregateProduct((int) $productPostId);
            }

            $paged++;

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        } while ($q->max_num_pages >= $paged);
    }

    private static function aggregateProduct(int $productPostId): void
    {
        $variants = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'post_parent'    => $productPostId,
            'no_found_rows'  => true,
        ]);

        if (!$variants) {
            // Guardado de campos agregados mínimos
            self::write($productPostId, 'fs_has_stock', 0);
            self::write($productPostId, 'fs_variant_count', 0);
            self::write($productPostId, 'fs_offer_count', 0);
            self::write($productPostId, 'fs_last_aggregated_at', current_time('mysql'));
            return;
        }

        $hasStock    = false;
        $offerCount  = 0;
        $minPrice    = null;
        $maxPrice    = null;
        $bestOfferId = null;
        $bestPrice   = null;

        foreach ($variants as $variantPostId) {
            $offers = get_posts([
                'post_type'      => 'fs_oferta',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post_parent'    => (int) $variantPostId,
                'no_found_rows'  => true,
            ]);

            if (!$offers) {
                continue;
            }

            foreach ($offers as $offerPostId) {
                $offerPostId = (int) $offerPostId;
                $offerCount++;

                $priceRaw = get_post_meta($offerPostId, 'fs_price', true);
                $price    = (float) $priceRaw;

                if ($price <= 0) {
                    continue;
                }

                $minPrice = $minPrice === null ? $price : min($minPrice, $price);
                $maxPrice = $maxPrice === null ? $price : max($maxPrice, $price);

                $inStockRaw = get_post_meta($offerPostId, 'fs_in_stock', true);
                $inStock = filter_var($inStockRaw, FILTER_VALIDATE_BOOL);

                if ($inStock) {
                    $hasStock = true;

                    if ($bestPrice === null || $price < $bestPrice) {
                        $bestPrice   = $price;
                        $bestOfferId = $offerPostId;
                    }
                }
            }
        }

        // Guardado de campos agregados
        self::write($productPostId, 'fs_has_stock', $hasStock ? 1 : 0);
        self::write($productPostId, 'fs_variant_count', count($variants));
        self::write($productPostId, 'fs_offer_count', $offerCount);
        self::write($productPostId, 'fs_last_aggregated_at', current_time('mysql'));

        if ($minPrice !== null) {
            self::write($productPostId, 'fs_price_min', $minPrice);
        }
        if ($maxPrice !== null) {
            self::write($productPostId, 'fs_price_max', $maxPrice);
        }
        if ($bestOfferId) {
            self::write($productPostId, 'fs_best_offer_post_id', $bestOfferId);
        }
    }

    private static function write(int $postId, string $key, mixed $value): void
    {
        if ($postId <= 0 || $key === '') {
            return;
        }

        if (function_exists('update_field')) {
            update_field($key, $value, $postId);
            return;
        }

        update_post_meta($postId, $key, $value);
    }
}
