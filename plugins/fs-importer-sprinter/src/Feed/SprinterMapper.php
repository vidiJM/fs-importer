<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Feed;

use FS\ImporterCore\DTO\ProductDTO;
use FS\ImporterCore\DTO\VariantDTO;
use FS\ImporterCore\DTO\OfferDTO;
use FS\ImporterCore\Signature\ProductSignature;
use FS\ImporterCore\Signature\VariantSignature;
use FS\ImporterCore\Normalizer\BrandNormalizer;
use FS\ImporterCore\Normalizer\ModelNormalizer;
use FS\ImporterCore\Normalizer\SizeNormalizer;
use FS\ImporterCore\Normalizer\ColorNormalizer;
use FS\ImporterCore\Extractor\ProductInfoExtractor;

final class SprinterMapper
{
    /**
     * @param iterable<int, array<string, mixed>> $rows
     * @return array<int, ProductDTO>
     */
    public static function map(iterable $rows, int $limit = 0): array
    {
        $products = [];
        $seenOfferKey = [];
        $processed = 0;

        foreach ($rows as $row) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }
            $processed++;

            $brandRaw = trim((string) ($row['brand'] ?? ''));
            $titleRaw = trim((string) ($row['title'] ?? ''));

            if ($brandRaw === '' || $titleRaw === '') {
                continue;
            }

            // ===== SIZE =====
            $size = SizeNormalizer::normalize($row['size'] ?? null);

            // ===== MODELO LIMPIO =====
            $titleForModel = $titleRaw;

            if ($size !== null) {
                $titleForModel = preg_replace(
                    '~\b' . preg_quote((string) $size, '~') . '\b~i',
                    '',
                    $titleForModel
                );
            }

            $titleForModel = preg_replace('~\btalla\b~i', '', $titleForModel);
            $titleForModel = preg_replace('~\s+~', ' ', trim($titleForModel));

            // ===== INFO EXTRA =====
            $info = ProductInfoExtractor::extract($titleForModel);

            // ===== NORMALIZACIÓN =====
            $brand = BrandNormalizer::normalize($brandRaw);
            $model = ModelNormalizer::normalize($titleForModel, $brand);

            if ($model === '') {
                continue;
            }

            // ===== PRODUCT =====
            $productId = ProductSignature::make($brand, $model);

            if (!isset($products[$productId])) {
                $p = new ProductDTO();
                $p->productId = $productId;
                $p->brand     = strtoupper($brand);
                $p->model     = $model;
                $p->rawName   = $titleRaw;
                $p->image     = trim((string) ($row['image_link'] ?? '')) ?: null;
                $p->info      = $info;

                $products[$productId] = $p;
            }

            // ===== VARIANT =====
            $rawColor = $row['color'] ?? null;
            if (is_array($rawColor)) {
                $rawColor = implode(' ', $rawColor);
            }

            $rawColor = is_string($rawColor) ? trim($rawColor) : null;
            $colorBase = ColorNormalizer::normalize($rawColor) ?: 'SIN_COLOR';

            $gtin = trim((string) ($row['gtin'] ?? '')) ?: null;

            $variantId = VariantSignature::make($productId, $gtin, $colorBase);

            $variant = null;
            foreach ($products[$productId]->variants as $existing) {
                if ($existing->variantId === $variantId) {
                    $variant = $existing;
                    break;
                }
            }

            if (!$variant) {
                $variant = new VariantDTO();
                $variant->variantId = $variantId;
                $variant->color     = $colorBase;

                $variant->imageMain = trim((string) ($row['image_link'] ?? '')) ?: null;

                $additionalRaw = trim((string) ($row['additional_image_link'] ?? ''));
                if ($additionalRaw !== '') {
                    // Puede venir separado por ',' o por '|'
                    $separator = str_contains($additionalRaw, '|') ? '|' : ',';
                    $variant->images = array_values(array_filter(
                        array_map('trim', explode($separator, $additionalRaw))
                    ));
                }

                $products[$productId]->variants[] = $variant;
            }

            // ===== OFFER =====
            $salePriceRaw = (string) ($row['sale_price'] ?? '');
            $priceRaw = $salePriceRaw !== '' ? $salePriceRaw : (string) ($row['price'] ?? '');
            $price = self::parsePrice($priceRaw);

            if ($price > 0 && $size !== null) {
                $availability = strtolower(trim((string) ($row['availability'] ?? '')));
                $inStock = in_array($availability, [
                    'in stock', 'instock', 'in_stock', 'available', '1', 'yes', 'true'
                ], true);

                if (!$inStock) {
                    continue;
                }

                $merchantName = trim((string) (
                    ($row['advertiser_name'] ?? '')
                    ?: ($row['merchant_name'] ?? 'Sprinter')
                ));

                $merchantId = trim((string) ($row['advertiser_id'] ?? '')) ?: null;

                // Evitar duplicados en preview (misma variante+tienda+talla+precio)
                $offerKey = sha1($variantId . '|' . ($merchantId ?: $merchantName) . '|' . (string) $size . '|' . (string) $price);
                if (isset($seenOfferKey[$offerKey])) {
                    continue;
                }
                $seenOfferKey[$offerKey] = true;

                $offer = new OfferDTO();
                $offer->merchantName = $merchantName;
                $offer->merchantId   = $merchantId;
                $offer->price        = $price;
                $offer->size         = $size;
                $offer->inStock      = true;

                $variant->offers[] = $offer;
            }
        }

        return array_values($products);
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
