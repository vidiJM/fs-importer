<?php
namespace FS\ImporterCore\DTO;

/**
 * DTO de oferta (tienda + variante)
 */
final class OfferDTO
{
    public ?string $merchantId = null;
    public string  $merchantName = '';
    public float   $price = 0.0;
    public bool    $inStock = false;
    public string  $size = 'UNICA';

    public ?string $url = null;
    public ?string $trackingUrl = null;

    // 🔥 NECESARIOS PARA EL IMPORT
    public ?string $variantId = null;
    public string $currency = 'EUR';
    public ?string $lastSeenAt = null;
}
