<?php
/**
 * API: Compare Records
 * Compares rows based on FULL ROW DATA (not just primary key)
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
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $result = compareRecords('db_a', 'db_b', $tableName, $primaryKeys, $limit, $offset);
    
    if (isset($result['error'])) {
        throw new Exception($result['error']);
    }
    
    // Fetch actual row data for differences
    $pdoA = DatabaseConnection::getConnection('db_a');
    $pdoB = DatabaseConnection::getConnection('db_b');
    
    // Get all columns
    $structA = getTableStructure('db_a', $tableName);
    $columns = array_column($structA['columns'], 'Field');
    
    $pkSelectCols = [];
    foreach ($primaryKeys as $pk) {
        $pkSelectCols[] = "`{$pk}`";
    }
    
    // Fetch all rows from DB A
    $sqlA = "SELECT * FROM `{$tableName}`";
    $stmtA = $pdoA->query($sqlA);
    $allRowsA = [];
    while ($row = $stmtA->fetch()) {
        $pkKey = implode('_', array_map(fn($pk) => $row[$pk], $primaryKeys));
        $allRowsA[$pkKey] = $row;
    }
    
    // Fetch all rows from DB B
    $sqlB = "SELECT * FROM `{$tableName}`";
    $stmtB = $pdoB->query($sqlB);
    $allRowsB = [];
    while ($row = $stmtB->fetch()) {
        $pkKey = implode('_', array_map(fn($pk) => $row[$pk], $primaryKeys));
        $allRowsB[$pkKey] = $row;
    }
    
    // Build hash for full row comparison
    $hashRowsA = [];
    $hashRowsB = [];
    
    foreach ($allRowsA as $pkKey => $row) {
        $hashValue = md5(implode('', array_values($row)));
        $hashRowsA[$pkKey] = ['hash' => $hashValue, 'data' => $row];
    }
    
    foreach ($allRowsB as $pkKey => $row) {
        $hashValue = md5(implode('', array_values($row)));
        $hashRowsB[$pkKey] = ['hash' => $hashValue, 'data' => $row];
    }
    
    // Categorize rows
    $missingInA = [];
    $missingInB = [];
    $differentData = [];
    $matched = [];
    
    foreach ($hashRowsA as $pkKey => $rowA) {
        if (!isset($hashRowsB[$pkKey])) {
            $missingInB[] = $rowA['data'];
        } elseif ($rowA['hash'] !== $hashRowsB[$pkKey]['hash']) {
            $differentData[] = [
                'pk' => $pkKey,
                'dataA' => $rowA['data'],
                'dataB' => $hashRowsB[$pkKey]['data']
            ];
        } else {
            $matched[] = $pkKey;
        }
    }
    
    foreach ($hashRowsB as $pkKey => $rowB) {
        if (!isset($hashRowsA[$pkKey])) {
            $missingInA[] = $rowB['data'];
        }
    }
    
    $response['data'] = [
        'totalA' => count($allRowsA),
        'totalB' => count($allRowsB),
        'missingInA' => count($missingInA),
        'missingInB' => count($missingInB),
        'differentData' => count($differentData),
        'matched' => count($matched),
        'missingInA_rows' => array_slice($missingInA, $offset, $limit),
        'missingInB_rows' => array_slice($missingInB, $offset, $limit),
        'differentData_rows' => array_slice($differentData, $offset, $limit),
        'primaryKeys' => $primaryKeys,
        'columns' => $columns,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'totalMissingInA' => count($missingInA),
            'totalMissingInB' => count($missingInB),
            'totalDifferent' => count($differentData)
        ]
    ];
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
