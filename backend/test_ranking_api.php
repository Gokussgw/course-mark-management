<?php
// Test the ranking API to see if the LIMIT/OFFSET issue is fixed

$url = 'http://localhost:8000/ranking-api.php?action=class_rankings&limit=20&offset=0';

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'GET',
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === false) {
    echo "Failed to fetch data from API\n";

    // Check HTTP response headers
    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        echo "Header: $header\n";
    }
} else {
    echo "API Response:\n";
    echo $result . "\n";

    // Check if it's valid JSON
    $data = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\n✅ Valid JSON response received\n";
        if (isset($data['rankings'])) {
            echo "Number of rankings returned: " . count($data['rankings']) . "\n";
        }
    } else {
        echo "\n❌ Invalid JSON response\n";
    }
}
