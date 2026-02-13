<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use WP_Query;

defined('ABSPATH') || exit;

final class Search_Service
{
    private const MIN_LENGTH = 3;
    private const LIMIT      = 12;

    /**
     * Ejecuta búsqueda predictiva sobre fs_producto
     *
     * @return array<int,array<string,mixed>>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_LENGTH) {
            return [];
        }

        $args = [
            'post_type'      => 'fs_producto',
            'post_status'    => 'publish',
            'posts_per_page' => self::LIMIT,
            's'              => $query,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ];

        $wp_query = new WP_Query($args);

        if (!$wp_query->have_posts()) {
            return [];
        }

        return $this->build_dataset($wp_query->posts);
    }

    /**
     * Construye dataset ligero para overlay search
     *
     * @param array<int,int> $product_ids
     * @return array<int,array<string,mixed>>
     */
    private function build_dataset(array $product_ids): array
    {
        $results = [];

        foreach ($product_ids as $product_id) {

            $results[] = [
                'id'        => $product_id,
                'name'      => get_the_title($product_id),
                'permalink' => get_permalink($product_id),
                'image'     => get_the_post_thumbnail_url($product_id, 'medium'),
                'price'     => $this->get_min_price($product_id),
            ];
        }

        return $results;
    }

    /**
     * Obtiene precio mínimo agregado desde fs_producto
     */
    private function get_min_price(int $product_id): ?float
    {
        $price = get_post_meta($product_id, 'fs_price_min', true);

        if (!$price) {
            return null;
        }

        return (float) $price;
    }
}
