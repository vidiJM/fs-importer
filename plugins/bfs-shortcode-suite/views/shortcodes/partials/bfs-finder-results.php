<?php
defined('ABSPATH') || exit;

// Expects: $products (array<BFS\Helpers\BfsPreviewProduct>), $guide_url (string)
if (empty($products)) {
    echo '<p>' . esc_html__('No se han encontrado productos para estas opciones.', 'bfs-shortcodes') . '</p>';
    return;
}

$guide_url = isset($guide_url) ? (string) $guide_url : '';
?>
<div class="bfs-finder__results-head">
  <div class="bfs-finder__results-title"><?php echo esc_html__('Top 3 recomendadas', 'bfs-shortcodes'); ?></div>
  <div class="bfs-finder__results-note"><?php echo esc_html__('Comparativa rápida en columnas.', 'bfs-shortcodes'); ?></div>
</div>

<div class="bfs-finder__grid">
  <?php foreach ($products as $index => $p) : ?>
    <?php
      $href  = !empty($p->permalink) ? (string) $p->permalink : '#';
      $img   = !empty($p->image) ? (string) $p->image : '';
      $title = (string) ($p->title ?? '');

      $minPrice = isset($p->minPrice) ? (float) $p->minPrice : 0.0;
      $price = $minPrice > 0 ? ('€ ' . number_format($minPrice, 2, ',', '')) : '';

      $highlight = ((int) $index === 0) ? ' bfs-finder__card--highlight' : '';

      $variants = (isset($p->variants) && is_iterable($p->variants)) ? $p->variants : [];
      $colorsCount = is_countable($variants) ? count($variants) : 0;

      $sizes = [];
      foreach ($variants as $v) {
          if (!empty($v->sizes) && is_array($v->sizes)) {
              $sizes = array_merge($sizes, array_keys($v->sizes));
          }
      }
      $sizes = array_values(array_unique(array_filter(array_map('strval', $sizes), static fn($s) => $s !== '')));
      sort($sizes, SORT_NATURAL);

      $sizesPreview = array_slice($sizes, 0, 6);
      $hasMoreSizes = count($sizes) > 6;
    ?>
    <article class="bfs-finder__card<?php echo esc_attr($highlight); ?>">
      <a class="bfs-finder__card-link" href="<?php echo esc_url($href); ?>" aria-label="<?php echo esc_attr($title); ?>"></a>

      <div class="bfs-finder__media">
        <?php if ($img !== '') : ?>
          <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
        <?php endif; ?>
      </div>

      <div class="bfs-finder__body">
        <?php if ($price !== '') : ?>
          <div class="bfs-finder__price"><?php echo esc_html($price); ?></div>
        <?php endif; ?>

        <div class="bfs-finder__name"><?php echo esc_html($title); ?></div>

        <div class="bfs-finder__meta">
          <?php echo esc_html((string) $colorsCount); ?> <?php echo esc_html__('colores', 'bfs-shortcodes'); ?>
          <?php if (!empty($sizesPreview)) : ?>
            <?php echo esc_html(' · '); ?>
            <?php echo esc_html__('Tallas:', 'bfs-shortcodes'); ?>
            <?php echo esc_html(' ' . implode(' · ', $sizesPreview)); ?>
            <?php if ($hasMoreSizes) : ?>
              <?php echo esc_html(' …'); ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="bfs-finder__actions">
          <a class="bfs-finder__btn bfs-finder__btn--primary" href="<?php echo esc_url($href); ?>">
            <?php echo esc_html__('Ver producto', 'bfs-shortcodes'); ?>
          </a>

          <?php if ($guide_url !== '') : ?>
            <a class="bfs-finder__btn bfs-finder__btn--ghost" href="<?php echo esc_url($guide_url); ?>">
              <?php echo esc_html__('Ver guía', 'bfs-shortcodes'); ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
