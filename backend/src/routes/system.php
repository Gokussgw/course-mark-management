<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// System health monitoring routes
$app->group('/api/admin/system', function (RouteCollectorProxy $group) {
    // Get system health status
    $group->get('/health', function (Request $request, Response $response) {
        // Check database connection
        $pdo = $this->get('pdo');
        $dbStatus = 'online';
        $dbResponseTime = 0;

        try {
            $startTime = microtime(true);
            $stmt = $pdo->query('SELECT 1');
            $dbResponseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms
        } catch (PDOException $e) {
            $dbStatus = 'offline';

            // Log the database connection failure
            error_log('Database connection failure: ' . $e->getMessage());
        }

        // Get PHP info
        $phpVersion = phpversion();
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');

        // Get disk space info
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $diskUsagePercent = round(($totalSpace - $freeSpace) / $totalSpace * 100, 2);

        // Get MySQL version
        $mysqlVersion = '';
        if ($dbStatus === 'online') {
            try {
                $stmt = $pdo->query('SELECT VERSION() as version');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $mysqlVersion = $result['version'];
            } catch (PDOException $e) {
                // Silently fail
            }
        }

        // Get last backup time (this would be implemented based on your backup system)
        $lastBackupTime = ''; // You would need to implement this based on your backup system

        // Get system load (Linux only)
        $systemLoad = [];
        if (function_exists('sys_getloadavg')) {
            $systemLoad = sys_getloadavg();
        }

        $healthData = [
            'status' => 'ok',
            'database' => [
                'status' => $dbStatus,
                'responseTime' => $dbResponseTime,
                'version' => $mysqlVersion
            ],
            'system' => [
                'phpVersion' => $phpVersion,
                'memoryLimit' => $memoryLimit,
                'maxExecutionTime' => $maxExecutionTime,
                'diskUsage' => [
                    'total' => $totalSpace,
                    'free' => $freeSpace,
                    'usedPercent' => $diskUsagePercent
                ]
            ],
            'load' => $systemLoad,
            'lastBackup' => $lastBackupTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Overall system status
        if ($dbStatus === 'offline' || $diskUsagePercent > 90) {
            $healthData['status'] = 'critical';
        } else if ($diskUsagePercent > 80) {
            $healthData['status'] = 'warning';
        }

        $response->getBody()->write(json_encode($healthData));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get system logs summary (count by type for dashboard)
    $group->get('/logs-summary', function (Request $request, Response $response) {
        $pdo = $this->get('pdo');

        try {
            // Get counts of different log actions
            $stmt = $pdo->query('
                SELECT 
                    action,
                    COUNT(*) as count
                FROM system_logs
                GROUP BY action
                ORDER BY count DESC
            ');
            $actionCounts = $stmt->fetchAll();

            // Get recent error logs
            $stmt = $pdo->query('
                SELECT 
                    id, action, description, created_at, user_id
                FROM system_logs
                WHERE action IN ("error", "warning")
                ORDER BY created_at DESC
                LIMIT 5
            ');
            $recentErrors = $stmt->fetchAll();

            // Get total log count
            $stmt = $pdo->query('SELECT COUNT(*) FROM system_logs');
            $totalCount = $stmt->fetchColumn();

            // Get today's log count
            $stmt = $pdo->prepare('
                SELECT COUNT(*) 
                FROM system_logs 
                WHERE DATE(created_at) = CURDATE()
            ');
            $stmt->execute();
            $todayCount = $stmt->fetchColumn();

            $response->getBody()->write(json_encode([
                'total' => $totalCount,
                'today' => $todayCount,
                'byCategoryCount' => $actionCounts,
                'recentErrors' => $recentErrors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Trigger database backup
    $group->post('/backup', function (Request $request, Response $response) {
        // This would be implemented according to your backup system
        // For demonstration purposes, we'll just log the request and return success

        $userId = $request->getAttribute('userId');

        // Log the backup request
        $pdo = $this->get('pdo');
        $stmt = $pdo->prepare('
            INSERT INTO system_logs (action, description, user_id)
            VALUES ("backup", "Database backup initiated", :userId)
        ');
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Database backup initiated successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
});
