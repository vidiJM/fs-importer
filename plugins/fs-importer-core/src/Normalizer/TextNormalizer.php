<?php
namespace FS\ImporterCore\Normalizer;

/**
 * Normalizador base de texto
 * - lowercase
 * - sin acentos
 * - solo caracteres seguros
 */
final class TextNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // minúsculas UTF-8
        $value = mb_strtolower($value, 'UTF-8');

        // eliminar acentos
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        // eliminar caracteres raros
        $value = preg_replace('~[^a-z0-9\s\-_]~', '', $value);

        // colapsar espacios
        $value = preg_replace('~\s+~', ' ', $value);

        return trim($value);
    }
}
