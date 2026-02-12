<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Utils\Color_Normalizer;

final class Grid_Service {

    private Product_Repository $repository;

    public function __construct( Product_Repository $repository ) {
        
        $this->repository = $repository;
    }
    
    public function get_grid(int $limit = 12, array $filters = []): array {
        $filters = array_filter($filters);
    
        $cache_key = 'fs_grid_' . md5(serialize([
            'limit'   => $limit,
            'filters' => $filters
        ]));
    
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    
        /*
         * 1️⃣ Obtener variantes filtradas (si hay filtros)
         */
        if (!empty($filters)) {
    
            $variants = $this->repository
                ->get_variants_with_filters($filters, $limit * 5); // multiplicamos para no cortar demasiado pronto
    
            if (empty($variants)) {
                return [];
            }
    
            $product_ids = array_unique(
                array_column($variants, 'product_id')
            );
    
        } else {
    
            $product_ids = $this->repository->get_product_ids($limit);
    
            if (empty($product_ids)) {
                return [];
            }
    
            $variants = $this->repository
                ->get_variants_by_products($product_ids);
        }
    
        if (empty($variants)) {
            return [];
        }
    
        /*
         * 2️⃣ Obtener datos base
         */
        $products = $this->repository
            ->get_products_basic_data($product_ids);
    
        $variant_ids = array_column($variants, 'ID');
    
        $offers = $this->repository
            ->get_offers_in_stock($variant_ids);
    
        /*
         * 3️⃣ Indexar ofertas por variante
         */
        $offers_by_variant = [];
    
        foreach ($offers as $offer) {
    
            $variant_id = (int) $offer['variant_id'];
    
            if (empty($offer['size'])) {
                continue;
            }
    
            $offers_by_variant[$variant_id][] = [
                'size'  => $offer['size'],
                'price' => (float) $offer['price'],
                'url'   => esc_url_raw($offer['url']),
            ];
        }
    
        /*
         * 4️⃣ Indexar productos base
         */
        $products_indexed = [];
    
        foreach ($products as $product) {
    
            $products_indexed[$product['ID']] = [
                'id'     => (int) $product['ID'],
                'title'  => $product['post_title'],
                'image'  => $product['image'],
                'price'  => (float) $product['price_min'],
                'colors' => [],
            ];
        }
    
        /*
         * 5️⃣ Construcción final SOLO con variantes válidas
         */
        foreach ($variants as $variant) {
    
            $variant_id = (int) $variant['ID'];
            $product_id = (int) $variant['product_id'];
    
            if (
                !isset($products_indexed[$product_id]) ||
                !isset($offers_by_variant[$variant_id])
            ) {
                continue;
            }
    
            $raw_color = $variant['color'] ?? '';
            $color     = Color_Normalizer::normalize($raw_color);
    
            if (!$color) {
                continue;
            }
    
            foreach ($offers_by_variant[$variant_id] as $offer) {
    
                $size = $offer['size'];
    
                if (!isset($products_indexed[$product_id]['colors'][$color])) {
    
                    $products_indexed[$product_id]['colors'][$color] = [
                        'image' => !empty($variant['image'])
                            ? $variant['image']
                            : $products_indexed[$product_id]['image'],
                        'sizes' => [],
                    ];
                }
    
                $products_indexed[$product_id]['colors'][$color]['sizes'][$size] = [
                    'price' => $offer['price'],
                    'url'   => $offer['url'],
                ];
            }
        }
    
        /*
         * 6️⃣ Eliminar productos sin colores válidos
         */
        $final = [];
    
        foreach ($products_indexed as $product) {
            if (!empty($product['colors'])) {
                $final[] = $product;
            }
        }
    
        /*
         * 7️⃣ Limitar resultados reales
         */
        $final = array_slice($final, 0, $limit);
    
        set_transient($cache_key, $final, 10 * MINUTE_IN_SECONDS);
    
        return $final;
    }

}
