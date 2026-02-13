<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin;

use FS\ShortcodeSuite\Admin\Pages\Dashboard_Page;
use FS\ShortcodeSuite\Admin\Pages\Grid_Page;
use FS\ShortcodeSuite\Admin\Pages\Settings_Page;
use FS\ShortcodeSuite\Admin\Pages\System_Page;

defined('ABSPATH') || exit;

final class Admin_Menu {

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void {

        add_menu_page(
            'FS Shortcode Suite',
            'FS Shortcodes',
            'manage_options',
            'fs-shortcode-suite',
            [new Dashboard_Page(), 'render'],
            'dashicons-screenoptions',
            58
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'fs-shortcode-suite',
            [new Dashboard_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'FS Grid',
            'FS Grid',
            'manage_options',
            'fs-shortcode-suite-grid',
            [new Grid_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'Settings',
            'Settings',
            'manage_options',
            'fs-shortcode-suite-settings',
            [new Settings_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'System Info',
            'System Info',
            'manage_options',
            'fs-shortcode-suite-system',
            [new System_Page(), 'render']
        );
    }

    public function enqueue_assets(string $hook): void {

        if (strpos($hook, 'fs-shortcode-suite') === false) {
            return;
        }

        wp_enqueue_style(
            'fs-admin-style',
            FS_SC_SUITE_URL . 'includes/Admin/Assets/admin.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_enqueue_script(
            'fs-admin-script',
            FS_SC_SUITE_URL . 'includes/Admin/Assets/admin.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );
    }
}
