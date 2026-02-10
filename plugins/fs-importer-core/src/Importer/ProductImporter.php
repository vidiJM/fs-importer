<?php
declare(strict_types=1);

namespace FS\ImporterCore\Importer;

use FS\ImporterCore\DTO\ProductDTO;
use FS\ImporterCore\Signature\ProductSignature;

final class ProductImporter
{
    public static function upsert(ProductDTO $product): int
    {
        $signature = ProductSignature::make(
            $product->brand,
            $product->model
        );

        $existing = get_posts([
            'post_type'   => 'fs_producto',
            'meta_key'    => 'fs_signature',
            'meta_value'  => $signature,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);

        $brand = strtoupper(trim((string) $product->brand));
        $model = strtoupper(trim((string) $product->model));

        $title = str_starts_with($model, $brand . ' ')
            ? $model
            : $brand . ' ' . $model;

        if ($existing) {
            $postId = (int) $existing[0];
            wp_update_post([
                'ID'           => $postId,
                'post_title'   => $title,
                'post_content' => (string) ($product->description ?? ''),
            ]);
        } else {
            $postId = (int) wp_insert_post([
                'post_type'   => 'fs_producto',
                'post_status' => 'publish',
                'post_title'  => $title,
            ]);
        }

        if ($postId <= 0) {
            return 0;
        }

        // =========================
        // ACF / META (ACF OPTIONAL)
        // =========================
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        $writer('fs_signature', $signature);
        $writer('fs_product_id', (string) ($product->productId ?? ''));
        $writer('fs_model_signature', $model);
        $writer('fs_name_raw', (string) ($product->rawName ?? ''));
        $writer('fs_brand_raw', $brand);

        if (!empty($product->image)) {
            $writer('fs_image_main_url', (string) $product->image);
        }

        // 🔥 IMÁGENES ADICIONALES (textarea)
        $imagesRaw = $product->info['images_raw'] ?? null;
        if (is_array($imagesRaw) && $imagesRaw) {
            $imagesRaw = array_values(array_filter(array_map('trim', array_map('strval', $imagesRaw))));
            if ($imagesRaw) {
                $writer('fs_images_raw', implode("\n", $imagesRaw));
            }
        }

        // (opcional) info técnica completa
        if (!empty($product->info) && is_array($product->info)) {
            update_post_meta(
                $postId,
                '_fs_info',
                wp_json_encode($product->info, JSON_UNESCAPED_UNICODE)
            );
        }

        // =========================
        // TAXONOMÍAS
        // =========================
        TaxonomyAssigner::setSingle($postId, 'fs_marca', $brand);

        // gender puede venir string o array
        if (!empty($product->info['gender'])) {
            $gender = $product->info['gender'];
            if (is_array($gender)) {
                // Si TaxonomyAssigner solo soporta 1 término, guardamos el primero.
                // (Si tienes setMultiple(), dímelo y lo cambiamos.)
                $gender = reset($gender) ?: '';
            }
            if (is_string($gender) && $gender !== '') {
                TaxonomyAssigner::setSingle($postId, 'fs_genero', $gender);
            }
        }

        if (!empty($product->info['sole']) && is_string($product->info['sole'])) {
            TaxonomyAssigner::setSingle($postId, 'fs_suela', $product->info['sole']);
        }

        if (
            !empty($product->info['environment']) &&
            is_string($product->info['environment']) &&
            taxonomy_exists('fs_entorno')
        ) {
            TaxonomyAssigner::setSingle($postId, 'fs_entorno', $product->info['environment']);
        }

        return $postId;
    }
}
