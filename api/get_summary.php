<?php
/**
 * API: Get Dashboard Summary
 * Returns quick summary of both databases with actual database names
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Load configuration to get actual database names
    $config = DatabaseConfig::load();
    $dbNameA = $config['db_a']['name'] ?: 'Database A';
    $dbNameB = $config['db_b']['name'] ?: 'Database B';
    
    // Test both connections
    $testA = testDatabaseConnection('db_a');
    $testB = testDatabaseConnection('db_b');
    
    $data = [
        'dbNames' => [
            'db_a' => $dbNameA,
            'db_b' => $dbNameB
        ],
        'dbA' => [
            'connected' => $testA['success'],
            'message' => $testA['message'] ?? 'Not connected',
            'version' => $testA['version'] ?? null,
            'tables' => [],
            'displayName' => $dbNameA
        ],
        'dbB' => [
            'connected' => $testB['success'],
            'message' => $testB['message'] ?? 'Not connected',
            'version' => $testB['version'] ?? null,
            'tables' => [],
            'displayName' => $dbNameB
        ],
        'summary' => [
            'totalTables' => 0,
            'missingTables' => 0,
            'matchedTables' => 0,
            'mismatchedTables' => 0,
            'totalDifferences' => 0
        ]
    ];
    
    if ($testA['success']) {
        $pdoA = DatabaseConnection::getConnection('db_a');
        $stmt = $pdoA->query("SHOW TABLES");
        $tablesA = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $data['dbA']['tables'] = $tablesA;
    }
    
    if ($testB['success']) {
        $pdoB = DatabaseConnection::getConnection('db_b');
        $stmt = $pdoB->query("SHOW TABLES");
        $tablesB = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $data['dbB']['tables'] = $tablesB;
    }
    
    // Calculate summary
    $tablesInA = $data['dbA']['tables'];
    $tablesInB = $data['dbB']['tables'];
    
    $data['summary']['totalTables'] = count($tablesInA) + count($tablesInB);
    $data['summary']['missingTables'] = count(array_diff($tablesInA, $tablesInB)) + count(array_diff($tablesInB, $tablesInA));
    $data['summary']['matchedTables'] = count(array_intersect($tablesInA, $tablesInB));
    
    $response['data'] = $data;
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
