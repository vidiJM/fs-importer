<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Builders\Grid_Dataset_Builder;
use FS\ShortcodeSuite\Core\Cache_Manager;

defined('ABSPATH') || exit;

final class Grid_Service {
    
    private Product_Repository $repository;
    private Grid_Dataset_Builder $builder;
    private Cache_Manager $cache;

    public function __construct(
        Product_Repository $repository,
        Grid_Dataset_Builder $builder,
        Cache_Manager $cache
    ) {
        $this->repository = $repository;
        $this->builder    = $builder;
        $this->cache      = $cache;
    }

    /**
     * Devuelve dataset paginado para el grid.
     */
    public function get_grid(array $filters, int $page = 1, int $per_page = 12): array
    {
        $page     = max(1, $page);
        $per_page = max(1, min(48, $per_page));

        $cache_key = $this->cache->generate_key($filters, $page, $per_page);
        $cached = $this->cache->get($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        // 1️⃣ Productos válidos partiendo de ofertas
        $product_ids = $this->repository->get_valid_product_ids($filters);

        if (empty($product_ids)) {
            return $this->empty_response();
        }

        // 2️⃣ Aplicar filtros de variante (color, gender, age_group)
        $product_ids = $this->apply_variant_filters($product_ids, $filters);

        if (empty($product_ids)) {
            return $this->empty_response();
        }

        // 3️⃣ Filtro brand (nivel producto)
        if (!empty($filters['brand'])) {
            $product_ids = $this->filter_products_by_taxonomy(
                $product_ids,
                'fs_marca',
                $filters['brand']
            );
        }

        if (empty($product_ids)) {
            return $this->empty_response();
        }

        // 4️⃣ Paginación
        $total     = count($product_ids);
        $offset    = ($page - 1) * $per_page;
        $paged_ids = array_slice($product_ids, $offset, $per_page);
        $has_more  = ($offset + $per_page) < $total;

        if (empty($paged_ids)) {
            return [
                'items'    => [],
                'total'    => $total,
                'has_more' => false,
            ];
        }

        // 5️⃣ Dataset coherente final
        $dataset = $this->builder->build($paged_ids, $filters);

        $result = [
            'items'    => $dataset,
            'total'    => $total,
            'has_more' => $has_more,
        ];
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }

    /**
     * Aplica filtros de variante de forma inclusiva (AND real).
     */
    private function apply_variant_filters(array $product_ids, array $filters): array
    {
        if (empty($product_ids)) {
            return [];
        }

        $filtered = [];

        foreach ($product_ids as $product_id) {

            $variants = get_posts([
                'post_type'     => 'fs_variante',
                'post_status'   => 'publish',
                'post_parent'   => $product_id,
                'numberposts'   => -1,
                'no_found_rows' => true,
            ]);

            if (empty($variants)) {
                continue;
            }

            foreach ($variants as $variant) {

                if (!$this->variant_passes_filters($variant->ID, $filters)) {
                    continue;
                }

                $filtered[] = $product_id;
                break; // con una válida ya vale
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Comprueba si una variante cumple todos los filtros (AND lógico).
     */
    private function variant_passes_filters(int $variant_id, array $filters): bool
    {
        // Color
        if (!empty($filters['color'])) {
            if (!$this->has_term($variant_id, 'fs_color', $filters['color'])) {
                return false;
            }
        }

        // Gender
        if (!empty($filters['gender'])) {
            if (!$this->has_term($variant_id, 'fs_genero', $filters['gender'])) {
                return false;
            }
        }

        // Age group
        if (!empty($filters['age_group'])) {
            if (!$this->has_term($variant_id, 'fs_age_group', $filters['age_group'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprueba si el post tiene un término concreto.
     */
    private function has_term(int $post_id, string $taxonomy, string $slug): bool
    {
        $terms = get_the_terms($post_id, $taxonomy);

        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }

        foreach ($terms as $term) {
            if ($term->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    private function filter_products_by_taxonomy(
        array $product_ids,
        string $taxonomy,
        string $slug
    ): array {

        $filtered = [];

        foreach ($product_ids as $product_id) {

            $terms = get_the_terms($product_id, $taxonomy);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                if ($term->slug === $slug) {
                    $filtered[] = $product_id;
                    break;
                }
            }
        }

        return $filtered;
    }

    private function empty_response(): array
    {
        return [
            'items'    => [],
            'total'    => 0,
            'has_more' => false,
        ];
    }
}
