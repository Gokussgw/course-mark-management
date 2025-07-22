<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtAuthMiddleware
{
    private $jwtSecret;

    public function __construct($jwtSecret)
    {
        $this->jwtSecret = $jwtSecret;
    }

    public function __invoke(Request $request, RequestHandler $handler)
    {
        $response = new Response();

        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];

            try {
                $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

                // Add user info to request attributes
                $request = $request->withAttribute('user', $decoded);

                // Continue with the request
                return $handler->handle($request);
            } catch (ExpiredException $e) {
                $response->getBody()->write(json_encode(['error' => 'Token expired']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode(['error' => 'Invalid token']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        }

        $response->getBody()->write(json_encode(['error' => 'Invalid authorization format']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
