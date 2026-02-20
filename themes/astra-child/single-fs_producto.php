<?php
/**
 * Single template for CPT: fs_producto
 * Child theme override
 */

defined('ABSPATH') || exit;

get_header();

?>
<style type="text/css">
  .fs-product {
    display: block;
  </style>
<main id="primary" class="site-main fs-single fs-single-product" role="main">
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('fs-product'); ?>>


				<div class="fs-product__body">
					<?php
					/**
					 * Render principal del producto via shortcode
					 * - Usamos get_the_ID() para asegurar el ID correcto
					 * - Escapamos el ID por si acaso
					 */
					echo do_shortcode('[fs_product_detail]');
					?>
				</div>

				<?php
				/**
				 * Opcional: contenido del editor (si algún día lo usas para FAQs, texto extra, etc.)
				 * Déjalo comentado si no lo quieres.
				 */
				/*
				<div class="fs-product__content editor-content">
					<?php the_content(); ?>
				</div>
				*/
				?>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<section class="fs-empty">
			<h1><?php esc_html_e('Producto no encontrado', 'tu-textdomain'); ?></h1>
			<p><?php esc_html_e('No hay contenido disponible para este producto.', 'tu-textdomain'); ?></p>
		</section>
	<?php endif; ?>
</main>
<?php

get_footer();
