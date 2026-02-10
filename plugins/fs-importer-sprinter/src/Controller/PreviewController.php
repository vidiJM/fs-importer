<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Controller;

use FS\ImporterSprinter\Feed\SsvReader;
use FS\ImporterSprinter\Feed\SprinterMapper;
use FS\ImporterCore\Preview\PreviewBuilder;

final class PreviewController
{
    private const TRANSIENT_KEY = 'fs_sprinter_products';
    private const PREVIEW_LIMIT = 200;

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'fs-importer-sprinter'));
        }

        echo '<div class="wrap"><h1>Sprinter – Vista previa</h1>';

        // Aviso: WP-CLI only
        echo '<div class="notice notice-info"><p>';
        echo '<strong>Importación masiva:</strong> deshabilitada en el admin. Usa WP-CLI: ';
        echo '<code>wp fs:import-sprinter /ruta/feed.csv --batch=500</code>';
        echo '</p></div>';

        // =========================
        // PREVIEW DESDE SUBIDA
        // =========================
        if (
            isset($_POST['fs_preview']) &&
            check_admin_referer('fs_sprinter_preview', 'fs_sprinter_nonce') &&
            isset($_FILES['feed']['tmp_name']) &&
            is_uploaded_file($_FILES['feed']['tmp_name'])
        ) {
            $products = $this->buildPreviewProducts((string) $_FILES['feed']['tmp_name']);

            if ($products) {
                set_transient(self::TRANSIENT_KEY, $products, 30 * MINUTE_IN_SECONDS);

                $preview = PreviewBuilder::build($products);
                $this->renderPreview($preview);

                echo '<div class="notice notice-success"><p>Vista previa generada.</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>No se han encontrado filas válidas para la vista previa.</p></div>';
            }
        }

        // =========================
        // IMPORTAR DIRECTO (DESHABILITADO EN MODO A)
        // =========================
        if (isset($_POST['fs_do_import'])) {
            if (!check_admin_referer('fs_sprinter_preview', 'fs_sprinter_nonce')) {
                wp_die(esc_html__('Nonce inválido.', 'fs-importer-sprinter'));
            }

            echo '<div class="notice notice-error"><p>';
            echo '<strong>Acción no permitida:</strong> la importación masiva se ejecuta solo por WP-CLI.';
            echo '</p></div>';
        }

        $this->renderMainForm();
        echo '</div>';
    }

    /**
     * Construye un dataset pequeño para preview sin cargar todo el fichero en memoria.
     *
     * @return array<int, \FS\ImporterCore\DTO\ProductDTO>
     */
    private function buildPreviewProducts(string $tmpFile): array
    {
        // Streaming + límite + formato esperado por PreviewBuilder (ProductDTO[])
        return SprinterMapper::map(SsvReader::readGenerator($tmpFile), self::PREVIEW_LIMIT);
    }

    private function renderMainForm(): void
    {
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('fs_sprinter_preview', 'fs_sprinter_nonce');

        echo '<p><input type="file" name="feed" accept=".csv,.ssv" required></p>';

        echo '<p>';
        echo '<button class="button button-secondary" name="fs_preview" value="1">Cargar feed</button> ';
        echo '<button class="button button-primary" name="fs_do_import" value="1" disabled title="Importación solo por WP-CLI">IMPORTAR PRODUCTOS (CLI)</button>';
        echo '</p>';

        echo '</form>';
    }

    private function renderPreview(array $preview): void
    {
        if (empty($preview)) {
            echo '<p><em>No hay datos para mostrar.</em></p>';
            return;
        }

        echo '<div class="fs-preview-grid">';

        foreach ($preview as $p) {
            $minPrice = isset($p->minPrice) ? (float) $p->minPrice : 0.0;
            if ($minPrice <= 0) {
                continue;
            }

            echo '<div class="fs-card">';

            if (!empty($p->image)) {
                echo '<img class="fs-img" src="' . esc_url((string) $p->image) . '">';
            }

            echo '<h3>' . esc_html((string) ($p->brand . ' ' . $p->model)) . '</h3>';
            echo '<div class="fs-price">Desde ' . number_format($minPrice, 2) . ' €</div>';

            // COLORES
            echo '<div class="fs-colors">';
            foreach ($p->variants as $i => $v) {
                $active = $i === 0 ? 'active' : '';
                $img = $v->imageMain ?: $p->image;

                echo '<span
                    class="fs-color-dot ' . esc_attr($active) . '"
                    data-product="' . esc_attr((string) $p->productId) . '"
                    data-color="' . esc_attr((string) $v->color) . '"
                    data-img="' . esc_url((string) $img) . '"
                    data-price="' . esc_attr(number_format((float) $v->minPrice, 2)) . '"
                ></span>';
            }
            echo '</div>';

            // TALLAS
            foreach ($p->variants as $i => $v) {
                $active = $i === 0 ? 'active' : '';
                echo '<div class="fs-sizes-group ' . esc_attr($active) . '" data-color="' . esc_attr((string) $v->color) . '">';
                foreach ($v->sizes as $s) {
                    echo '<span class="fs-size">' . esc_html((string) $s) . '</span>';
                }
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }
}
