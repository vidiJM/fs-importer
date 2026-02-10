<?php
/**
 * Plugin Name: FS Importer – Sprinter
 * Description: Importador de feed Sprinter (SSV) con vista previa.
 * Version: 1.1.5
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FS_SPRINTER_PATH', plugin_dir_path(__FILE__));
define('FS_SPRINTER_URL', plugin_dir_url(__FILE__));

// =========================
// VALIDACIÓN CORE
// =========================
if (!defined('FS_IMPORTER_CORE_PATH')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>FS Importer – Sprinter</strong> requiere que el plugin <strong>FS Importer Core</strong> esté activo.';
        echo '</p></div>';
    });
    return;
}

// =========================
// AUTOLOAD COMPOSER
// =========================
$autoload = FS_SPRINTER_PATH . 'vendor/autoload.php';
if (!file_exists($autoload)) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>FS Importer Sprinter:</strong> Dependencias no instaladas (vendor/autoload.php).';
        echo '</p></div>';
    });
    return;
}
require_once $autoload;

// =========================
// CARGA DE ASSETS SOLO EN PÁGINA DE PREVIEW
// =========================
add_action('admin_enqueue_scripts', function () {
    $currentPage = $_GET['page'] ?? '';
    if ($currentPage !== 'fs-importer') {
        return;
    }

    wp_enqueue_style(
        'fs-preview-style',
        FS_SPRINTER_URL . 'assets/fs-preview.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'fs-preview-script',
        FS_SPRINTER_URL . 'assets/fs-preview.js',
        [],
        '1.0',
        true
    );
});

// =========================
// INICIALIZACIÓN DE COMPONENTES
// =========================
require_once FS_SPRINTER_PATH . 'src/Admin/Menu.php';
require_once FS_SPRINTER_PATH . 'src/Admin/Assets.php';
require_once FS_SPRINTER_PATH . 'src/Feed/SsvReader.php';

// FIX: el pipeline usa SprinterRowMapper (no SprinterMapper)
if (file_exists(FS_SPRINTER_PATH . 'src/Feed/SprinterRowMapper.php')) {
    require_once FS_SPRINTER_PATH . 'src/Feed/SprinterRowMapper.php';
} else {
    // fallback por compatibilidad si el proyecto realmente lo llama SprinterMapper.php
    require_once FS_SPRINTER_PATH . 'src/Feed/SprinterMapper.php';
}

require_once FS_SPRINTER_PATH . 'src/Controller/PreviewController.php';
require_once FS_SPRINTER_PATH . 'src/Importer/SprinterOfferImporter.php';

add_action('plugins_loaded', function () {
    \FS\ImporterSprinter\Admin\Menu::init();
    \FS\ImporterSprinter\Admin\Assets::init();
});

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('fs:import-sprinter', \FS\ImporterSprinter\CLI\SprinterImportCommand::class);
}

// =========================
// INVALIDACIÓN LIGERA DE CACHÉ AL TERMINAR IMPORT
// =========================
add_action('fs_import_finished', static function (): void {
    // Cache versioning: cualquier consumidor (p.ej. BFS REST) puede incorporar este número en su cache key.
    $ver = (int) get_option('bfs_cache_version', 1);
    update_option('bfs_cache_version', $ver + 1, false);

    if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
        \WP_CLI::log('Cache version bumped: bfs_cache_version=' . (string) ($ver + 1));
    }
}, 10, 0);

// =========================
// MANEJO DE ERRORES CENTRALIZADO
// =========================
add_action(
    'fs_importer_core_import_error',
    fn(Throwable $e) => \FS\ImporterCore\Support\Logger::error($e)
);
