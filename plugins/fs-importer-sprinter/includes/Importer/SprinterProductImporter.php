<?php
namespace FS\ImporterSprinter\Importer;

use WP_Query;
use WP_Error;

final class SprinterProductImporter
{
    private const POST_TYPE = 'fs_producto';
    private const META_KEY  = 'fs_product_id';

    public static function upsert(array $data): ?int
    {
        if (empty($data['product_id'])) {
            return null;
        }

        $postId = self::findByProductId($data['product_id']);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => strtoupper($data['model'] ?? 'PRODUCTO'),
            ], true);

            if ($postId instanceof WP_Error) {
                error_log('[PRODUCT IMPORTER] insert failed: ' . $postId->get_error_message());
                return null;
            }
        }

        self::updateAcf($postId, $data);
        self::updateTaxonomies($postId, $data);

        return $postId;
    }

    private static function findByProductId(string $productId): ?int
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_KEY,
                    'value' => $productId,
                ],
            ],
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function updateAcf(int $postId, array $data): void
    {
        update_field('fs_product_id', (string) $data['product_id'], $postId);
        update_field('fs_model_signature', (string) ($data['model'] ?? ''), $postId);
        update_field('fs_brand_raw', (string) ($data['brand_raw'] ?? ''), $postId);
        update_field('fs_name_raw', (string) ($data['title_raw'] ?? ''), $postId);
        update_field('fs_description_raw', (string) ($data['description_raw'] ?? ''), $postId);
        update_field('fs_category_path_raw', (string) ($data['category_raw'] ?? ''), $postId);

        if (!empty($data['image_main']) && is_string($data['image_main'])) {
            update_field('fs_image_main_url', $data['image_main'], $postId);
        }

        if (!empty($data['images_raw']) && is_string($data['images_raw'])) {
            update_field('fs_images_raw', $data['images_raw'], $postId);
        }
    }

    private static function updateTaxonomies(int $postId, array $data): void
    {
        if (!empty($data['brand'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['brand']),
                'fs_marca',
                false
            );
        }

        $gender = strtolower(trim((string) ($data['info']['gender'] ?? '')));
        $ageGroup = strtolower(trim((string) ($data['info']['age_group'] ?? '')));

        $terms = [];

        if ($ageGroup === 'adult') {
            if ($gender === 'male') {
                $terms[] = 'hombre';
            } elseif ($gender === 'female') {
                $terms[] = 'mujer';
            } elseif ($gender === 'unisex') {
                $terms[] = 'hombre';
                $terms[] = 'mujer';
            }
        } elseif (in_array($ageGroup, ['kids', 'junior', 'toddler'], true)) {
            $terms[] = 'infantil';
        }

        if (!empty($terms)) {
            wp_set_object_terms(
                $postId,
                array_map('sanitize_text_field', $terms),
                'fs_genero',
                false
            );
        }
    }
}
