<?php
declare(strict_types=1);

namespace FS\ImporterCore\Importer;

use Generator;
use FS\ImporterCore\DTO\ProductDTO;

final class BatchImporter
{
    /**
     * @param Generator<ProductDTO[]> $batches
     */
    public static function importBatches(Generator $batches): void
    {
        foreach ($batches as $batch) {
            Importer::import($batch);

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }
}
