<?php
declare(strict_types=1);

namespace FS\ImporterCore\Importer;

use FS\ImporterCore\DTO\OfferDTO;

final class OfferImporter
{
    public static function upsert(OfferDTO $offer, int $variantPostId): int
    {
        if ($variantPostId <= 0) {
            return 0;
        }

        $merchantName = trim((string) ($offer->merchantName ?? ''));
        $price        = (float) ($offer->price ?? 0);

        if ($merchantName === '' || $price <= 0) {
            return 0;
        }

        $size = trim((string) ($offer->size ?? ''));
        $size = $size !== '' ? $size : 'UNICA';

        // =========================
        // BUSCAR OFERTA EXISTENTE
        // VARIANTE + TIENDA + TALLA
        // =========================
        $existing = get_posts([
            'post_type'   => 'fs_oferta',
            'post_parent' => $variantPostId,
            'meta_query'  => [
                'relation' => 'AND',
                [
                    'key'   => 'fs_merchant_name',
                    'value' => $merchantName,
                ],
                [
                    'key'   => 'fs_size_eu',
                    'value' => $size,
                ],
            ],
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);

        if ($existing) {
            $postId = (int) $existing[0];
        } else {
            $postId = wp_insert_post([
                'post_type'   => 'fs_oferta',
                'post_status' => 'publish',
                'post_title'  => trim($merchantName . ' · ' . $size),
                'post_parent' => $variantPostId,
            ]);
        }

        if (!is_int($postId) || $postId <= 0) {
            return 0;
        }

        // =========================
        // ACF / META (ACF optional)
        // =========================
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        $writer('fs_price', $price);
        $writer('fs_in_stock', (bool) $offer->inStock);
        $writer('fs_merchant_name', $merchantName);
        $writer('fs_size_eu', $size);

        if (!empty($offer->merchantId)) {
            $writer('fs_merchant_id', (string) $offer->merchantId);
        }

        if (!empty($offer->url)) {
            $writer('fs_url', (string) $offer->url);
        }

        if (!empty($offer->trackingUrl)) {
            $writer('fs_tracking_url', (string) $offer->trackingUrl);
        }

        // =========================
        // TAXONOMÍAS
        // =========================
        TaxonomyAssigner::setSingle(
            $postId,
            'fs_tienda',
            sanitize_text_field($merchantName)
        );

        TaxonomyAssigner::setSingle(
            $postId,
            'fs_talla_eu',
            $size
        );

        return $postId;
    }
}
