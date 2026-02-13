<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Repository;

use wpdb;

defined('ABSPATH') || exit;

final class Product_Repository
{
    private wpdb $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Obtiene IDs de productos válidos partiendo de ofertas válidas (offer-driven).
     *
     * @param array<string, mixed> $filters
     * @return array<int>
     */
    public function get_valid_product_ids(array $filters): array
    {
        $variant_ids = $this->get_valid_variant_ids_from_offers($filters);

        if (empty($variant_ids)) {
            return [];
        }

        return $this->get_product_ids_from_variants($variant_ids);
    }

    /**
     * Obtiene IDs de variantes válidas partiendo de ofertas con:
     * - in_stock = 1
     * - price > 0
     * - url no vacía
     * - filtro size opcional
     *
     * IMPORTANTE: fs_variant_id en ofertas es un hash externo, NO el post ID.
     * Se resuelve a post IDs reales de fs_variante mediante postmeta(fs_variant_id).
     *
     * @param array<string, mixed> $filters
     * @return array<int>
     */
    private function get_valid_variant_ids_from_offers(array $filters): array
    {
        $size_filter = $filters['size'] ?? null;

        // 1) Obtener hashes de variantes desde ofertas válidas
        $sql = "
            SELECT DISTINCT pm_variant.meta_value AS variant_hash
            FROM {$this->wpdb->posts} o
            INNER JOIN {$this->wpdb->postmeta} pm_variant
                ON o.ID = pm_variant.post_id
                AND pm_variant.meta_key = 'fs_variant_id'
            INNER JOIN {$this->wpdb->postmeta} pm_stock
                ON o.ID = pm_stock.post_id
                AND pm_stock.meta_key = 'fs_in_stock'
                AND pm_stock.meta_value = '1'
            INNER JOIN {$this->wpdb->postmeta} pm_price
                ON o.ID = pm_price.post_id
                AND pm_price.meta_key = 'fs_price'
                AND CAST(pm_price.meta_value AS DECIMAL(10,2)) > 0
            INNER JOIN {$this->wpdb->postmeta} pm_url
                ON o.ID = pm_url.post_id
                AND pm_url.meta_key = 'fs_url'
                AND pm_url.meta_value != ''
        ";

        if (!empty($size_filter)) {
            $sql .= "
                INNER JOIN {$this->wpdb->postmeta} pm_size
                    ON o.ID = pm_size.post_id
                    AND pm_size.meta_key = 'fs_size_eu'
                    AND pm_size.meta_value = %s
            ";
        }

        $sql .= "
            WHERE o.post_type = 'fs_oferta'
            AND o.post_status = 'publish'
        ";

        if (!empty($size_filter)) {
            $prepared = $this->wpdb->prepare($sql, (string) $size_filter);
            $variant_hashes = $this->wpdb->get_col($prepared);
        } else {
            $variant_hashes = $this->wpdb->get_col($sql);
        }

        if (empty($variant_hashes)) {
            return [];
        }

        // 2) Resolver hashes a IDs reales de posts fs_variante
        $placeholders = implode(',', array_fill(0, count($variant_hashes), '%s'));

        $sql = "
            SELECT DISTINCT v.ID
            FROM {$this->wpdb->posts} v
            INNER JOIN {$this->wpdb->postmeta} pm
                ON v.ID = pm.post_id
                AND pm.meta_key = 'fs_variant_id'
                AND pm.meta_value IN ($placeholders)
            WHERE v.post_type = 'fs_variante'
            AND v.post_status = 'publish'
        ";

        $prepared = $this->wpdb->prepare($sql, ...$variant_hashes);
        $variant_ids = $this->wpdb->get_col($prepared);

        return array_map('intval', $variant_ids);
    }

    /**
     * Devuelve IDs únicos de productos a partir de variantes válidas (post_parent).
     *
     * @param array<int> $variant_ids
     * @return array<int>
     */
    private function get_product_ids_from_variants(array $variant_ids): array
    {
        if (empty($variant_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));

        $sql = "
            SELECT DISTINCT v.post_parent AS product_id
            FROM {$this->wpdb->posts} v
            WHERE v.ID IN ($placeholders)
            AND v.post_type = 'fs_variante'
            AND v.post_status = 'publish'
            AND v.post_parent > 0
        ";

        $prepared = $this->wpdb->prepare($sql, ...$variant_ids);
        $results = $this->wpdb->get_col($prepared);

        return array_values(array_unique(array_map('intval', $results)));
    }
}
