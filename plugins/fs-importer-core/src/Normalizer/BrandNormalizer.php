<?php
namespace FS\ImporterCore\Normalizer;

use FS\ImporterCore\Normalizer\TextNormalizer;

final class BrandNormalizer
{
    private const MAP = [
        'joma sport'  => 'joma',
        'joma sports' => 'joma',
        'nike inc'    => 'nike',
    ];

    public static function normalize(string $raw): string
    {
        $value = TextNormalizer::normalize($raw);

        return self::MAP[$value] ?? $value;
    }
}
