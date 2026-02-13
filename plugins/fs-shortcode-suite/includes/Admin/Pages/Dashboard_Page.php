<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Dashboard_Page {

    public function render(): void {
        ?>
        <div class="fs-admin-wrap">
            <div class="fs-admin-header">
                <h1>FS Shortcode Suite</h1>
                <p>Arquitectura modular de shortcodes optimizados.</p>
            </div>

            <div class="fs-admin-card">
                <h2>Shortcodes disponibles</h2>

                <div class="fs-admin-grid">
                    <a href="<?php echo admin_url('admin.php?page=fs-shortcode-suite-grid'); ?>" class="fs-admin-tile">
                        <h3>FS Grid</h3>
                        <p>Grid offer-driven con filtros dinámicos.</p>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
