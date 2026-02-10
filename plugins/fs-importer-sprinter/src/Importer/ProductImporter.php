<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class ProductImporter
{
    private const POST_TYPE = 'fs_producto';
    private const META_KEY  = 'fs_product_id';

    /**
     * Inserta o actualiza un producto (fs_producto) a partir de datos de Sprinter.
     */
    public static function upsert(array $data): ?int
    {
        if (empty($data['product_id'])) {
            return null;
        }

        $productId = sanitize_text_field((string) $data['product_id']);
        $postId = self::findByProductId($productId);

        if (!$postId) {
            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => trim(($data['brand'] ?? '') . ' ' . ($data['model'] ?? '')),
            ], true);

            if ($postId instanceof WP_Error) {
                self::logError('INSERT PRODUCT FAILED', $postId->get_error_message(), $data);
                return null;
            }
        }

        self::updateAcf($postId, $data);
        self::updateTaxonomies($postId, $data);

        return (int) $postId;
    }

    /**
     * Busca un producto por su ID lógico (fs_product_id).
     */
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

    /**
     * Guarda campos ACF en el producto.
     */
    private static function updateAcf(int $postId, array $data): void
    {
        update_field('fs_product_id', sanitize_text_field((string) $data['product_id']), $postId);
        update_field('fs_model_signature', sanitize_text_field((string) ($data['model'] ?? '')), $postId);
        update_field('fs_brand_raw', sanitize_text_field((string) ($data['brand_raw'] ?? '')), $postId);
        update_field('fs_name_raw', sanitize_text_field((string) ($data['title_raw'] ?? '')), $postId);
        update_field('fs_description_raw', sanitize_textarea_field((string) ($data['description_raw'] ?? '')), $postId);
        update_field('fs_category_path_raw', sanitize_text_field((string) ($data['category_raw'] ?? '')), $postId);

        if (!empty($data['image_main'])) {
            update_field('fs_image_main_url', esc_url_raw($data['image_main']), $postId);
        }

        if (!empty($data['images_raw'])) {
            $images = is_array($data['images_raw']) ? $data['images_raw'] : [$data['images_raw']];
            $images = array_map('esc_url_raw', array_filter($images));
            update_field('fs_images_raw', implode("\n", $images), $postId);
        }
    }

    /**
     * Asigna taxonomías al producto.
     */
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

        if (!empty($data['category_raw'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['category_raw']),
                'fs_categoria',
                false
            );
        }

        if (!empty($data['info']['gender'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field($data['info']['gender']),
                'fs_genero',
                false
            );
        }
    }

    /**
     * Log de errores centralizado.
     */
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
