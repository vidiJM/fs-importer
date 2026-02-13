<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Data\Services\Grid_Service;

defined('ABSPATH') || exit;

final class Product_Grid
{
    private Grid_Service $service;

    public function __construct(Grid_Service $service)
    {
        $this->service = $service;

        add_shortcode('fs_grid', [$this, 'render']);
    }

    /**
     * Render del shortcode [fs_grid]
     */
    public function render(array $atts = []): string
    {
        $atts = shortcode_atts([
            'color'     => '',
            'gender'    => '',
            'age_group' => '',
            'brand'     => '',
            'size'      => '',
            'per_page'  => 12,
        ], $atts, 'fs_grid');

        $filters = [
            'color'     => sanitize_title($atts['color']),
            'gender'    => sanitize_title($atts['gender']),
            'age_group' => sanitize_title($atts['age_group']),
            'brand'     => sanitize_title($atts['brand']),
            'size'      => sanitize_text_field($atts['size']),
        ];

        $page     = 1;
        $per_page = max(1, min(48, (int) $atts['per_page']));

        $result = $this->service->get_grid($filters, $page, $per_page);

        // Encolar assets solo si se usa el shortcode
        wp_enqueue_script('fs-grid');
        wp_enqueue_style('fs-grid');

        wp_localize_script('fs-grid', 'FSGridConfig', [
            'restUrl'  => esc_url_raw(rest_url('fs/v1/grid')),
            'filters'  => $filters,
            'page'     => $page,
            'perPage'  => $per_page,
            'hasMore'  => $result['has_more'],
        ]);

        ob_start();

        echo '<div class="fs-grid-wrapper" data-page="1">';

        foreach ($result['items'] as $product) {
            $this->render_card($product);
        }

        echo '</div>';

        if ($result['has_more']) {
            echo '<button type="button" class="fs-grid-load-more">Ver más</button>';
        }

        return ob_get_clean();
    }

    /**
     * Render de una tarjeta de producto
     *
     * @param array<string, mixed> $product
     */
    private function render_card(array $product): void
    {
        $product_id = isset($product['id']) ? (int) $product['id'] : 0;

        $name = isset($product['name']) && is_string($product['name'])
            ? esc_html($product['name'])
            : '';

        $permalink = isset($product['permalink']) && is_string($product['permalink'])
            ? esc_url($product['permalink'])
            : '#';

        if ($product_id <= 0 || $name === '' || $permalink === '#') {
            return;
        }

        $dataset = esc_attr(wp_json_encode($product));

        echo '<a href="' . $permalink . '" class="fs-card" data-product="' . $dataset . '">';

        echo '<div class="fs-card__image-wrapper">';

        echo '<img class="fs-card__image" src="" alt="' . $name . '" loading="lazy" decoding="async">';

        echo '</div>';

        echo '<div class="fs-card__content">';

        echo '<h3 class="fs-card__title">' . $name . '</h3>';

        echo '<div class="fs-card__sizes-count" aria-live="polite"></div>';

        echo '<div class="fs-card__price" aria-live="polite"></div>';

        echo '<div class="fs-card__colors"></div>';

        echo '</div>';

        echo '</a>';
    }
}
