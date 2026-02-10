<?php
declare(strict_types=1);

namespace BFS\Admin;

defined('ABSPATH') || exit;

/**
 * Admin menu + pages for shortcode documentation.
 *
 * UX goals:
 * - Keep it fast (no heavy JS, no global assets).
 * - Provide a dedicated submenu "Shortcodes".
 * - Use WP native nav tabs to separate categories.
 */
final class Menu
{
    private const MENU_SLUG   = 'bfs-shortcodes';
    private const CAPABILITY  = 'manage_options';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    public static function addMenu(): void
    {
        add_menu_page(
            __('BFS Shortcodes', 'bfs-shortcodes'),
            __('BFS Shortcodes', 'bfs-shortcodes'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [self::class, 'renderShortcodesPage'],
            'dashicons-shortcode',
            58
        );

        // Rename the first submenu entry to “Shortcodes” (WP duplicates top-level by default).
        add_submenu_page(
            self::MENU_SLUG,
            __('Shortcodes', 'bfs-shortcodes'),
            __('Shortcodes', 'bfs-shortcodes'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [self::class, 'renderShortcodesPage']
        );
        // Size guide help page.
        add_submenu_page(
            self::MENU_SLUG,
            __('Guía de tallas', 'bfs-shortcodes'),
            __('Guía de tallas', 'bfs-shortcodes'),
            self::CAPABILITY,
            'bfs-shortcodes-size-guide',
            [SizeGuidePage::class, 'render']
        );

    }

    /**
     * Load assets only on our admin screens.
     *
     * @param string $hook_suffix
     */
    public static function enqueueAssets(string $hook_suffix): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        // Our screens:
        // - toplevel_page_bfs-shortcodes
        // - bfs-shortcodes_page_bfs-shortcodes (submenu alias)
        if (strpos($hook_suffix, 'bfs-shortcodes') === false) {
            return;
        }

        wp_enqueue_style(
            'bfs-shortcodes-admin',
            BFS_SHORTCODES_URL . 'admin/css/bfs-admin.css',
            [],
            defined('BFS_SHORTCODES_VERSION') ? BFS_SHORTCODES_VERSION : '1.0.0'
        );
    }

    /**
     * Shortcodes page with category tabs.
     */
    /**
     * Infer a "type" bucket for tabs based on shortcode tag.
     * Keeps docs provider simple (no extra fields needed).
     */
    private static function inferType(string $tag): string
    {
        $tag = strtolower($tag);

        if (strpos($tag, 'home') !== false) {
            return 'home';
        }

        if (strpos($tag, 'carousel') !== false) {
            return 'carousel';
        }

        if (strpos($tag, 'grid') !== false) {
            return 'grid';
        }

        // Finder: quiz / recommender / search overlay.
        if (strpos($tag, 'finder') !== false || strpos($tag, 'search') !== false) {
            return 'finder';
        }

        if (strpos($tag, 'detail') !== false) {
            return 'detail';
        }

        return 'other';
    }

    /**
     * Shortcodes page with type tabs (all / carousel / grid / finder / detail).
     */
    public static function renderShortcodesPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'bfs-shortcodes'));
        }

        $shortcodes = ShortcodeDocsProvider::getAll();

        echo '<div class="wrap bfs-shortcodes-wrap">';

        echo '<div class="bfs-shortcodes-header">';
        echo '<div>';
        echo '<h1>' . esc_html__('BFS Shortcodes', 'bfs-shortcodes') . '</h1>';
        echo '<p class="bfs-shortcodes-subtitle">' . esc_html__('Documentación rápida para pegar shortcodes en páginas, entradas o bloques de Shortcode.', 'bfs-shortcodes') . '</p>';
        echo '</div>';
        echo '<span class="bfs-sc-pill">' . esc_html__('Versión: ', 'bfs-shortcodes') . esc_html(defined('BFS_SHORTCODES_VERSION') ? BFS_SHORTCODES_VERSION : '') . '</span>';
        echo '</div>';

        if (empty($shortcodes)) {
            echo '<div class="notice notice-info"><p>' . esc_html__('Todavía no hay shortcodes documentados.', 'bfs-shortcodes') . '</p></div>';
            echo '</div>';
            return;
        }

        // Bucket by type (tabs) and keep category for labels.
        $byType = [
            'carousel' => [],
            'grid'     => [],
            'finder'   => [],
            'detail'   => [],
            'other'    => [],
        ];

        foreach ($shortcodes as $sc) {
            $tag = (string) ($sc['tag'] ?? '');
            $type = self::inferType($tag);
            if (!isset($byType[$type])) {
                $byType[$type] = [];
            }
            $byType[$type][] = $sc;
        }

        // Tabs (fixed order).
        $tabs = [
            'home'     => __('Home', 'bfs-shortcodes'),
            'carousel'  => __('Carousel', 'bfs-shortcodes'),
            'grid'      => __('Grid', 'bfs-shortcodes'),
            'finder'    => __('Finder', 'bfs-shortcodes'),
            'detail'    => __('Detail', 'bfs-shortcodes'),
        ];

        // Only show tabs that have items (except 'all').
        foreach (['carousel','grid','finder','detail'] as $k) {
            if (empty($byType[$k])) {
                unset($tabs[$k]);
            }
        }

        $active = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'home';
        if (!isset($tabs[$active])) {
            $active = 'home';
        }

        echo '<h2 class="nav-tab-wrapper bfs-nav-tabs" style="margin-top: 14px;">';
        foreach ($tabs as $tabKey => $label) {
            $url = add_query_arg(['page' => self::MENU_SLUG, 'tab' => $tabKey], admin_url('admin.php'));
            $class = 'nav-tab' . ($tabKey === $active ? ' nav-tab-active' : '');

            $count = count($byType[$tabKey] ?? []);

            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">';
            echo esc_html((string) $label);
            echo ' <span class="bfs-tab-count">' . esc_html((string) $count) . '</span>';
            echo '</a>';
        }
        echo '</h2>';

        // Optional search (client-side, lightweight; no extra assets).
        echo '<div class="bfs-admin-toolbar">';
        echo '<label class="screen-reader-text" for="bfs-sc-filter">' . esc_html__('Filtrar shortcodes', 'bfs-shortcodes') . '</label>';
        echo '<input id="bfs-sc-filter" class="regular-text" type="search" placeholder="' . esc_attr__('Filtrar por nombre o tag…', 'bfs-shortcodes') . '">';
        echo '<p class="description" style="margin:6px 0 0;">' . esc_html__('Tip: escribe “grid”, “carousel”, “finder”, “detail” o el tag del shortcode.', 'bfs-shortcodes') . '</p>';
        echo '</div>';

        // Choose items to render.
        $items = $shortcodes;
        if ($active !== 'all') {
            $items = $byType[$active] ?? [];
        }

        echo '<div class="bfs-shortcodes-grid" data-bfs-sc-grid>';

        foreach ($items as $sc) {
            $tag = (string) ($sc['tag'] ?? '');
            $title = (string) ($sc['title'] ?? '');
            $description = (string) ($sc['description'] ?? '');
            $example = (string) ($sc['example'] ?? '');
            $params = (array) ($sc['params'] ?? []);
            $category = (string) ($sc['category'] ?? '');

            $filterText = strtolower($title . ' ' . $tag . ' ' . $category . ' ' . wp_strip_all_tags($description));

            echo '<section class="bfs-sc-card" data-bfs-filter="' . esc_attr($filterText) . '">';
            echo '<div class="bfs-sc-card__top">';
            echo '<span class="bfs-sc-card__badge">' . esc_html($title) . '</span>';
            if ($category !== '') {
                echo '<span class="bfs-sc-pill" style="opacity:.85;">' . esc_html($category) . '</span>';
            }
            echo '<span class="bfs-sc-pill">' . esc_html('[' . $tag . ']') . '</span>';
            echo '</div>';

            if (!empty($example)) {
                echo '<pre class="bfs-sc-card__code" style="white-space:pre-wrap;">' . esc_html($example) . '</pre>';
            }

            echo '<dl class="bfs-sc-card__meta">';
            echo '<div><dt>' . esc_html__('Descripción', 'bfs-shortcodes') . '</dt><dd>' . wp_kses_post($description) . '</dd></div>';
            echo '</dl>';

            if (!empty($params)) {
                echo '<h3 style="margin:10px 0 6px;">' . esc_html__('Parámetros', 'bfs-shortcodes') . '</h3>';
                echo '<ul class="bfs-sc-card__params">';
                foreach ($params as $p) {
                    $name = (string) ($p['name'] ?? '');
                    $type = (string) ($p['type'] ?? '');
                    $default = (string) ($p['default'] ?? '');
                    $help = (string) ($p['help'] ?? '');

                    $line = sprintf(
                        '<strong>%s</strong> <span style="opacity:.7;">(%s)</span> %s<br><span style="opacity:.75;">%s</span>',
                        esc_html($name),
                        esc_html($type),
                        $default !== '' ? '<span style="opacity:.85;">— ' . esc_html('default: ' . $default) . '</span>' : '',
                        wp_kses_post($help)
                    );

                    echo '<li>' . $line . '</li>';
                }
                echo '</ul>';
            }

            echo '</section>';
        }

        echo '</div>';

        // Inline tiny script to filter cards without enqueuing a new file.
        echo '<script>
        (function(){
            var input = document.getElementById("bfs-sc-filter");
            if(!input) return;
            input.addEventListener("input", function(){
                var q = (input.value || "").toLowerCase().trim();
                var cards = document.querySelectorAll(".bfs-sc-card[data-bfs-filter]");
                cards.forEach(function(card){
                    if(!q){ card.style.display = ""; return; }
                    var hay = card.getAttribute("data-bfs-filter") || "";
                    card.style.display = hay.indexOf(q) !== -1 ? "" : "none";
                });
            });
        })();
        </script>';

        echo '</div>';
    }
}
