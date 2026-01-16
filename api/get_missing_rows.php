<?php
/**
 * API: Get Missing Rows
 * Fetches rows that exist in source DB but not in target DB
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
    $sourceDb = isset($_GET['source']) ? sanitize($_GET['source']) : '';
    $targetDb = isset($_GET['target']) ? sanitize($_GET['target']) : '';
    $tableName = isset($_GET['table']) ? sanitize($_GET['table']) : '';
    
    // Validate input
    if (!in_array($sourceDb, ['db_a', 'db_b'])) {
        throw new Exception('Invalid source database');
    }
    
    if (!in_array($targetDb, ['db_a', 'db_b'])) {
        throw new Exception('Invalid target database');
    }
    
    if ($sourceDb === $targetDb) {
        throw new Exception('Source and target databases must be different');
    }
    
    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }
    
    // Get primary keys
    $primaryKeys = getPrimaryKeyColumns($sourceDb, $tableName);
    
    if (empty($primaryKeys)) {
        throw new Exception('Table has no primary key');
    }
    
    $pdoSource = DatabaseConnection::getConnection($sourceDb);
    $pdoTarget = DatabaseConnection::getConnection($targetDb);
    
    // Get all primary keys from target
    $pkListTarget = [];
    $pkCols = implode(', ', array_map(fn($pk) => "`{$pk}`", $primaryKeys));
    
    $stmtTarget = $pdoTarget->query("SELECT {$pkCols} FROM `{$tableName}`");
    while ($row = $stmtTarget->fetch()) {
        $pkKey = implode('_', array_map(fn($pk) => $row[$pk], $primaryKeys));
        $pkListTarget[$pkKey] = true;
    }
    
    // Get rows from source that don't exist in target
    $missingRows = [];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $stmtSource = $pdoSource->query("SELECT * FROM `{$tableName}` LIMIT {$limit} OFFSET {$offset}");
    $allSourceRows = $stmtSource->fetchAll();
    
    foreach ($allSourceRows as $row) {
        $pkValues = [];
        foreach ($primaryKeys as $pk) {
            $pkValues[] = $row[$pk];
        }
        $pkKey = implode('_', $pkValues);
        
        if (!isset($pkListTarget[$pkKey])) {
            $missingRows[] = $row;
        }
    }
    
    $response['data'] = $missingRows;
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

