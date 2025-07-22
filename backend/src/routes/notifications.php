<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Notifications routes
$app->group('/api/notifications', function (RouteCollectorProxy $group) {
    // Get notifications for the current user
    $group->get('', function (Request $request, Response $response) {
        $userId = $request->getAttribute('userId');
        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT * FROM notifications
                WHERE user_id = :userId
                ORDER BY created_at DESC
                LIMIT 20
            ');
            $stmt->execute(['userId' => $userId]);
            $notifications = $stmt->fetchAll();

            $response->getBody()->write(json_encode($notifications));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Mark notification as read
    $group->put('/{id}', function (Request $request, Response $response, $args) {
        $notificationId = $args['id'];
        $userId = $request->getAttribute('userId');
        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                UPDATE notifications
                SET is_read = 1
                WHERE id = :notificationId AND user_id = :userId
            ');
            $stmt->execute([
                'notificationId' => $notificationId,
                'userId' => $userId
            ]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Notification not found or does not belong to this user']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'message' => 'Notification marked as read'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Mark all notifications as read
    $group->put('/read/all', function (Request $request, Response $response) {
        $userId = $request->getAttribute('userId');
        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                UPDATE notifications
                SET is_read = 1
                WHERE user_id = :userId AND is_read = 0
            ');
            $stmt->execute(['userId' => $userId]);

            $response->getBody()->write(json_encode([
                'message' => 'All notifications marked as read',
                'count' => $stmt->rowCount()
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Send a notification (for lecturers to students)
    $group->post('/send', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $recipients = $data['recipients'] ?? [];
        $content = $data['content'] ?? '';
        $type = $data['type'] ?? 'message';
        $relatedId = $data['relatedId'] ?? null;
        $userId = $request->getAttribute('userId');
        $userRole = $request->getAttribute('userRole');

        if (empty($recipients) || empty($content)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Only lecturers, advisors, and admins can send notifications
        if (!in_array($userRole, ['lecturer', 'advisor', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized to send notifications']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            $pdo->beginTransaction();

            $successCount = 0;
            foreach ($recipients as $recipientId) {
                $stmt = $pdo->prepare('
                    INSERT INTO notifications 
                    (user_id, type, content, related_id, created_at, is_read, sender_id) 
                    VALUES 
                    (:userId, :type, :content, :relatedId, NOW(), 0, :senderId)
                ');
                $stmt->execute([
                    'userId' => $recipientId,
                    'type' => $type,
                    'content' => $content,
                    'relatedId' => $relatedId,
                    'senderId' => $userId
                ]);

                $successCount++;
            }

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'message' => 'Notifications sent successfully',
                'count' => $successCount
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($app->getContainer()->get('jwt'));
