<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;
use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Services\Grid_Service;

final class Product_Grid {

    public function register(): void {
        add_shortcode( 'fs_grid', [ $this, 'render' ] );
    }

    public function render( array $atts ): string {
        
        global $wpdb;

        $atts = shortcode_atts([
            'limit'      => 12,
            'gender'     => '',
            'age_group'  => '',
            'color'      => '',
        ], $atts );
        
        $filters = [
            'gender'    => sanitize_text_field($atts['gender']),
            'age_group' => sanitize_text_field($atts['age_group']),
            'color'     => sanitize_text_field($atts['color']),
        ];

        $limit = (int) $atts['limit'];

        $repository = new Product_Repository( $wpdb );
        $service    = new Grid_Service( $repository );
        $products = $service->get_grid( $limit, $filters );

        Assets::enqueue_grid();

        ob_start();

        echo '<div class="fs-grid">';

        foreach ( $products as $product ) {

            $id    = (int) $product['id'];
            $title = esc_html( $product['title'] ?? '' );
            $image = esc_url( $product['image'] ?? '' );
            $price = isset($product['price']) 
                ? number_format((float)$product['price'], 2, ',', '.') 
                : '';
        
            $data_json = esc_attr( wp_json_encode( $product ) );
        
            echo '<article class="fs-card" data-product="' . $data_json . '">';
        
                echo '<div class="fs-card__image">';
                    echo '<img src="' . $image . '" alt="' . $title . '" loading="lazy" decoding="async" data-image>';
                echo '</div>';
        
                echo '<div class="fs-card__body">';
                    echo '<h3 class="fs-card__title">' . $title . '</h3>';
                    echo '<span class="fs-card__price" data-price>' . $price . ' €</span>';
                    echo '<a href="#" class="fs-card__cta" data-cta target="_blank" rel="noopener">Ver oferta</a>';
                    echo '<div class="fs-card__colors"></div>';
                    echo '<div class="fs-card__sizes"></div>';
                echo '</div>';
        
            echo '</article>';
        }

        echo '</div>';

        return ob_get_clean();
    }
}
