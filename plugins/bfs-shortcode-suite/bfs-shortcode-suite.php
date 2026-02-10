<?php
/**
 * Plugin Name: BFS Shortcode Suite
 * Plugin URI:  https://botasfutsal.com
 * Description: Shortcodes profesionales para mostrar productos en carrusel Swiper con variantes, colores, tallas y precios dinámicos.
 * Version: 4.6.4
 * Author:      Vidal Joven Montull
 * Text Domain: bfs-shortcodes
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DEFINIMOS CONSTANTES
 */
if (!defined('BFS_SHORTCODES_PATH')) {
    define('BFS_SHORTCODES_PATH', plugin_dir_path(__FILE__));
    define('BFS_SHORTCODES_URL', plugin_dir_url(__FILE__));
    define('BFS_SHORTCODES_VERSION', '4.6.4');
}

/**
 * Image sizes (optimized for icons, no hard-crop).
 * Note: If the size doesn't exist for an attachment yet, WordPress will fall back to original.
 */
add_action('after_setup_theme', static function (): void {
    add_image_size('bfs_icon_128', 128, 128, false);
}, 20);

/**
 * AUTOLOAD DE CLASES DEL PLUGIN
 */
spl_autoload_register(static function ($class): void {
    if (strpos((string) $class, 'BFS\\') !== 0) {
        return;
    }

    // Convertir namespace → ruta de archivo
    $relative = str_replace(['BFS\\', '\\'], ['', '/'], (string) $class);
    $path = BFS_SHORTCODES_PATH . 'includes/' . $relative . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

/**
 * REGISTRAR SHORTCODES
 */
add_action('init', static function (): void {
    // Carrusel
    if (class_exists('BFS\\Shortcodes\\ProductCarouselShortcode')) {
        BFS\Shortcodes\ProductCarouselShortcode::register();
    }

    // Buscador premium
    if (class_exists('BFS\\Shortcodes\\SearchBarShortcode')) {
        BFS\Shortcodes\SearchBarShortcode::register();
    }

    // Ficha de producto (Nike-like)
    if (class_exists('BFS\\Shortcodes\\ProductDetailShortcode')) {
        BFS\Shortcodes\ProductDetailShortcode::register();
    }

    // Grid minimalista (Adidas/Nike)
    if (class_exists('BFS\\Shortcodes\\ProductGridShortcode')) {
        BFS\Shortcodes\ProductGridShortcode::register();
    }

    // Finder / comparador (encuesta + TOP 3)
    if (class_exists('BFS\\Shortcodes\\ProductFinderShortcode')) {
        BFS\Shortcodes\ProductFinderShortcode::register();
    }

    // Finder results page
    if (class_exists('BFS\\Shortcodes\\FinderResultsShortcode')) {
        BFS\Shortcodes\FinderResultsShortcode::register();
    }

    // Home hero (sección home)
    if (class_exists('BFS\\Shortcodes\\HomeHeroShortcode')) {
        BFS\Shortcodes\HomeHeroShortcode::register();
    }

    // Home features (iconos + texto)
    if (class_exists('BFS\\Shortcodes\\HomeFeaturesShortcode')) {
        BFS\Shortcodes\HomeFeaturesShortcode::register();
    }
    if (class_exists('BFS\\Shortcodes\\HomeFeatureItemShortcode')) {
        BFS\Shortcodes\HomeFeatureItemShortcode::register();
    }

    // Guía de tallas (segura, orientativa)
    if (class_exists('BFS\\Shortcodes\\SizeGuideShortcode')) {
        BFS\Shortcodes\SizeGuideShortcode::register();
    }
});

/**
 * Registrar assets (CSS & JS) – se registran aquí y se encolan bajo demanda desde el shortcode.
 */
add_action('wp_enqueue_scripts', static function (): void {
    // Swiper (registramos, no encolamos globalmente)
    wp_register_style(
        'bfs-swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        [],
        '11.0'
    );

    wp_register_script(
        'bfs-swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        '11.0',
        true
    );

    // CSS del carrusel
    wp_register_style(
        'bfs-carousel',
        BFS_SHORTCODES_URL . 'public/css/bfs-carousel.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // JS del carrusel
    wp_register_script(
        'bfs-carousel',
        BFS_SHORTCODES_URL . 'public/js/bfs-carousel.js',
        ['bfs-swiper'],
        BFS_SHORTCODES_VERSION,
        true
    );

    // CSS de ficha de producto
    wp_register_style(
        'bfs-product-detail',
        BFS_SHORTCODES_URL . 'public/css/bfs-product-detail.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // JS de ficha de producto
    wp_register_script(
        'bfs-product-detail',
        BFS_SHORTCODES_URL . 'public/js/bfs-product-detail.js',
        [],
        BFS_SHORTCODES_VERSION,
        true
    );

    // CSS del buscador
    wp_register_style(
        'bfs-search',
        BFS_SHORTCODES_URL . 'public/css/bfs-search.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // JS del buscador
    wp_register_script(
        'bfs-search',
        BFS_SHORTCODES_URL . 'public/js/bfs-search.js',
        [],
        BFS_SHORTCODES_VERSION,
        true
    );

    // CSS del grid minimalista
    wp_register_style(
        'bfs-grid',
        BFS_SHORTCODES_URL . 'public/css/bfs-grid.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // JS del grid minimalista
    wp_register_script(
        'bfs-grid',
        BFS_SHORTCODES_URL . 'public/js/bfs-grid.js',
        [],
        BFS_SHORTCODES_VERSION,
        true
    );

    // CSS del finder (encuesta + comparador)
    wp_register_style(
        'bfs-finder',
        BFS_SHORTCODES_URL . 'public/css/bfs-finder.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // JS del finder
    wp_register_script(
        'bfs-finder',
        BFS_SHORTCODES_URL . 'public/js/bfs-finder.js',
        [],
        BFS_SHORTCODES_VERSION,
        true
    );

    // CSS del hero (home)
    wp_register_style(
        'bfs-home-hero',
        BFS_SHORTCODES_URL . 'public/css/bfs-home-hero.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // CSS de features (home)
    wp_register_style(
        'bfs-home-features',
        BFS_SHORTCODES_URL . 'public/css/bfs-home-features.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // CSS de guía de tallas (página /guia-tallas)
    wp_register_style(
        'bfs-size-guide',
        BFS_SHORTCODES_URL . 'public/css/bfs-size-guide.css',
        [],
        BFS_SHORTCODES_VERSION
    );

    // (Opcional) Defer para scripts propios (mejora LCP)
    if (function_exists('wp_script_add_data')) {
        wp_script_add_data('bfs-carousel', 'strategy', 'defer');
        wp_script_add_data('bfs-search', 'strategy', 'defer');
        wp_script_add_data('bfs-grid', 'strategy', 'defer');
        wp_script_add_data('bfs-finder', 'strategy', 'defer');
        wp_script_add_data('bfs-product-detail', 'strategy', 'defer');
        // Swiper lo dejo sin defer por compatibilidad; si quieres lo activamos también.
    }
}, 10);

/**
 * Frontend performance: enqueue assets early if the current request contains our shortcodes.
 * - Keeps existing "enqueue inside shortcode render" as a safe fallback.
 * - Avoids late enqueues that can cause small layout shifts in some builders/themes.
 */
add_action('wp_enqueue_scripts', static function (): void {
    if (is_admin()) {
        return;
    }

    $post_id = 0;

    if (is_singular()) {
        $post_id = (int) get_queried_object_id();
    } elseif (is_front_page()) {
        $post_id = (int) get_option('page_on_front');
    }

    if ($post_id <= 0) {
        return;
    }

    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return;
    }

    // Quick guard
    if (strpos($content, '[') === false) {
        return;
    }

    $needs = [
        'swiper'         => false,
        'carousel'       => false,
        'search'         => false,
        'product_detail' => false,
        'grid'           => false,
        'finder'         => false,
        'home_hero'      => false,
        'home_features'  => false,
        'size_guide'     => false,
    ];

    // Map shortcodes → needs.
    if (has_shortcode($content, 'bfs_carousel')) {
        $needs['swiper']   = true;
        $needs['carousel'] = true;
    }

    if (has_shortcode($content, 'bfs_search_bar')) {
        $needs['search'] = true;
    }

    if (has_shortcode($content, 'bfs_product_detail')) {
        $needs['product_detail'] = true;
    }

    if (has_shortcode($content, 'bfs_grid')) {
        $needs['grid'] = true;
    }

    if (has_shortcode($content, 'bfs_finder') || has_shortcode($content, 'bfs_finder_results')) {
        $needs['finder'] = true;
    }

    if (has_shortcode($content, 'bfs_home_hero')) {
        $needs['home_hero'] = true;
    }

    if (has_shortcode($content, 'bfs_home_features') || has_shortcode($content, 'bfs_home_feature')) {
        $needs['home_features'] = true;
    }

    if (has_shortcode($content, 'bfs_size_guide')) {
        $needs['size_guide'] = true;
    }

    // Enqueue only what we need.
    if ($needs['swiper']) {
        wp_enqueue_style('bfs-swiper');
        wp_enqueue_script('bfs-swiper');
        $GLOBALS['bfs_shortcodes_needs_jsdelivr'] = true;
    }

    if ($needs['carousel']) {
        wp_enqueue_style('bfs-carousel');
        wp_enqueue_script('bfs-carousel');
    }

    if ($needs['search']) {
        wp_enqueue_style('bfs-search');
        wp_enqueue_script('bfs-search');
    }

    if ($needs['product_detail']) {
        wp_enqueue_style('bfs-product-detail');
        wp_enqueue_script('bfs-product-detail');
    }

    if ($needs['grid']) {
        wp_enqueue_style('bfs-grid');
        wp_enqueue_script('bfs-grid');
    }

    if ($needs['finder']) {
        wp_enqueue_style('bfs-finder');
        wp_enqueue_script('bfs-finder');
    }

    if ($needs['home_hero']) {
        wp_enqueue_style('bfs-home-hero');
    }

    if ($needs['home_features']) {
        wp_enqueue_style('bfs-home-features');
    }

    if ($needs['size_guide']) {
        wp_enqueue_style('bfs-size-guide');
    }
}, 20);

/**
 * Resource hints for CDN assets (low-risk win on first-load).
 * Only added when the carousel (Swiper) is used on the page.
 */
add_filter('wp_resource_hints', static function (array $urls, string $relation_type): array {
    if (empty($GLOBALS['bfs_shortcodes_needs_jsdelivr'])) {
        return $urls;
    }

    if ($relation_type === 'dns-prefetch') {
        $urls[] = '//cdn.jsdelivr.net';
    }

    if ($relation_type === 'preconnect') {
        $urls[] = 'https://cdn.jsdelivr.net';
    }

    return $urls;
}, 10, 2);

/**
 * ADMIN: menú con documentación de shortcodes (solo en wp-admin)
 */
add_action('plugins_loaded', static function (): void {
    if (is_admin() && class_exists('BFS\\Admin\\Menu')) {
        BFS\Admin\Menu::register();
    }
});

/**
 * REST: endpoints de búsqueda (público)
 */
add_action('plugins_loaded', static function (): void {
    if (class_exists('BFS\\Rest\\SearchController')) {
        BFS\Rest\SearchController::register();
    }

    if (class_exists('BFS\\Rest\\PreviewController')) {
        BFS\Rest\PreviewController::register();
    }

    if (class_exists('BFS\\Rest\\FinderController')) {
        BFS\Rest\FinderController::register();
    }
});
