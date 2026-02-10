<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Admin;

final class Assets
{
    public static function init(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(string $hook): void
    {
        // Hooks típicos de WP para:
        // - toplevel_page_fs-importer
        // - fs-importer_page_fs-importer-sprinter
        $allowedHooks = [
            'toplevel_page_fs-importer',
            'fs-importer_page_fs-importer-sprinter',
        ];

        if (!in_array($hook, $allowedHooks, true)) {
            return;
        }

        if (!defined('FS_SPRINTER_URL') || !defined('FS_SPRINTER_PATH')) {
            return;
        }

        $rel = 'assets/admin.css';
        $path = FS_SPRINTER_PATH . $rel;
        $ver  = is_file($path) ? (string) filemtime($path) : '0.1.0';

        wp_enqueue_style(
            'fs-importer-sprinter-admin',
            FS_SPRINTER_URL . $rel,
            [],
            $ver
        );
    }
}
