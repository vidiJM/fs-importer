<?php
defined('ABSPATH') || exit;

$title = !empty($title) ? $title : __('Encuentra las mejores botas de fútbol sala para tu estilo', 'bfs-shortcodes');
$subtitle = !empty($subtitle) ? $subtitle : __('Responde 4 preguntas rápidas y te mostramos una comparativa con 3 botas recomendadas según tu superficie, nivel y forma de jugar.', 'bfs-shortcodes');

$cta = __('Empieza el cuestionario', 'bfs-shortcodes');
?>
<section class="bfs-finder-launch" data-bfs-finder-launch>
  <div class="bfs-finder-launch__inner">
    <div class="bfs-finder-launch__copy">
      <h2 class="bfs-finder-launch__title"><?php echo esc_html($title); ?></h2>
      <p class="bfs-finder-launch__subtitle"><?php echo esc_html($subtitle); ?></p>
    </div>

    <div class="bfs-finder-launch__actions">
      <button type="button" class="bfs-finder-launch__cta" data-bfs-open>
        <?php echo esc_html($cta); ?>
      </button>
      <?php if (!empty($guideUrl)) : ?>
        <a class="bfs-finder-launch__guide" href="<?php echo esc_url($guideUrl); ?>">
          <?php echo esc_html__('Ver guía de tallas', 'bfs-shortcodes'); ?>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal -->
  <div class="bfs-finder-modal" data-bfs-modal hidden>
    <div class="bfs-finder-modal__overlay" data-bfs-close aria-hidden="true"></div>

    <div class="bfs-finder-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Cuestionario', 'bfs-shortcodes'); ?>">
      <button type="button" class="bfs-finder-modal__close" data-bfs-close aria-label="<?php echo esc_attr__('Cerrar', 'bfs-shortcodes'); ?>">×</button>

      <div class="bfs-finder" data-bfs-finder>
        <div class="bfs-finder__container">
          <header class="bfs-finder__header">
            <div class="bfs-finder__copy">
              <div class="bfs-finder__title"><?php echo esc_html($title); ?></div>
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

            <div class="bfs-finder__footer">
              <button type="button" class="bfs-finder__finish" data-bfs-finish disabled>
                <?php echo esc_html__('Finalizar', 'bfs-shortcodes'); ?>
              </button>
            </div>

            <div class="bfs-finder__loading" data-bfs-loading hidden>
              <span class="bfs-finder__spinner" aria-hidden="true"></span>
              <span class="bfs-finder__loading-text"><?php echo esc_html__('Preparando resultados…', 'bfs-shortcodes'); ?></span>
            </div>

            <div class="bfs-finder__error" data-bfs-error hidden></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
