<?php
// Router for PHP built-in server
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Handle specific API files
if ($path === '/db-api.php' && file_exists(__DIR__ . '/db-api.php')) {
    include __DIR__ . '/db-api.php';
    return;
}

if ($path === '/simple-api.php' && file_exists(__DIR__ . '/simple-api.php')) {
    include __DIR__ . '/simple-api.php';
    return;
}

if ($path === '/marks-api.php' && file_exists(__DIR__ . '/marks-api.php')) {
    include __DIR__ . '/marks-api.php';
    return;
}

if ($path === '/feedback-api.php' && file_exists(__DIR__ . '/feedback-api.php')) {
    include __DIR__ . '/feedback-api.php';
    return;
}

if ($path === '/breakdown-api.php' && file_exists(__DIR__ . '/breakdown-api.php')) {
    include __DIR__ . '/breakdown-api.php';
    return;
}

if ($path === '/ranking-api.php' && file_exists(__DIR__ . '/ranking-api.php')) {
    include __DIR__ . '/ranking-api.php';
    return;
}

// Handle auth routes
if (strpos($path, '/api/auth/') === 0) {
    $file = __DIR__ . $path . '.php';
    if (file_exists($file)) {
        include $file;
        return;
    }
}

// Handle static files
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let the built-in server handle static files
}

// All other routes go to Slim index.php
include __DIR__ . '/index.php';
