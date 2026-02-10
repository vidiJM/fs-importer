<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class SprinterOfferImporter
{
    private const POST_TYPE = 'fs_oferta';
    private const META_KEY  = 'fs_offer_id';

    public static function upsert(array $data, int $variantPostId): ?int
    {
        // ============================
        // VALIDACIÓN ROBUSTA
        // ============================
        if ($variantPostId <= 0) {
            return null;
        }

        $variantId = isset($data['variant_id']) ? trim((string) $data['variant_id']) : '';
        if ($variantId === '') {
            return null;
        }

        $merchantName = isset($data['merchant_name']) ? trim((string) $data['merchant_name']) : '';
        if ($merchantName === '') {
            return null;
        }

        // merchant_id puede venir vacío → lo generamos (string, no int)
        $merchantId = isset($data['merchant_id']) ? trim((string) $data['merchant_id']) : '';
        if ($merchantId === '') {
            $merchantId = substr(sha1($merchantName), 0, 12);
            $data['merchant_id'] = $merchantId;
        }

        $price = isset($data['price']) ? (float) $data['price'] : 0.0;
        if ($price <= 0) {
            return null;
        }

        $size = trim((string) ($data['size'] ?? ''));
        $size = $size !== '' ? $size : 'UNICA';

        // ============================
        // OFFER_ID ÚNICO
        // ============================
        $offerId = self::buildOfferId($variantId, $merchantId, (string) $size);

        // ============================
        // ¿EXISTE YA ESTA OFERTA?
        // ============================
        $postId = self::findByOfferId($offerId);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => self::buildTitle($data),
                'post_parent' => $variantPostId,
            ], true);

            if ($postId instanceof WP_Error) {
                error_log('[SPRINTER_OFFER_IMPORTER] Insert failed: ' . $postId->get_error_message());
                return null;
            }
        }

        $postId = (int) $postId;
        if ($postId <= 0) {
            return null;
        }

        // ============================
        // GUARDAR META/ACF
        // ============================
        self::updateAcf($postId, $data, $offerId, $variantPostId);
        self::updateTaxonomies($postId, $data);

        return $postId;
    }

    private static function buildOfferId(string $variantId, string $merchantId, string $size): string
    {
        return sha1($variantId . '|' . $merchantId . '|' . $size);
    }

    private static function findByOfferId(string $offerId): ?int
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_KEY,
                    'value' => $offerId,
                ],
            ],
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function buildTitle(array $data): string
    {
        $parts = [];

        $merchantName = !empty($data['merchant_name']) ? trim((string) $data['merchant_name']) : '';
        if ($merchantName !== '') {
            $parts[] = $merchantName;
        }

        $size = !empty($data['size']) ? trim((string) $data['size']) : '';
        if ($size !== '') {
            $parts[] = 'EU ' . $size;
        }

        if (!empty($data['price'])) {
            $parts[] = number_format((float) $data['price'], 2) . ' €';
        }

        return implode(' · ', $parts);
    }

    private static function updateAcf(
        int $postId,
        array $data,
        string $offerId,
        int $variantPostId
    ): void {
        // Writer ACF opcional: si no existe ACF, cae a post meta.
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        // Identificadores
        $writer('fs_offer_id', $offerId);

        // Guardar FS_VARIANT_ID REAL del post variante
        $realVariantId = get_post_meta($variantPostId, 'fs_variant_id', true);
        $writer('fs_variant_id', (string) $realVariantId);

        // Datos económicos
        $writer('fs_price', (float) ($data['price'] ?? 0));
        $writer('fs_currency', 'EUR');
        $writer('fs_in_stock', true);

        // Merchant info (IMPORTANTE: merchant_id string, no int)
        $merchantId = isset($data['merchant_id']) ? trim((string) $data['merchant_id']) : '';
        $merchantName = isset($data['merchant_name']) ? trim((string) $data['merchant_name']) : '';
        $writer('fs_merchant_id', $merchantId);
        $writer('fs_merchant_name', $merchantName);

        // Size
        $size = trim((string) ($data['size'] ?? 'UNICA'));
        $size = $size !== '' ? $size : 'UNICA';
        $writer('fs_size_eu', $size);

        // Optional fields
        if (!empty($data['delivery_cost']) && is_numeric($data['delivery_cost'])) {
            $writer('fs_delivery_cost', (float) $data['delivery_cost']);
        }

        if (!empty($data['gtin'])) {
            $writer('fs_gtin', (string) $data['gtin']);
        }

        // URLs (solo si hay valor; evita escribir '' constantemente)
        if (!empty($data['url'])) {
            $writer('fs_url', (string) $data['url']);
        }

        if (!empty($data['tracking_url'])) {
            $writer('fs_tracking_url', (string) $data['tracking_url']);
        }

        $writer('fs_last_seen_at', current_time('mysql'));

        $uid = isset($data['offer_uid']) && $data['offer_uid'] !== '' ? (string) $data['offer_uid'] : $offerId;
        $writer('fs_offer_uid', $uid);
    }

    private static function updateTaxonomies(int $postId, array $data): void
    {
        if (!empty($data['merchant_name'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['merchant_name']),
                'fs_tienda',
                false
            );
        }

        if (!empty($data['size'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['size']),
                'fs_talla_eu',
                false
            );
        }
    }
}
