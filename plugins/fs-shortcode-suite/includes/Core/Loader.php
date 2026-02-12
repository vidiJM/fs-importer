<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

final class Loader {

    public function init(): void {

        spl_autoload_register( function ( string $class ) {

            if ( strpos( $class, 'FS\\ShortcodeSuite\\' ) !== 0 ) {
                return;
            }

            $relative = str_replace(
                ['FS\\ShortcodeSuite\\', '\\'],
                ['', '/'],
                $class
            );

            $file = FS_SC_SUITE_PATH . 'includes/' . $relative . '.php';

            if ( file_exists( $file ) ) {
                require_once $file;
            }
        });
    }
}
