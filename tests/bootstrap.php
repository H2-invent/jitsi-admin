<?php

use Symfony\Component\Dotenv\Dotenv;
require dirname(__DIR__) . '/vendor/dg/bypass-finals/src/bootstrap.php';

// BypassFinals rewrites both `final` and `readonly` in loaded sources. Under ParaTest
// some PHPUnit classes are loaded before this bootstrap runs (e.g. the readonly
// PHPUnit\Metadata\Metadata base class), while the PHPUnit\Metadata\* subclasses are
// autoloaded afterwards. Rewriting only the subclass then triggers a fatal error:
// "Non-readonly class ... cannot extend readonly class". Exclude PHPUnit's own sources
// from rewriting; application and test classes keep the final/readonly bypass.
DG\BypassFinals::denyPaths(['*/phpunit/*']);

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->usePutenv(true)->bootEnv(dirname(__DIR__) . '/.env');
}

// --- ParaTest parallel database isolation ---
// ParaTest sets a unique TEST_TOKEN for each worker (e.g., "0", "1", "2", ...)
$token = getenv('TEST_TOKEN');

if ($token !== false && $token !== '') {
    // Append the token to the database name so each worker gets its own DB
    $originalUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
    if ($originalUrl !== '') {
        // Parse and modify the database name
        // e.g., mysql://db:db@db:3306/test  ->  mysql://db:db@db:3306/test_0
        $parts  = parse_url($originalUrl);
        $dbName = ltrim($parts['path'] ?? '', '/') . '_' . $token;
        $newUrl = sprintf(
            '%s://%s%s@%s:%d/%s',
            $parts['scheme'] ?? 'mysql',
            $parts['user'] ?? '',
            isset($parts['pass']) ? ':' . $parts['pass'] : '',
            $parts['host'] ?? 'localhost',
            $parts['port'] ?? 3306,
            $dbName
        );
        // Append query string if present
        if (isset($parts['query'])) {
            $newUrl .= '?' . $parts['query'];
        }
        $_ENV['DATABASE_URL']    = $newUrl;
        $_SERVER['DATABASE_URL'] = $newUrl;
        putenv('DATABASE_URL=' . $newUrl);
    }
}
