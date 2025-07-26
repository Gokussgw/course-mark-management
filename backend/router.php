<?php
// Router for PHP built-in server
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Add CORS headers for all requests
function addCORSHeaders()
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:8083',
        'http://localhost:8084',
        'http://localhost:3000'
    ];

    if (in_array($origin, $allowedOrigins) || preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }

    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    addCORSHeaders();
    http_response_code(200);
    exit;
}

// Add CORS headers to all responses
addCORSHeaders();

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

if ($path === '/advisor-dashboard-api.php' && file_exists(__DIR__ . '/advisor-dashboard-api.php')) {
    include __DIR__ . '/advisor-dashboard-api.php';
    return;
}

if ($path === '/ranking-api.php' && file_exists(__DIR__ . '/ranking-api.php')) {
    include __DIR__ . '/ranking-api.php';
    return;
}

// Handle auth routes - route to Slim app
if (strpos($path, '/api/') === 0) {
    include __DIR__ . '/index.php';
    return;
}

// Handle static files
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let the built-in server handle static files
}

// All other routes go to Slim index.php
include __DIR__ . '/index.php';
