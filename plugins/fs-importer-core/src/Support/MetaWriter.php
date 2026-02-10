<?php
declare(strict_types=1);

namespace FS\ImporterCore\Support;

final class MetaWriter
{
    public static function update(int $postId, string $key, mixed $value): void
    {
        if ($postId <= 0 || $key === '') {
            return;
        }

        // Si ACF existe, lo usamos, si no, caemos a meta normal.
        if (function_exists('update_field')) {
            update_field($key, $value, $postId);
            return;
        }

        update_post_meta($postId, $key, $value);
    }
}
