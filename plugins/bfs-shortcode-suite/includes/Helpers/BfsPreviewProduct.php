<?php
declare(strict_types=1);

namespace BFS\Helpers;

defined('ABSPATH') || exit;

final class BfsPreviewProduct
{
    /** @var string */
    public string $productId = '';

    /** @var int|null Post ID del fs_producto */
    public ?int $postId = null;

    /** @var string Permalink real del producto */
    public string $permalink = '';

    /** @var string Nombre mostrado (fs_model_signature) */
    public string $title = '';

    /** @var string Imagen principal */
    public string $image = '';

    /** @var string Imagen hover (2ª imagen si existe) */
    public string $imageHover = '';

    /** @var float Precio mínimo entre todas las variantes */
    public float $minPrice = 0.0;

    /**
     * Variantes indexadas por color
     *
     * @var array<string, BfsPreviewVariant>
     */
    public array $variants = [];
}
