<?php

/**
 * Minimal PSR-4 autoloader for GenAI\Routing (the package) and Cache (the
 * compiled router, cache/Router.php).
 *
 * Use this when you are not running Composer. If you are, just rely on
 * Composer's generated autoloader instead (see composer.json).
 *
 * Compatible with PHP 5.3.29.
 */

spl_autoload_register(function ($class) {
    $prefixes = array(
        'GenAI\\Routing\\' => __DIR__ . '/../src/',
        'Cache\\'          => __DIR__ . '/cache/',
    );

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
});
