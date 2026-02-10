<?php
namespace FS\ImporterCore\Signature;

final class OfferSignature {

    public static function make(string $variantId, string $merchantId): string
    {
        return sha1($variantId . '|' . $merchantId);
    }
}
