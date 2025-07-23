<?php
// Router for PHP built-in server - Route all requests to index.php for Slim Framework

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Handle legacy standalone API files
$standalonePHPFiles = [
    '/db-api.php',
    '/simple-api.php', 
    '/marks-api.php',
    '/feedback-api.php',
    '/breakdown-api.php',
    '/ranking-api.php'
];

foreach ($standalonePHPFiles as $file) {
    if ($path === $file && file_exists(__DIR__ . $file)) {
        include __DIR__ . $file;
        return;
    }
}

// Handle static files (but not directories)
if ($path !== '/' && file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false; // Let PHP built-in server handle static files
}

// For all other requests (including API routes), route through Slim Framework
// This ensures proper handling of /api/* routes
require_once __DIR__ . '/index.php';
