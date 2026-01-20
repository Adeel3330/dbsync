<?php
/**
 * API: Get Table Data with Pagination
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
    $dbKey = isset($_GET['db']) ? sanitize($_GET['db']) : 'db_a';
    
    if (!in_array($dbKey, ['db_a', 'db_b'])) {
        $dbKey = 'db_a';
    }
    
    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $orderBy = isset($_GET['order_by']) ? sanitize($_GET['order_by']) : '';
    $orderDir = isset($_GET['order_dir']) ? sanitize($_GET['order_dir']) : 'ASC';
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    
    $pdo = DatabaseConnection::getConnection($dbKey);
    
    // Build query
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$tableName}`";
    
    if (!empty($search)) {
        $conditions = [];
        $columns = getTableStructure($dbKey, $tableName);
        if ($columns) {
            foreach ($columns['columns'] as $col) {
                if (stripos($col['Type'], 'text') !== false || stripos($col['Type'], 'varchar') !== false) {
                    $conditions[] = "`{$col['Field']}` LIKE " . $pdo->quote("%{$search}%");
                }
            }
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' OR ', $conditions);
        }
    }
    
    if (!empty($orderBy)) {
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY `{$orderBy}` {$orderDir}";
    }
    
    $sql .= " LIMIT {$limit} OFFSET {$offset}";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->query("SELECT FOUND_ROWS()");
    $total = (int)$countStmt->fetchColumn();
    
    // Get row count
    $rowCount = getTableRowCount($dbKey, $tableName);
    
    $response['data'] = [
        'rows' => $data,
        'total' => $total,
        'rowCount' => $rowCount,
        'limit' => $limit,
        'offset' => $offset
    ];
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
