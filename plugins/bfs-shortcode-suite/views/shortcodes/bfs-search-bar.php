<?php
defined('ABSPATH') || exit;

/**
 * Vista del buscador premium.
 * La lógica vive en JS. Aquí solo se imprime el markup base.
 */
?>
<div class="bfs-search">
  <button type="button" class="bfs-search__trigger" aria-haspopup="dialog" aria-expanded="false">
    <span class="bfs-search__icon" aria-hidden="true">⌕</span>
    <span class="bfs-search__placeholder"><?php echo esc_html($placeholder); ?></span>
  </button>

  <div class="bfs-search__overlay" hidden>
    <div class="bfs-search__backdrop" data-bfs-close></div>

    <div class="bfs-search__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Buscador', 'bfs-shortcodes'); ?>">
      <div class="bfs-search__top">
        <div class="bfs-search__top-left">
          <span class="bfs-search__logo" aria-hidden="true"></span>
        </div>

        <div class="bfs-search__inputwrap">
          <span class="bfs-search__icon" aria-hidden="true">⌕</span>
          <input
            type="search"
            class="bfs-search__input"
            autocomplete="off"
            autocapitalize="none"
            spellcheck="false"
            placeholder="<?php echo esc_attr($placeholder); ?>"
          />
          <button type="button" class="bfs-search__clear" aria-label="<?php echo esc_attr__('Borrar', 'bfs-shortcodes'); ?>" hidden>×</button>
        </div>

        <button type="button" class="bfs-search__cancel" data-bfs-close>
          <?php echo esc_html__('Cancelar', 'bfs-shortcodes'); ?>
        </button>
      </div>

      <div class="bfs-search__filters" hidden>
        <button type="button" class="bfs-chip" data-bfs-chip="price"><?php echo esc_html__('Precio', 'bfs-shortcodes'); ?></button>
        <button type="button" class="bfs-chip" data-bfs-chip="marca"><?php echo esc_html__('Marca', 'bfs-shortcodes'); ?></button>
        <button type="button" class="bfs-chip" data-bfs-chip="talla"><?php echo esc_html__('Talla', 'bfs-shortcodes'); ?></button>
        <button type="button" class="bfs-chip" data-bfs-chip="color"><?php echo esc_html__('Color', 'bfs-shortcodes'); ?></button>
        <button type="button" class="bfs-chip bfs-chip--ghost" data-bfs-chip="more"><?php echo esc_html__('Más filtros', 'bfs-shortcodes'); ?></button>

        <label class="bfs-stock">
          <input type="checkbox" class="bfs-stock__check" checked>
          <span><?php echo esc_html__('En stock', 'bfs-shortcodes'); ?></span>
        </label>
      </div>

      <div class="bfs-search__body">
        <aside class="bfs-search__side">
          <div class="bfs-section bfs-section--recent">
            <div class="bfs-section__head">
              <h3 class="bfs-section__title"><?php echo esc_html__('Búsquedas recientes', 'bfs-shortcodes'); ?></h3>
              <button type="button" class="bfs-link" data-bfs-clear-history hidden><?php echo esc_html__('Borrar todo', 'bfs-shortcodes'); ?></button>
            </div>
            <ul class="bfs-list" data-bfs-history></ul>
          </div>

          <div class="bfs-section bfs-section--suggest">
            <div class="bfs-section__head">
              <h3 class="bfs-section__title"><?php echo esc_html__('Sugerencias', 'bfs-shortcodes'); ?></h3>
            </div>
            <ul class="bfs-list" data-bfs-suggestions>
              <li class="bfs-list__item"><button type="button" class="bfs-suggest" data-bfs-suggest="futbol sala">futbol sala</button></li>
              <li class="bfs-list__item"><button type="button" class="bfs-suggest" data-bfs-suggest="zapatillas">zapatillas</button></li>
              <li class="bfs-list__item"><button type="button" class="bfs-suggest" data-bfs-suggest="botas">botas</button></li>
            </ul>
          </div>
        </aside>

        <main class="bfs-search__main">
          <div class="bfs-hint" data-bfs-hint></div>
          <div class="bfs-results" data-bfs-results></div>
          <button type="button" class="bfs-loadmore" data-bfs-more hidden><?php echo esc_html__('Ver más', 'bfs-shortcodes'); ?></button>
        </main>
      </div>

      <div class="bfs-popover" data-bfs-popover hidden></div>
    </div>
  </div>
</div>
