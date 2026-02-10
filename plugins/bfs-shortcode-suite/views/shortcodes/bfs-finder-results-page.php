<?php
defined('ABSPATH') || exit;
$title = !empty($title) ? $title : __('Comparativa de botas recomendadas', 'bfs-shortcodes');
$subtitle = !empty($subtitle) ? $subtitle : __('Estas son las 3 mejores opciones según tus respuestas.', 'bfs-shortcodes');
?>
<section class="bfs-finder-results-page" data-bfs-finder-results>
  <div class="bfs-finder-results-page__head">
    <h1 class="bfs-finder-results-page__title"><?php echo esc_html($title); ?></h1>
    <p class="bfs-finder-results-page__subtitle"><?php echo esc_html($subtitle); ?></p>
  </div>

  <div class="bfs-finder__loading" data-bfs-loading>
    <span class="bfs-finder__spinner" aria-hidden="true"></span>
    <span class="bfs-finder__loading-text"><?php echo esc_html__('Buscando las mejores opciones…', 'bfs-shortcodes'); ?></span>
  </div>

  <div class="bfs-finder__results" data-bfs-results hidden></div>
  <div class="bfs-finder__error" data-bfs-error hidden></div>
</section>
