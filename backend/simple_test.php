<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/test', function (Request $request, Response $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'Test route working!']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/auth/login', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Login route found but simplified']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
