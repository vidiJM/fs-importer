<?php
/**
 * Plugin Name: FS Importer – Core
 * Description: Núcleo compartido para normalización y lógica de importación.
 * Author: Vidal Joven Montull
 * Url: https://botasfutsal.com
 * Version: 0.2.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FS_IMPORTER_CORE_PATH', plugin_dir_path(__FILE__));
define('FS_IMPORTER_CORE_URL', plugin_dir_url(__FILE__));

$autoload = FS_IMPORTER_CORE_PATH . 'vendor/autoload.php';

if (!file_exists($autoload)) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>FS Importer Core:</strong> Dependencias no instaladas.';
        echo '</p></div>';
    });
    return;
}

require_once $autoload;
