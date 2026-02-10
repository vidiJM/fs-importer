<?php
declare(strict_types=1);

namespace FS\ImporterCore\Importer;

use FS\ImporterCore\DTO\ProductDTO;
use FS\ImporterCore\Aggregator\ProductAggregator;
use Throwable;

final class Importer
{
    /**
     * @param ProductDTO[] $products
     * @return array{imported:int, failed:int}
     */
    public static function import(array $products): array
    {
        $imported = 0;
        $failed   = 0;

        foreach ($products as $product) {
            try {
                $productPostId = ProductImporter::upsert($product);

                foreach ($product->variants as $variant) {
                    $variantPostId = VariantImporter::upsert($variant, $productPostId);

                    foreach ($variant->offers as $offer) {
                        OfferImporter::upsert($offer, $variantPostId);
                        unset($offer);
                    }

                    unset($variant, $variantPostId);
                }

                ProductAggregator::aggregateProduct($productPostId);
                $imported++;

            } catch (Throwable $e) {
                $failed++;

                if (function_exists('do_action')) {
                    do_action(
                        'fs_importer_core_import_error',
                        $e,
                        $product
                    );
                }
            }

            unset($product, $productPostId);
        }

        return [
            'imported' => $imported,
            'failed'   => $failed,
        ];
    }
}
