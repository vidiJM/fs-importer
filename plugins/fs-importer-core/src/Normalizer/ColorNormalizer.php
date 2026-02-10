<?php
namespace FS\ImporterCore\Normalizer;

use FS\ImporterCore\Support\ConfigLoader;

final class ColorNormalizer
{
    /**
     * Normaliza un color del feed
     * Devuelve:
     *  - base: para agrupar variantes
     *  - full: color combinado limpio
     */
    public static function normalize(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
    
        $value = TextNormalizer::normalize($raw);
        if ($value === '') {
            return null;
        }
    
        $colors = ConfigLoader::load('colors.json');
        if (!is_array($colors)) {
            return null;
        }
    
        foreach ($colors as $canonical => $variants) {
            foreach ((array) $variants as $variant) {
                $variant = preg_quote($variant, '~');
                if (preg_match('~\b' . $variant . '\b~', $value)) {
                    return (string) $canonical; // 🔒 SIEMPRE string
                }
            }
        }
    
        // fallback: un solo color limpio
        return $value !== '' ? $value : null;
    }


    /**
     * Elimina cualquier color del nombre del modelo
     */
    public static function stripFromModel(string $model): string
    {
        $value = TextNormalizer::normalize($model);
        $colors = ConfigLoader::load('colors.json');

        foreach ($colors as $variants) {
            foreach ((array) $variants as $variant) {
                $variant = preg_quote($variant, '~');
                $value = preg_replace('~\b' . $variant . '\b~', '', $value);
            }
        }

        $value = preg_replace('~\s+~', ' ', trim($value));

        return strtoupper($value);
    }
}
