<?php
// Simple router for PHP built-in server to work with Slim Framework

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// If the request is for a physical file that exists, let the server handle it
if ($requestPath !== '/' && file_exists(__DIR__ . $requestPath)) {
    return false;
}

// For all other requests, route through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require_once __DIR__ . '/index.php';
