<?php
declare(strict_types=1);
namespace BFS\Shortcodes;

use BFS\Helpers\BfsPreviewBuilder;

defined('ABSPATH') || exit;

final class ProductCarouselShortcode
{
    /**
     * Registrar el shortcode
     */
    public static function register(): void
    {
        add_shortcode('bfs_carousel', [self::class, 'render']);
    }

    /**
     * Renderizar el shortcode
     * 
     * Uso:
     * [bfs_carousel genero="hombre" limit="12"]
     */
    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'genero' => '',
            'limit'  => 12,
        ], $atts);

        $genero = sanitize_text_field($atts['genero']);
        $limit  = max(1, (int) $atts['limit']);

        
        /**
         * Encolar assets solo cuando se usa el shortcode (performance)
         */
        wp_enqueue_style('bfs-swiper');
        wp_enqueue_script('bfs-swiper');
        wp_enqueue_style('bfs-carousel');
        wp_enqueue_script('bfs-carousel');

/**
         * Obtener los productos desde el builder
         */
        $products = BfsPreviewBuilder::build($genero, $limit);

        ob_start();

        /**
         * Cargar la vista del carrusel
         */
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-product-carousel.php';

        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>Error: vista del carrusel no encontrada.</p>';
        }

        return ob_get_clean();
    }
    
    public static function mapColor(string $color): string
    {
        $color = strtoupper(trim($color));
    
        $map = [
            "NEGRO" => "#000000",
            "GRIS" => "#808080",
            "BLANCO" => "#FFFFFF",
            "ROJO" => "#E10600",
            "AZUL" => "#0043CE",
            "VERDE" => "#00A62B",
            "AMARILLO" => "#FFD200",
            "NARANJA" => "#FF6A00",
            "ROSA" => "#FF9BCF",
            "MORADO" => "#6B0AFF",
            "MULTICOLOR" => "linear-gradient(45deg, red, blue, green)"
        ];
    
        return $map[$color] ?? '#CCC';
    }
}
