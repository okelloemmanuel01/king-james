<?php
header('Content-Type: application/json');
session_start();

// Simple authentication check
if (!isset($_SESSION['loggedin'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$dataFile = 'admissions.json';
if (file_exists($dataFile)) {
    $applications = json_decode(file_get_contents($dataFile), true);
    // Add IDs for reference
    $applications = array_map(function($app, $index) {
        $app['id'] = $index;
        return $app;
    }, $applications, array_keys($applications));
    echo json_encode($applications);
} else {
    echo json_encode([]);
}
?>