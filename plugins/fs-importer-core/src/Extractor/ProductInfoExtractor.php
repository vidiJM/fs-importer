<?php
namespace FS\ImporterCore\Extractor;

use FS\ImporterCore\Support\ConfigLoader;
use FS\ImporterCore\Normalizer\TextNormalizer;

final class ProductInfoExtractor
{
    public static function extract(string $rawTitle): array
    {
        // 1. normalización base (NO cortar por "-")
        $text = TextNormalizer::normalize($rawTitle);

        // 2. limpieza mínima de ruido técnico
        $text = preg_replace(
            '~\b(mkp|indooroutdoor)\b~i',
            '',
            $text
        );

        return [
            'gender'  => self::extractGender($text),
            'surface' => self::matchSingle($text, 'surfaces.json'),
            'sole'    => self::matchSingle($text, 'soles.json'),
        ];
    }

    /**
     * Género con prioridad:
     * infantil > mujer > hombre
     * Default: hombre
     */
    private static function extractGender(string $text): string
    {
        $map = ConfigLoader::load('genders.json');

        if (!is_array($map)) {
            return 'hombre';
        }

        $priority = ['infantil', 'mujer', 'hombre'];

        foreach ($priority as $key) {
            if (!isset($map[$key])) {
                continue;
            }

            foreach ($map[$key] as $word) {
                if (preg_match('~\b' . preg_quote($word, '~') . '\b~i', $text)) {
                    return $key;
                }
            }
        }

        return 'hombre';
    }

    /**
     * Devuelve UN solo valor canónico o null
     */
    private static function matchSingle(string $text, string $file): ?string
    {
        $map = ConfigLoader::load($file);

        if (!is_array($map)) {
            return null;
        }

        foreach ($map as $canonical => $variants) {
            foreach ($variants as $word) {
                if (preg_match('~\b' . preg_quote($word, '~') . '\b~i', $text)) {
                    return $canonical;
                }
            }
        }

        return null;
    }
}
