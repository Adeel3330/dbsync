<?php
/**
 * API: Generate Database Comparison Report
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
    // Get all tables from both databases
    $tablesA = getAllTables('db_a');
    $tablesB = getAllTables('db_b');
    
    if (empty($tablesA) || empty($tablesB)) {
        throw new Exception('One or both databases are not connected or have no tables');
    }
    
    // Build comprehensive report
    $report = [
        'generated_at' => date('Y-m-d H:i:s'),
        'databases' => [
            'db_a' => [
                'name' => DatabaseConfig::load()['db_a']['name'] ?: 'Database A',
                'tables_count' => count($tablesA)
            ],
            'db_b' => [
                'name' => DatabaseConfig::load()['db_b']['name'] ?: 'Database B',
                'tables_count' => count($tablesB)
            ]
        ],
        'summary' => [
            'total_tables_a' => count($tablesA),
            'total_tables_b' => count($tablesB),
            'missing_in_a' => 0,
            'missing_in_b' => 0,
            'common_tables' => 0,
            'tables_with_differences' => 0,
            'total_missing_records_a' => 0,
            'total_missing_records_b' => 0
        ],
        'missing_tables' => [
            'in_a' => [],
            'in_b' => []
        ],
        'tables' => []
    ];
    
    // Find missing tables
    $missingInA = array_diff($tablesB, $tablesA);
    $missingInB = array_diff($tablesA, $tablesB);
    $commonTables = array_intersect($tablesA, $tablesB);
    
    $report['summary']['missing_in_a'] = count($missingInA);
    $report['summary']['missing_in_b'] = count($missingInB);
    $report['summary']['common_tables'] = count($commonTables);
    
    // Add missing tables to report
    foreach ($missingInA as $table) {
        $report['missing_tables']['in_b'][] = [
            'table_name' => $table,
            'in_db_a' => false,
            'in_db_b' => true
        ];
    }
    
    foreach ($missingInB as $table) {
        $report['missing_tables']['in_a'][] = [
            'table_name' => $table,
            'in_db_a' => true,
            'in_db_b' => false
        ];
    }
    
    // Compare each common table
    foreach ($commonTables as $tableName) {
        $primaryKeys = getPrimaryKeyColumns('db_a', $tableName);
        
        $tableReport = [
            'table_name' => $tableName,
            'in_both' => true,
            'structure_differences' => [],
            'records' => [
                'count_a' => getTableRowCount('db_a', $tableName),
                'count_b' => getTableRowCount('db_b', $tableName),
                'missing_in_a' => 0,
                'missing_in_b' => 0,
                'matched' => 0,
                'difference' => 0
            ],
            'missing_records' => [
                'in_a' => [],
                'in_b' => []
            ]
        ];
        
        // Compare structure
        $structA = getTableStructure('db_a', $tableName);
        $structB = getTableStructure('db_b', $tableName);
        
        if ($structA && $structB) {
            $diff = compareStructureDetails($structA, $structB, $tableName);
            
            if ($diff['hasDifference']) {
                $report['summary']['tables_with_differences']++;
                $tableReport['structure_differences'] = [
                    'missing_columns_in_a' => $diff['missingColumnsA'],
                    'missing_columns_in_b' => $diff['missingColumnsB'],
                    'column_differences' => $diff['columnDifferences']
                ];
            }
        }
        
        // Compare records if table has primary key
        if (!empty($primaryKeys)) {
            $comparison = compareRecords('db_a', 'db_b', $tableName, $primaryKeys, 1000000, 0);
            
            if (!isset($comparison['error'])) {
                $tableReport['records'] = [
                    'count_a' => $comparison['totalA'],
                    'count_b' => $comparison['totalB'],
                    'missing_in_a' => $comparison['missingInA'],
                    'missing_in_b' => $comparison['missingInB'],
                    'different' => $comparison['differentData'],
                    'matched' => $comparison['matched']
                ];
                
                $report['summary']['total_missing_records_a'] += $comparison['missingInA'];
                $report['summary']['total_missing_records_b'] += $comparison['missingInB'];
                
                // Get missing row details (limit to 100 for performance)
                if ($comparison['missingInA'] > 0 || $comparison['missingInB'] > 0) {
                    $missingRows = getDetailedRecordDifferences('db_a', 'db_b', $tableName, $primaryKeys);
                    
                    if ($missingRows) {
                        $tableReport['missing_records']['in_a'] = array_slice($missingRows['in_a'], 0, 100);
                        $tableReport['missing_records']['in_b'] = array_slice($missingRows['in_b'], 0, 100);
                        
                        if (count($missingRows['in_a']) > 100) {
                            $tableReport['missing_records']['in_a_truncated'] = count($missingRows['in_a']) - 100;
                        }
                        if (count($missingRows['in_b']) > 100) {
                            $tableReport['missing_records']['in_b_truncated'] = count($missingRows['in_b']) - 100;
                        }
                    }
                }
            }
        }
        
        // Only include table if there are differences (skip if perfect match)
        $hasDifferences = 
            !empty($tableReport['structure_differences']) ||
            $tableReport['records']['missing_in_a'] > 0 ||
            $tableReport['records']['missing_in_b'] > 0 ||
            $tableReport['records']['different'] > 0;
        
        if ($hasDifferences) {
            $report['tables'][] = $tableReport;
        }
    }
    
    // Sort tables by total differences
    usort($report['tables'], function($a, $b) {
        $diffA = $a['records']['missing_in_a'] + $a['records']['missing_in_b'] + $a['records']['different'];
        $diffB = $b['records']['missing_in_a'] + $b['records']['missing_in_b'] + $b['records']['different'];
        return $diffB - $diffA;
    });
    
    $response['success'] = true;
    $response['data'] = $report;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Get detailed record differences
 */
function getDetailedRecordDifferences($dbKeyA, $dbKeyB, $tableName, $primaryKeys) {
    try {
        $pdoA = DatabaseConnection::getConnection($dbKeyA);
        $pdoB = DatabaseConnection::getConnection($dbKeyB);
        
        // Get all primary keys from both databases
        $pkListA = [];
        $pkListB = [];
        
        $pkCols = implode(', ', array_map(fn($pk) => "`{$pk}`", $primaryKeys));
        $pkHashCols = implode(', ', array_map(fn($pk) => "IFNULL(`{$pk}`,'')", $primaryKeys));
        
        $sql = "SELECT {$pkCols}, MD5(CONCAT({$pkHashCols})) as row_hash FROM `{$tableName}`";
        
        $stmtA = $pdoA->query($sql);
        while ($row = $stmtA->fetch()) {
            $pkKey = implode('_', array_map(fn($pk) => $row[$pk], $primaryKeys));
            $pkListA[$pkKey] = $row;
        }
        
        $stmtB = $pdoB->query($sql);
        while ($row = $stmtB->fetch()) {
            $pkKey = implode('_', array_map(fn($pk) => $row[$pk], $primaryKeys));
            $pkListB[$pkKey] = $row;
        }
        
        $missingInA = [];
        $missingInB = [];
        
        foreach ($pkListA as $pk => $row) {
            if (!isset($pkListB[$pk])) {
                $missingInB[] = $row;
            }
        }
        
        foreach ($pkListB as $pk => $row) {
            if (!isset($pkListA[$pk])) {
                $missingInA[] = $row;
            }
        }
        
        return [
            'in_a' => $missingInA,
            'in_b' => $missingInB
        ];
        
    } catch (Exception $e) {
        return null;
    }
}

