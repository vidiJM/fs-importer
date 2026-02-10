<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

use FS\ImporterSprinter\Feed\SsvReader;
use FS\ImporterSprinter\Feed\SprinterRowMapper;
use FS\ImporterSprinter\Service\InferenceService;

final class SprinterImportPipeline
{
    /**
     * @param array{
     *   batch?: int,
     *   limit?: int,
     *   offset?: int,
     *   dry_run?: bool,
     *   log_every?: int
     * } $options
     */
    public static function run(string $filePath, array $options = []): array
    {
        $stats = [
            'rows_total'   => 0,
            'rows_read'    => 0,
            'rows_skipped' => 0,
            'rows_mapped'  => 0,
            'products'     => 0,
            'variants'     => 0,
            'offers'       => 0,
            'errors'       => 0,
            'dry_run'      => !empty($options['dry_run']) ? 1 : 0,
        ];

        $batch    = isset($options['batch']) ? (int) $options['batch'] : 500;
        $batch    = $batch > 0 ? $batch : 500;

        $limit    = isset($options['limit']) ? (int) $options['limit'] : 0;
        $limit    = $limit > 0 ? $limit : 0;

        $offset   = isset($options['offset']) ? (int) $options['offset'] : 0;
        $offset   = $offset > 0 ? $offset : 0;

        $dryRun   = !empty($options['dry_run']);

        $logEvery = isset($options['log_every']) ? (int) $options['log_every'] : 500;
        $logEvery = $logEvery > 0 ? $logEvery : 500;

        if (!is_file($filePath) || !is_readable($filePath)) {
            $stats['errors']++;
            self::log('ERROR: archivo no encontrado o no legible: ' . $filePath);
            return $stats;
        }

        // IDs de productos realmente tocados en este import (para agregación incremental)
        $touchedProductIds = [];

        $processed = 0;
        $lineIndex = 0; // cuenta de filas de datos (no header), para offset/limit

        foreach (SsvReader::readGenerator($filePath) as $row) {
            $stats['rows_total']++;
            $stats['rows_read']++;

            // offset
            if ($offset > 0 && $lineIndex < $offset) {
                $lineIndex++;
                $stats['rows_skipped']++;
                continue;
            }

            // limit
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $lineIndex++;
            $processed++;

            try {
                // 1️⃣ MAPEO
                $mapped = SprinterRowMapper::map($row);
                if (!$mapped || !isset($mapped['product'], $mapped['variant'], $mapped['offer'])) {
                    $stats['errors']++;
                    continue;
                }
                $stats['rows_mapped']++;

                // 2️⃣ INFERENCIA
                $mapped = InferenceService::apply($mapped);

                // 3️⃣ PRODUCTO
                if ($dryRun) {
                    $stats['products']++;
                    $stats['variants']++;
                    $stats['offers']++;
                    continue;
                }

                $productPostId = SprinterProductImporter::upsert($mapped['product']);
                if (!$productPostId) {
                    throw new \RuntimeException('Producto no creado');
                }
                $productPostId = (int) $productPostId;
                $touchedProductIds[$productPostId] = true;
                $stats['products']++;

                // 4️⃣ VARIANTE
                $mapped['variant']['product_wp_post_id'] = $productPostId;

                $variantPostId = SprinterVariantImporter::upsert($mapped['variant']);
                if (!$variantPostId) {
                    throw new \RuntimeException('Variante no creada');
                }
                $stats['variants']++;

                // 5️⃣ OFERTA
                $offerPostId = SprinterOfferImporter::upsert($mapped['offer'], (int) $variantPostId);
                if ($offerPostId) {
                    $stats['offers']++;
                }

            } catch (\Throwable $e) {
                $stats['errors']++;
                error_log('[FS IMPORT ERROR] Row ' . $processed . ': ' . $e->getMessage());
            }

            if ($processed % $logEvery === 0) {
                self::log('Progreso: procesadas ' . $processed . ' filas. Errors: ' . $stats['errors']);
            }

            if ($processed % $batch === 0) {
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        }

        // 6️⃣ AGREGACIÓN FINAL (solo productos tocados)
        if (!$dryRun) {
            $productIds = array_keys($touchedProductIds);
            if ($productIds) {
                AggregationService::run($productIds);
            }

            do_action('fs_import_finished', [
                'file'   => $filePath,
                'stats'  => $stats,
                'time'   => time(),
                'products_touched' => $productIds ?? [],
            ]);
        }

        return $stats;
    }

    private static function log(string $message): void
    {
        if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
            \WP_CLI::log($message);
        }
    }
}
