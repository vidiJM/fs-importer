<?php
declare(strict_types=1);

namespace BFS\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Home Features section (icon + title + description) wrapper.
 *
 * Usage:
 * [bfs_home_features columns="4" bg="#6f7c8a" accent="#9ad000"]
 *   [bfs_home_feature icon_id="123" title="Especialistas" text="..." url="/especialistas/"]
 *   ...
 * [/bfs_home_features]
 */
final class HomeFeaturesShortcode
{
    public static function register(): void
    {
        add_shortcode('bfs_home_features', [self::class, 'render']);
    }

    /**
     * @param array<string, mixed> $atts
     * @param string|null         $content
     */
    public static function render($atts = [], $content = null): string
    {
        $atts = shortcode_atts([
            'columns' => 4,
            'bg'      => '',
            'accent'  => '',
            'text'    => '',
            'class'   => '',
        ], (array) $atts, 'bfs_home_features');

        $columns = absint($atts['columns'] ?? 4);
        if ($columns < 1) {
            $columns = 1;
        }
        if ($columns > 6) {
            $columns = 6;
        }

        $extra_class = sanitize_html_class((string) ($atts['class'] ?? ''));

        $style_vars = self::buildCssVars(
            (string) ($atts['bg'] ?? ''),
            (string) ($atts['accent'] ?? ''),
            (string) ($atts['text'] ?? '')
        );

        // Performance: load assets only when shortcode is present.
        wp_enqueue_style('bfs-home-features');

        $data = [
            'columns'     => (string) $columns,
            'extra_class' => $extra_class,
            'style_vars'  => $style_vars,
            'inner_html'  => do_shortcode((string) $content),
        ];

        ob_start();

        $view = BFS_SHORTCODES_PATH . 'views/shortcodes/bfs-home-features.php';
        if (file_exists($view)) {
            /** @var array<string, string> $data */
            include $view;
        } else {
            echo '<p>' . esc_html__('Error: vista de features no encontrada.', 'bfs-shortcodes') . '</p>';
        }

        return (string) ob_get_clean();
    }

    private static function buildCssVars(string $bg, string $accent, string $text): string
    {
        $vars = [];

        $bg = trim($bg);
        if (self::isSafeColor($bg)) {
            $vars[] = '--bfs-home-features-bg:' . $bg;
        }

        $accent = trim($accent);
        if (self::isSafeColor($accent)) {
            $vars[] = '--bfs-home-features-accent:' . $accent;
        }

        $text = trim($text);
        if (self::isSafeColor($text)) {
            $vars[] = '--bfs-home-features-text:' . $text;
        }

        return implode(';', $vars);
    }

    private static function isSafeColor(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        // Only allow hex colors to prevent CSS injection.
        return (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value);
    }
}
