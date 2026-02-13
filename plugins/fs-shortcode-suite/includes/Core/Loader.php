<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Builders\Grid_Dataset_Builder;
use FS\ShortcodeSuite\Data\Services\Grid_Service;
use FS\ShortcodeSuite\Core\Cache_Manager;
use FS\ShortcodeSuite\Shortcodes\Product_Grid;
use FS\ShortcodeSuite\REST\Grid_Controller;
use FS\ShortcodeSuite\Core\Assets;
use FS\ShortcodeSuite\Admin\Admin_Menu;

defined('ABSPATH') || exit;

final class Loader
{
    public function init(): void
    {
        $this->boot_grid_system();
        $this->boot_admin();
    }

    /*
    |--------------------------------------------------------------------------
    | Front / Grid System
    |--------------------------------------------------------------------------
    */

    private function boot_grid_system(): void
    {
        // Assets
        $assets = new Assets();
        $assets->init();

        // Core services
        $repository = new Product_Repository();
        $builder    = new Grid_Dataset_Builder($repository);
        $cache      = new Cache_Manager();

        $service = new Grid_Service($repository, $builder, $cache);

        // Shortcode
        new Product_Grid($service);

        // REST
        add_action('rest_api_init', function () use ($service) {
            $controller = new Grid_Controller($service);
            $controller->register_routes();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Area
    |--------------------------------------------------------------------------
    */

    private function boot_admin(): void {
    if (is_admin()) {
        $admin = new Admin_Menu();
        $admin->init();
    }
}
}
