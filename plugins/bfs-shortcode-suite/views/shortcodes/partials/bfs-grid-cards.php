<?php
use BFS\Shortcodes\ProductCarouselShortcode;

defined('ABSPATH') || exit;

if (empty($products)) {
    return;
}

/**
 * Normaliza un string de color (feed) en una lista de colores principales para pintar swatches.
 *
 * @param string $raw
 * @return array<int, string>
 */
$wpa_normalize_colors = static function (string $raw): array {
    $raw = strtoupper(trim($raw));
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/\s*(?:-|\/|–|—|\+|\||,)\s*/u', $raw) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));

    $modifiers = [
        'MARINO', 'ROYAL', 'OSCURO', 'CLARO', 'FLUOR', 'NEON', 'NEÓN',
        'PASTEL', 'METAL', 'METALICO', 'METÁLICO', 'GRIS', 'PLATA', 'DORADO',
    ];

    $colors = [];
    foreach ($parts as $p) {
        if ($p === '') {
            continue;
        }

        if (in_array($p, $modifiers, true) && !empty($colors)) {
            $colors[count($colors) - 1] .= ' ' . $p;
            continue;
        }

        $colors[] = $p;
    }

    $unique = [];
    foreach ($colors as $c) {
        if (!in_array($c, $unique, true)) {
            $unique[] = $c;
        }
    }

    return $unique;
};

/**
 * Construye el background CSS de un swatch a partir de 1..N colores.
 *
 * @param string $raw
 * @return string
 */
$wpa_swatch_style = static function (string $raw) use ($wpa_normalize_colors): string {
    $tokens = $wpa_normalize_colors($raw);
    if (empty($tokens)) {
        return 'background-color: #CBD5E1;';
    }

    $mapped = [];
    foreach ($tokens as $t) {
        $val = ProductCarouselShortcode::mapColor($t);

        if (is_string($val) && str_starts_with($val, 'linear-gradient')) {
            return "background: $val;";
        }

        $mapped[] = is_string($val) && $val !== '' ? $val : '#CBD5E1';
    }

    $mapped = array_values(array_unique($mapped));

    if (count($mapped) === 1) {
        return 'background-color: ' . $mapped[0] . ';';
    }

    if (count($mapped) === 2) {
        return 'background: linear-gradient(135deg, ' . $mapped[0] . ' 0 50%, ' . $mapped[1] . ' 50% 100%);';
    }

    $n = count($mapped);
    $step = 100 / $n;
    $stops = [];
    for ($i = 0; $i < $n; $i++) {
        $from = $i * $step;
        $to   = ($i + 1) * $step;
        $stops[] = $mapped[$i] . ' ' . $from . '% ' . $to . '%';
    }

    return 'background: conic-gradient(from 90deg, ' . implode(', ', $stops) . ');';
};

foreach ($products as $product) :
    $href = $product->permalink ?: '#';

    $firstVariant = !empty($product->variants) ? reset($product->variants) : null;
    $firstImage   = $firstVariant?->imageMain ?: $product->image;

    $variantJson = [];
    foreach ($product->variants as $color => $variant) {
        $variantJson[$color] = [
            'image' => $variant->imageMain ?: $product->image,
            'price' => (float) $variant->minPrice,
            'sizes' => array_values(array_keys($variant->sizes)),
        ];
    }
    ?>

    <article class="bfs-grid-card"
      data-product="<?php echo esc_attr((string) $product->productId); ?>"
      data-hover-img="<?php echo esc_url((string) ($product->imageHover ?? '')); ?>"
      data-base-img="<?php echo esc_url((string) $firstImage); ?>"
      data-variants="<?php echo esc_attr(wp_json_encode($variantJson, JSON_UNESCAPED_SLASHES)); ?>">

      <a class="bfs-card-link" href="<?php echo esc_url((string) $href); ?>" aria-label="<?php echo esc_attr((string) $product->title); ?>"></a>

      <div class="bfs-grid-media">
        <img
          class="bfs-grid-img"
          src="<?php echo esc_url((string) $firstImage); ?>"
          alt="<?php echo esc_attr((string) $product->title); ?>"
          loading="lazy"
          decoding="async">
      </div>

      <div class="bfs-grid-body">
        <div class="bfs-grid-price bfs-price-value">
          <?php echo ($product->minPrice > 0) ? esc_html('€ ' . number_format((float) $product->minPrice, 2, ',', '')) : ''; ?>
        </div>

        <div class="bfs-grid-title"><?php echo esc_html((string) $product->title); ?></div>

        <div class="bfs-grid-meta">
          <?php echo esc_html((string) ($genero ?? '')); ?> · <?php echo esc_html((string) count((array) $product->variants)); ?> colores
        </div>

        <div class="bfs-grid-colors" aria-label="<?php echo esc_attr__('Colores', 'bfs-shortcodes'); ?>">
          <?php foreach ($product->variants as $variant): ?>
            <?php $style = $wpa_swatch_style((string) $variant->color); ?>
            <button
              type="button"
              class="bfs-color-dot"
              data-color="<?php echo esc_attr((string) $variant->color); ?>"
              data-img="<?php echo esc_url((string) ($variant->imageMain ?: $product->image)); ?>"
              data-price="<?php echo esc_attr((string) $variant->minPrice); ?>"
              data-sizes="<?php echo esc_attr(wp_json_encode(array_keys((array) $variant->sizes))); ?>"
              style="<?php echo esc_attr($style); ?>"
              aria-label="<?php echo esc_attr((string) $variant->color); ?>">
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </article>

<?php endforeach; ?>
