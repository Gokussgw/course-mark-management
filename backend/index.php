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
    // JWT middleware with proper authentication
    'jwt' => function ($c) {
        return function ($request, $handler) {
            $authHeader = $request->getHeaderLine('Authorization');

            if (empty($authHeader)) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];

                try {
                    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
                    $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtSecret, 'HS256'));

                    // Add user info to request attributes
                    $request = $request->withAttribute('user', $decoded);

                    // Continue with the request
                    return $handler->handle($request);
                } catch (\Firebase\JWT\ExpiredException $e) {
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['error' => 'Token expired']));
                    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
                } catch (\Exception $e) {
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['error' => 'Invalid token']));
                    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
                }
            }

            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid authorization header format']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
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

    // Allow specific localhost ports including 8084
    $allowedOrigins = [
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:8083',
        'http://localhost:8084',
        'http://localhost:3000'
    ];

    $allowedOrigin = '*'; // Default fallback
    if (in_array($origin, $allowedOrigins) || preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
        $allowedOrigin = $origin;
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Max-Age', '86400'); // Cache preflight for 1 day
});

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add middleware for parsing request body
$app->addBodyParsingMiddleware();

// Handle preflight requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    $origin = $request->getHeaderLine('Origin');

    // Allow specific localhost ports including 8084
    $allowedOrigins = [
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:8083',
        'http://localhost:8084',
        'http://localhost:3000'
    ];

    $allowedOrigin = '*'; // Default fallback
    if (in_array($origin, $allowedOrigins) || preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
        $allowedOrigin = $origin;
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Max-Age', '86400') // Cache preflight for 1 day
        ->withStatus(200);
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
require __DIR__ . '/src/routes/comparisons.php';
require __DIR__ . '/src/routes/adviseeReports.php';

// Catch-all route to handle 404 errors
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

// Run the app
$app->run();
