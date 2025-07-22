<?php

// Add debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Exception\HttpNotFoundException;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set up dependency injection
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'settings' => [
        'displayErrorDetails' => $_ENV['APP_ENV'] === 'development',
        'db' => [
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS'],
        ],
        'jwt' => [
            'secret' => $_ENV['JWT_SECRET'],
        ],
    ],
    'pdo' => function ($c) {
        $settings = $c->get('settings');
        $host = $settings['db']['host'];
        $dbname = $settings['db']['name'];
        $username = $settings['db']['user'];
        $password = $settings['db']['pass'];

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return new PDO($dsn, $username, $password, $options);
    },
    // Middleware factories - simplified for now
    'jwt' => function ($c) {
        // Simple no-auth middleware for now
        return function ($request, $handler) {
            return $handler->handle($request);
        };
    },
    'lecturerOnly' => function ($c) {
        return function ($request, $handler) {
            return $handler->handle($request);
        };
    },
    'studentOnly' => function ($c) {
        return function ($request, $handler) {
            return $handler->handle($request);
        };
    },
    'advisorOnly' => function ($c) {
        return function ($request, $handler) {
            return $handler->handle($request);
        };
    },
    'adminOnly' => function ($c) {
        return function ($request, $handler) {
            return $handler->handle($request);
        };
    },
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add error middleware
// Add CORS middleware (must be added before other middleware)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    $origin = $request->getHeaderLine('Origin');

    // Allow any localhost port
    if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
        $allowedOrigin = $origin;
    } else {
        $allowedOrigin = 'http://localhost:3000'; // fallback
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add middleware for parsing request body
$app->addBodyParsingMiddleware();

// Handle preflight requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    $origin = $request->getHeaderLine('Origin');

    // Allow any localhost port
    if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
        $allowedOrigin = $origin;
    } else {
        $allowedOrigin = 'http://localhost:3000'; // fallback
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Define routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Course Mark Management API']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Include route files
require __DIR__ . '/src/routes/auth.php';
require __DIR__ . '/src/routes/users.php';
require __DIR__ . '/src/routes/courses.php';
require __DIR__ . '/src/routes/enrollments.php';
require __DIR__ . '/src/routes/assessments.php';
require __DIR__ . '/src/routes/marks.php';
require __DIR__ . '/src/routes/csvOperations.php';
require __DIR__ . '/src/routes/remarkRequests.php';
require __DIR__ . '/src/routes/atRiskStudents.php';
require __DIR__ . '/src/routes/notifications.php';
require __DIR__ . '/src/routes/admin.php';
require __DIR__ . '/src/routes/system.php';

// Catch-all route to handle 404 errors
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

// Run the app
$app->run();
