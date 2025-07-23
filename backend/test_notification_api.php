<?php
// Test notification API endpoints

echo "Testing notification API endpoints...\n\n";

// Test unread notifications count
echo "1. Testing unread notifications count for user ID 4:\n";
$url1 = 'http://localhost:8000/marks-api.php?action=unread_notifications&user_id=4';
$response1 = file_get_contents($url1);
echo "Response: $response1\n\n";

// Test recent notifications
echo "2. Testing recent notifications for user ID 4:\n";
$url2 = 'http://localhost:8000/marks-api.php?action=recent_notifications&user_id=4&limit=5';
$response2 = file_get_contents($url2);
echo "Response: $response2\n\n";

// Test if data is valid JSON
echo "3. Validating JSON responses:\n";
$data1 = json_decode($response1, true);
$data2 = json_decode($response2, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Unread count API: Valid JSON\n";
    echo "   Unread count: " . ($data1['unread_count'] ?? 'N/A') . "\n";
} else {
    echo "❌ Unread count API: Invalid JSON\n";
}

if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Recent notifications API: Valid JSON\n";
    echo "   Number of notifications: " . count($data2['notifications'] ?? []) . "\n";

    if (!empty($data2['notifications'])) {
        echo "   Latest notification: " . ($data2['notifications'][0]['content'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ Recent notifications API: Invalid JSON\n";
}
