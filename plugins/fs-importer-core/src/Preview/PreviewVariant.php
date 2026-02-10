<?php
namespace FS\ImporterCore\Preview;

final class PreviewVariant
{
    public string $variantId;
    public string $color;

    /** imagen principal del color */
    public ?string $imageMain = null;

    /** galería de imágenes */
    public array $images = [];

    /** tallas disponibles */
    public array $sizes = [];

    /** ofertas */
    public array $offers = [];

    public float $minPrice = 0.0;
}
