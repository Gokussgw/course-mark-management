<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response as Psr7Response;

class JwtMiddleware
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $jwtHeader = $request->getHeaderLine('Authorization');

        if (!$jwtHeader) {
            $response = new Psr7Response();
            $response->getBody()->write(json_encode(['error' => 'Missing token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $jwt = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $jwtHeader));

        try {
            $jwtSecret = $this->container->get('settings')['jwt']['secret'];
            $payload = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));

            // Create user object for backward compatibility
            $user = (object) [
                'id' => $payload->id,
                'email' => $payload->email,
                'role' => $payload->role,
                'name' => $payload->name
            ];

            // Add user info to the request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('userId', $payload->id);
            $request = $request->withAttribute('userEmail', $payload->email);
            $request = $request->withAttribute('userRole', $payload->role);
            $request = $request->withAttribute('userName', $payload->name);

            return $handler->handle($request);
        } catch (Exception $e) {
            $response = new Psr7Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}

class RoleMiddleware
{
    protected $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $userRole = $request->getAttribute('userRole');

        if ($userRole !== $this->role) {
            $response = new Psr7Response();
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}

class AdminOnlyMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $userRole = $request->getAttribute('userRole');

        if ($userRole !== 'admin') {
            $response = new Psr7Response();
            $response->getBody()->write(json_encode(['error' => 'Admin access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
