<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class SprinterProductImporter
{
    private const POST_TYPE = 'fs_producto';
    private const META_KEY  = 'fs_product_id';

    public static function upsert(array $data): ?int
    {
        $productId = isset($data['product_id']) ? trim((string) $data['product_id']) : '';
        if ($productId === '') {
            return null;
        }

        $postId = self::findByProductId($productId);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => strtoupper((string) ($data['model'] ?? 'PRODUCTO')),
            ], true);

            if ($postId instanceof WP_Error) {
                error_log('[PRODUCT IMPORTER] insert failed: ' . $postId->get_error_message());
                return null;
            }
        }

        $postId = (int) $postId;
        if ($postId <= 0) {
            return null;
        }

        self::updateAcf($postId, $data, $productId);
        self::updateTaxonomies($postId, $data);

        return $postId;
    }

    private static function findByProductId(string $productId): ?int
    {
        // Más simple/eficiente que meta_query cuando es igualdad exacta
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => self::META_KEY,
            'meta_value'     => $productId,
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function updateAcf(int $postId, array $data, string $productId): void
    {
        // Writer ACF opcional
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        $writer('fs_product_id', $productId);
        $writer('fs_model_signature', (string) ($data['model'] ?? ''));
        $writer('fs_brand_raw', (string) ($data['brand_raw'] ?? ''));
        $writer('fs_name_raw', (string) ($data['title_raw'] ?? ''));
        $writer('fs_description_raw', (string) ($data['description_raw'] ?? ''));
        $writer('fs_category_path_raw', (string) ($data['category_raw'] ?? ''));

        $imageMain = isset($data['image_main']) && is_string($data['image_main']) ? trim($data['image_main']) : '';
        if ($imageMain !== '') {
            $writer('fs_image_main_url', $imageMain);
        }

        $imagesRaw = isset($data['images_raw']) && is_string($data['images_raw']) ? trim($data['images_raw']) : '';
        if ($imagesRaw !== '') {
            $writer('fs_images_raw', $imagesRaw);
        }
    }

    private static function updateTaxonomies(int $postId, array $data): void
    {
        if (!empty($data['brand'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['brand']),
                'fs_marca',
                false
            );
        }

        // Género (solo si viene en info, respeta reglas kids/adult del mapper)
        if (!empty($data['info']['gender'])) {
            $terms = $data['info']['gender'];

            if (is_string($terms)) {
                $terms = [$terms];
            }

            if (is_array($terms)) {
                $terms = array_values(array_filter(array_map(static fn($t) => sanitize_text_field((string) $t), $terms)));
                if ($terms) {
                    wp_set_object_terms($postId, $terms, 'fs_genero', false);
                }
            }
        }

        if (!empty($data['info']['age_group'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['info']['age_group']),
                'fs_age_group',
                false
            );
        }
    }
}
