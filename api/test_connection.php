<?php
/**
 * API: Test Database Connection
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = ['success' => false, 'message' => '', 'version' => ''];

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $_POST = array_merge($_POST, $input ?? []);
    
    $dbKey = isset($_POST['db_key']) ? sanitize($_POST['db_key']) : 'db_a';
    
    // Validate input
    if (empty($dbKey) || !in_array($dbKey, ['db_a', 'db_b'])) {
        throw new Exception('Invalid database key');
    }
    
    // Test connection
    $result = testDatabaseConnection($dbKey);
    
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    if (isset($result['version'])) {
        $response['version'] = $result['version'];
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

