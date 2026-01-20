<?php
/**
 * API: Insert Row(s) from one DB to another
 * Supports single and bulk insert operations
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'log_id' => null
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $tableName = isset($input['table']) ? sanitize($input['table']) : '';
    $sourceDbKey = isset($input['source_db']) ? sanitize($input['source_db']) : '';
    $targetDbKey = isset($input['target_db']) ? sanitize($input['target_db']) : '';
    $bulk = isset($input['bulk']) && $input['bulk'] === true;
    
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
    
    $logId = uniqid('log_');
    
    if ($bulk) {
        // Bulk insert mode
        $rows = isset($input['rows']) && is_array($input['rows']) ? $input['rows'] : [];
        
        if (empty($rows)) {
            throw new Exception('No rows provided for bulk insert');
        }
        
        logAction('INFO', "Starting bulk insert: {$tableName} ({$sourceDbKey} → {$targetDbKey})", [
            'log_id' => $logId,
            'total_rows' => count($rows)
        ]);
        
        $successCount = 0;
        $failedCount = 0;
        $failedRows = [];
        
        foreach ($rows as $index => $rowData) {
            if (!is_array($rowData) || empty($rowData)) {
                $failedCount++;
                $failedRows[] = ['index' => $index, 'error' => 'Invalid row data'];
                continue;
            }
            
            try {
                $result = insertRow($sourceDbKey, $targetDbKey, $tableName, $rowData);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedRows[] = [
                        'index' => $index,
                        'error' => $result['message'],
                        'data' => $rowData
                    ];
                    
                    logAction('ERROR', "Bulk insert row {$index} failed: {$result['message']}", [
                        'log_id' => $logId,
                        'row_index' => $index,
                        'table' => $tableName
                    ]);
                }
            } catch (Exception $e) {
                $failedCount++;
                $failedRows[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'data' => $rowData
                ];
            }
        }
        
        logAction('INFO', "Bulk insert completed: {$successCount} success, {$failedCount} failed", [
            'log_id' => $logId,
            'table' => $tableName,
            'success' => $successCount,
            'failed' => $failedCount
        ]);
        
        $response['success'] = $failedCount === 0;
        $response['message'] = "Inserted {$successCount} of " . count($rows) . " rows";
        $response['data'] = [
            'success' => array_fill(0, $successCount, true),
            'failed' => $failedRows
        ];
        $response['log_id'] = $logId;
        
    } else {
        // Single insert mode
        $rowData = isset($input['row_data']) ? $input['row_data'] : [];
        
        if (empty($rowData)) {
            throw new Exception('Row data is required');
        }
        
        logAction('INFO', "Starting insert: {$tableName} from {$sourceDbKey} to {$targetDbKey}", [
            'log_id' => $logId,
            'columns' => array_keys($rowData)
        ]);
        
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
    }
    
} catch (Exception $e) {
    $errorId = uniqid('err_');
    logAction('ERROR', $e->getMessage(), ['log_id' => $errorId]);
    $response['message'] = $e->getMessage();
    $response['log_id'] = $errorId;
}

echo json_encode($response);
