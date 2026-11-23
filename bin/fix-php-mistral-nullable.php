<?php

/**
 * Idempotently applies the PHP 8.4 fix for the implicit nullable $batch
 * parameter in partitech/php-mistral (no fixed release exists yet).
 * Runs on every composer install/update. Remove once the package ships the fix.
 */

declare(strict_types=1);

$file = dirname(__DIR__) . '/vendor/partitech/php-mistral/src/Embeddings/EmbeddingCollectionTrait.php';
$from = 'public function embedTexts(array $texts, ?string $model=null, int $batch=null): EmbeddingCollection';
$to   = 'public function embedTexts(array $texts, ?string $model = null, ?int $batch = null): EmbeddingCollection';

$source = file_get_contents($file);

if (str_contains($source, $to)) {
    echo "php-mistral already patched; nothing to do.\n";
    exit(0);
}

if (!str_contains($source, $from)) {
    fwrite(STDERR, "Unexpected php-mistral source; patch not applied. Please update this script.\n");
    exit(1);
}

file_put_contents($file, str_replace($from, $to, $source));
echo "Patched partitech/php-mistral implicit nullable \$batch parameter.\n";
