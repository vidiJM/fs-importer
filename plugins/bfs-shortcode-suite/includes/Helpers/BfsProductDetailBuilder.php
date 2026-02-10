<?php
declare(strict_types=1);

namespace BFS\Helpers;

use WP_Query;

defined('ABSPATH') || exit;

final class BfsProductDetailBuilder
{
    /**
     * Build payload for product detail page.
     *
     * @return array<string, mixed>
     */
    public static function build(int $productPostId, string $spuCanonical = ''): array
    {
        $productPostId = max(0, $productPostId);

        if ($productPostId <= 0 && $spuCanonical !== '') {
            $productPostId = self::getProductPostIdBySpu($spuCanonical);
        }

        if ($productPostId <= 0 || get_post_type($productPostId) !== 'fs_producto') {
            return [];
        }

        $spu = (string) get_post_meta($productPostId, 'fs_product_id', true);
        $spu = trim($spu);
        if ($spu === '') {
            return [];
        }

        $cacheKey = 'bfs_pd_' . md5((string) $productPostId . '|' . $spu);
        $cached = wp_cache_get($cacheKey, 'bfs_pd');
        if (is_array($cached)) {
            return $cached;
        }

        $title = (string) get_post_meta($productPostId, 'fs_model_signature', true);
        if ($title === '') {
            $title = get_the_title($productPostId);
        }

        $imageMain = (string) get_post_meta($productPostId, 'fs_image_main_url', true);
        $imageMain = is_string($imageMain) ? trim($imageMain) : '';

        $descRaw = (string) get_post_meta($productPostId, 'fs_description_raw', true);
        if ($descRaw === '') {
            $descRaw = (string) get_post_field('post_content', $productPostId);
        }
        $descHtml = BfsHtmlSanitizer::sanitizeDescription($descRaw);

        $variants = self::getVariantsBySpu($spu);

        $descriptionsByColor = [];

        $colors = [];
        $minPriceGlobal = null;

        foreach ($variants as $v) {
            $colorKey = $v['color'] !== '' ? $v['color'] : 'SIN_COLOR';

            if (!empty($v['description']) && empty($descriptionsByColor[$colorKey])) {
                $descriptionsByColor[$colorKey] = (string) $v['description'];
            }

            if (!isset($colors[$colorKey])) {
                $colors[$colorKey] = [
                    'color' => $colorKey,
                    'images' => [], // gallery for this color
                    'sizes' => [],  // size => {in_stock, offers[], min_price}
                    'min_price' => null,
                ];
            }

            // Merge images (Nike-like thumbs)
            foreach ($v['images'] as $img) {
                if ($img !== '' && !in_array($img, $colors[$colorKey]['images'], true)) {
                    $colors[$colorKey]['images'][] = $img;
                }
            }

            // Merge sizes/offers
            foreach ($v['sizes'] as $size => $offers) {
                if (!isset($colors[$colorKey]['sizes'][$size])) {
                    $colors[$colorKey]['sizes'][$size] = [
                        'in_stock'  => false,
                        'offers'    => [],
                        'min_price' => null,
                    ];
                }

                foreach ($offers as $offer) {
                    $colors[$colorKey]['sizes'][$size]['offers'][] = $offer;

                    if (!empty($offer['in_stock'])) {
                        $colors[$colorKey]['sizes'][$size]['in_stock'] = true;
                    }

                    if ($offer['price'] !== null) {
                        $p = (float) $offer['price'];
                        $curr = $colors[$colorKey]['sizes'][$size]['min_price'];
                        $colors[$colorKey]['sizes'][$size]['min_price'] = ($curr === null) ? $p : min((float) $curr, $p);
                    }
                }

                $sp = $colors[$colorKey]['sizes'][$size]['min_price'];
                if ($sp !== null) {
                    $cp = $colors[$colorKey]['min_price'];
                    $colors[$colorKey]['min_price'] = ($cp === null) ? (float) $sp : min((float) $cp, (float) $sp);
                }
            }

            if ($colors[$colorKey]['min_price'] !== null) {
                $minPriceGlobal = ($minPriceGlobal === null)
                    ? (float) $colors[$colorKey]['min_price']
                    : min((float) $minPriceGlobal, (float) $colors[$colorKey]['min_price']);
            }
        }

        // Fallback image if a color has none
        foreach ($colors as $ck => $cdata) {
            if (empty($colors[$ck]['images']) && $imageMain !== '') {
                $colors[$ck]['images'][] = $imageMain;
            }
        }

        // Sort sizes
        foreach ($colors as $ck => $cdata) {
            $sizeKeys = array_keys($cdata['sizes']);
            usort($sizeKeys, static fn($a, $b) => (float) $a <=> (float) $b);
            $sorted = [];
            foreach ($sizeKeys as $sk) {
                $sorted[$sk] = $cdata['sizes'][$sk];
            }
            $colors[$ck]['sizes'] = $sorted;
        }

        $activeColor = self::pickInitialColor($colors);
        $activeImages = [];
        foreach ($colors as $c) {
            if ($c['color'] === $activeColor) {
                $activeImages = $c['images'];
                break;
            }
        }

                $activeDescription = $descriptionsByColor[$activeColor] ?? $descHtml;

$payload = [
            'id'            => $productPostId,
            'spu'           => $spu,
            'title'         => $title,
            'min_price'     => $minPriceGlobal,
            'active_color'  => $activeColor,
            'colors'        => array_values($colors),
            'active_images' => $activeImages,
            'description_default'   => $descHtml,
            'descriptions_by_color' => $descriptionsByColor,
            'description'           => $activeDescription,
        ];

        wp_cache_set($cacheKey, $payload, 'bfs_pd', 120);

        return $payload;
    }

    private static function getProductPostIdBySpu(string $spu): int
    {
        $q = new WP_Query([
            'post_type'      => 'fs_producto',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'fs_product_id', 'value' => $spu],
            ],
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : 0;
    }

    /**
     * @return array<int, array{color:string,images:array<int,string>,sizes:array<string, array<int, array<string,mixed>>>}>
     */
    private static function getVariantsBySpu(string $spu): array
    {
        $q = new WP_Query([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'posts_per_page' => 600,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'fs_product_id', 'value' => $spu],
            ],
        ]);

        $out = [];

        foreach ($q->posts as $variantId) {
            $variantId = (int) $variantId;

            $variantCanonical = trim((string) get_post_meta($variantId, 'fs_variant_id', true));
            if ($variantCanonical === '') {
                continue;
            }

            $color = strtoupper(trim((string) get_post_meta($variantId, 'fs_colour_raw', true)));

            $variantDescHtml = '';
            $variantDescRaw  = trim((string) get_post_meta($variantId, 'fs_description_raw', true));
            if ($variantDescRaw !== '') {
                $variantDescHtml = BfsHtmlSanitizer::sanitizeDescription($variantDescRaw);
            }


            $rawImages = (string) get_post_meta($variantId, 'fs_images', true);
            $images = self::extractUrls($rawImages);

            // Ofertas por canonical
            $offers = self::getOffersByVariantCanonical($variantCanonical);

            $sizes = [];
            foreach ($offers as $of) {
                $size = trim((string) ($of['size'] ?? ''));
                if ($size === '') {
                    continue;
                }
                if (!isset($sizes[$size])) {
                    $sizes[$size] = [];
                }
                $sizes[$size][] = $of;
            }

            $out[] = [
                'color'       => $color,
                'images'      => $images,
                'sizes'       => $sizes,
                'description' => $variantDescHtml,
            ];
        }

        wp_reset_postdata();
        return $out;
    }

    /**
     * @return array<int, array{merchant:string,url:string,price:float|null,in_stock:bool,size:string}>
     */
    private static function getOffersByVariantCanonical(string $variantCanonicalId): array
    {
        $variantCanonicalId = trim($variantCanonicalId);
        if ($variantCanonicalId === '') {
            return [];
        }

        $q = new WP_Query([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'posts_per_page' => 999,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'fs_variant_id', 'value' => $variantCanonicalId],
            ],
        ]);

        $offers = [];

        foreach ($q->posts as $offerId) {
            $offerId = (int) $offerId;

            $merchant = trim((string) get_post_meta($offerId, 'fs_merchant_name', true));

            $url = (string) get_post_meta($offerId, 'fs_tracking_url', true);
            if ($url === '') {
                $url = (string) get_post_meta($offerId, 'fs_url', true);
            }
            $url = trim($url);

            $price = get_post_meta($offerId, 'fs_price', true);
            $price = is_numeric($price) ? (float) $price : null;

            $size = (string) get_post_meta($offerId, 'fs_size_eu', true);

            $raw = strtolower(trim((string) get_post_meta($offerId, 'fs_in_stock', true)));
            $qty = (int) get_post_meta($offerId, 'fs_stock_quantity', true);

            $inStock = ($qty > 0) || in_array($raw, ['1','true','yes','on','si','sí','s'], true);

            $offers[] = [
                'merchant' => $merchant !== '' ? $merchant : 'Tienda',
                'url'      => $url,
                'price'    => $price,
                'in_stock' => $inStock,
                'size'     => trim((string) $size),
            ];
        }

        wp_reset_postdata();
        return $offers;
    }

    /**
     * @return string[]
     */
    private static function extractUrls(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
    
        // Normaliza saltos de línea
        $text = str_replace(["\r", "\n"], ' ', $text);
    
        // OJO: excluimos coma para evitar "jpg,https://..."
        preg_match_all('/https?:\/\/[^\s",]+/i', $text, $matches);
    
        $urls = $matches[0] ?? [];
    
        // De-dup manteniendo orden + trim de posibles restos
        $out = [];
        foreach ($urls as $u) {
            $u = trim((string) $u, " \t\n\r\0\x0B,");
            if ($u !== '' && !in_array($u, $out, true)) {
                $out[] = $u;
            }
        }
    
        return $out;
    }


    /**
     * @param array<string, array<string,mixed>> $colors
     */
    private static function pickInitialColor(array $colors): string
    {
        foreach ($colors as $ck => $c) {
            foreach (($c['sizes'] ?? []) as $s) {
                if (!empty($s['in_stock'])) {
                    return (string) $ck;
                }
            }
        }
        $keys = array_keys($colors);
        return $keys[0] ?? '';
    }
}
