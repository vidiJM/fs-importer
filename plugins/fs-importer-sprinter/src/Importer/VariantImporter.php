<?php
namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class VariantImporter
{
    private const POST_TYPE = 'fs_variante';
    private const META_KEY  = 'fs_variant_id';

    public static function upsert(array $data, int $productPostId): ?int
    {
        if (
            empty($data['variant_id']) ||
            empty($data['product_id']) ||
            !$productPostId ||
            empty($data['color']) ||
            $data['color'] === 'SIN_COLOR'
        ) {
            return null;
        }

        $variantId = sanitize_text_field((string) $data['variant_id']);
        $postId = self::findByVariantId($variantId);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => strtoupper($data['color']),
                'post_parent' => $productPostId,
            ], true);

            if ($postId instanceof WP_Error) {
                self::logError('INSERT VARIANT FAILED', $postId->get_error_message(), $data);
                return null;
            }
        }

        self::updateAcf($postId, $data, $productPostId);
        self::updateTaxonomies($postId, $data, $productPostId);

        return $postId;
    }

    private static function findByVariantId(string $variantId): ?int
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_KEY,
                    'value' => $variantId,
                ]
            ],
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function updateAcf(int $postId, array $data, int $productPostId): void
    {
        update_field('fs_variant_id', sanitize_text_field($data['variant_id']), $postId);
        update_field('fs_product_id', sanitize_text_field($data['product_id']), $postId);
        update_field('fs_color_name', sanitize_text_field($data['color']), $postId);
        update_field('fs_gender_raw', sanitize_text_field($data['gender'] ?? ''), $postId);
        update_field('fs_gtin', sanitize_text_field($data['gtin'] ?? ''), $postId);
        update_field('fs_colour_raw', sanitize_text_field($data['color_raw'] ?? ''), $postId);

        // Imágenes
        $images = array_values(array_unique(array_filter(
            array_merge(
                [$data['image_main'] ?? null],
                $data['images_raw'] ?? []
            )
        )));

        if (!empty($images)) {
            $images = array_map('esc_url_raw', $images);
            update_field('fs_images', implode("\n", $images), $postId);
        }

        // STOCK y PRECIO MÍNIMO (a partir de ofertas hijas)
        $hasStock = false;
        $minPrice = null;

        $offers = get_posts([
            'post_type'   => 'fs_oferta',
            'post_parent' => $postId,
            'numberposts' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($offers as $offer) {
            $stock = get_post_meta($offer->ID, 'fs_in_stock', true);
            $price = get_post_meta($offer->ID, 'fs_price', true);

            if ($stock && $stock !== '0') {
                $hasStock = true;
                if (is_numeric($price)) {
                    $minPrice = is_null($minPrice) ? $price : min($minPrice, $price);
                }
            }
        }

        update_field('fs_has_stock', $hasStock ? '1' : '0', $postId);
        if (!is_null($minPrice)) {
            update_field('fs_price_min', floatval($minPrice), $postId);
        }
    }

    private static function updateTaxonomies(int $postId, array $data, int $productPostId): void
    {
        if (!empty($data['color'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['color']),
                'fs_color',
                false
            );
        }

        if (!empty($data['surface'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['surface']),
                'fs_superficie',
                false
            );
        }

        // === NUEVO: copiar fs_genero desde el producto padre ===
        $genero_terms = get_the_terms($productPostId, 'fs_genero');
        if (!empty($genero_terms) && !is_wp_error($genero_terms)) {
            $term_slugs = wp_list_pluck($genero_terms, 'slug');
            wp_set_object_terms($postId, $term_slugs, 'fs_genero', false);
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
