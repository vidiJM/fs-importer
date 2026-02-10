<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Size guide (safe, generic) shortcode.
 *
 * Usage:
 * [bfs_size_guide]
 * [bfs_size_guide title="Guía de tallas" cta_text="Hacer el cuestionario" cta_url="/finder/"]
 */
final class SizeGuideShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_size_guide', [self::class, 'render']);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public static function render($atts = []): string
    {
        // Enqueue only when used (and keep early enqueue detection as primary path).
        wp_enqueue_style('bfs-size-guide');

        $atts = shortcode_atts([
            'title'      => __('Guía de tallas de botas de fútbol sala', 'bfs-shortcodes'),
            'intro'      => __('Aprende a medir tu pie en cm y elige una talla orientativa. Esta guía es orientativa: la horma cambia según marca y modelo.', 'bfs-shortcodes'),
            'cta_text'   => __('Hacer el cuestionario', 'bfs-shortcodes'),
            'cta_url'    => '',
            'show_table' => '1',
        ], (array) $atts, 'bfs_size_guide');

        $title = sanitize_text_field((string) $atts['title']);
        $intro = sanitize_text_field((string) $atts['intro']);

        $cta_text = sanitize_text_field((string) $atts['cta_text']);
        $cta_url_raw = (string) $atts['cta_url'];
        $cta_url = $cta_url_raw !== '' ? esc_url($cta_url_raw) : '';

        $show_table = ((string) $atts['show_table'] === '1');

        ob_start();
        ?>
        <section class="bfs-size-guide" aria-label="<?php echo esc_attr($title); ?>">
            <div class="bfs-size-guide__container">
                <header class="bfs-size-guide__header">
                    <h2 class="bfs-size-guide__title"><?php echo esc_html($title); ?></h2>
                    <p class="bfs-size-guide__intro"><?php echo esc_html($intro); ?></p>
                </header>

                <div class="bfs-size-guide__grid">
                    <article class="bfs-size-guide__card">
                        <h3 class="bfs-size-guide__h"><?php echo esc_html__('Cómo medir tu pie (en cm)', 'bfs-shortcodes'); ?></h3>
                        <ol class="bfs-size-guide__list">
                            <li><?php echo esc_html__('Ponte el calcetín con el que sueles jugar.', 'bfs-shortcodes'); ?></li>
                            <li><?php echo esc_html__('Coloca un folio en el suelo contra una pared.', 'bfs-shortcodes'); ?></li>
                            <li><?php echo esc_html__('Apoya el talón en la pared y marca la punta del dedo más largo.', 'bfs-shortcodes'); ?></li>
                            <li><?php echo esc_html__('Mide talón → dedo en cm. Repite en ambos pies y usa la medida mayor.', 'bfs-shortcodes'); ?></li>
                        </ol>
                        <p class="bfs-size-guide__note"><?php echo esc_html__('Consejo: mide al final del día; el pie suele estar ligeramente más “expandido”.', 'bfs-shortcodes'); ?></p>
                    </article>

                    <article class="bfs-size-guide__card">
                        <h3 class="bfs-size-guide__h"><?php echo esc_html__('Si estás entre dos tallas', 'bfs-shortcodes'); ?></h3>
                        <ul class="bfs-size-guide__list">
                            <li><?php echo esc_html__('Pie ancho / empeine alto / usas plantilla: suele ir mejor media talla más.', 'bfs-shortcodes'); ?></li>
                            <li><?php echo esc_html__('Ajuste ceñido (más control): elige la talla más ajustada solo si no presiona los dedos.', 'bfs-shortcodes'); ?></li>
                            <li><?php echo esc_html__('Piel suele ceder algo con el uso; sintético suele ceder menos.', 'bfs-shortcodes'); ?></li>
                        </ul>

                        <?php if ($cta_url !== '' && $cta_text !== '') : ?>
                            <div class="bfs-size-guide__cta">
                                <a class="bfs-size-guide__btn" href="<?php echo esc_url($cta_url); ?>">
                                    <?php echo esc_html($cta_text); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <p class="bfs-size-guide__disclaimer">
                            <?php echo esc_html__('Aviso: guía orientativa. La equivalencia exacta puede variar por marca y modelo.', 'bfs-shortcodes'); ?>
                        </p>
                    </article>
                </div>

                <?php if ($show_table) : ?>
                    <div class="bfs-size-guide__table-wrap" role="region" aria-label="<?php echo esc_attr__('Tabla orientativa EU ↔ cm', 'bfs-shortcodes'); ?>">
                        <h3 class="bfs-size-guide__h bfs-size-guide__h--table"><?php echo esc_html__('Tabla orientativa (EU ↔ cm)', 'bfs-shortcodes'); ?></h3>

                        <div class="bfs-size-guide__table-scroll">
                            <table class="bfs-size-guide__table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php echo esc_html__('Longitud del pie (cm)', 'bfs-shortcodes'); ?></th>
                                        <th scope="col"><?php echo esc_html__('Talla EU orientativa', 'bfs-shortcodes'); ?></th>
                                        <th scope="col"><?php echo esc_html__('Recomendación', 'bfs-shortcodes'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>24.0 – 24.4</td><td>EU 38</td><td><?php echo esc_html__('Ajuste ceñido', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>24.5 – 24.9</td><td>EU 39</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>25.0 – 25.4</td><td>EU 40</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>25.5 – 25.9</td><td>EU 41</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>26.0 – 26.4</td><td>EU 42</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>26.5 – 26.9</td><td>EU 43</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>27.0 – 27.4</td><td>EU 44</td><td><?php echo esc_html__('Estándar', 'bfs-shortcodes'); ?></td></tr>
                                    <tr><td>27.5 – 27.9</td><td>EU 45</td><td><?php echo esc_html__('Pie ancho/plantilla: valorar +0.5', 'bfs-shortcodes'); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
