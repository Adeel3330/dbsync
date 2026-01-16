<?php
/**
 * API: Compare Records
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
    $tableName = isset($_GET['table']) ? sanitize($_GET['table']) : '';
    
    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }
    
    // Get primary keys
    $primaryKeys = getPrimaryKeyColumns('db_a', $tableName);
    
    if (empty($primaryKeys)) {
        throw new Exception('Table has no primary key');
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $result = compareRecords('db_a', 'db_b', $tableName, $primaryKeys, $limit, $offset);
    
    if (isset($result['error'])) {
        throw new Exception($result['error']);
    }
    
    $response['data'] = $result;
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
