<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use FS\ShortcodeSuite\Data\Services\Search_Service;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

final class Search_Controller
{
    private Search_Service $service;

    public function __construct(Search_Service $service)
    {
        $this->service = $service;
    }

    /**
     * Registra rutas REST
     */
    public function register_routes(): void
    {
        register_rest_route(
            'fs/v1',
            '/search',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle'],
                'permission_callback' => '__return_true',
                'args' => [
                    'q' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                ],
            ]
        );
    }

    /**
     * Maneja request
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $query = (string) $request->get_param('q');

        $results = $this->service->search($query);

        return new WP_REST_Response($results, 200);
    }
}
