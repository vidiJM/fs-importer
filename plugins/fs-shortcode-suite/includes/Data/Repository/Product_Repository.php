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

        return $this->get_product_ids_from_variants($variant_ids, $filters);
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
        $size_filter     = $filters['size'] ?? null;
        $color_filter    = $filters['color'] ?? null;
        $gender_filter   = $filters['gender'] ?? null;
        $age_filter      = $filters['age_group'] ?? null;
    
        /*
        ------------------------------------------------------------
        1️⃣ Ofertas válidas → obtener hashes de variantes
        ------------------------------------------------------------
        */
    
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
    
        $params = [];
    
        if (!empty($size_filter)) {
            $sql .= "
                INNER JOIN {$this->wpdb->postmeta} pm_size
                    ON o.ID = pm_size.post_id
                    AND pm_size.meta_key = 'fs_size_eu'
                    AND pm_size.meta_value = %s
            ";
            $params[] = (string) $size_filter;
        }
    
        $sql .= "
            WHERE o.post_type = 'fs_oferta'
            AND o.post_status = 'publish'
        ";
    
        $prepared = !empty($params)
            ? $this->wpdb->prepare($sql, ...$params)
            : $sql;
    
        $variant_hashes = $this->wpdb->get_col($prepared);
    
        if (empty($variant_hashes)) {
            return [];
        }
    
        /*
        ------------------------------------------------------------
        2️⃣ Resolver hashes → IDs reales fs_variante
        con filtros taxonomía en SQL
        ------------------------------------------------------------
        */
    
        $placeholders = implode(',', array_fill(0, count($variant_hashes), '%s'));
    
        $sql = "
            SELECT DISTINCT v.ID
            FROM {$this->wpdb->posts} v
    
            INNER JOIN {$this->wpdb->postmeta} pm
                ON v.ID = pm.post_id
                AND pm.meta_key = 'fs_variant_id'
                AND pm.meta_value IN ($placeholders)
        ";
    
        $params = $variant_hashes;
    
        /*
        -----------------------------
        Filtros de taxonomía
        -----------------------------
        */
    
        $taxonomy_filters = [
            'fs_color'     => $color_filter,
            'fs_genero'    => $gender_filter,
            'fs_age_group' => $age_filter,
        ];
    
        $i = 0;
    
        foreach ($taxonomy_filters as $taxonomy => $value) {
    
            if (!empty($value)) {
    
                $alias_tr = "tr{$i}";
                $alias_tt = "tt{$i}";
                $alias_t  = "t{$i}";
    
                $sql .= "
                    INNER JOIN {$this->wpdb->term_relationships} {$alias_tr}
                        ON v.ID = {$alias_tr}.object_id
    
                    INNER JOIN {$this->wpdb->term_taxonomy} {$alias_tt}
                        ON {$alias_tr}.term_taxonomy_id = {$alias_tt}.term_taxonomy_id
                        AND {$alias_tt}.taxonomy = %s
    
                    INNER JOIN {$this->wpdb->terms} {$alias_t}
                        ON {$alias_tt}.term_id = {$alias_t}.term_id
                        AND {$alias_t}.slug = %s
                ";
    
                $params[] = $taxonomy;
                $params[] = (string) $value;
    
                $i++;
            }
        }
    
        $sql .= "
            WHERE v.post_type = 'fs_variante'
            AND v.post_status = 'publish'
        ";
    
        $prepared = $this->wpdb->prepare($sql, ...$params);
        $variant_ids = $this->wpdb->get_col($prepared);
    
        return array_map('intval', $variant_ids);
    }


    /**
     * Devuelve IDs únicos de productos a partir de variantes válidas (post_parent).
     *
     * @param array<int> $variant_ids
     * @return array<int>
     */
    private function get_product_ids_from_variants(array $variant_ids, array $filters = []): array
{
    if (empty($variant_ids)) {
        return [];
    }

    $brand_filter = $filters['brand'] ?? null;

    $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));

    $sql = "
        SELECT DISTINCT p.ID
        FROM {$this->wpdb->posts} v

        INNER JOIN {$this->wpdb->posts} p
            ON v.post_parent = p.ID
    ";

    $params = [];

    /*
    -----------------------------------
    Brand filter (producto taxonomy)
    -----------------------------------
    */

    if (!empty($brand_filter)) {

        $sql .= "
            INNER JOIN {$this->wpdb->term_relationships} tr_brand
                ON p.ID = tr_brand.object_id

            INNER JOIN {$this->wpdb->term_taxonomy} tt_brand
                ON tr_brand.term_taxonomy_id = tt_brand.term_taxonomy_id
                AND tt_brand.taxonomy = %s

            INNER JOIN {$this->wpdb->terms} t_brand
                ON tt_brand.term_id = t_brand.term_id
                AND t_brand.slug = %s
        ";

        $params[] = 'fs_marca';
        $params[] = sanitize_title((string) $brand_filter);
    }

    $sql .= "
        WHERE v.ID IN ($placeholders)
        AND v.post_type = 'fs_variante'
        AND v.post_status = 'publish'
        AND p.post_type = 'fs_producto'
        AND p.post_status = 'publish'
    ";

    $params = array_merge($params, $variant_ids);

    $prepared = $this->wpdb->prepare($sql, ...$params);
    $results  = $this->wpdb->get_col($prepared);

    return array_map('intval', $results);
}


}
