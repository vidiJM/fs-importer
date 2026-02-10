<?php
declare(strict_types=1);

namespace BFS\Helpers;

defined('ABSPATH') || exit;

final class BfsPreviewVariant
{
    /** @var string */
    public string $variantId = '';

    /** @var string Color en mayúsculas normalizado (fs_colour_raw) */
    public string $color = '';

    /** @var string URL principal de la imagen */
    public string $imageMain = '';

    /** @var string URL secundaria (hover) */
    public string $imageHover = '';

    /** @var float Precio mínimo de la variante con stock */
    public float $minPrice = 0.0;

    /**
     * Tallas disponibles con stock para esta variante
     *
     * Ejemplo: ['39' => true, '40' => true]
     *
     * @var array<string, bool>
     */
    public array $sizes = [];

    /**
     * Ofertas asociadas a esta variante
     *
     * @var BfsPreviewOffer[]
     */
    public array $offers = [];
}
