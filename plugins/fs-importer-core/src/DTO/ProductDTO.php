<?php
namespace FS\ImporterCore\DTO;

final class ProductDTO
{
    public string $productId;
    public string $brand;
    public string $model;
    public string $rawName;
    public ?string $image = null;
    public string $description = '';
    public array $info = [];

    /** @var VariantDTO[] */
    public array $variants = [];
}
