<?php
declare(strict_types=1);

/**
 * File: /includes/Shortcodes/Product_Search.php
 */

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

defined('ABSPATH') || exit;

final class Product_Search
{
    
    public function __construct()
    {
        add_shortcode('fs_search', [$this, 'render']);
    }

    /**
     * Render shortcode [fs_search]
     */
    public function render(array $atts = []): string
    {
        // Enqueue assets SOLO cuando se usa el shortcode
        Assets::enqueue_search();

        // Localizamos REST endpoint
        wp_localize_script(
            'fs-search',
            'FSSearchConfig',
            [
                'restUrl'   => esc_url_raw(rest_url('fs/v1/search')),
                'minLength' => 3,
            ]
        );

        ob_start();

        echo '<div class="fs-search" data-fs-search>
            <button
                class="fs-search-trigger"
                type="button"
                aria-haspopup="dialog"
                aria-expanded="false">
                <span class="fs-search-icon" aria-hidden="true"></span>
                <span class="fs-search-placeholder">Buscar</span>
            </button>
        </div>

        <div
            class="fs-search-overlay"
            data-fs-search-overlay
            aria-hidden="true"
            role="dialog"
            aria-modal="true"
        >
            <div class="fs-search-overlay-inner">

                <div class="fs-search-header">

                    <input
                        type="search"
                        class="fs-search-input"
                        placeholder="Buscar productos"
                        aria-label="Buscar productos"
                        autocomplete="off"
                        spellcheck="false"
                    >

                    <button
                        class="fs-search-close"
                        type="button"
                        aria-label="Cerrar búsqueda"
                    >
                        Cancelar
                    </button>

                </div>

                <div class="fs-search-body">
                    <div
                        class="fs-search-results"
                        data-fs-search-results
                    ></div>
                </div>

            </div>
        </div>';

        return (string) ob_get_clean();
    }
}
