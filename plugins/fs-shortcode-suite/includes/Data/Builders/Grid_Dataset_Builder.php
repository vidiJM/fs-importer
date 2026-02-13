<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Builders;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;

defined('ABSPATH') || exit;

final class Grid_Dataset_Builder
{
    private Product_Repository $repository;

    public function __construct(Product_Repository $repository)
    {
        $this->repository = $repository;
    }

    public function build(array $product_ids, array $filters = []): array
    {
        if (empty($product_ids)) {
            return [];
        }

        $filter_color = '';
        if (!empty($filters['color']) && is_string($filters['color'])) {
            $filter_color = sanitize_title($filters['color']);
        }

        /*
        ------------------------------------------------------------
        1️⃣ Obtener TODAS las variantes en batch
        ------------------------------------------------------------
        */

        $variants = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'post_parent__in'=> $product_ids,
            'numberposts'    => -1,
            'no_found_rows'  => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ]);

        if (!$variants) {
            return [];
        }

        /*
        ------------------------------------------------------------
        2️⃣ Indexar variantes por producto
        ------------------------------------------------------------
        */

        $variants_by_product = [];
        $variant_ids = [];

        foreach ($variants as $variant) {
            $variants_by_product[$variant->post_parent][] = $variant;
            $variant_ids[] = (int) $variant->ID;
        }

        if (empty($variant_ids)) {
            return [];
        }

        /*
        ------------------------------------------------------------
        3️⃣ Obtener TODAS las ofertas en batch
        ------------------------------------------------------------
        */

        $variant_hashes = [];

        foreach ($variant_ids as $variant_id) {
            $hash = get_post_meta($variant_id, 'fs_variant_id', true);
            if (is_string($hash) && $hash !== '') {
                $variant_hashes[$variant_id] = $hash;
            }
        }

        if (empty($variant_hashes)) {
            return [];
        }

        $offers = get_posts([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => array_values($variant_hashes),
                    'compare' => 'IN',
                ],
                [
                    'key'     => 'fs_in_stock',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => 'fs_price',
                    'value'   => 0,
                    'compare' => '>',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'fs_url',
                    'compare' => 'EXISTS',
                ],
            ],
            'update_post_meta_cache' => true,
        ]);

        /*
        ------------------------------------------------------------
        4️⃣ Indexar ofertas por variante_hash
        ------------------------------------------------------------
        */

        $offers_by_hash = [];

        if ($offers) {
            foreach ($offers as $offer) {

                $hash = get_post_meta($offer->ID, 'fs_variant_id', true);
                if (!is_string($hash) || $hash === '') {
                    continue;
                }

                $offers_by_hash[$hash][] = $offer;
            }
        }

        /*
        ------------------------------------------------------------
        5️⃣ Construir dataset final
        ------------------------------------------------------------
        */

        $products = [];

        foreach ($product_ids as $product_id) {

            if (empty($variants_by_product[$product_id])) {
                continue;
            }

            $title     = get_the_title($product_id);
            $permalink = get_permalink($product_id);

            if (!is_string($title) || !is_string($permalink)) {
                continue;
            }

            $product_data = [
                'id'        => (int) $product_id,
                'name'      => $title,
                'permalink' => $permalink,
                'colors'    => [],
            ];

            foreach ($variants_by_product[$product_id] as $variant) {

                $variant_id = (int) $variant->ID;

                $terms = get_the_terms($variant_id, 'fs_color');
                if (empty($terms) || is_wp_error($terms)) {
                    continue;
                }

                $color = sanitize_title($terms[0]->slug ?? '');
                if ($color === '') {
                    continue;
                }

                if ($filter_color !== '' && $color !== $filter_color) {
                    continue;
                }

                $hash = $variant_hashes[$variant_id] ?? null;
                if (!$hash || empty($offers_by_hash[$hash])) {
                    continue;
                }

                if (!isset($product_data['colors'][$color])) {
                    $product_data['colors'][$color] = [
                        'images' => $this->get_variant_images($variant_id),
                        'sizes'  => [],
                    ];
                }

                foreach ($offers_by_hash[$hash] as $offer) {

                    $price = (float) get_post_meta($offer->ID, 'fs_price', true);
                    $size  = get_post_meta($offer->ID, 'fs_size_eu', true);
                    $url   = get_post_meta($offer->ID, 'fs_url', true);

                    if (!is_string($size) || $size === '' || $price <= 0) {
                        continue;
                    }

                    if (
                        !isset($product_data['colors'][$color]['sizes'][$size]) ||
                        $price < (float) $product_data['colors'][$color]['sizes'][$size]['price']
                    ) {
                        $product_data['colors'][$color]['sizes'][$size] = [
                            'price' => $price,
                            'url'   => esc_url_raw((string) $url),
                        ];
                    }
                }
            }

            if (!empty($product_data['colors'])) {
                $products[] = $product_data;
            }
        }

        return $products;
    }

    private function get_variant_images(int $variant_id): array
    {
        $images = [];

        $main = get_post_meta($variant_id, 'fs_image_main_url', true);
        if (is_string($main) && $main !== '') {
            $images[] = esc_url_raw($main);
        }

        $raw = get_post_meta($variant_id, 'fs_images', true);
        if (is_string($raw) && $raw !== '') {
            $split = preg_split('/[\r\n,]+/', $raw);
            if ($split) {
                foreach ($split as $img) {
                    $img = trim((string) $img);
                    if ($img !== '') {
                        $images[] = esc_url_raw($img);
                    }
                }
            }
        }

        return array_values(array_unique($images));
    }
}
