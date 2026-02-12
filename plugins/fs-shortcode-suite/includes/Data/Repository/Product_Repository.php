<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Repository;

use wpdb;

final class Product_Repository {

    private wpdb $wpdb;

    /**
     * Constructor.
     */
    public function __construct( wpdb $wpdb ) {
        $this->wpdb = $wpdb;
    }

    /**
     * Obtener IDs de productos aplicando filtros por taxonomías de variante.
     *
     * @param array $filters
     * @param int   $limit
     * @return array<int>
     */
    public function get_variants_with_filters(array $filters, int $limit): array {
        $joins = [];
        $wheres = ["v.post_type = 'fs_variante'"];
        $params = [];
    
        if (!empty($filters['gender'])) {
            $joins[] = "
                INNER JOIN {$this->wpdb->term_relationships} trg 
                    ON trg.object_id = v.ID
                INNER JOIN {$this->wpdb->term_taxonomy} ttg 
                    ON ttg.term_taxonomy_id = trg.term_taxonomy_id 
                    AND ttg.taxonomy = 'fs_genero'
                INNER JOIN {$this->wpdb->terms} tg 
                    ON tg.term_id = ttg.term_id
            ";
            $wheres[] = "tg.slug = %s";
            $params[] = $filters['gender'];
        }
    
        if (!empty($filters['age_group'])) {
            $joins[] = "
                INNER JOIN {$this->wpdb->term_relationships} tra 
                    ON tra.object_id = v.ID
                INNER JOIN {$this->wpdb->term_taxonomy} tta 
                    ON tta.term_taxonomy_id = tra.term_taxonomy_id 
                    AND tta.taxonomy = 'fs_age_group'
                INNER JOIN {$this->wpdb->terms} ta 
                    ON ta.term_id = tta.term_id
            ";
            $wheres[] = "ta.slug = %s";
            $params[] = $filters['age_group'];
        }
    
        if (!empty($filters['color'])) {
            $joins[] = "
                INNER JOIN {$this->wpdb->term_relationships} trc 
                    ON trc.object_id = v.ID
                INNER JOIN {$this->wpdb->term_taxonomy} ttc 
                    ON ttc.term_taxonomy_id = trc.term_taxonomy_id 
                    AND ttc.taxonomy = 'fs_color'
                INNER JOIN {$this->wpdb->terms} tc 
                    ON tc.term_id = ttc.term_id
            ";
            $wheres[] = "tc.slug = %s";
            $params[] = $filters['color'];
        }
    
        // ⚠️ OJO: Primero obtenemos productos distintos
        $sql = "
            SELECT DISTINCT v.post_parent
            FROM {$this->wpdb->posts} v
            " . implode("\n", $joins) . "
            WHERE " . implode(" AND ", $wheres) . "
            ORDER BY v.post_parent DESC
            LIMIT %d
        ";
    
        $params[] = $limit;
    
        $prepared = $this->wpdb->prepare($sql, ...$params);
    
        $product_ids = $this->wpdb->get_col($prepared);
    
        if (empty($product_ids)) {
            return [];
        }
    
        // Ahora traemos TODAS las variantes filtradas de esos productos
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
    
        $sql2 = "
            SELECT 
                v.ID,
                v.post_parent as product_id,
                t.slug as color,
                images.meta_value as images_raw
            FROM {$this->wpdb->posts} v
    
            LEFT JOIN {$this->wpdb->postmeta} images
                ON images.post_id = v.ID
                AND images.meta_key = 'fs_images'
    
            INNER JOIN {$this->wpdb->term_relationships} tr
                ON tr.object_id = v.ID
    
            INNER JOIN {$this->wpdb->term_taxonomy} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
                AND tt.taxonomy = 'fs_color'
    
            INNER JOIN {$this->wpdb->terms} t
                ON t.term_id = tt.term_id
    
            WHERE v.post_type = 'fs_variante'
            AND v.post_parent IN ($placeholders)
        ";
    
        $prepared2 = $this->wpdb->prepare($sql2, ...$product_ids);
    
        $results = $this->wpdb->get_results($prepared2, ARRAY_A) ?: [];
    
        // Normalizar imágenes
        foreach ($results as &$row) {
            $raw = $row['images_raw'] ?? '';
    
            if (empty($raw)) {
                $row['image'] = null;
                continue;
            }
    
            preg_match_all('/https?:\/\/[^\s]+/i', $raw, $matches);
            $row['image'] = $matches[0][0] ?? null;
        }
    
        return $results;
    }

    /**
     * Obtener variantes de productos publicados.
     */
    public function get_variants_by_products(array $product_ids): array {

        if (empty($product_ids)) {
            return [];
        }
    
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
    
        $sql = "
            SELECT 
                v.ID,
                v.post_parent as product_id,
                t.slug as color,
                hash.meta_value as variant_hash,
                images.meta_value as images_raw
            FROM {$this->wpdb->posts} v
    
            LEFT JOIN {$this->wpdb->postmeta} images
                ON images.post_id = v.ID
                AND images.meta_key = 'fs_images'
    
            INNER JOIN {$this->wpdb->term_relationships} tr
                ON tr.object_id = v.ID
    
            INNER JOIN {$this->wpdb->term_taxonomy} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
                AND tt.taxonomy = 'fs_color'
    
            INNER JOIN {$this->wpdb->terms} t
                ON t.term_id = tt.term_id
    
            INNER JOIN {$this->wpdb->postmeta} hash
                ON hash.post_id = v.ID
                AND hash.meta_key = 'fs_variant_id'
    
            WHERE v.post_type = 'fs_variante'
            AND v.post_parent IN ($placeholders)
        ";
    
        $prepared = $this->wpdb->prepare($sql, ...$product_ids);
    
        $results = $this->wpdb->get_results($prepared, ARRAY_A) ?: [];
    
        // Normalizar imágenes
        foreach ($results as &$row) {
    
            $raw = $row['images_raw'] ?? '';
    
            if (empty($raw)) {
                $row['image'] = null;
                continue;
            }
    
            // Separar por http (robusto incluso sin comas)
            preg_match_all('/https?:\/\/[^\\s]+/i', $raw, $matches);
    
            $row['image'] = $matches[0][0] ?? null;
        }
    
        return $results;
    }


    /**
     * Obtener IDs de productos publicados.
     */
    public function get_product_ids( int $limit = 12 ): array {

        $sql = $this->wpdb->prepare("
            SELECT ID
            FROM {$this->wpdb->posts}
            WHERE post_type = %s
            AND post_status = 'publish'
            ORDER BY ID DESC
            LIMIT %d
        ", 'fs_producto', $limit );

        return $this->wpdb->get_col( $sql ) ?: [];
    }

    /**
     * Obtener datos básicos de productos.
     */
    public function get_products_basic_data( array $ids ): array {

        if ( empty( $ids ) ) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $sql = "
            SELECT 
                p.ID,
                p.post_title,
                price.meta_value as price_min,
                image.meta_value as image
            FROM {$this->wpdb->posts} p
            LEFT JOIN {$this->wpdb->postmeta} price 
                ON price.post_id = p.ID 
                AND price.meta_key = 'fs_price_min'
            LEFT JOIN {$this->wpdb->postmeta} image 
                ON image.post_id = p.ID 
                AND image.meta_key = 'fs_image_main_url'
            WHERE p.ID IN ($placeholders)
        ";

        $prepared = $this->wpdb->prepare( $sql, ...$ids );

        return $this->wpdb->get_results( $prepared, ARRAY_A ) ?: [];
    }
    
    /**
     * Obtener ofertas con stock para variantes.
     */
    public function get_offers_in_stock(array $variant_ids): array {
        if (empty($variant_ids)) {
            return [];
        }
    
        $placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));
    
        $sql = "
            SELECT 
                o.ID as offer_id,
                o.post_parent as variant_id,
                size.meta_value as size,
                price.meta_value as price,
                url.meta_value as url
            FROM {$this->wpdb->posts} o
    
            INNER JOIN {$this->wpdb->postmeta} stock
                ON stock.post_id = o.ID
                AND stock.meta_key = 'fs_in_stock'
                AND stock.meta_value = '1'
    
            LEFT JOIN {$this->wpdb->postmeta} size
                ON size.post_id = o.ID
                AND size.meta_key = 'fs_size_eu'
    
            LEFT JOIN {$this->wpdb->postmeta} price
                ON price.post_id = o.ID
                AND price.meta_key = 'fs_price'
    
            LEFT JOIN {$this->wpdb->postmeta} url
                ON url.post_id = o.ID
                AND url.meta_key = 'fs_url'
    
            WHERE o.post_type = 'fs_oferta'
            AND o.post_parent IN ($placeholders)
        ";
    
        $prepared = $this->wpdb->prepare($sql, ...$variant_ids);
    
        return $this->wpdb->get_results($prepared, ARRAY_A) ?: [];
    }

}
