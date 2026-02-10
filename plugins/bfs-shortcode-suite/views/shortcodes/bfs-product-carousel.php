<?php
use BFS\Shortcodes\ProductCarouselShortcode;

defined('ABSPATH') || exit;

if (empty($products)) : ?>
    <p>No hay productos disponibles.</p>
<?php return; endif; ?>

<div class="bfs-swiper-container swiper">
    <div class="swiper-wrapper">

        <?php foreach ($products as $product) : ?>

            <?php
                // Tomar la primera variante como activa por defecto
                $firstVariant = reset($product->variants);
                $firstImage   = $firstVariant->imageMain ?: $product->image;

                // Construir JSON de variantes (imagen, tallas, precio)
                $variantJson = [];
                foreach ($product->variants as $color => $variant) {
                    $variantJson[$color] = [
                        'image' => $variant->imageMain,
                        'price' => (float) $variant->minPrice,
                        'sizes' => array_keys($variant->sizes),
                    ];
                }
            ?>

            <div class="bfs-card swiper-slide"
                data-product="<?= esc_attr($product->productId); ?>"
                data-variants='<?= esc_attr(json_encode($variantJson, JSON_UNESCAPED_SLASHES)); ?>'>

                <!-- Imagen -->
                <div class="bfs-card-img">
                    <img class="bfs-card-img-main"
                        src="<?= esc_url($firstImage); ?>"
                        alt="<?= esc_attr($product->title); ?>"
                        loading="lazy">
                </div>

                <!-- Título -->
                <div class="bfs-card-title">
                    <?= esc_html($product->title); ?>
                </div>

                <!-- Precio -->
                <div class="bfs-card-price bfs-price-value">
                    
                </div>

                <!-- Colores -->
                <div class="bfs-card-colors">
                    <?php foreach ($product->variants as $variant):

                        // Extraer color base
                        $mainColor = strtoupper(trim(explode('-', $variant->color)[0] ?? ''));
                        $mainColor = explode(' ', $mainColor)[0];

                        // Mapa de colores
                        $bg = ProductCarouselShortcode::mapColor($mainColor);

                        $style = str_starts_with($bg, 'linear-gradient')
                            ? "background: $bg;"
                            : "background-color: $bg;";
                    ?>
                        <span class="bfs-color-dot"
                            data-color="<?= esc_attr($variant->color); ?>"
                            data-img="<?= esc_url($variant->imageMain ?: $product->image); ?>"
                            data-price="<?= esc_attr(number_format($variant->minPrice, 2)); ?>"
                            data-sizes='<?= esc_attr(json_encode(array_keys($variant->sizes))); ?>'
                            style="<?= esc_attr($style); ?>">
                        </span>
                    <?php endforeach; ?>
                </div>

                <!-- Tallas dinámicas (cuando se hace click en color) -->
                <!-- Tallas (las pinta el JS) -->
                <div class="bfs-card-sizes dynamic-sizes"
                     aria-label="<?php echo esc_attr__( 'Tallas disponibles', 'bfs-shortcode-suite' ); ?>">
                </div>

                <!-- Botón -->
                <a class="bfs-card-button"
                   href="#"
                   target="_blank"
                   rel="nofollow">
                   Ver producto
                </a>

            </div>
        <?php endforeach; ?>

    </div>

    <!-- Flechas Swiper -->
    <div class="swiper-button-prev bfs-swiper-prev"></div>
    <div class="swiper-button-next bfs-swiper-next"></div>
</div>
