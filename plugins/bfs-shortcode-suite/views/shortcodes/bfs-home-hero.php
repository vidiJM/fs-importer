<?php
/**
 * View: Home Hero.
 *
 * @var array<string, mixed> $data
 */
defined('ABSPATH') || exit;

$title       = $data['title'] ?? '';
$subtitle    = $data['subtitle'] ?? '';
$button_text = $data['button_text'] ?? '';
$button_url  = $data['button_url'] ?? '';
$image_html  = (string) ($data['image_html'] ?? '');
$align       = $data['align'] ?? 'left';

$classes = 'bfs-home-hero bfs-home-hero--' . $align;
?>
<section class="<?php echo esc_attr($classes); ?>">
    <div class="bfs-home-hero__inner">
        <div class="bfs-home-hero__content">
            <?php if ($title !== '') : ?>
                <h2 class="bfs-home-hero__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if ($subtitle !== '') : ?>
                <p class="bfs-home-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>

            <?php if ($button_text !== '') : ?>
                <?php if ($button_url !== '') : ?>
                    <a class="button button-primary bfs-home-hero__cta" href="<?php echo esc_url($button_url); ?>">
                        <?php echo esc_html($button_text); ?>
                    </a>
                <?php else : ?>
                    <button type="button" class="button button-primary bfs-home-hero__cta" disabled aria-disabled="true">
                        <?php echo esc_html($button_text); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="bfs-home-hero__media" aria-hidden="true">
            <?php if ($image_html !== '') : ?>
                <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <span class="bfs-home-hero__placeholder"></span>
            <?php endif; ?>
        </div>
    </div>
</section>
