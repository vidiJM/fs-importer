<?php
defined('ABSPATH') || exit;
/** @var array $data */
$payloadJson = wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<section class="bfs-pd" data-bfs-pd='<?php echo esc_attr($payloadJson); ?>'>
  <div class="bfs-pd__top">
    <div class="bfs-pd__media">
      <div class="bfs-pd__thumbs" aria-label="<?php echo esc_attr__('Galería', 'bfs-shortcodes'); ?>"></div>

      <div class="bfs-pd__imgWrap">
        <img class="bfs-pd__img" src="" alt="<?php echo esc_attr($data['title']); ?>" loading="eager">
      </div>
    </div>

    <div class="bfs-pd__info">
      <h1 class="bfs-pd__title"><?php echo esc_html($data['title']); ?></h1>

      <div class="bfs-pd__price">
        <span class="bfs-pd__priceLabel"><?php echo esc_html__('Precio', 'bfs-shortcodes'); ?></span>
        <strong class="bfs-pd__priceValue"></strong>
      </div>

      <div class="bfs-pd__section">
        <div class="bfs-pd__sectionTitle"><?php echo esc_html__('Color', 'bfs-shortcodes'); ?></div>
        <div class="bfs-pd__colors"></div>
      </div>

      <div class="bfs-pd__section">
        <div class="bfs-pd__sectionTitle"><?php echo esc_html__('Selecciona tu talla', 'bfs-shortcodes'); ?></div>
        <div class="bfs-pd__sizes"></div>
      </div>

      <div class="bfs-pd__section">
        <div class="bfs-pd__sectionTitle"><?php echo esc_html__('Tiendas disponibles', 'bfs-shortcodes'); ?></div>
        <div class="bfs-pd__merchants"></div>
      </div>
    </div>
  </div>

  <div class="bfs-pd__desc">
    <?php echo \BFS\Helpers\DescriptionFormatter::format((string) ($data['description'] ?? '')); ?>
  </div>
</section>
