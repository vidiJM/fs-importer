<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin;

final class Admin_Menu {

    public function init(): void {

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    public function register_menu(): void {

        add_menu_page(
            'FS Shortcode Suite',
            'FS Shortcodes',
            'manage_options',
            'fs-shortcode-suite',
            [ $this, 'render_page' ],
            'dashicons-screenoptions',
            58
        );
    }

    public function render_page(): void {

        echo '<div class="wrap">';
        echo '<h1>FS Shortcode Suite</h1>';

        echo '<div class="card">';
        echo '<h2>[fs_product_grid]</h2>';
        echo '<p>Grid premium optimizado para productos fs_producto.</p>';
        echo '</div>';

        echo '</div>';
    }
}
