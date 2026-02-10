<?php
namespace BFS\Helpers;

defined('ABSPATH') || exit;

final class BfsPreviewOffer
{
    /** @var string Nombre del comerciante */
    public string $merchantName = '';

    /** @var int ID del comerciante */
    public int $merchantId = 0;

    /** @var float Precio */
    public float $price = 0.0;

    /** @var bool Si hay stock */
    public bool $inStock = false;

    /** @var string|null Talla EU de esta oferta */
    public ?string $size = null;
}
