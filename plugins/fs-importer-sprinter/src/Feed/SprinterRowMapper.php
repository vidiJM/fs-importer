<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Feed;

use FS\ImporterCore\Signature\ProductSignature;
use FS\ImporterCore\Signature\VariantSignature;
use FS\ImporterCore\Normalizer\BrandNormalizer;
use FS\ImporterCore\Normalizer\ModelNormalizer;
use FS\ImporterCore\Normalizer\ColorNormalizer;
use FS\ImporterCore\Extractor\ProductInfoExtractor;

final class SprinterRowMapper
{
    public static function map(array $row): ?array
    {
        // ========================
        // VALIDACIÓN BÁSICA
        // ========================
        $brandRaw = trim((string) ($row['brand'] ?? ''));
        $titleRaw = trim((string) ($row['title'] ?? ''));

        if ($brandRaw === '' || $titleRaw === '') {
            return null;
        }

        $mpn = trim((string) ($row['mpn'] ?? '')) ?: null;

        // ========================
        // STOCK (REGLA ABSOLUTA)
        // ========================
        $availability = strtolower(trim((string) ($row['availability'] ?? '')));
        $inStock = in_array($availability, ['in stock', 'instock', 'in_stock', 'available', '1', 'yes', 'true'], true);

        if (!$inStock) {
            return null;
        }

        // ========================
        // TALLA REAL DESDE CSV
        // ========================
        $size = null;
        if (isset($row['size'])) {
            $raw = trim((string) $row['size']);
            if ($raw !== '') {
                // Normaliza tallas tipo "40 EU", "40.5", "40 2/3"
                $clean = preg_replace('~[^0-9\.]~', '', $raw);
                $size = $clean !== '' ? $clean : $raw;
            }
        }

        // ========================
        // MODELO LIMPIO
        // ========================
        $titleForModel = $titleRaw;
        if ($size) {
            $titleForModel = preg_replace('~\b' . preg_quote((string) $size, '~') . '\b~i', '', $titleForModel);
        }

        $titleForModel = preg_replace('~\btalla\b~i', '', $titleForModel);
        $titleForModel = preg_replace('~\s+~', ' ', trim($titleForModel));

        // ========================
        // NORMALIZACIÓN
        // ========================
        $brand = BrandNormalizer::normalize($brandRaw);
        $model = ModelNormalizer::normalize($titleForModel, $brand);

        if ($model === '') {
            return null;
        }

        // ========================
        // IDS CANÓNICOS
        // ========================
        $productId = ProductSignature::make($brand, $model);

        $rawColor = $row['color'] ?? null;
        if (is_array($rawColor)) {
            $rawColor = implode(' ', $rawColor);
        }
        $rawColor = is_string($rawColor) ? trim($rawColor) : null;

        $color = ColorNormalizer::normalize($rawColor) ?: 'SIN_COLOR';

        $gtin = trim((string) ($row['gtin'] ?? ''));
        $variantId = VariantSignature::make($productId, $gtin, $color);

        // ========================
        // IMÁGENES
        // ========================
        $imageMain = trim((string) ($row['image_link'] ?? '')) ?: null;

        $imagesRaw = $row['additional_image_link'] ?? '';
        if (is_array($imagesRaw)) {
            $imagesRaw = implode('|', $imagesRaw);
        } else {
            $imagesRaw = trim((string) $imagesRaw);
        }

        // ========================
        // INFO EXTRA
        // ========================
        $info = ProductInfoExtractor::extract(
            $titleForModel . ' ' . (string) ($row['description'] ?? '')
        );

        // Detectar superficie (fallback si no viene de ProductInfoExtractor)
        $surface = $info['surface'] ?? null;
        if (!$surface) {
            $titleLc = strtolower($titleRaw);
            if (str_contains($titleLc, 'indoor') || str_contains($titleLc, 'sala') || str_contains($titleLc, 'in ')) {
                $surface = 'Indoor';
            } elseif (str_contains($titleLc, 'outdoor') || str_contains($titleLc, 'turf') || str_contains($titleLc, 'calle')) {
                $surface = 'Outdoor';
            }
        }

        // ========================
        // PRECIO
        // ========================
        $salePriceRaw = (string) ($row['sale_price'] ?? '');
        $salePrice = self::parsePrice($salePriceRaw);

        $priceRaw = ($row['sale_price'] ?? '') !== '' ? (string) ($row['sale_price'] ?? '') : (string) ($row['price'] ?? '');
        $price = self::parsePrice($priceRaw);

        // No descartamos el producto por precio 0,
        // dejamos que el OfferImporter se encargue.

        // ========================
        // DATOS ADICIONALES
        // ========================
        $gender = [];

        $genderCsv   = strtolower(trim((string) ($row['gender'] ?? '')));
        $ageGroupCsv = strtolower(trim((string) ($row['age_group'] ?? '')));

        // Normaliza age_group a 2 valores: adult | kids
        $ageGroup = 'adult';
        if (in_array($ageGroupCsv, ['kids', 'junior', 'toddler', 'infant', 'infantil'], true)) {
            $ageGroup = 'kids';
        } elseif ($ageGroupCsv === 'adult') {
            $ageGroup = 'adult';
        }

        /**
         * Reglas de negocio:
         * - kids: NO asignar fs_genero (evita contaminar grids hombre/mujer). Se filtra por fs_age_group=kids.
         * - adult: asignar fs_genero según gender, y unisex => hombre+mujer.
         */
        if ($ageGroup === 'adult') {
            if ($genderCsv === 'male') {
                $gender = ['hombre'];
            } elseif ($genderCsv === 'female') {
                $gender = ['mujer'];
            } elseif ($genderCsv === 'unisex') {
                $gender = ['hombre', 'mujer'];
            }
        }

        $merchantIdRaw = trim((string) ($row['advertiser_id'] ?? ''));
        $merchantName  = trim((string) ($row['advertiser_name'] ?? 'Sprinter'));

        // ========================
        // RESULTADO FINAL
        // ========================
        return [
            'product' => [
                'product_id'      => $productId,
                'brand_raw'       => $brandRaw,
                'brand'           => strtoupper($brand),
                'model'           => $model,
                'title_raw'       => $titleRaw,
                'description_raw' => trim((string) ($row['description'] ?? '')),
                'category_raw'    => trim((string) ($row['product_type'] ?? '')),
                'image_main'      => $imageMain,
                'images_raw'      => $imagesRaw,
                'info'            => array_merge($info, [
                    'gender'    => $gender,
                    'age_group' => $ageGroup,
                ]),
            ],
            'variant' => [
                'variant_id'       => $variantId,
                'product_id'       => $productId,
                'gtin'             => $gtin,
                'mpn'              => $mpn,
                'size'             => $size,
                'color_raw'        => $rawColor,
                'color'            => $color,
                'age_group'        => $ageGroup,
                'gender'           => $gender,
                'surface'          => $surface,
                'image_main'       => $imageMain,
                'description_raw'  => trim((string) ($row['description'] ?? '')),
                'images_raw'       => $imagesRaw,
                'price'            => $price,
                'price_sale'       => ($salePrice > 0 && $salePrice < $price) ? $salePrice : null,
                'merchant_name'    => $merchantName,
            ],
            'offer' => [
                'merchant_id'    => $merchantIdRaw !== '' ? $merchantIdRaw : null,
                'merchant_name'  => $merchantName,
                'price'          => $price,
                'in_stock'       => $inStock,
                'size'           => $size ?: 'UNICA',
                'url'            => trim((string) ($row['link'] ?? '')),
                'tracking_url'   => trim((string) ($row['aw_deep_link'] ?? '')),
                'delivery_cost'  => isset($row['shipping']) ? self::parsePrice((string) $row['shipping']) : null,
                'variant_id'     => $variantId,
                'offer_uid'      => sha1($gtin . '|' . ($merchantIdRaw !== '' ? $merchantIdRaw : $merchantName) . '|' . ($size ?: 'UNICA')),
            ],
        ];
    }

    private static function parsePrice(string $raw): float
    {
        if ($raw === '') {
            return 0.0;
        }

        $clean = preg_replace('~[^0-9\.,]~', '', $raw);

        // Si viene "1.234,56" => "1234.56"
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
        }

        return (float) str_replace(',', '.', $clean);
    }
}
