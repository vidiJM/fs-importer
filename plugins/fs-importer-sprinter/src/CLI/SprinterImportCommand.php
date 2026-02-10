<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\CLI;

use FS\ImporterSprinter\Importer\SprinterImportPipeline;

final class SprinterImportCommand
{
    /**
     * Importa el feed Sprinter desde un CSV normalizado.
     *
     * ## OPTIONS
     *
     * <csv_file>
     * : Ruta al archivo CSV
     *
     * [--batch=<n>]
     * : Tamaño de batch para GC/limpieza (default: 500)
     *
     * [--limit=<n>]
     * : Máximo de filas a procesar (0 = sin límite)
     *
     * [--offset=<n>]
     * : Saltar las primeras N filas
     *
     * [--dry-run]
     * : No escribe en BD (solo simula y devuelve stats)
     *
     * [--log-every=<n>]
     * : Log de progreso cada N filas (default: 500)
     *
     * ## EXAMPLES
     *     wp fs:import-sprinter wp-content/uploads/feeds/normalizer_feed_sprinter.csv --dry-run
     *     wp fs:import-sprinter wp-content/uploads/feeds/normalizer_feed_sprinter.csv --batch=500 --log-every=500
     *     wp fs:import-sprinter wp-content/uploads/feeds/normalizer_feed_sprinter.csv --limit=2000 --offset=10000
     *
     * @when after_wp_load
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        $file = $args[0] ?? null;

        if (!$file || !is_string($file) || !file_exists($file)) {
            \WP_CLI::error('Archivo CSV no encontrado: ' . (string) $file);
            return;
        }

        $options = [
            'batch'     => isset($assoc_args['batch']) ? (int) $assoc_args['batch'] : 500,
            'limit'     => isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 0,
            'offset'    => isset($assoc_args['offset']) ? (int) $assoc_args['offset'] : 0,
            'dry_run'   => isset($assoc_args['dry-run']) ? true : false,
            'log_every' => isset($assoc_args['log-every']) ? (int) $assoc_args['log-every'] : 500,
        ];

        \WP_CLI::log('Importando desde: ' . $file);
        \WP_CLI::log('Opciones: ' . wp_json_encode($options));

        $stats = SprinterImportPipeline::run($file, $options);

        \WP_CLI::success('Importación completada:');
        foreach ($stats as $key => $val) {
            \WP_CLI::log(strtoupper((string) $key) . ': ' . (string) $val);
        }
    }
}
