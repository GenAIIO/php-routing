<?php

/**
 * Minimal PSR-4 autoloader for the GenAI\Routing namespace.
 *
 * Use this when you are not running Composer. If you are, just rely on
 * Composer's generated autoloader instead (see composer.json).
 *
 * Compatible with PHP 5.3.29.
 */

spl_autoload_register(function ($class) {
    $prefix  = 'GenAI\\Routing\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Not our namespace; let another registered autoloader handle it.
        return;
    }

    $relative = substr($class, $len);
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
