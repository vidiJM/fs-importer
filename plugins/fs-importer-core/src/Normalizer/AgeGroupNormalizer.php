<?php
namespace FS\ImporterCore\Normalizer;

final class AgeGroupNormalizer {

    private const BABY_KEYWORDS = [
        'bebe', 'baby', 'infantil', 'kids', 'kid', 'junior', 'jr'
    ];

    public static function detect(string $text): ?string
    {
        $text = TextNormalizer::normalize($text);

        foreach (self::BABY_KEYWORDS as $word) {
            if (str_contains($text, $word)) {
                return 'junior';
            }
        }

        return 'adult';
    }
}
