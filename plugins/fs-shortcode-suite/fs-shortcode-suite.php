<?php
/**
 * Plugin Name: FS Shortcode Suite
 * Plugin URI: https://botasfutsal.com
 * Description: Motor profesional de shortcodes optimizados para BOTASFUTSAL.
 * Version: 1.0.0
 * Author: Vidal Joven Montull
 * Author URI: https://botasfutsal.com
 * Text Domain: fs-shortcode-suite
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FS_SC_SUITE_PATH', plugin_dir_path(__FILE__));
define('FS_SC_SUITE_URL', plugin_dir_url(__FILE__));
define('FS_SC_SUITE_VERSION', '1.0.0');

/*
|--------------------------------------------------------------------------
| PSR-4 Autoloader
|--------------------------------------------------------------------------
*/

spl_autoload_register(function (string $class) {

    if (strpos($class, 'FS\\ShortcodeSuite\\') !== 0) {
        return;
    }

    $relative = str_replace(
        ['FS\\ShortcodeSuite\\', '\\'],
        ['', '/'],
        $class
    );

    $file = FS_SC_SUITE_PATH . 'includes/' . $relative . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/*
|--------------------------------------------------------------------------
| Boot Plugin
|--------------------------------------------------------------------------
*/

$loader = new FS\ShortcodeSuite\Core\Loader();
$loader->init();
