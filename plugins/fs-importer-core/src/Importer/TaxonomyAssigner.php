<?php
namespace FS\ImporterCore\Importer;

final class TaxonomyAssigner
{
    public static function setSingle(int $postId, string $taxonomy, string $term): void
    {
        $term = trim($term);
        if ($term === '') return;
    
        wp_set_object_terms($postId, [$term], $taxonomy, false);
    }


    public static function setMultiple(
        int $postId,
        string $taxonomy,
        array $terms
    ): void {
        if (!$terms) return;

        wp_set_object_terms(
            $postId,
            $terms,
            $taxonomy,
            false
        );
    }
    
    
}
