<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Adapter;

use FS\ImporterCore\DTO\ProductDTO;
use FS\ImporterCore\DTO\VariantDTO;
use FS\ImporterCore\DTO\OfferDTO;

final class CoreDtoFactory
{
    private static function normalizeImages(mixed $raw): array
    {
        if (empty($raw)) return [];

        if (is_array($raw)) {
            return array_values(array_filter(array_map(
                static fn($v) => is_string($v) ? trim($v) : null,
                $raw
            )));
        }

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') return [];

            if (str_contains($raw, "\n")) {
                return array_values(array_filter(array_map('trim', explode("\n", $raw))));
            }

            if (str_contains($raw, '|')) {
                return array_values(array_filter(array_map('trim', explode('|', $raw))));
            }

            return [$raw];
        }

        return [];
    }

    // =========================
    // PRODUCT
    // =========================

    public static function product(array $data): ProductDTO
    {
        $dto = new ProductDTO();

        $dto->productId   = (string) ($data['product_id'] ?? '');
        $dto->brand       = (string) ($data['brand'] ?? '');
        $dto->model       = (string) ($data['model'] ?? '');
        $dto->rawName     = (string) ($data['title_raw'] ?? '');
        $dto->description = (string) ($data['description_raw'] ?? '');
        $dto->image       = (string) ($data['image_main'] ?? '');

        $dto->info = is_array($data['info'] ?? null) ? $data['info'] : [];

        return $dto;
    }

    // =========================
    // VARIANT
    // =========================
    public static function variant(array $data): VariantDTO
    {
        $dto = new VariantDTO();
    
        // =========================
        // VARIANT ID (CRÍTICO)
        // =========================
        if (empty($data['variant_id']) || !is_string($data['variant_id'])) {
            error_log('[DTO FACTORY ERROR] variant_id inválido: ' . print_r($data['variant_id'] ?? 'NULL', true));
            return $dto; // se devolverá vacío y será descartado aguas abajo
        }
    
        $dto->variantId = trim($data['variant_id']);
    
        // DEBUG FINAL (temporal, puedes quitarlo luego)
        error_log('[DTO OK] variantId asignado = ' . $dto->variantId);
    
        // =========================
        // COLOR (OBLIGATORIO)
        // =========================
        $color = trim((string) ($data['color'] ?? ''));
        $dto->color = $color !== '' ? strtoupper($color) : 'SIN_COLOR';
    
        // =========================
        // IMAGEN PRINCIPAL
        // =========================
        $dto->imageMain = (!empty($data['image_main']) && is_string($data['image_main']))
            ? $data['image_main']
            : null;
    
        // =========================
        // IMÁGENES ADICIONALES
        // =========================
        $dto->images = self::normalizeImages($data['images_raw'] ?? null);
    
        // =========================
        // SUPERFICIE (OPCIONAL)
        // =========================
        if (!empty($data['surface']) && is_string($data['surface'])) {
            $dto->surface = $data['surface'];
        }
    
        // =========================
        // OFERTAS (SE AÑADEN EN PIPELINE)
        // =========================
        $dto->offers = [];
    
        return $dto;
    }

    // =========================
    // OFFER
    // =========================

    public static function offer(array $data): OfferDTO
    {
        $dto = new OfferDTO();

        $dto->merchantId    = isset($data['merchant_id']) ? (string) $data['merchant_id'] : null;
        $dto->merchantName  = (string) ($data['merchant_name'] ?? '');
        $dto->price         = isset($data['price']) ? (float) $data['price'] : 0.0;
        $dto->inStock       = (bool) ($data['in_stock'] ?? false);
        $dto->variantId     = (string) ($data['variant_id'] ?? '');
        $dto->currency      = 'EUR';
        $dto->lastSeenAt    = date('Y-m-d H:i:s');

        $rawSize = $data['size'] ?? '';
        $dto->size = trim((string) $rawSize) !== '' ? (string) $rawSize : 'UNICA';

        if (!empty($data['url'])) {
            $dto->url = (string) $data['url'];
        }

        if (!empty($data['tracking_url'])) {
            $dto->trackingUrl = (string) $data['tracking_url'];
        }

        return $dto;
    }
}
