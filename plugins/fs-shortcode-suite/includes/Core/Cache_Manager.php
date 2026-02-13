<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

defined('ABSPATH') || exit;

final class Cache_Manager
{
    private const TTL = 600; // 10 minutos

    /**
     * Genera clave única basada en filtros + página.
     */
    public function generate_key(array $filters, int $page, int $per_page): string
    {
        ksort($filters);

        $hash = md5(wp_json_encode($filters));

        return sprintf(
            'fs_grid_%s_page_%d_per_%d',
            $hash,
            $page,
            $per_page
        );
    }

    /**
     * Obtiene valor cacheado.
     */
    public function get(string $key): ?array
    {
        $cached = get_transient($key);

        if ($cached === false) {
            return null;
        }

        return $cached;
    }

    /**
     * Guarda resultado en cache.
     */
    public function set(string $key, array $data): void
    {
        set_transient($key, $data, self::TTL);
    }

    /**
     * Permite limpiar manualmente si se necesita.
     */
    public function delete(string $key): void
    {
        delete_transient($key);
    }
}
