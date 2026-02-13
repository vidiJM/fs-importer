<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Settings_Page {

    private string $option_name = 'fs_shortcode_suite_settings';

    public function render(): void {

        if (isset($_POST['fs_settings_nonce']) &&
            wp_verify_nonce($_POST['fs_settings_nonce'], 'fs_save_settings')) {

            $this->save_settings();
            echo '<div class="updated notice"><p>Settings guardados correctamente.</p></div>';
        }

        $settings = $this->get_settings();
        ?>

        <div class="fs-admin-wrap">

            <div class="fs-admin-header">
                <h1>Settings</h1>
                <p>Configuración global del motor FS Shortcode Suite.</p>
            </div>

            <div class="fs-admin-card">

                <form method="post">

                    <?php wp_nonce_field('fs_save_settings', 'fs_settings_nonce'); ?>

                    <div class="fs-form-grid">

                        <div class="fs-field">
                            <label>
                                <input type="checkbox" name="enable_cache" value="1"
                                    <?php checked($settings['enable_cache'], true); ?>>
                                Activar cache interna
                            </label>
                        </div>

                        <div class="fs-field">
                            <label>TTL Cache (minutos)</label>
                            <input type="number" name="cache_ttl"
                                   value="<?php echo esc_attr($settings['cache_ttl']); ?>"
                                   min="1" max="1440">
                        </div>

                        <div class="fs-field">
                            <label>Per Page por defecto</label>
                            <input type="number" name="default_per_page"
                                   value="<?php echo esc_attr($settings['default_per_page']); ?>"
                                   min="1" max="48">
                        </div>

                        <div class="fs-field">
                            <label>
                                <input type="checkbox" name="preload_secondary_image" value="1"
                                    <?php checked($settings['preload_secondary_image'], true); ?>>
                                Preload imagen secundaria
                            </label>
                        </div>

                        <div class="fs-field">
                            <label>
                                <input type="checkbox" name="debug_mode" value="1"
                                    <?php checked($settings['debug_mode'], true); ?>>
                                Activar modo debug
                            </label>
                        </div>

                    </div>

                    <button class="fs-btn-primary" type="submit">
                        Guardar cambios
                    </button>

                </form>

            </div>

        </div>

        <?php
    }

    private function save_settings(): void {

        $settings = [
            'enable_cache'            => isset($_POST['enable_cache']),
            'cache_ttl'               => max(1, (int) ($_POST['cache_ttl'] ?? 60)),
            'default_per_page'        => max(1, min(48, (int) ($_POST['default_per_page'] ?? 12))),
            'preload_secondary_image' => isset($_POST['preload_secondary_image']),
            'debug_mode'              => isset($_POST['debug_mode']),
        ];

        update_option($this->option_name, $settings);
    }

    public function get_settings(): array {

        $defaults = [
            'enable_cache'            => true,
            'cache_ttl'               => 60,
            'default_per_page'        => 12,
            'preload_secondary_image' => true,
            'debug_mode'              => false,
        ];

        $saved = get_option($this->option_name);

        if (!is_array($saved)) {
            return $defaults;
        }

        return array_merge($defaults, $saved);
    }
}
