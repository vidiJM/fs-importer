<?php
defined('ABSPATH') || exit;

use FS\ShortcodeSuite\Query\ProductQuery;

get_header();

/*
====================================================
1️⃣ Filtros GET
====================================================
*/

$current_genero     = isset($_GET['genero']) ? sanitize_text_field($_GET['genero']) : '';
$current_superficie = isset($_GET['superficie']) ? sanitize_text_field($_GET['superficie']) : '';
$current_marca      = isset($_GET['marca']) ? sanitize_text_field($_GET['marca']) : '';
$current_color      = isset($_GET['color']) ? sanitize_text_field($_GET['color']) : '';
$current_precio_max = isset($_GET['precio_max']) ? (float) $_GET['precio_max'] : 0;

$paged    = get_query_var('paged') ? (int) get_query_var('paged') : 1;
$per_page = 12;

/*
====================================================
2️⃣ Query Offer-driven
====================================================
*/

$result = ProductQuery::get_products([
    'genero'     => $current_genero,
    'superficie' => $current_superficie,
    'marca'      => $current_marca,
    'color'      => $current_color,
    'precio_max' => $current_precio_max,
    'per_page'   => $per_page,
    'paged'      => $paged,
]);

$product_ids = $result['ids'] ?? [];
$total       = $result['total'] ?? 0;
$images      = $result['images'] ?? [];
$prices      = $result['prices'] ?? [];

/*
====================================================
3️⃣ Datos para filtros
====================================================
*/

// Superficies
$superficies = get_terms([
    'taxonomy'   => 'fs_superficie',
    'hide_empty' => false,
]);

// Marcas desde meta (limpio)
global $wpdb;

$brands_raw = $wpdb->get_col("
    SELECT DISTINCT meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'fs_brand_raw'
    AND meta_value != ''
    ORDER BY meta_value ASC
");

$brands = [];

if (!empty($brands_raw)) {
    foreach ($brands_raw as $brand) {

        // Ignorar valores numéricos puros
        if (is_numeric($brand)) {
            continue;
        }

        $brands[] = strtolower(trim($brand));
    }
}

// Colores (normalizados visualmente)
$colores_raw = get_terms([
    'taxonomy'   => 'fs_color',
    'hide_empty' => false,
]);

$colores = [];

if (!empty($colores_raw) && !is_wp_error($colores_raw)) {
    foreach ($colores_raw as $term) {

        $clean_name = str_replace(['_', '-'], ' ', $term->name);
        $term->clean_name = ucwords($clean_name);

        $colores[] = $term;
    }

    usort($colores, function($a, $b) {
        return strcmp($a->clean_name, $b->clean_name);
    });
}
?>

<style>
.fs-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 40px;
}

.fs-archive__layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 40px;
    margin-top: 40px;
}

.fs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.fs-card {
    border: 1px solid #eee;
    background: #fff;
    transition: .2s ease;
}

.fs-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
}

.fs-card__image {
    padding: 20px;
    text-align: center;
    background: #f8f8f8;
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fs-card__image img {
    max-width: 100%;
    height: auto;
}

.fs-card__body {
    padding: 20px;
}

.fs-card__title {
    font-size: 16px;
    margin-bottom: 10px;
}

.fs-card__price {
    font-weight: bold;
    font-size: 18px;
    color: #7dbf00;
}

.fs-archive__sidebar h3 {
    font-size: 18px;
    margin-top: 25px;
}

@media (max-width: 1024px) {
    .fs-archive__layout {
        grid-template-columns: 1fr;
    }
    .fs-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .fs-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="fs-archive">

    <!-- HEADER -->
    <div class="fs-archive__header">
        <div class="fs-container">

            <nav class="fs-breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a>
                <span>/</span>
                <span>Zapatillas Fútbol Sala</span>
            </nav>

            <h2 class="fs-archive__title" style="font-size:30px;font-style: normal;font-weight: 600;letter-spacing: 1px;line-height: 1.5;">
                <?php post_type_archive_title(); ?>
            </h1>

            <p class="fs-archive__description" style="font-size:15px;font-style: normal;font-weight: 500line-height: 1.3;">
                Encuentra las mejores zapatillas de fútbol sala para hombre, mujer, infantil y unisex.
                Modelos indoor, turf y multisuperficie al mejor precio.
            </p>

        </div>
    </div>

    <div class="fs-container fs-archive__layout">

        <!-- SIDEBAR -->
        <aside class="fs-archive__sidebar">

            <form method="get">

                <!-- GÉNERO -->
                <h3>Género</h3>
                <?php
                $generos = [
                    'hombre'   => 'Hombre',
                    'mujer'    => 'Mujer',
                    'unisex'   => 'Unisex',
                    'infantil' => 'Infantil',
                ];
                foreach ($generos as $slug => $label) :
                ?>
                    <label>
                        <input type="radio"
                               name="genero"
                               value="<?php echo esc_attr($slug); ?>"
                               <?php checked($current_genero, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </label><br>
                <?php endforeach; ?>

                <!-- SUPERFICIE -->
                <h3>Superficie</h3>
                <?php foreach ($superficies as $term) : ?>
                    <label>
                        <input type="radio"
                               name="superficie"
                               value="<?php echo esc_attr($term->slug); ?>"
                               <?php checked($current_superficie, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </label><br>
                <?php endforeach; ?>

                <!-- MARCA -->
                <h3>Marca</h3>
                <?php foreach ($brands as $brand) : ?>
                    <label>
                        <input type="radio"
                               name="marca"
                               value="<?php echo esc_attr($brand); ?>"
                               <?php checked($current_marca, $brand); ?>>
                        <?php echo esc_html(ucfirst($brand)); ?>
                    </label><br>
                <?php endforeach; ?>

                <!-- COLOR -->
                <h3>Color</h3>
                <?php foreach ($colores as $term) : ?>
                    <label>
                        <input type="radio"
                               name="color"
                               value="<?php echo esc_attr($term->slug); ?>"
                               <?php checked($current_color, $term->slug); ?>>
                        <?php echo esc_html($term->clean_name); ?>
                    </label><br>
                <?php endforeach; ?>

                <!-- PRECIO -->
                <h3>Precio máximo (€)</h3>
                <input type="number"
                       name="precio_max"
                       value="<?php echo esc_attr($current_precio_max); ?>"
                       placeholder="Ej: 100">

                <br><br>
                <button type="submit">Filtrar</button>

            </form>

        </aside>

        <!-- CONTENT -->
        <main class="fs-archive__content">

            <div class="fs-archive__toolbar">
                <span><?php echo esc_html($total); ?> productos</span>
            </div>

            <?php if (!empty($product_ids)) : ?>

                <div class="fs-grid">

                    <?php foreach ($product_ids as $product_id) :

                        $title = get_the_title($product_id);
                        $link  = get_permalink($product_id);
                        $image_url = $images[$product_id] ?? '';
                        $min_price = $prices[$product_id] ?? '';
                    ?>

                        <article class="fs-card">
                            <a href="<?php echo esc_url($link); ?>">

                                <div class="fs-card__image">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>"
                                             alt="<?php echo esc_attr($title); ?>"
                                             loading="lazy">
                                    <?php else : ?>
                                        <div style="width:100%;height:200px;background:#f3f3f3;"></div>
                                    <?php endif; ?>
                                </div>

                                <div class="fs-card__body">
                                    <h2 class="fs-card__title">
                                        <?php echo esc_html($title); ?>
                                    </h2>

                                    <?php if ($min_price) : ?>
                                        <div class="fs-card__price">
                                            <?php echo number_format((float)$min_price, 2, ',', '.'); ?> €
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </a>
                        </article>

                    <?php endforeach; ?>

                </div>

                <?php
                $total_pages = ceil($total / $per_page);
                if ($total_pages > 1) :
                    echo '<div class="fs-pagination">';
                    echo paginate_links([
                        'total'   => $total_pages,
                        'current' => $paged,
                    ]);
                    echo '</div>';
                endif;
                ?>

            <?php else : ?>
                <p>No se encontraron productos con los filtros seleccionados.</p>
            <?php endif; ?>

        </main>

    </div>

</section>

<?php get_footer(); ?>