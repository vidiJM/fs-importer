<?php
namespace FS\ImporterSprinter\Admin;

final class Menu {

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register']);
    }

    public static function register(): void
    {
        add_menu_page(
            'FS Importer',
            'FS Importer',
            'manage_options',
            'fs-importer',
            [self::class, 'render'],
            'dashicons-database-import',
            26
        );

        add_submenu_page(
            'fs-importer',
            'Sprinter',
            'Sprinter',
            'manage_options',
            'fs-importer-sprinter',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        (new \FS\ImporterSprinter\Controller\PreviewController())->handle();
    }
}
