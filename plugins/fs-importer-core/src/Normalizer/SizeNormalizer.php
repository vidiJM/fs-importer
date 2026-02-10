<?php
namespace FS\ImporterCore\Normalizer;

final class SizeNormalizer
{
    /**
     * Normaliza una talla EU desde CSV
     * Acepta: 32–50 y .5
     *
     * @param mixed $value
     * @return string|null
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convertir a string limpia
        $value = trim(str_replace(',', '.', (string) $value));

        // Validar talla EU (32–50, con .5 opcional)
        if (preg_match('~^(3[2-9]|4[0-9]|50)(\.5)?$~', $value)) {
            return $value;
        }

        return null;
    }
}
