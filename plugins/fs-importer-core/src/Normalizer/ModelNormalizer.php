<?php
namespace FS\ImporterCore\Normalizer;

use FS\ImporterCore\Support\ConfigLoader;

final class ModelNormalizer
{
    private const JUNK_WORDS = [
        'TACOS','IN','IC','FG','AG','TF','JR','W','KIDS','KID'
    ];

    private static function resolveByWhitelist(string $brand, string $text): ?string
    {
        $models = ConfigLoader::load('models.json');
        if (!is_array($models) || !isset($models[$brand])) {
            return null;
        }

        // limpiar números cortos antes de matchear
        $cleanText = preg_replace('~\b\d{2,3}\b~', '', $text);

        foreach ($models[$brand] as $modelName => $rule) {
            if (empty($rule['pattern'])) {
                continue;
            }

            if (preg_match('~' . $rule['pattern'] . '~i', $cleanText)) {

                if (!empty($rule['strip_trailing_numbers'])) {
                    return strtoupper($modelName);
                }

                if (!empty($rule['year'])) {
                    if (preg_match('~\b(2[0-9])\b~', $text, $m)) {
                        return strtoupper($modelName . ' ' . $m[1]);
                    }
                }

                return strtoupper($modelName);
            }
        }

        return null;
    }

    public static function normalize(string $raw, string $brand = ''): string
    {
        $value = TextNormalizer::normalize($raw);

        // cortar por guion
        if (str_contains($value, '-')) {
            $value = trim(explode('-', $value, 2)[0]);
        }

        // eliminar marcas (palabra y pegada)
        $brands = ConfigLoader::load('brands.json');
        if (is_array($brands)) {
            foreach ($brands as $variants) {
                foreach ((array) $variants as $b) {
                    $b = preg_quote(TextNormalizer::normalize($b), '~');
                    $value = preg_replace('~\b' . $b . '\b~i', '', $value);
                    $value = preg_replace('~\b' . $b . '(?=[A-Z])~i', '', $value);
                }
            }
        }

        // resolver whitelist (ANTES de limpiar más)
        $brandKey = strtoupper($brand);
        if ($brandKey !== '') {
            $wl = self::resolveByWhitelist($brandKey, $value);
            if ($wl) {
                return $wl;
            }
        }

        // eliminar palabras basura
        $value = preg_replace(
            '~\b(' . implode('|', self::JUNK_WORDS) . ')\b~i',
            '',
            $value
        );

        // género / edad
        $value = preg_replace(
            '~\b(hombre|mujer|nino|ninos|nina|ninas|adulto)\b~i',
            '',
            $value
        );

        // superficies / marketing
        $value = preg_replace(
            '~\b(indoor|outdoor|indooroutdoor|futsal|sala|turf|court|mkp)\b~i',
            '',
            $value
        );

        // categorías genéricas
        $value = preg_replace(
            '~\b(zapatilla|zapatillas|zapato|zapatos|botas|calzado|futbol|competition|liga)\b~i',
            '',
            $value
        );

        // colores
        $value = ColorNormalizer::stripFromModel($value);

        // unir letras + números
        $value = preg_replace('~\b([a-z])\s+(\d)\b~i', '$1$2', $value);

        // TOP FLEX 2528 → TOP FLEX 25
        $value = preg_replace(
            '~\b(top\s*flex)\s*(\d{2})\d+\b~i',
            '$1 $2',
            $value
        );

        // eliminar números cortos finales
        $value = preg_replace('~\b\d{3}\b~', '', $value);

        // IDs largos
        $value = preg_replace('~\b\d{4,}\b~', '', $value);

        // limpiar separadores
        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('~\s+~', ' ', trim($value));

        // deduplicar palabras
        $parts = explode(' ', $value);
        $clean = [];
        foreach ($parts as $p) {
            if (!in_array($p, $clean, true)) {
                $clean[] = $p;
            }
        }

        $result = strtoupper(implode(' ', $clean));

        // ️si queda vacío o igual a la marca → NO crear producto basura
        if ($result === '' || $result === strtoupper($brand)) {
            return '';
        }

        return $result;
    }
}
