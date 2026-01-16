<?php
/**
 * Database Comparison API
 * 
 * This file provides REST API endpoints for programmatic access
 * to the database comparison functionality.
 * 
 * @package DBSync
 * @version 1.0.0
 */

require_once __DIR__ . '/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse JSON input for POST requests
$input = [];
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
    $input = array_merge($_POST, $input);
}

// Route handling
$response = ['success' => false, 'data' => null, 'error' => null];

try {
    $db1 = getDB1Connection();
    $db2 = getDB2Connection();
    
    if (!$db1 || !$db2) {
        throw new Exception('Database connection failed');
    }
    
    // API Routes
    if ($path === '/api/status') {
        // Get connection status
        $response['success'] = true;
        $response['data'] = [
            'connections' => testConnections(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_time' => date('Y-m-d H:i:s')
            ]
        ];
    }
    elseif ($path === '/api/tables') {
        // Get all tables from both databases
        $response['success'] = true;
        $response['data'] = [
            'db1_tables' => getTables($db1),
            'db2_tables' => getTables($db2)
        ];
    }
    elseif (preg_match('#^/api/compare/tables$#', $path) && $method === 'POST') {
        // Compare tables between databases
        $response['success'] = true;
        $response['data'] = compareTables($db1, $db2);
    }
    elseif (preg_match('#^/api/compare/columns/(.+)$#', $path, $matches) && $method === 'GET') {
        // Compare columns for a specific table
        $tableName = sanitizeName($matches[1]);
        $response['success'] = true;
        $response['data'] = compareTableColumns($db1, $db2, $tableName);
    }
    elseif (preg_match('#^/api/compare/records/(.+)$#', $path, $matches) && $method === 'GET') {
        // Compare records for a specific table
        $tableName = sanitizeName($matches[1]);
        $limit = intval($_GET['limit'] ?? 1000);
        $offset = intval($_GET['offset'] ?? 0);
        
        $response['success'] = true;
        $response['data'] = compareTableRecords($db1, $db2, $tableName, $limit, $offset);
    }
    elseif ($path === '/api/full-report' && $method === 'POST') {
        // Get complete comparison report
        $recordLimit = intval($input['record_limit'] ?? 1000);
        $response['success'] = true;
        $response['data'] = getFullComparisonReport($db1, $db2, $recordLimit);
    }
    elseif ($path === '/api/insert' && $method === 'POST') {
        // Insert missing rows
        $tableName = sanitizeName($input['table_name'] ?? '');
        $rows = $input['rows'] ?? [];
        
        if (empty($tableName) || empty($rows)) {
            throw new Exception('Table name and rows are required');
        }
        
        $result = insertMissingRows($db1, $db2, $tableName, $rows);
        
        // Log the action
        logSyncAction('insert', $tableName, $result, $result['failed'] === 0);
        
        $response['success'] = $result['failed'] === 0;
        $response['data'] = $result;
        
        if ($result['failed'] > 0) {
            $response['error'] = "{$result['failed']} rows failed to insert";
        }
    }
    elseif ($path === '/api/export/json' && $method === 'POST') {
        // Export report to JSON
        $report = $input['report'] ?? null;
        if (!$report) {
            $report = getFullComparisonReport($db1, $db2, intval($input['record_limit'] ?? 1000));
        }
        
        $response['success'] = true;
        $response['data'] = [
            'json' => exportToJSON($report, true)
        ];
    }
    elseif ($path === '/api/table/data' && $method === 'POST') {
        // Get table data
        $tableName = sanitizeName($input['table_name'] ?? '');
        $limit = intval($input['limit'] ?? 100);
        $offset = intval($input['offset'] ?? 0);
        
        if (empty($tableName)) {
            throw new Exception('Table name is required');
        }
        
        $response['success'] = true;
        $response['data'] = [
            'table_name' => $tableName,
            'columns' => getTableColumns($db1, $tableName),
            'primary_key' => getPrimaryKeyColumns($db1, $tableName),
            'data' => getTableData($db1, $tableName, $limit, $offset),
            'total_count' => countTableRows($db1, $tableName)
        ];
    }
    else {
        throw new Exception('Endpoint not found: ' . $path);
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    if (DEBUG_MODE) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    http_response_code(400);
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

