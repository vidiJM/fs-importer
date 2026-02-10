<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Feed;

final class SsvReader
{
    public static function readGenerator(string $file): \Generator
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $fh = new \SplFileObject($file, 'rb');
        $fh->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);

        $headers = null;
        $delimiter = null;
        $headerCount = 0;

        foreach ($fh as $line) {
            $line = is_string($line) ? trim($line) : '';
            if ($line === '') {
                continue;
            }

            $line = self::normalizeLine($line);

            if ($headers === null) {
                $delimiter = self::detectDelimiter($line);

                // str_getcsv maneja comillas correctamente
                $headers = array_map('trim', str_getcsv($line, $delimiter));
                $headerCount = count($headers);

                if ($headerCount < 1) {
                    return;
                }
                continue;
            }

            $values = str_getcsv($line, (string) $delimiter);
            $valueCount = count($values);

            // Normalizar número de columnas para evitar warnings / errores
            if ($valueCount < $headerCount) {
                $values = array_pad($values, $headerCount, null);
            } elseif ($valueCount > $headerCount) {
                $values = array_slice($values, 0, $headerCount);
            }

            $row = array_combine($headers, $values);
            if (is_array($row)) {
                yield $row;
            }
        }
    }

    public static function read(string $file): array
    {
        $content = file_get_contents($file);

        // Normalizar encoding a UTF-8
        if (!mb_detect_encoding($content, 'UTF-8', true)) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        // Eliminar BOM si existe
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $lines = array_filter(
            array_map('trim', explode("\n", $content)),
            static fn ($l) => $l !== ''
        );

        if (count($lines) < 2) {
            return [];
        }

        // Detectar delimitador desde el header
        $delimiter = self::detectDelimiter($lines[0]);

        // Leer header
        $headers = array_map('trim', str_getcsv(array_shift($lines), $delimiter));
        $headerCount = count($headers);

        $rows = [];

        foreach ($lines as $lineNumber => $line) {
            $values = str_getcsv($line, $delimiter);
            $valueCount = count($values);

            // Normalizar número de columnas
            if ($valueCount < $headerCount) {
                $values = array_pad($values, $headerCount, null);
            } elseif ($valueCount > $headerCount) {
                $values = array_slice($values, 0, $headerCount);
            }

            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    private static function normalizeLine(string $line): string
    {
        // Eliminar BOM si existe (por si viene en primera línea)
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

        // Intento barato de normalizar a UTF-8 por línea (sin cargar todo el fichero)
        if (!mb_detect_encoding($line, 'UTF-8', true)) {
            $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
        }

        return $line;
    }

    private static function detectDelimiter(string $line): string
    {
        foreach (['|', ',', "\t"] as $d) {
            if (substr_count($line, $d) > 5) {
                return $d;
            }
        }

        return '|';
    }
}
