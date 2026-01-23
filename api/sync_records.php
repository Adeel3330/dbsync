<?php
/**
 * API: Sync Records
 * Migrate all records from source DB to target DB
 * Supports single and multiple table selection
 * Distinguishes between CREATE (new records) and UPDATE (modified records)
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');
ini_set('max_ececution_time', '12340000');


$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Preview mode - just show what would be synced
        $tablesParam = isset($_GET['tables']) ? sanitize($_GET['tables']) : '';
        $tableName = isset($_GET['table']) ? sanitize($_GET['table']) : ''; // Single table for backward compat
        $sourceDb = isset($_GET['source']) ? sanitize($_GET['source']) : 'db_a';
        $targetDb = isset($_GET['target']) ? sanitize($_GET['target']) : 'db_b';
        
        // Support both single table (table=) and multiple tables (tables=comma-separated)
        $tableNames = [];
        if (!empty($tablesParam)) {
            $tableNames = array_filter(array_map('trim', explode(',', $tablesParam)));
        } elseif (!empty($tableName)) {
            $tableNames = [$tableName];
        }
        
        if (empty($tableNames)) {
            throw new Exception('At least one table is required');
        }
        
        if (!in_array($sourceDb, ['db_a', 'db_b'])) {
            throw new Exception('Invalid source database');
        }
        
        if (!in_array($targetDb, ['db_a', 'db_b'])) {
            throw new Exception('Invalid target database');
        }
        
        if ($sourceDb === $targetDb) {
            throw new Exception('Source and target databases must be different');
        }
        
        // Get preview data for all tables
        $tablePreviews = [];
        $totalCreate = 0;
        $totalUpdate = 0;
        $totalSourceRows = 0;
        $totalTargetRows = 0;
        
        foreach ($tableNames as $table) {
            try {
                $preview = getSyncPreview($sourceDb, $targetDb, $table);
                $tablePreviews[$table] = $preview;
                $totalCreate += $preview['sync_preview']['create_count'];
                $totalUpdate += $preview['sync_preview']['update_count'];
                $totalSourceRows += $preview['counts']['source'];
                $totalTargetRows += $preview['counts']['target'];
            } catch (Exception $e) {
                $tablePreviews[$table] = [
                    'table' => $table,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Preview generated successfully';
        $response['data'] = [
            'tables' => $tableNames,
            'source_db' => $sourceDb,
            'target_db' => $targetDb,
            'table_previews' => $tablePreviews,
            'summary' => [
                'total_tables' => count($tableNames),
                'total_source_rows' => $totalSourceRows,
                'total_target_rows' => $totalTargetRows,
                'total_create' => $totalCreate,
                'total_update' => $totalUpdate,
                'total_operations' => $totalCreate + $totalUpdate
            ]
        ];
        
    } else {
        // POST - Execute sync
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tablesParam = isset($input['tables']) ? $input['tables'] : [];
        $tableName = isset($input['table']) ? sanitize($input['table']) : ''; // Single table for backward compat
        $sourceDb = isset($input['source_db']) ? sanitize($input['source_db']) : '';
        $targetDb = isset($input['target_db']) ? sanitize($input['target_db']) : '';
        $options = isset($input['options']) ? $input['options'] : [];
        
        // Support both single table (table=) and multiple tables (tables=array)
        $tableNames = [];
        if (is_array($tablesParam) && !empty($tablesParam)) {
            $tableNames = array_filter(array_map('sanitize', $tablesParam));
        } elseif (!empty($tableName)) {
            $tableNames = [$tableName];
        }
        
        // Options with defaults
        $createMissing = isset($options['create_missing']) && $options['create_missing'];
        $updateExisting = isset($options['update_existing']) && $options['update_existing'];
        $dryRun = isset($options['dry_run']) && $options['dry_run'];
        
        // Validate input
        if (empty($tableNames)) {
            throw new Exception('At least one table is required');
        }
        
        if (!in_array($sourceDb, ['db_a', 'db_b'])) {
            throw new Exception('Invalid source database');
        }
        
        if (!in_array($targetDb, ['db_a', 'db_b'])) {
            throw new Exception('Invalid target database');
        }
        
        if ($sourceDb === $targetDb) {
            throw new Exception('Source and target databases must be different');
        }
        
        if (!$createMissing && !$updateExisting) {
            throw new Exception('At least one sync option must be enabled (create_missing or update_existing)');
        }
        
        // Execute sync for all tables
        $result = executeMultiTableSync($sourceDb, $targetDb, $tableNames, [
            'create_missing' => $createMissing,
            'update_existing' => $updateExisting,
            'dry_run' => $dryRun
        ]);
        
        $response['success'] = $result['success'];
        $response['message'] = $result['message'];
        $response['data'] = $result['data'];
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Get preview of what would be synced for a single table
 * Uses deduplication mode: compares by non-PK columns only
 */
function getSyncPreview($sourceDb, $targetDb, $tableName) {
    // Get primary keys
    $primaryKeys = getPrimaryKeyColumns($sourceDb, $tableName);
    
    if (empty($primaryKeys)) {
        // Try getting from target if source fails
        $primaryKeys = getPrimaryKeyColumns($targetDb, $tableName);
    }
    
    if (empty($primaryKeys)) {
        throw new Exception('Table has no primary key');
    }
    
    // Get row counts
    $countSource = getTableRowCount($sourceDb, $tableName);
    $countTarget = getTableRowCount($targetDb, $tableName);
    
    // Get comparison data using deduplication mode (compare by non-PK columns)
    $comparison = compareRecordsByNonPK($sourceDb, $targetDb, $tableName, $primaryKeys);
    
    if (isset($comparison['error'])) {
        throw new Exception($comparison['error']);
    }
    
    // Get columns
    $struct = getTableStructure($sourceDb, $tableName);
    $columns = array_column($struct['columns'], 'Field');
    
    // Deduplication mode: CREATE only, no UPDATE
    $createCount = $comparison['missingInB'];
    $updateCount = 0; // No updates in deduplication mode
    $skipCount = $comparison['matched'];
    
    return [
        'table' => $tableName,
        'source_db' => $sourceDb,
        'target_db' => $targetDb,
        'primary_keys' => $primaryKeys,
        'columns' => $columns,
        'non_pk_columns' => $comparison['nonPkColumns'] ?? [],
        'counts' => [
            'source' => $countSource,
            'target' => $countTarget
        ],
        'sync_mode' => 'deduplication',
        'sync_preview' => [
            'create_count' => $createCount,     // Records with new non-PK data (CREATE)
            'update_count' => $updateCount,     // 0 in deduplication mode
            'skip_count' => $skipCount,         // Records with existing non-PK data (skip)
            'unchanged_count' => $skipCount
        ],
        'preview_rows' => [
            'to_create' => array_slice($comparison['missingInB_rows'], 0, 5),
            'to_update' => [],                  // No updates in deduplication mode
            'skipped' => array_slice($comparison['alreadyExist_rows'] ?? [], 0, 5)
        ]
    ];
}

/**
 * Execute sync for multiple tables
 */
function executeMultiTableSync($sourceDb, $targetDb, $tableNames, $options) {
    $createMissing = $options['create_missing'];
    $updateExisting = $options['update_existing'];
    $dryRun = $options['dry_run'];
    
    $logId = 'sync_multi_' . uniqid();
    
    logAction('INFO', "Starting multi-table sync: " . implode(', ', $tableNames) . " ({$sourceDb} → {$targetDb})", [
        'log_id' => $logId,
        'tables' => $tableNames,
        'create_missing' => $createMissing,
        'update_existing' => $updateExisting,
        'dry_run' => $dryRun
    ]);
    
    $tableResults = [];
    $grandTotalCreate = 0;
    $grandTotalUpdate = 0;
    $grandSuccessCreate = 0;
    $grandSuccessUpdate = 0;
    $grandFailedCreate = 0;
    $grandFailedUpdate = 0;
    $errors = [];
    
    foreach ($tableNames as $tableName) {
        try {
            $result = executeSync($sourceDb, $targetDb, $tableName, $options);
            
            $tableResults[$tableName] = $result['data'];
            
            $grandTotalCreate += $result['data']['create']['total'];
            $grandTotalUpdate += $result['data']['update']['total'];
            $grandSuccessCreate += $result['data']['create']['success'];
            $grandSuccessUpdate += $result['data']['update']['success'];
            $grandFailedCreate += $result['data']['create']['failed'];
            $grandFailedUpdate += $result['data']['update']['failed'];
            
        } catch (Exception $e) {
            $errors[] = [
                'table' => $tableName,
                'error' => $e->getMessage()
            ];
            $tableResults[$tableName] = [
                'error' => $e->getMessage()
            ];
        }
    }
    
    $totalOperations = $grandTotalCreate + $grandTotalUpdate;
    $totalSuccess = $grandSuccessCreate + $grandSuccessUpdate;
    $totalFailed = $grandFailedCreate + $grandFailedUpdate;
    $hasErrors = !empty($errors) || $totalFailed > 0;
    
    logAction('INFO', "Multi-table sync completed: " . count($tableNames) . " tables ({$totalSuccess}/{$totalOperations} success, {$totalFailed} failed)", [
        'log_id' => $logId,
        'tables' => $tableNames,
        'total' => $totalOperations,
        'success' => $totalSuccess,
        'failed' => $totalFailed,
        'errors' => $errors
    ]);
    
    $success = $dryRun || !$hasErrors;
    
    return [
        'success' => $success,
        'message' => $dryRun 
            ? "Dry run: {$grandTotalCreate} CREATE, {$grandTotalUpdate} UPDATE would be executed across " . count($tableNames) . " tables"
            : "Sync completed: {$totalSuccess} of {$totalOperations} operations successful across " . count($tableNames) . " tables" . 
              ($totalFailed > 0 ? ", {$totalFailed} failed" : ""),
        'data' => [
            'tables' => $tableNames,
            'source_db' => $sourceDb,
            'target_db' => $targetDb,
            'dry_run' => $dryRun,
            'table_results' => $tableResults,
            'errors' => $errors,
            'summary' => [
                'total_tables' => count($tableNames),
                'total_create' => $grandTotalCreate,
                'total_update' => $grandTotalUpdate,
                'create_success' => $grandSuccessCreate,
                'create_failed' => $grandFailedCreate,
                'update_success' => $grandSuccessUpdate,
                'update_failed' => $grandFailedUpdate,
                'total_operations' => $totalOperations,
                'total_success' => $totalSuccess,
                'total_failed' => $totalFailed
            ]
        ]
    ];
}

/**
 * Execute the sync operation for a single table
 * Uses deduplication mode: only CREATE new records, no UPDATE operations
 */
function executeSync($sourceDb, $targetDb, $tableName, $options) {
    $createMissing = $options['create_missing'];
    $updateExisting = $options['update_existing']; // Ignored in deduplication mode
    $dryRun = $options['dry_run'];
    
    $logId = 'sync_' . uniqid();
    
    logAction('INFO', "Starting deduplication sync: {$tableName} ({$sourceDb} → {$targetDb})", [
        'log_id' => $logId,
        'create_missing' => $createMissing,
        'update_existing' => $updateExisting,  // Ignored
        'dry_run' => $dryRun,
        'mode' => 'deduplication'
    ]);
    
    // Get primary keys
    $primaryKeys = getPrimaryKeyColumns($sourceDb, $tableName);
    
    if (empty($primaryKeys)) {
        throw new Exception('Table has no primary key');
    }
    
    // Get comparison data using deduplication mode (compare by non-PK columns)
    $comparison = compareRecordsByNonPK($sourceDb, $targetDb, $tableName, $primaryKeys);
    
    if (isset($comparison['error'])) {
        throw new Exception($comparison['error']);
    }
    
    // Deduplication mode: only CREATE, no UPDATE
    $recordsToCreate = $comparison['missingInB_rows'];
    $recordsToSkip = $comparison['alreadyExist_rows'] ?? [];
    
    $results = [
        'table' => $tableName,
        'source_db' => $sourceDb,
        'target_db' => $targetDb,
        'dry_run' => $dryRun,
        'sync_mode' => 'deduplication',
        'create' => [
            'total' => count($recordsToCreate),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ],
        'update' => [
            'total' => 0,  // No updates in deduplication mode
            'success' => 0,
            'failed' => 0,
            'details' => []
        ],
        'skip' => [
            'total' => count($recordsToSkip),
            'details' => []
        ]
    ];
    
    // Execute CREATE operations for new unique records
    if ($createMissing && !$dryRun) {
        foreach ($recordsToCreate as $row) {
            // Extract row data from comparison structure (has 'pk' and 'data' keys)
            $rowData = $row['data'] ?? $row;
            // Remove 'pk' key if it exists (computed column, not actual DB column)
            unset($rowData['pk'], $rowData['data_hash']);
            
            try {
                $result = insertRow($sourceDb, $targetDb, $tableName, $rowData);
                
                if ($result['success']) {
                    $results['create']['success']++;
                    $results['create']['details'][] = [
                        'status' => 'success',
                        'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys),
                        'message' => 'Created successfully'
                    ];
                } else {
                    $results['create']['failed']++;
                    $results['create']['details'][] = [
                        'status' => 'failed',
                        'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys),
                        'message' => $result['message']
                    ];
                    
                    logAction('ERROR', "CREATE failed for {$tableName}: {$result['message']}", [
                        'log_id' => $logId,
                        'table' => $tableName,
                        'operation' => 'CREATE',
                        'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys)
                    ]);
                }
            } catch (Exception $e) {
                $results['create']['failed']++;
                $results['create']['details'][] = [
                    'status' => 'failed',
                    'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys),
                    'message' => $e->getMessage()
                ];
            }
        }
    } else {
        // Dry run or skipped: just show what would happen
        $results['create']['details'] = array_map(function($row) use ($primaryKeys, $dryRun) {
            $rowData = $row['data'] ?? $row;
            unset($rowData['pk'], $rowData['data_hash']);
            return [
                'status' => $dryRun ? 'pending' : 'skipped',
                'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys),
                'message' => $dryRun ? 'Would be created' : 'Skipped (create option not enabled)'
            ];
        }, $recordsToCreate);
    }
    
    // Track skipped records (duplicates)
    $results['skip']['details'] = array_map(function($row) use ($primaryKeys) {
        $rowData = $row['data'] ?? $row;
        unset($rowData['pk'], $rowData['data_hash']);
        return [
            'pk' => $row['pk'] ?? getPkValue($rowData, $primaryKeys),
            'reason' => 'Non-PK data already exists (duplicate)'
        ];
    }, $recordsToSkip);
    
    $totalOperations = $results['create']['total'] + $results['update']['total'];
    $totalSuccess = $results['create']['success'] + $results['update']['success'];
    $totalFailed = $results['create']['failed'] + $results['update']['failed'];
    
    logAction('INFO', "Sync completed: {$tableName} ({$totalSuccess}/{$totalOperations} success, {$totalFailed} failed, {$results['skip']['total']} skipped)", [
        'log_id' => $logId,
        'table' => $tableName,
        'total' => $totalOperations,
        'success' => $totalSuccess,
        'failed' => $totalFailed,
        'skipped' => $results['skip']['total']
    ]);
    
    $success = $dryRun || $totalFailed === 0;
    
    return [
        'success' => $success,
        'message' => $dryRun 
            ? "Dry run: {$results['create']['total']} CREATE would be executed, {$results['skip']['total']} duplicates would be skipped"
            : "Sync completed: {$totalSuccess} of {$totalOperations} operations successful" . 
              ($totalFailed > 0 ? ", {$totalFailed} failed" : "") . 
              ", {$results['skip']['total']} duplicates skipped",
        'data' => $results
    ];
}

/**
 * Get primary key value as string
 */
function getPkValue($row, $primaryKeys) {
    $values = [];
    foreach ($primaryKeys as $pk) {
        $values[] = $row[$pk] ?? '';
    }
    return implode('_', $values);
}

/**
 * Update existing row in target database
 */
function updateRow($targetDbKey, $tableName, $primaryKeys, $sourceRowData) {
    try {
        $pdo = DatabaseConnection::getConnection($targetDbKey);
        
        $columns = array_keys($sourceRowData);
        $setClauses = [];
        $whereClauses = [];
        $values = [];
        
        foreach ($columns as $col) {
            // Skip primary keys in UPDATE SET
            if (!in_array($col, $primaryKeys)) {
                $setClauses[] = "`{$col}` = ?";
                $values[] = $sourceRowData[$col];
            }
        }
        
        foreach ($primaryKeys as $pk) {
            $whereClauses[] = "`{$pk}` = ?";
            $values[] = $sourceRowData[$pk];
        }
        
        if (empty($setClauses)) {
            return ['success' => false, 'message' => 'No columns to update'];
        }
        
        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        return [
            'success' => $result,
            'message' => $result ? 'Row updated successfully' : 'No rows affected'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

