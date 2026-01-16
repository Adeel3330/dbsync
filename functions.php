<?php
/**
 * Database Comparison Functions
 * 
 * This file contains all the core functions for comparing databases,
 * tables, columns, and records. It provides modular functions that
 * can be used independently or through the web interface.
 * 
 * @package DBSync
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';

/**
 * Get all tables from a database
 * 
 * @param PDO $pdo PDO connection object
 * @return array List of table names
 */
function getTables(PDO $pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

/**
 * Get all columns for a specific table
 * 
 * @param PDO $pdo PDO connection object
 * @param string $tableName Table name
 * @return array Array of column definitions with name, type, nullability, etc.
 */
function getTableColumns(PDO $pdo, $tableName) {
    $stmt = $pdo->query("DESCRIBE `{$tableName}`");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = [
            'name' => $row['Field'],
            'type' => $row['Type'],
            'null' => $row['Null'],
            'key' => $row['Key'],
            'default' => $row['Default'],
            'extra' => $row['Extra']
        ];
    }
    return $columns;
}

/**
 * Get primary key columns for a table
 * 
 * @param PDO $pdo PDO connection object
 * @param string $tableName Table name
 * @return array List of primary key column names
 */
function getPrimaryKeyColumns(PDO $pdo, $tableName) {
    $stmt = $pdo->query("DESCRIBE `{$tableName}`");
    $pkColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Key'] === 'PRI') {
            $pkColumns[] = $row['Field'];
        }
    }
    return $pkColumns;
}

/**
 * Get all data from a table with optional pagination
 * 
 * @param PDO $pdo PDO connection object
 * @param string $tableName Table name
 * @param int $limit Maximum number of rows
 * @param int $offset Offset for pagination
 * @return array Array of rows
 */
function getTableData(PDO $pdo, $tableName, $limit = 0, $offset = 0) {
    $sql = "SELECT * FROM `{$tableName}`";
    if ($limit > 0) {
        $sql .= " LIMIT " . intval($limit);
        if ($offset > 0) {
            $sql .= " OFFSET " . intval($offset);
        }
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Count total rows in a table
 * 
 * @param PDO $pdo PDO connection object
 * @param string $tableName Table name
 * @return int Total row count
 */
function countTableRows(PDO $pdo, $tableName) {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `{$tableName}`");
    $result = $stmt->fetch();
    return (int)$result['cnt'];
}

/**
 * Compare tables between two databases
 * 
 * @param PDO $db1 Connection to database 1
 * @param PDO $db2 Connection to database 2
 * @return array Comparison results including missing tables
 */
function compareTables(PDO $db1, PDO $db2) {
    $tables1 = getTables($db1);
    $tables2 = getTables($db2);
    
    $result = [
        'tables_in_db1' => $tables1,
        'tables_in_db2' => $tables2,
        'missing_in_db2' => array_diff($tables1, $tables2),
        'missing_in_db1' => array_diff($tables2, $tables1),
        'common_tables' => array_intersect($tables1, $tables2)
    ];
    
    return $result;
}

/**
 * Compare columns between two tables
 * 
 * @param PDO $db1 Connection to database 1
 * @param PDO $db2 Connection to database 2
 * @param string $tableName Table name to compare
 * @return array Column comparison results
 */
function compareTableColumns(PDO $db1, PDO $db2, $tableName) {
    $columns1 = getTableColumns($db1, $tableName);
    $columns2 = getTableColumns($db2, $tableName);
    
    $result = [
        'table_name' => $tableName,
        'columns_db1' => $columns1,
        'columns_db2' => $columns2,
        'missing_in_db2' => [],
        'missing_in_db1' => [],
        'type_mismatches' => [],
        'null_mismatches' => []
    ];
    
    // Find missing columns
    foreach ($columns1 as $name => $col) {
        if (!isset($columns2[$name])) {
            $result['missing_in_db2'][$name] = $col;
        }
    }
    
    foreach ($columns2 as $name => $col) {
        if (!isset($columns1[$name])) {
            $result['missing_in_db1'][$name] = $col;
        }
    }
    
    // Check for type and nullability mismatches in common columns
    foreach ($columns1 as $name => $col1) {
        if (isset($columns2[$name])) {
            $col2 = $columns2[$name];
            
            // Compare types (normalize for comparison)
            $type1 = strtolower(preg_replace('/\(.*\)/', '', $col1['type']));
            $type2 = strtolower(preg_replace('/\(.*\)/', '', $col2['type']));
            
            if ($type1 !== $type2) {
                $result['type_mismatches'][$name] = [
                    'db1' => $col1['type'],
                    'db2' => $col2['type']
                ];
            }
            
            // Compare nullability
            if ($col1['null'] !== $col2['null']) {
                $result['null_mismatches'][$name] = [
                    'db1' => $col1['null'],
                    'db2' => $col2['null']
                ];
            }
        }
    }
    
    return $result;
}

/**
 * Compare records between two tables
 * 
 * @param PDO $db1 Connection to database 1
 * @param PDO $db2 Connection to database 2
 * @param string $tableName Table name to compare
 * @param int $limit Maximum rows to compare (0 for all)
 * @param int $offset Offset for pagination
 * @return array Record comparison results
 */
function compareTableRecords(PDO $db1, PDO $db2, $tableName, $limit = 0, $offset = 0) {
    $pkColumns = getPrimaryKeyColumns($db1, $tableName);
    
    // If no primary key, use all columns
    if (empty($pkColumns)) {
        $pkColumns = array_keys(getTableColumns($db1, $tableName));
    }
    
    $data1 = getTableData($db1, $tableName, $limit, $offset);
    $data2 = getTableData($db2, $tableName, $limit, $offset);
    
    $result = [
        'table_name' => $tableName,
        'primary_key' => $pkColumns,
        'db1_count' => count($data1),
        'db2_count' => count($data2),
        'missing_in_db2' => [],
        'missing_in_db1' => [],
        'different_rows' => [],
        'identical_count' => 0
    ];
    
    // Index data2 by primary key for fast lookup
    $indexedData2 = [];
    foreach ($data2 as $row) {
        $pkValues = [];
        foreach ($pkColumns as $pk) {
            $pkValues[] = $row[$pk] ?? null;
        }
        $indexKey = implode('|||', $pkValues);
        $indexedData2[$indexKey] = $row;
    }
    
    // Compare data1 against data2
    foreach ($data1 as $row1) {
        $pkValues = [];
        foreach ($pkColumns as $pk) {
            $pkValues[] = $row1[$pk] ?? null;
        }
        $indexKey = implode('|||', $pkValues);
        
        if (!isset($indexedData2[$indexKey])) {
            // Row exists in DB1 but not in DB2
            $result['missing_in_db2'][$indexKey] = $row1;
        } else {
            // Compare the rows
            $row2 = $indexedData2[$indexKey];
            $diff = [];
            foreach ($row1 as $col => $val1) {
                $val2 = $row2[$col] ?? null;
                if ($val1 !== $val2) {
                    $diff[$col] = [
                        'db1' => $val1,
                        'db2' => $val2
                    ];
                }
            }
            if (!empty($diff)) {
                $result['different_rows'][$indexKey] = [
                    'primary_key' => array_combine($pkColumns, $pkValues),
                    'differences' => $diff,
                    'db1_row' => $row1,
                    'db2_row' => $row2
                ];
            } else {
                $result['identical_count']++;
            }
            unset($indexedData2[$indexKey]);
        }
    }
    
    // Rows only in DB2 (missing in DB1)
    foreach ($indexedData2 as $indexKey => $row2) {
        $pkParts = explode('|||', $indexKey);
        $result['missing_in_db1'][$indexKey] = $row2;
    }
    
    return $result;
}

/**
 * Get complete comparison report for all tables
 * 
 * @param PDO $db1 Connection to database 1
 * @param PDO $db2 Connection to database 2
 * @param int $recordLimit Maximum records per table to compare (0 for all)
 * @return array Complete comparison report
 */
function getFullComparisonReport(PDO $db1, PDO $db2, $recordLimit = 0) {
    $tableComparison = compareTables($db1, $db2);
    
    $report = [
        'generated_at' => date('Y-m-d H:i:s'),
        'tables' => [
            'in_db1' => $tableComparison['tables_in_db1'],
            'in_db2' => $tableComparison['tables_in_db2'],
            'missing_in_db2' => $tableComparison['missing_in_db2'],
            'missing_in_db1' => $tableComparison['missing_in_db1'],
            'common' => $tableComparison['common_tables']
        ],
        'table_details' => [],
        'summary' => [
            'total_tables_db1' => count($tableComparison['tables_in_db1']),
            'total_tables_db2' => count($tableComparison['tables_in_db2']),
            'missing_tables_db2' => count($tableComparison['missing_in_db2']),
            'missing_tables_db1' => count($tableComparison['missing_in_db1']),
            'total_missing_rows' => 0,
            'total_different_rows' => 0,
            'total_identical_rows' => 0
        ]
    ];
    
    // Compare each common table
    foreach ($tableComparison['common_tables'] as $table) {
        $columnCompare = compareTableColumns($db1, $db2, $table);
        $recordCompare = compareTableRecords($db1, $db2, $table, $recordLimit);
        
        $report['table_details'][$table] = [
            'columns' => $columnCompare,
            'records' => $recordCompare
        ];
        
        // Update summary
        $report['summary']['total_missing_rows'] += count($recordCompare['missing_in_db2']);
        $report['summary']['total_different_rows'] += count($recordCompare['different_rows']);
        $report['summary']['total_identical_rows'] += $recordCompare['identical_count'];
    }
    
    return $report;
}

/**
 * Insert a single row from DB1 to DB2
 * 
 * @param PDO $db1 Source database connection
 * @param PDO $db2 Target database connection
 * @param string $tableName Table name
 * @param array $rowData Row data to insert
 * @return array Result with success status and message
 */
function insertRow(PDO $db1, PDO $db2, $tableName, $rowData) {
    try {
        $columns = array_keys($rowData);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db2->prepare($sql);
        $stmt->execute(array_values($rowData));
        
        return [
            'success' => true,
            'message' => 'Row inserted successfully',
            'inserted_id' => $db2->lastInsertId(),
            'row_data' => $rowData
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_info' => $e->errorInfo ?? [],
            'row_data' => $rowData
        ];
    }
}

/**
 * Insert multiple rows from DB1 to DB2 with error handling
 * 
 * @param PDO $db1 Source database connection
 * @param PDO $db2 Target database connection
 * @param string $tableName Table name
 * @param array $rows Array of rows to insert
 * @return array Results for all insertions
 */
function insertMissingRows(PDO $db1, PDO $db2, $tableName, $rows) {
    $results = [
        'table' => $tableName,
        'total' => count($rows),
        'success' => 0,
        'failed' => 0,
        'errors' => [],
        'inserted_ids' => []
    ];
    
    foreach ($rows as $index => $row) {
        $result = insertRow($db1, $db2, $tableName, $row);
        if ($result['success']) {
            $results['success']++;
            $results['inserted_ids'][] = $result['inserted_id'];
        } else {
            $results['failed']++;
            $results['errors'][] = [
                'row_index' => $index,
                'message' => $result['message'],
                'error_code' => $result['error_code'],
                'row_data' => $result['row_data']
            ];
        }
    }
    
    $results['success_rate'] = $results['total'] > 0 
        ? round(($results['success'] / $results['total']) * 100, 2) 
        : 0;
    
    return $results;
}

/**
 * Detect and categorize common database errors
 * 
 * @param string $errorMessage Error message from PDO exception
 * @return array Error category and suggestions
 */
function categorizeError($errorMessage) {
    $errorMessage = strtolower($errorMessage);
    
    // Primary key duplicate
    if (strpos($errorMessage, 'duplicate entry') !== false && 
        strpos($errorMessage, 'primary') !== false) {
        return [
            'category' => 'duplicate_primary_key',
            'title' => 'Duplicate Primary Key',
            'description' => 'A row with this primary key already exists in the target database.',
            'severity' => 'high',
            'suggestion' => 'Update the existing row instead of inserting, or check if you need to sync in reverse direction.'
        ];
    }
    
    // Foreign key constraint
    if (strpos($errorMessage, 'foreign key') !== false || 
        strpos($errorMessage, 'constraint') !== false) {
        return [
            'category' => 'foreign_key_constraint',
            'title' => 'Foreign Key Constraint Violation',
            'description' => 'Referenced record does not exist in the parent table.',
            'severity' => 'high',
            'suggestion' => 'Insert the parent record first, or disable foreign key checks temporarily (not recommended for production).'
        ];
    }
    
    // Data type mismatch
    if (strpos($errorMessage, 'data too long') !== false ||
        strpos($errorMessage, 'incorrect integer value') !== false ||
        strpos($errorMessage, 'truncated') !== false) {
        return [
            'category' => 'data_type_mismatch',
            'title' => 'Data Type Mismatch',
            'description' => 'Data cannot be converted to the target column type.',
            'severity' => 'medium',
            'suggestion' => 'Check column types in both databases and ensure data compatibility.'
        ];
    }
    
    // Null constraint
    if (strpos($errorMessage, 'null') !== false && 
        strpos($errorMessage, 'cannot be null') !== false) {
        return [
            'category' => 'null_constraint',
            'title' => 'NULL Constraint Violation',
            'description' => 'Attempting to insert NULL into a NOT NULL column.',
            'severity' => 'medium',
            'suggestion' => 'Provide a default value or update the column to allow NULLs.'
        ];
    }
    
    // Unknown error
    return [
        'category' => 'unknown',
        'title' => 'Unknown Error',
        'description' => 'An unexpected error occurred during insertion.',
        'severity' => 'medium',
        'suggestion' => 'Review the error message and check database compatibility.'
    ];
}

/**
 * Export comparison report to JSON format
 * 
 * @param array $report Comparison report array
 * @param bool $pretty Print with formatted JSON
 * @return string JSON string
 */
function exportToJSON($report, $pretty = true) {
    return $pretty 
        ? json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        : json_encode($report, JSON_UNESCAPED_UNICODE);
}

/**
 * Export comparison report to HTML format
 * 
 * @param array $report Comparison report array
 * @return string HTML string
 */
function exportToHTML($report) {
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Comparison Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .summary { background: #f5f5f5; padding: 15px; margin-bottom: 20px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .danger { color: #dc3545; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Database Comparison Report</h1>
    <p>Generated: <?php echo $report['generated_at']; ?></p>
    
    <div class="summary">
        <h2>Summary</h2>
        <p>Tables in DB1: <?php echo $report['summary']['total_tables_db1']; ?></p>
        <p>Tables in DB2: <?php echo $report['summary']['total_tables_db2']; ?></p>
        <p class="danger">Missing Tables in DB2: <?php echo $report['summary']['missing_tables_db2']; ?></p>
        <p class="danger">Total Missing Rows: <?php echo $report['summary']['total_missing_rows']; ?></p>
        <p class="warning">Total Different Rows: <?php echo $report['summary']['total_different_rows']; ?></p>
        <p class="success">Total Identical Rows: <?php echo $report['summary']['total_identical_rows']; ?></p>
    </div>
</body>
</html>
    <?php
    return ob_get_clean();
}

/**
 * Log sync action to file or database
 * 
 * @param string $action Action type (insert, update, delete, etc.)
 * @param string $table Table name
 * @param mixed $details Additional details
 * @param bool $success Whether action was successful
 * @return bool
 */
function logSyncAction($action, $table, $details, $success = true) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'table' => $table,
        'success' => $success,
        'details' => $details
    ];
    
    $logFile = __DIR__ . '/logs/sync_actions.log';
    
    // Create logs directory if it doesn't exist
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    return file_put_contents(
        $logFile, 
        json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", 
        FILE_APPEND | LOCK_EX
    ) !== false;
}

/**
 * Sanitize table/column names for SQL queries
 * 
 * @param string $name Table or column name
 * @return string Sanitized name
 */
function sanitizeName($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

/**
 * Get formatted column type for display
 * 
 * @param string $type Raw type string from DESCRIBE
 * @return string Formatted type
 */
function formatColumnType($type) {
    return htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
}
