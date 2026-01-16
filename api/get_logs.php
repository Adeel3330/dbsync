<?php
/**
 * API: Get Logs
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
    
    $logs = getLogs($limit);
    
    // Filter by type if specified
    if (!empty($type)) {
        $logs = array_filter($logs, function($log) use ($type) {
            return $log['type'] === $type;
        });
        $logs = array_values($logs);
    }
    
    // Count by type
    $counts = [
        'total' => count($logs),
        'ERROR' => 0,
        'SUCCESS' => 0,
        'INFO' => 0,
        'WARNING' => 0
    ];
    
    foreach ($logs as $log) {
        if (isset($counts[$log['type']])) {
            $counts[$log['type']]++;
        }
    }
    
    $response['data'] = [
        'logs' => $logs,
        'counts' => $counts
    ];
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
