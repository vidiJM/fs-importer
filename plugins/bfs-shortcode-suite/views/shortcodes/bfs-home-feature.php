<?php
/**
 * @var array<string, string> $data
 */
defined('ABSPATH') || exit;

$icon_html = (string) ($data['icon_html'] ?? '');
$title     = (string) ($data['title'] ?? '');
$text      = (string) ($data['text'] ?? '');
$url       = (string) ($data['url'] ?? '');
?>
<?php ob_start(); ?>
<article class="bfs-home-feature">
    <?php if ($icon_html !== '') : ?>
        <div class="bfs-home-feature__media">
            <?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    <?php endif; ?>

    <div class="bfs-home-feature__body">
        <?php if ($title !== '') : ?>
            <h3 class="bfs-home-feature__title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if ($text !== '') : ?>
            <p class="bfs-home-feature__text"><?php echo esc_html($text); ?></p>
        <?php endif; ?>
    </div>
</article>
<?php $item = (string) ob_get_clean(); ?>

<?php if ($url !== '') : ?>
    <a class="bfs-home-feature__link" href="<?php echo esc_url($url); ?>">
        <?php echo $item; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </a>
<?php else : ?>
    <?php echo $item; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
