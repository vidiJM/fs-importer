<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Service;

final class InferenceService
{
    public static function apply(array $mapped): array
    {
        $title = (string) ($mapped['product']['title_raw'] ?? '');
        $desc  = (string) ($mapped['product']['description_raw'] ?? '');
        $text  = strtoupper(trim($title . ' ' . $desc));

        if ($text === '') {
            return $mapped;
        }

        // =========================
        // SUPERFICIE (VARIANTE)
        // =========================
        // No pisar si ya viene definida (por mapper o extractor)
        if (empty($mapped['variant']['surface'])) {
            $surface = self::inferSurface($text);
            if ($surface) {
                $mapped['variant']['surface'] = $surface;
            }
        }

        // =========================
        // ENTORNO (PRODUCTO)
        // =========================
        if (empty($mapped['product']['info']['environment'])) {
            $environment = self::inferEnvironment($text);
            if ($environment) {
                $mapped['product']['info']['environment'] = $environment;
            }
        }

        // =========================
        // TIPO DE SUELA (PRODUCTO)
        // =========================
        if (empty($mapped['product']['info']['sole'])) {
            $sole = self::inferSole($text);
            if ($sole) {
                $mapped['product']['info']['sole'] = $sole;
            }
        }

        return $mapped;
    }

    // =========================
    // HELPERS
    // =========================

    private static function inferSurface(string $text): ?string
    {
        // Normalizar separadores para facilitar matching por "tokens"
        $t = str_replace(['-', '_', '/', '\\', '.', ',', ';', ':', '(', ')', '[', ']', '{', '}', "\t"], ' ', $text);

        return match (true) {
            str_contains($t, ' INDOOR ') || str_contains($t, ' IC ') => 'IC',
            str_contains($t, ' TURF ')   || str_contains($t, ' TF ') => 'TF',
            str_contains($t, ' ARTIFICIAL ') || str_contains($t, ' AG ') => 'AG',
            str_contains($t, ' NATURAL ') || str_contains($t, ' FG ') => 'FG',
            default => null,
        };
    }

    private static function inferEnvironment(string $text): ?string
    {
        $t = str_replace(['-', '_', '/', '\\', '.', ',', ';', ':', '(', ')', '[', ']', '{', '}', "\t"], ' ', $text);

        return match (true) {
            str_contains($t, ' INDOOR ') || str_contains($t, ' IC ') => 'INDOOR',
            str_contains($t, ' OUTDOOR ') || str_contains($t, ' FG ') || str_contains($t, ' AG ') => 'OUTDOOR',
            default => null,
        };
    }

    private static function inferSole(string $text): ?string
    {
        $t = str_replace(['-', '_', '/', '\\', '.', ',', ';', ':', '(', ')', '[', ']', '{', '}', "\t"], ' ', $text);

        return match (true) {
            str_contains($t, ' GOMA ')   || str_contains($t, ' RUBBER ') => 'goma',
            str_contains($t, ' CAUCHO ') => 'caucho',
            str_contains($t, ' TPU ')    => 'tpu',
            str_contains($t, ' EVA ')    => 'eva',
            default => null,
        };
    }
}
