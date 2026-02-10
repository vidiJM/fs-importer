<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

use BFS\Helpers\BfsProductDetailBuilder;

defined('ABSPATH') || exit;

final class ProductDetailShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_product_detail', [self::class, 'render']);
    }

    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'id'  => '',
            'spu' => '',
        ], $atts);

        $postId = 0;

        if (is_singular('fs_producto')) {
            $postId = (int) get_the_ID();
        }

        if (!empty($atts['id'])) {
            $postId = max(0, (int) $atts['id']);
        }

        $spu = sanitize_text_field((string) $atts['spu']);

        wp_enqueue_style('bfs-product-detail');
        wp_enqueue_script('bfs-product-detail');

        $data = BfsProductDetailBuilder::build($postId, $spu);
        if (empty($data)) {
            return '<p>' . esc_html__('No se ha encontrado el producto.', 'bfs-shortcodes') . '</p>';
        }

        ob_start();
        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-product-detail.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista de ficha no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }
}
