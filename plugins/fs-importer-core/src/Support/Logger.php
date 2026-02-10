<?php
declare(strict_types=1);

namespace FS\ImporterCore\Support;

use Throwable;

final class Logger
{
    public static function error(Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FS Importer] ' . $e->getMessage());
        }
    }
}
