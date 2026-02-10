<?php
declare(strict_types=1);

namespace FS\ImporterCore\Aggregator;

final class ProductAggregator
{
    public static function aggregateProduct(int $productPostId): void
    {
        if ($productPostId <= 0) {
            return;
        }

        $variantIds = get_posts([
            'post_type'              => 'fs_variante',
            'post_status'            => 'publish',
            'post_parent'            => $productPostId,
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if (!$variantIds) {
            self::resetProduct($productPostId);
            return;
        }

        $offerIds = get_posts([
            'post_type'              => 'fs_oferta',
            'post_status'            => 'publish',
            'post_parent__in'        => $variantIds,
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $minPrice    = null;
        $maxPrice    = null;
        $hasStock    = false;
        $bestOfferId = null;
        $merchants   = [];

        foreach ($offerIds as $offerId) {
            $offerId = (int) $offerId;

            $priceRaw = get_post_meta($offerId, 'fs_price', true);
            $price    = (float) $priceRaw;

            if ($price <= 0) {
                continue;
            }

            $inStockRaw = get_post_meta($offerId, 'fs_in_stock', true);
            $inStock    = filter_var($inStockRaw, FILTER_VALIDATE_BOOL);

            if (!$inStock) {
                continue;
            }

            $hasStock = true;

            $merchant = trim((string) get_post_meta($offerId, 'fs_merchant_name', true));
            if ($merchant !== '') {
                $merchants[$merchant] = true;
            }

            if ($minPrice === null || $price < $minPrice) {
                $minPrice    = $price;
                $bestOfferId = $offerId;
            }

            if ($maxPrice === null || $price > $maxPrice) {
                $maxPrice = $price;
            }
        }

        // 🔒 regla final
        $hasStock = $hasStock && $minPrice !== null && $minPrice > 0;

        $writer = static function (string $key, mixed $value) use ($productPostId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $productPostId);
                return;
            }
            update_post_meta($productPostId, $key, $value);
        };

        $writer('fs_price_min', $hasStock ? (float) $minPrice : 0);
        $writer('fs_price_max', $hasStock ? (float) ($maxPrice ?? 0) : 0);
        $writer('fs_has_stock', $hasStock ? 1 : 0);
        $writer('fs_best_offer_post_id', $hasStock ? (int) $bestOfferId : '');
        $writer('fs_last_aggregated_at', current_time('mysql'));

        update_post_meta(
            $productPostId,
            '_fs_merchants',
            $hasStock ? array_keys($merchants) : []
        );
    }

    public static function aggregateAll(int $limit = 50): void
    {
        $limit = $limit > 0 ? $limit : 50;
        $paged = 1;

        do {
            $q = new \WP_Query([
                'post_type'              => 'fs_producto',
                'post_status'            => 'publish',
                'posts_per_page'         => $limit,
                'paged'                  => $paged,
                'fields'                 => 'ids',
                'no_found_rows'          => false,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]);

            if (!$q->have_posts()) {
                break;
            }

            foreach ($q->posts as $productId) {
                self::aggregateProduct((int) $productId);
            }

            $paged++;

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        } while ($q->max_num_pages >= $paged);
    }

    private static function resetProduct(int $productPostId): void
    {
        $writer = static function (string $key, mixed $value) use ($productPostId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $productPostId);
                return;
            }
            update_post_meta($productPostId, $key, $value);
        };

        $writer('fs_price_min', 0);
        $writer('fs_price_max', 0);
        $writer('fs_has_stock', 0);
        $writer('fs_best_offer_post_id', '');
        $writer('fs_last_aggregated_at', current_time('mysql'));

        update_post_meta($productPostId, '_fs_merchants', []);
    }
}
