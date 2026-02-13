<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Builders\Grid_Dataset_Builder;
use FS\ShortcodeSuite\Core\Cache_Manager;

defined('ABSPATH') || exit;

final class Grid_Service
{
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
     *
     * @param array<string,mixed> $filters
     * @return array{
     *     items: array<int,mixed>,
     *     total: int,
     *     has_more: bool
     * }
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

        /*
        ------------------------------------------------------------
        1️⃣ Obtener productos YA filtrados desde Repository
        (offer-driven + size + color + gender + age_group + brand)
        ------------------------------------------------------------
        */

        $product_ids = $this->repository->get_valid_product_ids($filters);

        if (empty($product_ids)) {
            return $this->empty_response();
        }

        /*
        ------------------------------------------------------------
        2️⃣ Paginación en memoria (IDs ya limpios)
        ------------------------------------------------------------
        */

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

        /*
        ------------------------------------------------------------
        3️⃣ Construcción dataset final
        ------------------------------------------------------------
        */

        $dataset = $this->builder->build($paged_ids, $filters);

        $result = [
            'items'    => $dataset,
            'total'    => $total,
            'has_more' => $has_more,
        ];

        $this->cache->set($cache_key, $result);

        return $result;
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
