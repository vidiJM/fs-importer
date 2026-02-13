<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use FS\ShortcodeSuite\Data\Services\Grid_Service;
use WP_REST_Controller;

defined('ABSPATH') || exit;

final class Grid_Controller extends WP_REST_Controller {
    
    private Grid_Service $service;

    public function __construct(Grid_Service $service) {
        $this->namespace = 'fs/v1';
        $this->rest_base = 'grid';
        $this->service   = $service;
    }

    /**
     * Registra rutas REST.
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ],
            ]
        );
    }

    /**
     * Devuelve items paginados del grid.
     */
    public function get_items($request) {
        $filters = [
            'color'     => sanitize_title($request->get_param('color')),
            'gender'    => sanitize_title($request->get_param('gender')),
            'age_group' => sanitize_title($request->get_param('age_group')),
            'brand'     => sanitize_title($request->get_param('brand')),
            'size'      => sanitize_text_field((string) $request->get_param('size')),
        ];
    
        $page     = max(1, (int) $request->get_param('page'));
        $per_page = max(1, min(48, (int) $request->get_param('per_page')));
    
        $result = $this->service->get_grid($filters, $page, $per_page);
    
        return rest_ensure_response($result);
    }

    /**
     * Define parámetros permitidos.
     */
    public function get_collection_params(): array {
        return [
            'color' => [
                'description'       => 'Filtro por color (slug).',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_title',
            ],
            'gender' => [
                'description'       => 'Filtro por género (slug).',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_title',
            ],
            'age_group' => [
                'description'       => 'Filtro por grupo de edad (slug).',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_title',
            ],
            'brand' => [
                'description'       => 'Filtro por marca (slug).',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_title',
            ],
            'size' => [
                'description'       => 'Filtro por talla.',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'page' => [
                'description'       => 'Página actual.',
                'type'              => 'integer',
                'default'           => 1,
            ],
            'per_page' => [
                'description'       => 'Elementos por página.',
                'type'              => 'integer',
                'default'           => 12,
            ],
        ];
    }
    
}
