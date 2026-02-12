<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Utils;

final class Color_Normalizer {

    private static array $map = [
        'negro' => 'negro',
        'blanco' => 'blanco',
        'azul marino' => 'azul marino',
        'azul royal' => 'azul royal',
        'azul' => 'azul',
        'rojo' => 'rojo',
        'naranja' => 'naranja',
        'amarillo fluor' => 'amarillo fluor',
        'amarillo' => 'amarillo',
        'verde fluor' => 'verde fluor',
        'verde lima' => 'verde',
        'verde' => 'verde',
        'gris' => 'gris',
        'marron' => 'marron',
        'beige' => 'beige',
        'rosa' => 'rosa',
        'fucsia' => 'fucsia',
        'morado' => 'morado',
        'plata' => 'plata',
        'plateado' => 'plata',
        'oro' => 'oro',
        'multicolor' => 'multicolor',
        'turquesa' => 'turquesa',
        'coral' => 'coral',
        'bordeaux' => 'bordeaux',
        'cuero' => 'cuero'
    ];

    public static function normalize( string $raw ): string {

        $raw = trim( strtolower( $raw ) );

        if ($raw === 'color') {
            return '';
        }

        // quitar acentos
        $raw = remove_accents( $raw );

        // corregir errores comunes
        $raw = str_replace('marinno', 'marino', $raw);

        // dividir combinaciones
        $parts = explode('-', $raw);
        $parts = array_map('trim', $parts);

        // usar primer color dominante
        $base = $parts[0];

        foreach ( self::$map as $key => $value ) {
            if ( strpos($base, $key) !== false ) {
                return $value;
            }
        }

        return $base;
    }
}
