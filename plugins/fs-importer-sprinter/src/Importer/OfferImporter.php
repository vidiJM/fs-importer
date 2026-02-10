<?php
namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class OfferImporter
{
    private const POST_TYPE = 'fs_oferta';
    private const META_KEY  = 'fs_offer_id';

    public static function upsert(array $data, int $variantPostId): ?int
    {
        if (
            !$variantPostId ||
            empty($data['merchant_id']) ||
            empty($data['merchant_name']) ||
            !isset($data['price']) || // permite 0.0€
            !isset($data['in_stock']) // permite 0 como falso
        ) {
            return null;
        }

        $offerUid = self::makeOfferUid(
            $data['merchant_id'],
            $data['url'] ?? '',
            $variantPostId
        );

        $postId = self::findByOfferId($offerUid);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => self::buildTitle($data),
                'post_parent' => $variantPostId,
            ], true);

            if ($postId instanceof WP_Error) {
                self::logError('INSERT OFFER FAILED', $postId->get_error_message(), $data);
                return null;
            }
        }

        self::updateAcf($postId, $data, $offerUid, $variantPostId);
        self::updateTaxonomies($postId, $data);

        return $postId;
    }

    private static function makeOfferUid(string $merchantId, string $url, int $variantPostId): string
    {
        return sha1($merchantId . '|' . $variantPostId . '|' . $url);
    }

    private static function findByOfferId(string $offerUid): ?int
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_KEY,
                    'value' => $offerUid,
                ]
            ],
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function buildTitle(array $data): string
    {
        $parts = [];

        $parts[] = sanitize_text_field($data['merchant_name']);

        if (!empty($data['size'])) {
            $parts[] = 'EU ' . sanitize_text_field((string) $data['size']);
        }

        if (isset($data['price']) && is_numeric($data['price'])) {
            $parts[] = number_format((float) $data['price'], 2) . '€';
        }

        return implode(' · ', $parts);
    }

    private static function updateAcf(
        int $postId,
        array $data,
        string $offerUid,
        int $variantPostId
    ): void {
        update_field('fs_offer_id', sanitize_text_field($offerUid), $postId);
        update_field('fs_variant_id', $variantPostId, $postId);

        update_field('fs_merchant_id', sanitize_text_field($data['merchant_id']), $postId);
        update_field('fs_merchant_name', sanitize_text_field($data['merchant_name']), $postId);

        update_field('fs_price', floatval($data['price']), $postId);
        update_field('fs_currency', 'EUR', $postId);

        $inStock = (bool) $data['in_stock'];
        update_field('fs_in_stock', $inStock ? '1' : '0', $postId);

        if (!empty($data['size'])) {
            update_field('fs_size_eu', sanitize_text_field((string) $data['size']), $postId);
        }

        if (!empty($data['url'])) {
            update_field('fs_url', esc_url_raw($data['url']), $postId);
        }

        if (!empty($data['tracking_url'])) {
            update_field('fs_tracking_url', esc_url_raw($data['tracking_url']), $postId);
        }

        update_field('fs_last_seen_at', current_time('mysql'), $postId);
    }

    private static function updateTaxonomies(int $postId, array $data): void
    {
        if (!empty($data['merchant_name'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['merchant_name']),
                'fs_tienda',
                false
            );
        }
    }

    private static function logError(string $title, string $message, array $context = []): void
    {
        if (function_exists('fs_log')) {
            fs_log($title, [
                'message' => $message,
                'context' => $context,
            ], 'error');
        }
    }
}
