<?php
/**
 * API: Get Dashboard Summary
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'dbA' => ['tables' => 0, 'connected' => false],
        'dbB' => ['tables' => 0, 'connected' => false],
        'summary' => [
            'missingTables' => 0,
            'matchedTables' => 0,
            'mismatchedTables' => 0,
            'totalDifferences' => 0
        ]
    ]
];

try {
    // Get tables from both databases
    $tablesA = getAllTables('db_a');
    $tablesB = getAllTables('db_b');
    
    $response['data']['dbA']['tables'] = count($tablesA);
    $response['data']['dbA']['connected'] = !empty($tablesA);
    
    $response['data']['dbB']['tables'] = count($tablesB);
    $response['data']['dbB']['connected'] = !empty($tablesB);
    
    if (!empty($tablesA) && !empty($tablesB)) {
        // Calculate summary
        $missingInA = count(array_diff($tablesB, $tablesA));
        $missingInB = count(array_diff($tablesA, $tablesB));
        $commonTables = array_intersect($tablesA, $tablesB);
        
        // Check for structural differences in common tables
        $mismatched = 0;
        foreach ($commonTables as $table) {
            $structA = getTableStructure('db_a', $table);
            $structB = getTableStructure('db_b', $table);
            
            if ($structA && $structB) {
                $diff = compareStructureDetails($structA, $structB, $table);
                if ($diff['hasDifference']) {
                    $mismatched++;
                }
            }
        }
        
        $response['data']['summary'] = [
            'missingTables' => $missingInA + $missingInB,
            'matchedTables' => count($commonTables) - $mismatched,
            'mismatchedTables' => $mismatched,
            'totalDifferences' => $missingInA + $missingInB + $mismatched
        ];
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
