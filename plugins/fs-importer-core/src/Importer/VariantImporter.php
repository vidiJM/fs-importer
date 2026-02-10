<?php
declare(strict_types=1);

namespace FS\ImporterCore\Importer;

use FS\ImporterCore\DTO\VariantDTO;

final class VariantImporter
{
    public static function upsert(VariantDTO $variant, int $productPostId): int
    {
        if (
            $productPostId <= 0 ||
            empty($variant->color) ||
            $variant->color === 'SIN_COLOR'
        ) {
            return 0;
        }

        $colorKey = strtoupper(trim((string) $variant->color));

        $existing = get_posts([
            'post_type'   => 'fs_variante',
            'post_parent' => $productPostId,
            'meta_key'    => 'fs_color_key',
            'meta_value'  => $colorKey,
            'fields'      => 'ids',
            'numberposts' => 1,
        ]);

        if ($existing) {
            $postId = (int) $existing[0];
        } else {
            $postId = (int) wp_insert_post([
                'post_type'   => 'fs_variante',
                'post_status' => 'publish',
                'post_title'  => $colorKey,
                'post_parent' => $productPostId,
            ]);
        }

        if ($postId <= 0) {
            return 0;
        }

        update_post_meta($postId, 'fs_color_key', $colorKey);
        update_post_meta($postId, 'fs_variant_id', (string) ($variant->variantId ?? ''));

        // Writer ACF opcional
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        // =========================
        // IMÁGENES
        // =========================
        $images = array_values(array_unique(array_filter(
            array_merge(
                [$variant->imageMain ?? null],
                is_array($variant->images ?? null) ? $variant->images : []
            ),
            static fn($v) => is_string($v) && trim($v) !== ''
        )));

        if ($images) {
            $writer('fs_images', implode("\n", $images));
        }

        // =========================
        // TAXONOMÍAS
        // =========================
        TaxonomyAssigner::setSingle($postId, 'fs_color', $colorKey);

        if (!empty($variant->surface)) {
            TaxonomyAssigner::setSingle(
                $postId,
                'fs_superficie',
                (string) $variant->surface
            );
        }

        // =========================
        // TALLAS (solo ofertas con stock)
        // =========================
        $sizes = [];

        if (!empty($variant->offers)) {
            foreach ($variant->offers as $offer) {
                if ($offer->inStock === true && !empty($offer->size)) {
                    $size = trim((string) $offer->size);
                    if ($size !== '') {
                        $sizes[] = $size;
                    }
                }
            }
        }

        $sizes = array_values(array_unique($sizes));

        if ($sizes) {
            TaxonomyAssigner::setMultiple(
                $postId,
                'fs_talla_eu',
                $sizes
            );
        }

        return $postId;
    }
}
