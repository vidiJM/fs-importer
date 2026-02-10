<?php
declare(strict_types=1);

namespace BFS\Admin;

defined('ABSPATH') || exit;

/**
 * Admin help page for the Size Guide shortcode.
 *
 * Keep it lightweight: no extra JS, reuse existing admin CSS.
 */
final class SizeGuidePage
{
    /**
     * @return void
     */
    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'bfs-shortcodes'));
        }

        $active = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'overview';
        if (!in_array($active, ['overview', 'shortcode'], true)) {
            $active = 'overview';
        }

        $base_url = add_query_arg(['page' => 'bfs-shortcodes-size-guide'], admin_url('admin.php'));

        echo '<div class="wrap bfs-shortcodes-wrap">';

        echo '<div class="bfs-shortcodes-header">';
        echo '<div>';
        echo '<h1>' . esc_html__('Guía de tallas', 'bfs-shortcodes') . '</h1>';
        echo '<p class="bfs-shortcodes-subtitle">' . esc_html__('Contenido seguro y orientativo para tu página /guia-tallas, sin copiar tablas oficiales.', 'bfs-shortcodes') . '</p>';
        echo '</div>';
        echo '<span class="bfs-sc-pill">' . esc_html__('Shortcode: ', 'bfs-shortcodes') . '<code>[bfs_size_guide]</code></span>';
        echo '</div>';

        echo '<h2 class="nav-tab-wrapper bfs-nav-tabs" style="margin-top: 14px;">';
        echo '<a class="nav-tab ' . ($active === 'overview' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg(['tab' => 'overview'], $base_url)) . '">' . esc_html__('Resumen', 'bfs-shortcodes') . '</a>';
        echo '<a class="nav-tab ' . ($active === 'shortcode' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg(['tab' => 'shortcode'], $base_url)) . '">' . esc_html__('Cómo usar', 'bfs-shortcodes') . '</a>';
        echo '</h2>';

        echo '<div class="bfs-sc-card" style="max-width: 980px;">';
        echo '<div class="bfs-sc-card__content">';

        if ($active === 'overview') {
            echo '<h2 style="margin-top:0;">' . esc_html__('Qué incluye', 'bfs-shortcodes') . '</h2>';
            echo '<ul style="margin: 0 0 14px 18px;">';
            echo '<li>' . esc_html__('Cómo medir el pie (en cm).', 'bfs-shortcodes') . '</li>';
            echo '<li>' . esc_html__('Recomendaciones si estás entre dos tallas.', 'bfs-shortcodes') . '</li>';
            echo '<li>' . esc_html__('Tabla orientativa EU ↔ cm (genérica).', 'bfs-shortcodes') . '</li>';
            echo '</ul>';

            echo '<p class="description" style="margin:0;">' . esc_html__('Tip: crea una página con slug “guia-tallas” y añade el shortcode dentro de un bloque “Shortcode”.', 'bfs-shortcodes') . '</p>';
        } else {
            echo '<h2 style="margin-top:0;">' . esc_html__('Ejemplos', 'bfs-shortcodes') . '</h2>';
            echo '<p><code>[bfs_size_guide]</code></p>';
            echo '<p><code>[bfs_size_guide cta_text="Hacer el cuestionario" cta_url="/finder/"]</code></p>';
            echo '<p><code>[bfs_size_guide show_table="0"]</code></p>';

            echo '<h3>' . esc_html__('Parámetros', 'bfs-shortcodes') . '</h3>';
            echo '<ul style="margin: 0 0 0 18px;">';
            echo '<li><code>title</code> (string)</li>';
            echo '<li><code>intro</code> (string)</li>';
            echo '<li><code>cta_text</code> (string)</li>';
            echo '<li><code>cta_url</code> (string)</li>';
            echo '<li><code>show_table</code> (0|1)</li>';
            echo '</ul>';
        }

        echo '</div></div>'; // card

        echo '</div>'; // wrap
    }
}
