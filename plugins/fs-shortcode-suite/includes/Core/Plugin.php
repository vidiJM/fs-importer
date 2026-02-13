<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

use FS\ShortcodeSuite\Admin\Admin_Menu;
use FS\ShortcodeSuite\Shortcodes\Product_Grid;
use FS\ShortcodeSuite\Shortcodes\Product_Search;


final class Plugin {

    public static function init(): void {

        add_action( 'plugins_loaded', [ self::class, 'boot' ] );
    }

    public static function boot(): void {

        ( new Assets() )->init();
        ( new Admin_Menu() )->init();
        ( new Product_Grid() )->register();
        ( new Product_Search() )->register();
    }
}
