<?php

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| Autoloader simple estilo PSR-4
|--------------------------------------------------------------------------
*/

spl_autoload_register(function ($class) {

    // solo cargamos clases de nuestro plugin
    if (strpos($class, 'WPLP_') !== 0) {
        return;
    }

    $class = strtolower($class);

    $paths = [
        WPLP_PATH . 'includes/core/class-' . str_replace('_', '-', $class) . '.php',
        WPLP_PATH . 'includes/admin/class-' . str_replace('_', '-', $class) . '.php',
        WPLP_PATH . 'includes/linkedin/class-' . str_replace('_', '-', $class) . '.php',
        WPLP_PATH . 'includes/content/class-' . str_replace('_', '-', $class) . '.php',
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

require_once WPLP_PATH . 'includes/helpers.php';