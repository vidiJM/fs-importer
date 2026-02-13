<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Grid_Page {

    public function render(): void {
        ?>
        <div class="fs-admin-wrap">
            <div class="fs-admin-header">
                <h1>FS Grid</h1>
                <p>Generador profesional del shortcode [fs_grid]</p>
            </div>

            <div class="fs-admin-card">

                <h2>Generador de shortcode</h2>

                <div class="fs-form-grid">
                    <div class="fs-field">
                        <label>Brand</label>
                        <input type="text" id="fs-brand" placeholder="nike, adidas..." />
                    </div>

                    <div class="fs-field">
                        <label>Color</label>
                        <input type="text" id="fs-color" placeholder="negro, blanco..." />
                    </div>

                    <div class="fs-field">
                        <label>Gender</label>
                        <input type="text" id="fs-gender" placeholder="hombre, mujer..." />
                    </div>

                    <div class="fs-field">
                        <label>Age Group</label>
                        <input type="text" id="fs-age" placeholder="adult, infantil..." />
                    </div>

                    <div class="fs-field">
                        <label>Size</label>
                        <input type="text" id="fs-size" placeholder="42..." />
                    </div>

                    <div class="fs-field">
                        <label>Per Page</label>
                        <input type="number" id="fs-perpage" value="12" min="1" max="48" />
                    </div>
                </div>

                <button class="fs-btn-primary" id="fs-generate">
                    Generar shortcode
                </button>

                <div class="fs-output">
                    <label>Shortcode:</label>
                    <div class="fs-code-box" id="fs-output">
                        [fs_grid]
                    </div>
                    <button class="fs-copy-btn" id="fs-copy">
                        Copiar
                    </button>
                </div>

            </div>
        </div>
        <?php
    }
}
