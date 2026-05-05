<?php
// Router for `php -S` built-in server.
// Returns false for existing static files so PHP serves them directly;
// otherwise hands off to Symfony's front controller.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
