<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Debug advisee reports route
$app->get('/api/debug/advisee-reports', function (Request $request, Response $response) {
    try {
        error_log('Debug: Starting advisee reports debug endpoint');

        // Test basic response
        $data = [
            'message' => 'Debug endpoint working',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        error_log('Debug endpoint error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Debug error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Debug with PDO test
$app->get('/api/debug/pdo-test', function (Request $request, Response $response) {
    try {
        error_log('Debug: Testing PDO connection');

        $pdo = $this->get('pdo');
        error_log('Debug: PDO retrieved from container');

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE role = "advisor"');
        $result = $stmt->fetch();
        error_log('Debug: Query executed successfully');

        $data = [
            'message' => 'PDO test successful',
            'advisor_count' => $result['count']
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        error_log('PDO test error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'PDO error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});
