<?php
namespace FS\ImporterCore\Preview;

final class PreviewProduct {

    public string $productId;
    public string $brand;
    public string $model;
    public ?string $image = null;

    /** @var PreviewVariant[] */
    public array $variants = [];

    public float $minPrice = 0.0;

    /** @var string[] */
    public array $merchants = [];
}
