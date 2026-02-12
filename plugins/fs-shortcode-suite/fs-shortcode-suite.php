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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FS_SC_SUITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FS_SC_SUITE_URL', plugin_dir_url( __FILE__ ) );
define( 'FS_SC_SUITE_VERSION', '1.0.0' );

/*
|--------------------------------------------------------------------------
| Cargar Autoloader primero (CRÍTICO)
|--------------------------------------------------------------------------
*/

require_once FS_SC_SUITE_PATH . 'includes/Core/Loader.php';

$loader = new FS\ShortcodeSuite\Core\Loader();
$loader->init();

/*
|--------------------------------------------------------------------------
| Ahora sí podemos cargar Plugin
|--------------------------------------------------------------------------
*/

FS\ShortcodeSuite\Core\Plugin::init();
