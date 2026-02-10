<?php
namespace FS\ImporterCore\Signature;

use FS\ImporterCore\Normalizer\BrandNormalizer;
use FS\ImporterCore\Normalizer\ModelNormalizer;

final class ProductSignature {

    public static function make(string $brand, string $model): string
    {
        $brand = BrandNormalizer::normalize($brand);
        $model = ModelNormalizer::normalize($model);

        return sha1($brand . '|' . $model);
    }
}
