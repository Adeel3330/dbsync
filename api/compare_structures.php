<?php
/**
 * API: Compare Table Structures
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
    // Get tables from both databases
    $tablesA = getAllTables('db_a');
    $tablesB = getAllTables('db_b');
    
    if (empty($tablesA) || empty($tablesB)) {
        throw new Exception('One or both databases have no tables');
    }
    
    // Calculate missing tables
    $missingInA = array_diff($tablesB, $tablesA);
    $missingInB = array_diff($tablesA, $tablesB);
    $commonTables = array_intersect($tablesA, $tablesB);
    
    // Compare structures of common tables
    $structureDifferences = [];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $paginatedTables = array_slice($commonTables, $offset, $limit);
    
    foreach ($paginatedTables as $table) {
        $structA = getTableStructure('db_a', $table);
        $structB = getTableStructure('db_b', $table);
        
        if ($structA && $structB) {
            $diff = compareStructureDetails($structA, $structB, $table);
            if ($diff['hasDifference']) {
                $structureDifferences[] = $diff;
            }
        }
    }
    
    $response['data'] = [
        'missingInA' => array_values($missingInA),
        'missingInB' => array_values($missingInB),
        'commonTables' => array_values($commonTables),
        'structureDifferences' => $structureDifferences,
        'pagination' => [
            'total' => count($commonTables),
            'limit' => $limit,
            'offset' => $offset
        ]
    ];
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
