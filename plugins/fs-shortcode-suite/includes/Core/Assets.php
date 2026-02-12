<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

final class Assets {

    public function init(): void {

        add_action( 'wp_enqueue_scripts', [ $this, 'register' ] );
    }

    public function register(): void {

        wp_register_style(
            'fs-grid',
            FS_SC_SUITE_URL . 'public/css/grid.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_register_script(
            'fs-grid',
            FS_SC_SUITE_URL . 'public/js/grid.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );
    }

    public static function enqueue_grid(): void {

        wp_enqueue_style( 'fs-grid' );
        wp_enqueue_script( 'fs-grid' );
    }
}
