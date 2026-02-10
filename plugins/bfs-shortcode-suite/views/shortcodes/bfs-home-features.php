<?php
/**
 * @var array<string, string> $data
 */
defined('ABSPATH') || exit;

$classes = trim('bfs-home-features ' . ($data['extra_class'] ?? ''));
$style   = (string) ($data['style_vars'] ?? '');
$cols    = (string) ($data['columns'] ?? '4');
$inner   = (string) ($data['inner_html'] ?? '');
?>
<section class="<?php echo esc_attr($classes); ?>"<?php echo $style !== '' ? ' style="' . esc_attr($style) . '"' : ''; ?>>
    <div class="bfs-home-features__grid" data-cols="<?php echo esc_attr($cols); ?>">
        <?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>
