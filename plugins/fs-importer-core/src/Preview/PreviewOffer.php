<?php
namespace FS\ImporterCore\Preview;

final class PreviewOffer {
    public string $merchantName;
    public ?string $merchantId = null;

    public float $price;
    public string|int $size;
    public bool $inStock;
}
