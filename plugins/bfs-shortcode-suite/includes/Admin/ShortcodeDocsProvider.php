<?php
declare(strict_types=1);

namespace BFS\Admin;

defined('ABSPATH') || exit;

/**
 * Central place to document shortcodes.
 * When you add new shortcodes, add a new entry here.
 */
final class ShortcodeDocsProvider
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(): array
    {
        return [
            [
                'category'    => __('Home', 'bfs-shortcodes'),
                'tag'         => 'bfs_home_hero',
                'title'       => __('Hero Home (título + CTA)', 'bfs-shortcodes'),
                'description' => __(
                    'Crea una sección “hero” para la home con título, subtítulo, botón CTA e imagen opcional. ' .
                    'Carga su CSS únicamente cuando el shortcode está presente (rendimiento).',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_home_hero button_url="/mejores-botas/" image_id="123" image_size="large"]',
                'params'      => [
                    [
                        'name'    => 'title',
                        'type'    => 'string',
                        'default' => __('Mejores botas de fútbol sala según tu estilo de juego', 'bfs-shortcodes'),
                        'help'    => __('Título principal del hero.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'subtitle',
                        'type'    => 'string',
                        'default' => __('Analizamos botas por tipo de jugador, superficie y nivel para ayudarte a elegir mejor.', 'bfs-shortcodes'),
                        'help'    => __('Texto secundario bajo el título.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'button_text',
                        'type'    => 'string',
                        'default' => __('Descubrir las mejores botas', 'bfs-shortcodes'),
                        'help'    => __('Texto del botón CTA.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'button_url',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('URL del botón CTA (si se deja vacío, el botón queda deshabilitado).', 'bfs-shortcodes'),
                    ],
                    
                    [
                        'name'    => 'image_id',
                        'type'    => 'int',
                        'default' => '0',
                        'help'    => __('ID de adjunto (Media Library) para la imagen. Recomendado: genera srcset/sizes automáticamente.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'image_size',
                        'type'    => 'string',
                        'default' => 'large',
                        'help'    => __('Tamaño de imagen para image_id (thumbnail|medium|large|full o tamaño personalizado).', 'bfs-shortcodes'),
                    ],
[
                        'name'    => 'image_url',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('URL de la imagen/ilustración (opcional, legacy). Se recomienda usar image_id. Si usas URL, optimiza en WebP/AVIF.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'align',
                        'type'    => 'string',
                        'default' => 'left',
                        'help'    => __('Alineación: "left" (2 columnas) o "center" (1 columna).', 'bfs-shortcodes'),
                    ],
                ],
            ],

            [
                'category'    => __('Home', 'bfs-shortcodes'),
                'tag'         => 'bfs_home_features',
                'title'       => __('Bloque Home (iconos + texto)', 'bfs-shortcodes'),
                'description' => __(
                    'Crea una franja de 2–6 columnas con icono (Media ID), título y descripción. ' .
                    'Ideal para la home (Especialistas / Comparativas / Guías / Ofertas). ' .
                    'Carga su CSS solo cuando se usa (rendimiento).',
                    'bfs-shortcodes'
                ),
                'example'     => "[bfs_home_features bg=\"#6f7c8a\" accent=\"#9ad000\"]\n  [bfs_home_feature icon_id=\"123\" title=\"Especialistas\" text=\"Analizamos botas específicas para pista indoor.\" url=\"/especialistas/\" ]\n  [bfs_home_feature icon_id=\"124\" title=\"Comparativas\" text=\"Modelos evaluados por tipo de jugador y superficie.\" url=\"/comparativas/\" ]\n[/bfs_home_features]",
                'params'      => [
                    [
                        'name'    => 'columns',
                        'type'    => 'int',
                        'default' => 4,
                        'help'    => __('Número de columnas (1–6). En responsive se adapta automáticamente.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'bg',
                        'type'    => 'hex-color',
                        'default' => '#6f7c8a',
                        'help'    => __('Color de fondo (solo HEX). Ej: #6f7c8a', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'accent',
                        'type'    => 'hex-color',
                        'default' => '#9ad000',
                        'help'    => __('Color de acento para títulos (solo HEX).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'text',
                        'type'    => 'hex-color',
                        'default' => '#ffffff',
                        'help'    => __('Color del texto (solo HEX).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'class',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('Clase CSS extra opcional para integrarlo con tu theme.', 'bfs-shortcodes'),
                    ],
                ],
                'notes'       => [
                    __('Este shortcode es contenedor: dentro debes añadir uno o más [bfs_home_feature].', 'bfs-shortcodes'),
                ],
            ],
            [
                'category'    => __('Home', 'bfs-shortcodes'),
                'tag'         => 'bfs_home_feature',
                'title'       => __('Item Home (icono + título)', 'bfs-shortcodes'),
                'description' => __(
                    'Elemento individual para usar dentro de [bfs_home_features]. El icono se carga por Media ID usando wp_get_attachment_image() (srcset/sizes, lazy).',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_home_feature icon_id="123" title="Guías" text="Recomendaciones revisadas cada temporada." url="/guias/"]',
                'params'      => [
                    [
                        'name'    => 'icon_id',
                        'type'    => 'int',
                        'default' => 0,
                        'help'    => __('ID del icono en la Librería Multimedia (recomendado).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'icon_size',
                        'type'    => 'string',
                        'default' => 'thumbnail',
                        'help'    => __('Tamaño de imagen (thumbnail/medium/large/full o custom).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'title',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('Título del item.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'text',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('Descripción breve.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'url',
                        'type'    => 'url',
                        'default' => '',
                        'help'    => __('Enlace opcional (si se indica, todo el item será clicable).', 'bfs-shortcodes'),
                    ],
                ],
            ],

            [
                'category'    => __('Catálogo', 'bfs-shortcodes'),
                'tag'         => 'bfs_carousel',
                'title'       => __('Carrusel de productos', 'bfs-shortcodes'),
                'description' => __(
                    'Muestra un carrusel (Swiper) de productos filtrados por género. Incluye variantes, colores y tallas con precios dinámicos.',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_carousel genero="hombre" limit="12"]',
                'params'      => [
                    [
                        'name'    => 'genero',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('Filtro de género (ej: "hombre", "mujer").', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'limit',
                        'type'    => 'int',
                        'default' => '12',
                        'help'    => __('Número máximo de productos a mostrar.', 'bfs-shortcodes'),
                    ],
                ],
            ],

            [
                'category'    => __('Catálogo', 'bfs-shortcodes'),
                'tag'         => 'bfs_grid',
                'title'       => __('Grid minimalista (Adidas/Nike)', 'bfs-shortcodes'),
                'description' => __(
                    'Muestra un grid minimalista y SEO-friendly (HTML SSR) de productos filtrados por género. ' .
                    'La tarjeta completa es clicable (enlace real al producto). Soporta <strong>hover de imagen</strong> si existe 2ª imagen en <code>fs_images</code> y <strong>infinite scroll</strong> opcional vía REST.',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_grid genero="hombre" per_page="12" ui="adidas" infinite="1"]',
                'params'      => [
                    [
                        'name'    => 'genero',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('Filtro de género (ej: "hombre", "mujer", "infantil").', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'per_page',
                        'type'    => 'int',
                        'default' => '12',
                        'help'    => __('Productos por página (SSR y en cada carga del infinite scroll).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'ui',
                        'type'    => 'string',
                        'default' => 'adidas',
                        'help'    => __('"adidas" muestra los dots solo al hover. "nike" los muestra siempre.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'infinite',
                        'type'    => 'bool',
                        'default' => '0',
                        'help'    => __('1 activa carga incremental al hacer scroll (REST). 0 muestra solo SSR.', 'bfs-shortcodes'),
                    ],
                ],
            ],

            [
                'category'    => __('Búsqueda', 'bfs-shortcodes'),
                'tag'         => 'bfs_search_bar',
                'title'       => __('Buscador premium (overlay)', 'bfs-shortcodes'),
                'description' => __(
                    'Muestra una barra de búsqueda con experiencia premium tipo “Nike”: al hacer foco abre un overlay a pantalla completa, muestra historial local, sugerencias y resultados dinámicos. ' .
                    '<ul style="margin:8px 0 0 18px; list-style:disc;">' .
                    '<li><strong>Historial</strong> (localStorage): últimas búsquedas con opción de eliminar (X).</li>' .
                    '<li><strong>Resultados en vivo</strong> con <em>debounce</em> y paginación para no penalizar mobile.</li>' .
                    '<li><strong>Filtros</strong> como “chips” minimal (Precio/Marca/Talla/Color + Más filtros) que actualizan resultados sin recargar.</li>' .
                    '</ul>' .
                    '<p style="margin:10px 0 0;"><strong>Data model:</strong> pensado para CPTs <code>fs_producto</code>, <code>fs_variante</code> y <code>fs_oferta</code> con taxonomías y rangos de precio/stock.</p>',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_search_bar placeholder="Buscar productos…" min_chars="2" max_results="12"]',
                'params'      => [
                    [
                        'name'    => 'placeholder',
                        'type'    => 'string',
                        'default' => __('Buscar…', 'bfs-shortcodes'),
                        'help'    => __('Texto del placeholder del input.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'min_chars',
                        'type'    => 'int',
                        'default' => '2',
                        'help'    => __('Mínimo de caracteres antes de lanzar búsqueda al servidor (recomendado: 2).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'max_results',
                        'type'    => 'int',
                        'default' => '12',
                        'help'    => __('Resultados máximos por petición (paginable).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'history_limit',
                        'type'    => 'int',
                        'default' => '8',
                        'help'    => __('Número máximo de términos guardados en historial local.', 'bfs-shortcodes'),
                    ],
                ],
            ],

            [
                'category'    => __('Ficha de producto', 'bfs-shortcodes'),
                'tag'         => 'bfs_product_detail',
                'title'       => __('Ficha de producto (Nike-like)', 'bfs-shortcodes'),
                'description' => __(
                    'Renderiza una ficha completa de producto para CPT <code>fs_producto</code> con experiencia tipo Nike: galería (thumbnails), selector de color + tallas (deshabilita sin stock), precio dinámico y botones por tienda (sin carrito).<br><br>' .
                    '<strong>Descripción:</strong> decodifica HTML escapado (<code>&amp;lt;p&amp;gt;</code>), normaliza secciones (<em>Características</em>, <em>Cómo cuidar</em>, <em>Composición</em>) y aplica sanitización segura.',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_product_detail]',
                'params'      => [
                    [
                        'name'    => 'id',
                        'type'    => 'int',
                        'default' => '',
                        'help'    => __('ID del post fs_producto (opcional si estás en single fs_producto).', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'spu',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('SPU canónico (meta fs_product_id) para resolver el producto (opcional).', 'bfs-shortcodes'),
                    ],
                ],
            ],

[
    'category'    => __('Recomendador', 'bfs-shortcodes'),
    'tag'         => 'bfs_finder',
    'title'       => __('Encuesta + Comparador (TOP 3)', 'bfs-shortcodes'),
    'description' => __(
        'Sección ultra minimalista tipo Nike/Adidas: muestra una encuesta dinámica (1 pregunta por vez) y al finalizar carga automáticamente un TOP 3 en columnas comparativas. ' .
        'SEO-friendly (enlaces reales a ficha) y performance-first (REST cacheado, imágenes lazy).',
        'bfs-shortcodes'
    ),
    'example'     => '[bfs_finder genero="hombre" limit="3" guide_url="/guia-tallas/"]',
    'params'      => [
        [
            'name'    => 'genero',
            'type'    => 'string',
            'default' => '',
            'help'    => __('Filtro de género (ej: "hombre", "mujer", "infantil").', 'bfs-shortcodes'),
        ],
        [
            'name'    => 'limit',
            'type'    => 'int',
            'default' => '3',
            'help'    => __('Número de recomendaciones finales (máx 6).', 'bfs-shortcodes'),
        ],
        [
            'name'    => 'pool',
            'type'    => 'int',
            'default' => '60',
            'help'    => __('Pool interno para rankear (máx 200). Más alto = más coste.', 'bfs-shortcodes'),
        ],
        [
            'name'    => 'guide_url',
            'type'    => 'string',
            'default' => '',
            'help'    => __('URL a guía (ej: guía de tallas) para el botón secundario.', 'bfs-shortcodes'),
        ],
        [
            'name'    => 'title',
            'type'    => 'string',
            'default' => '',
            'help'    => __('Título opcional de la sección.', 'bfs-shortcodes'),
        ],
        [
            'name'    => 'subtitle',
            'type'    => 'string',
            'default' => '',
            'help'    => __('Subtítulo opcional de la sección.', 'bfs-shortcodes'),
        ],
    ],
],

            [
                'category'    => __('Other', 'bfs-shortcodes'),
                'tag'         => 'bfs_size_guide',
                'title'       => __('Guía de tallas (segura)', 'bfs-shortcodes'),
                'description' => __(
                    'Sección de guía de tallas orientativa (sin copiar tablas oficiales). Ideal para crear la página /guia-tallas. ' .
                    'Carga CSS solo cuando el shortcode está presente (rendimiento).',
                    'bfs-shortcodes'
                ),
                'example'     => '[bfs_size_guide cta_text="Hacer el cuestionario" cta_url="/finder/"]',
                'params'      => [
                    [
                        'name'    => 'title',
                        'type'    => 'string',
                        'default' => __('Guía de tallas de botas de fútbol sala', 'bfs-shortcodes'),
                        'help'    => __('Título del bloque.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'intro',
                        'type'    => 'string',
                        'default' => __('Aprende a medir tu pie en cm y elige una talla orientativa.', 'bfs-shortcodes'),
                        'help'    => __('Texto introductorio.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'cta_text',
                        'type'    => 'string',
                        'default' => __('Hacer el cuestionario', 'bfs-shortcodes'),
                        'help'    => __('Texto del botón CTA.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'cta_url',
                        'type'    => 'string',
                        'default' => '',
                        'help'    => __('URL del botón CTA.', 'bfs-shortcodes'),
                    ],
                    [
                        'name'    => 'show_table',
                        'type'    => '0|1',
                        'default' => '1',
                        'help'    => __('Mostrar/ocultar tabla orientativa.', 'bfs-shortcodes'),
                    ],
                ],
            ],

        ];
    }
}
