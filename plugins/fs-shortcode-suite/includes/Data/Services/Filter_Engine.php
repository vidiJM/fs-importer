<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use WP_Query;

final class Filter_Engine {

    /**
     * Devuelve IDs de productos válidos según filtros inclusivos.
     */
    public function resolve_product_ids(array $filters): array
    {
        $product_ids = null;
        $variant_ids = null;

        /**
         * 1️⃣ SIZE → fs_oferta
         */
        if (!empty($filters['size'])) {

            $offers = new WP_Query([
                'post_type'      => 'fs_oferta',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'   => 'fs_size_eu',
                        'value' => sanitize_text_field($filters['size']),
                    ],
                ],
                'fields' => 'ids',
                'no_found_rows' => true,
            ]);

            if (!$offers->have_posts()) {
                return [];
            }

            $variant_ids = array_filter(array_map(
                fn($offer_id) => get_field('fs_variant_id', $offer_id),
                $offers->posts
            ));
        }

        /**
         * 2️⃣ VARIANTES → color / gender / age_group
         */
        $variant_tax_query = [];

        $map = [
            'color'     => 'fs_color',
            'gender'    => 'fs_genero',
            'age_group' => 'fs_age_group',
        ];

        foreach ($map as $filter_key => $taxonomy) {
            if (!empty($filters[$filter_key])) {
                $variant_tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($filters[$filter_key]),
                ];
            }
        }

        if ($variant_tax_query || $variant_ids) {

            $variants = new WP_Query([
                'post_type'      => 'fs_variante',
                'posts_per_page' => -1,
                'post__in'       => $variant_ids ?: [],
                'tax_query'      => $variant_tax_query,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);

            if (!$variants->have_posts()) {
                return [];
            }

            $variant_ids = $variants->posts;

            $product_ids = array_filter(array_map(
                fn($variant_id) => get_field('fs_product_id', $variant_id),
                $variant_ids
            ));
        }

        /**
         * 3️⃣ PRODUCTOS (aquí podrías añadir brand en futuro)
         */
        if ($product_ids === null) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $product_ids)));
    }
}
