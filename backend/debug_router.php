<?php
// Debug router for PHP built-in server
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Log all incoming requests for debugging
error_log("Router: Incoming request - URI: $uri, Path: $path, Method: " . $_SERVER['REQUEST_METHOD']);

// Handle static files first
if ($path !== '/' && file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    error_log("Router: Serving static file: $path");
    return false; // Let PHP built-in server handle static files
}

// For all other requests, route to index.php
error_log("Router: Routing to index.php for path: $path");
include __DIR__ . '/index.php';
