<?php
namespace FS\ImporterCore\Signature;

use FS\ImporterCore\Normalizer\TextNormalizer;

/**final class VariantSignature
{
    public static function make(
        string $productId,
        ?string $gtin,
        string $colorBase
    ): string {
        if ($gtin) {
            return sha1($gtin);
        }

        $color = TextNormalizer::normalize($colorBase);

        return sha1($productId . '|' . $color);
    }
}

**/

final class VariantSignature
{
    public static function make(
        string $productId,
        ?string $gtin,
        string $colorBase
    ): string {

        // SI HAY GTIN, usarlo SIEMPRE (Sprinter lo usa como identificador único)
        if (!empty($gtin)) {
            return sha1(strtolower(trim($gtin)));
        }

        // Si no hay GTIN, construir firma alternativa estable
        return sha1(
            strtolower(trim($productId)) . '|' . 
            strtolower(trim(TextNormalizer::normalize($colorBase)))
        );
    }
}
