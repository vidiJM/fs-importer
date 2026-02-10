<?php
defined('ABSPATH') || exit;

/**
 * Expects from shortcode:
 * - $products
 * - $bfs_grid_id (string)
 * - $bfs_ui (string)
 * - $bfs_genero (string)
 * - $bfs_cta_url (string)
 * - $bfs_cta_text (string)
 * - $bfs_infinite (int)
 * - $bfs_grid_config_json (string JSON)
 */

if (empty($products)) {
    return;
}

$gridId   = isset($bfs_grid_id) ? (string) $bfs_grid_id : '';
$ui       = isset($bfs_ui) ? (string) $bfs_ui : 'adidas';
$genero   = isset($bfs_genero) ? (string) $bfs_genero : '';
$ctaUrl   = isset($bfs_cta_url) ? (string) $bfs_cta_url : '';
$ctaText  = isset($bfs_cta_text) ? (string) $bfs_cta_text : __('Ver más botas', 'bfs-shortcodes');
$infinite = !empty($bfs_infinite);

// Config JSON (por instancia) para el JS
$configJson = isset($bfs_grid_config_json) ? (string) $bfs_grid_config_json : '';
?>
<div
  id="<?php echo esc_attr($gridId); ?>"
  class="bfs-grid bfs-grid--ui-<?php echo esc_attr($ui); ?>"
  data-bfs-grid
  data-bfs-ui="<?php echo esc_attr($ui); ?>"
  data-bfs-genero="<?php echo esc_attr($genero); ?>"
  data-bfs-grid-config="<?php echo esc_attr($configJson); ?>"
>
  <div class="bfs-grid__items" data-bfs-grid-items>
    <?php
      $partial = BFS_SHORTCODES_PATH . 'views/shortcodes/partials/bfs-grid-cards.php';
      if (file_exists($partial)) {
          include $partial;
      }
    ?>
  </div>

  <?php if ($infinite) : ?>
    <div class="bfs-grid__sentinel" data-bfs-grid-sentinel aria-hidden="true"></div>

    <div class="bfs-grid__cta" data-bfs-grid-cta hidden>
      <a class="bfs-grid__cta-link" href="<?php echo esc_url($ctaUrl); ?>">
        <?php echo esc_html($ctaText); ?>
      </a>
    </div>
  <?php endif; ?>
</div>
