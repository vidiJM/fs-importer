<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class System_Page {

    public function render(): void {

        global $wpdb;

        $memory_limit = ini_get('memory_limit');
        $php_version  = phpversion();
        $wp_version   = get_bloginfo('version');
        $mysql_version = $wpdb->db_version();

        $cache_enabled = function_exists('wp_cache_get') ? 'Sí' : 'No';
        $rest_enabled  = rest_url() ? 'Sí' : 'No';

        ?>
        <div class="fs-admin-wrap">

            <div class="fs-admin-header">
                <h1>System Information</h1>
                <p>Información técnica del entorno para diagnóstico y soporte.</p>
            </div>

            <div class="fs-admin-card">

                <h2>Entorno del servidor</h2>

                <table class="widefat striped" style="margin-top:20px;">
                    <tbody>
                        <tr>
                            <td><strong>Plugin Version</strong></td>
                            <td><?php echo esc_html(FS_SC_SUITE_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version</strong></td>
                            <td><?php echo esc_html($php_version); ?></td>
                        </tr>
                        <tr>
                            <td><strong>WordPress Version</strong></td>
                            <td><?php echo esc_html($wp_version); ?></td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Version</strong></td>
                            <td><?php echo esc_html($mysql_version); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Memory Limit</strong></td>
                            <td><?php echo esc_html($memory_limit); ?></td>
                        </tr>
                        <tr>
                            <td><strong>REST API disponible</strong></td>
                            <td><?php echo esc_html($rest_enabled); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Object Cache disponible</strong></td>
                            <td><?php echo esc_html($cache_enabled); ?></td>
                        </tr>
                    </tbody>
                </table>

                <hr style="margin:40px 0;">

                <h2>Estado del sistema FS</h2>

                <table class="widefat striped" style="margin-top:20px;">
                    <tbody>
                        <tr>
                            <td><strong>CPT fs_producto</strong></td>
                            <td><?php echo post_type_exists('fs_producto') ? 'Activo' : 'No detectado'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>CPT fs_variante</strong></td>
                            <td><?php echo post_type_exists('fs_variante') ? 'Activo' : 'No detectado'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>CPT fs_oferta</strong></td>
                            <td><?php echo post_type_exists('fs_oferta') ? 'Activo' : 'No detectado'; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top:40px;">
                    <button class="fs-btn-primary" id="fs-copy-system-info">
                        Copiar información
                    </button>
                </div>

            </div>

        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('fs-copy-system-info');
            if (!btn) return;

            btn.addEventListener('click', function () {
                const rows = document.querySelectorAll('.widefat tbody tr');
                let text = '';

                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length === 2) {
                        text += cells[0].innerText + ': ' + cells[1].innerText + '\n';
                    }
                });

                navigator.clipboard.writeText(text);
                btn.innerText = 'Copiado ✔';
            });
        });
        </script>

        <?php
    }
}
