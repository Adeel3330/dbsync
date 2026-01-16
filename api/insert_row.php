<?php
/**
 * API: Insert Row from one DB to another
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => '',
    'log_id' => null
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $tableName = isset($input['table']) ? sanitize($input['table']) : '';
    $sourceDbKey = isset($input['source_db']) ? sanitize($input['source_db']) : '';
    $targetDbKey = isset($input['target_db']) ? sanitize($input['target_db']) : '';
    $rowData = isset($input['row_data']) ? $input['row_data'] : [];
    
    // Validate input
    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }
    
    if (!in_array($sourceDbKey, ['db_a', 'db_b'])) {
        throw new Exception('Invalid source database');
    }
    
    if (!in_array($targetDbKey, ['db_a', 'db_b'])) {
        throw new Exception('Invalid target database');
    }
    
    if ($sourceDbKey === $targetDbKey) {
        throw new Exception('Source and target databases must be different');
    }
    
    if (empty($rowData)) {
        throw new Exception('Row data is required');
    }
    
    // Log the action
    $logId = uniqid('log_');
    
    logAction('INFO', "Starting insert: {$tableName} from {$sourceDbKey} to {$targetDbKey}", [
        'log_id' => $logId,
        'columns' => array_keys($rowData)
    ]);
    
    // Attempt insert
    $result = insertRow($sourceDbKey, $targetDbKey, $tableName, $rowData);
    
    if ($result['success']) {
        logAction('SUCCESS', "Insert completed: {$tableName}", [
            'log_id' => $logId,
            'source' => $sourceDbKey,
            'target' => $targetDbKey
        ]);
        
        $response['success'] = true;
        $response['message'] = $result['message'];
        $response['log_id'] = $logId;
    } else {
        logAction('ERROR', "Insert failed: {$result['message']}", [
            'log_id' => $logId,
            'table' => $tableName,
            'source' => $sourceDbKey,
            'target' => $targetDbKey
        ]);
        
        $response['message'] = $result['message'];
        $response['log_id'] = $logId;
    }
    
} catch (Exception $e) {
    $errorId = uniqid('err_');
    logAction('ERROR', $e->getMessage(), ['log_id' => $errorId]);
    $response['message'] = $e->getMessage();
    $response['log_id'] = $errorId;
}

echo json_encode($response);
