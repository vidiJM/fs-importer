<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

defined('ABSPATH') || exit;

final class Cache_Manager
{
    private const DEFAULT_TTL = 600; // fallback 10 min
    private const OPTION_NAME = 'fs_shortcode_suite_settings';

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
     * Obtiene valor cacheado (si cache está activa).
     */
    public function get(string $key): ?array
    {
        if (!$this->is_cache_enabled()) {
            return null;
        }

        $cached = get_transient($key);

        if ($cached === false) {
            return null;
        }

        return is_array($cached) ? $cached : null;
    }

    /**
     * Guarda resultado en cache.
     */
    public function set(string $key, array $data): void
    {
        if (!$this->is_cache_enabled()) {
            return;
        }

        $ttl = $this->get_ttl();

        set_transient($key, $data, $ttl);
    }

    /**
     * Permite limpiar manualmente si se necesita.
     */
    public function delete(string $key): void
    {
        delete_transient($key);
    }

    /**
     * Limpia todo el namespace del grid (brute safe).
     */
    public function flush_grid_cache(): void
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fs_grid_%'
             OR option_name LIKE '_transient_timeout_fs_grid_%'"
        );
    }

    /**
     * Comprueba si cache está activada.
     */
    private function is_cache_enabled(): bool
    {
        $settings = get_option(self::OPTION_NAME);

        if (!is_array($settings)) {
            return true;
        }

        return !empty($settings['enable_cache']);
    }

    /**
     * Obtiene TTL desde settings.
     */
    private function get_ttl(): int
    {
        $settings = get_option(self::OPTION_NAME);

        if (!is_array($settings)) {
            return self::DEFAULT_TTL;
        }

        $ttl_minutes = (int) ($settings['cache_ttl'] ?? 10);

        if ($ttl_minutes <= 0) {
            return self::DEFAULT_TTL;
        }

        return $ttl_minutes * 60;
    }
}
