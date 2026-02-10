<?php
namespace FS\ImporterCore\Support;

/**
 * Cargador centralizado de ficheros de configuración (JSON)
 * - Cachea en memoria
 * - No rompe si el fichero no existe
 * - Alineado con runtime WordPress (sin Composer)
 */
final class ConfigLoader
{
    /**
     * Cache en memoria por request
     * @var array<string, array>
     */
    private static array $cache = [];

    /**
     * Carga un fichero JSON desde /data
     *
     * @param string $file Ej: 'model_stopwords.json'
     * @return array
     */
    public static function load(string $file): array
    {
        if (isset(self::$cache[$file])) {
            return self::$cache[$file];
        }

        $path = FS_IMPORTER_CORE_PATH . 'data/' . $file;

        if (!file_exists($path)) {
            // Fichero inexistente → array vacío (fail-safe)
            return self::$cache[$file] = [];
        }

        $json = json_decode(file_get_contents($path), true);

        if (!is_array($json)) {
            return self::$cache[$file] = [];
        }

        return self::$cache[$file] = $json;
    }
}
