<?php
defined('ABSPATH') || exit;

$title = !empty($title) ? $title : __('Botas ideales según tu juego', 'bfs-shortcodes');
$subtitle = !empty($subtitle) ? $subtitle : __('Responde 4 preguntas. Te mostramos 3 opciones con diferencias claras.', 'bfs-shortcodes');
?>
<section class="bfs-finder" data-bfs-finder>
  <div class="bfs-finder__container">
    <header class="bfs-finder__header">
      <div class="bfs-finder__copy">
        <h2 class="bfs-finder__title"><?php echo esc_html($title); ?></h2>
        <p class="bfs-finder__subtitle"><?php echo esc_html($subtitle); ?></p>
      </div>

      <div class="bfs-finder__progress" aria-label="<?php echo esc_attr__('Progreso', 'bfs-shortcodes'); ?>">
        <div class="bfs-finder__progress-row">
          <span><?php echo esc_html__('Progreso', 'bfs-shortcodes'); ?></span>
          <span class="bfs-finder__progress-count">0/4</span>
        </div>
        <div class="bfs-finder__progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0">
          <span class="bfs-finder__progress-fill" style="width:0%"></span>
        </div>
      </div>
    </header>

    <div class="bfs-finder__chips" data-bfs-chips hidden></div>

    <div class="bfs-finder__panel">
      <div class="bfs-finder__panel-head">
        <div class="bfs-finder__panel-kicker" data-bfs-kicker></div>
        <button type="button" class="bfs-finder__back" data-bfs-back disabled>
          <span aria-hidden="true">←</span> <?php echo esc_html__('Atrás', 'bfs-shortcodes'); ?>
        </button>
      </div>

      <div class="bfs-finder__divider"></div>

      <div class="bfs-finder__step" data-bfs-step></div>

      <div class="bfs-finder__loading" data-bfs-loading hidden>
        <span class="bfs-finder__spinner" aria-hidden="true"></span>
        <span class="bfs-finder__loading-text"><?php echo esc_html__('Buscando las mejores opciones…', 'bfs-shortcodes'); ?></span>
      </div>

      <div class="bfs-finder__results" data-bfs-results hidden></div>
      <div class="bfs-finder__error" data-bfs-error hidden></div>
    </div>
  </div>
</section>
