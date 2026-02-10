<?php
declare(strict_types=1);

namespace FS\ImporterCore\Preview;

use FS\ImporterCore\DTO\ProductDTO;
use FS\ImporterCore\DTO\VariantDTO;
use FS\ImporterCore\DTO\OfferDTO;

final class PreviewBuilder
{
    private const COLOR_ORDER = [
        'NEGRO' => 1,
        'BLANCO' => 2,
        'AZUL' => 3,
        'ROJO' => 4,
        'VERDE' => 5,
        'GRIS' => 6,
        'AMARILLO' => 7,
        'NARANJA' => 8,
        'ROSA' => 9,
        'MORADO' => 10,
        'MULTICOLOR' => 99,
    ];

    /**
     * @param ProductDTO[] $products
     * @return PreviewProduct[]
     */
    public static function build(array $products): array
    {
        $previews = [];

        foreach ($products as $product) {
            $pp = new PreviewProduct();
            $pp->productId = $product->productId;

            self::normalizeBrandAndModel($pp, $product);

            $pp->image      = $product->image;
            $pp->minPrice   = 0.0;
            $pp->merchants  = [];
            $pp->variants   = [];

            foreach ($product->variants as $variant) {
                self::attachVariant($pp, $variant);
            }

            self::finalizeProduct($pp);
            $previews[] = $pp;

            unset($product);
        }

        return $previews;
    }

    private static function normalizeBrandAndModel(
        PreviewProduct $pp,
        ProductDTO $product
    ): void {
        $brand = strtoupper(trim((string) $product->brand));
        $model = strtoupper(trim((string) $product->model));

        if ($brand !== '' && str_starts_with($model, $brand . ' ')) {
            $pp->brand = $brand;
            $pp->model = trim(substr($model, strlen($brand)));
            return;
        }

        $pp->brand = $brand;
        $pp->model = $model;
    }

    private static function attachVariant(
        PreviewProduct $pp,
        VariantDTO $variant
    ): void {
        $colorKey = self::normalizeColorKey($variant->color);

        if (!isset($pp->variants[$colorKey])) {
            $pv = new PreviewVariant();
            $pv->variantId = $variant->variantId;
            $pv->color     = $variant->color;
            $pv->imageMain = $variant->imageMain ?? null;
            $pv->images    = $variant->images ?? [];
            $pv->sizes     = [];
            $pv->offers    = [];
            $pv->minPrice  = 0.0;

            $pp->variants[$colorKey] = $pv;
        }

        $pv = $pp->variants[$colorKey];

        foreach ($variant->offers as $offer) {
            self::attachOffer($pp, $pv, $offer);
        }
    }

    private static function attachOffer(
        PreviewProduct $pp,
        PreviewVariant $pv,
        OfferDTO $offer
    ): void {
        $po = new PreviewOffer();
        $po->merchantName = $offer->merchantName ?? '—';
        $po->merchantId   = $offer->merchantId ?? null;
        $po->price        = (float) $offer->price;
        $po->inStock      = (bool) $offer->inStock;

        $pv->offers[] = $po;

        if (!$po->inStock) {
            return;
        }

        if (!$offer->inStock) {
            return;
        }
        if ($offer->size !== null) {
            $pv->sizes[$offer->size] = true;
        }

        if (!empty($offer->merchantName)) {
            $pp->merchants[$offer->merchantName] = true;
        }

        if ($pv->minPrice === 0.0 || $po->price < $pv->minPrice) {
            $pv->minPrice = $po->price;
        }

        if ($pp->minPrice === 0.0 || $po->price < $pp->minPrice) {
            $pp->minPrice = $po->price;
        }
    }

    private static function finalizeProduct(PreviewProduct $pp): void
    {
        $pp->merchants = array_keys($pp->merchants);

        foreach ($pp->variants as $variant) {
            $variant->sizes = array_keys($variant->sizes);
        }

        self::sortVariantsByColorPriority($pp);
    }

    private static function sortVariantsByColorPriority(
        PreviewProduct $pp
    ): void {
        $priorityCache = [];

        $getPriority = static function (string $color) use (&$priorityCache): int {
            if (!isset($priorityCache[$color])) {
                $parts = preg_split('/[\s\-]+/', strtoupper($color));
                $main  = $parts[0] ?? '';
                $priorityCache[$color] = self::COLOR_ORDER[$main] ?? 100;
            }

            return $priorityCache[$color];
        };

        usort(
            $pp->variants,
            static fn($a, $b) => $getPriority($a->color) <=> $getPriority($b->color)
        );
    }

    private static function normalizeColorKey(?string $color): string
    {
        return strtoupper(trim((string) $color));
    }
}
