<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

defined('ABSPATH') || exit;

final class Assets {

    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'register']);
    }

    /**
     * Registro global de assets
     */
    public function register(): void {

        /*
        |--------------------------------------------------------------------------
        | GRID
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        wp_register_style(
            'fs-search',
            FS_SC_SUITE_URL . 'public/css/search.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_register_script(
            'fs-search',
            FS_SC_SUITE_URL . 'public/js/search.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue GRID
    |--------------------------------------------------------------------------
    */

    public static function enqueue_grid(): void {

        wp_enqueue_style('fs-grid');
        wp_enqueue_script('fs-grid');
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue SEARCH
    |--------------------------------------------------------------------------
    */

    public static function enqueue_search(): void {

        wp_enqueue_style('fs-search');
        wp_enqueue_script('fs-search');
    }
}
