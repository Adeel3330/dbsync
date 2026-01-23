<?php
/**
 * Database Helper Functions
 * Core utility functions for database comparison
 */

require_once __DIR__ . '/config/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Test database connection
 */
function testDatabaseConnection($dbKey) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        
        // Test query
        $pdo->query("SELECT 1");
        
        // Get database info
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        
        return [
            'success' => true,
            'message' => 'Connection successful',
            'version' => $version
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get all tables from database
 */
function getAllTables($dbKey) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tables;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get table count
 */
function getTableCount($dbKey) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get table structure
 */
function getTableStructure($dbKey, $tableName) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("DESCRIBE `{$tableName}`");
        $columns = $stmt->fetchAll();
        
        // Get indexes
        $indexStmt = $pdo->query("SHOW INDEX FROM `{$tableName}`");
        $indexes = $indexStmt->fetchAll();
        
        return [
            'columns' => $columns,
            'indexes' => $indexes
        ];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get primary key columns
 */
function getPrimaryKeyColumns($dbKey, $tableName) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("DESCRIBE `{$tableName}`");
        $columns = $stmt->fetchAll();
        
        $pkColumns = [];
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                $pkColumns[] = $column['Field'];
            }
        }
        
        return $pkColumns;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get table row count
 */
function getTableRowCount($dbKey, $tableName) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Compare table structures
 */
function compareTableStructures($dbA, $dbB, $tablesA, $tablesB) {
    $missingInA = array_diff($tablesB, $tablesA);
    $missingInB = array_diff($tablesA, $tablesB);
    $commonTables = array_intersect($tablesA, $tablesB);
    
    $structureDifferences = [];
    
    foreach ($commonTables as $table) {
        $structA = getTableStructure($dbA, $table);
        $structB = getTableStructure($dbB, $table);
        
        if (!$structA || !$structB) {
            continue;
        }
        
        $diff = compareStructureDetails($structA, $structB, $table);
        if ($diff['hasDifference']) {
            $structureDifferences[] = $diff;
        }
    }
    
    return [
        'missingInA' => array_values($missingInA),
        'missingInB' => array_values($missingInB),
        'commonTables' => $commonTables,
        'structureDifferences' => $structureDifferences
    ];
}

/**
 * Compare structure details
 */
function compareStructureDetails($structA, $structB, $tableName) {
    $columnsA = [];
    $columnsB = [];
    
    foreach ($structA['columns'] as $col) {
        $columnsA[$col['Field']] = $col;
    }
    
    foreach ($structB['columns'] as $col) {
        $columnsB[$col['Field']] = $col;
    }
    
    $missingColumnsA = array_diff(array_keys($columnsB), array_keys($columnsA));
    $missingColumnsB = array_diff(array_keys($columnsA), array_keys($columnsB));
    
    $columnDifferences = [];
    
    foreach (array_intersect(array_keys($columnsA), array_keys($columnsB)) as $colName) {
        $colA = $columnsA[$colName];
        $colB = $columnsB[$colName];
        
        $diff = [];
        if ($colA['Type'] !== $colB['Type']) {
            $diff['type'] = ['a' => $colA['Type'], 'b' => $colB['Type']];
        }
        if ($colA['Null'] !== $colB['Null']) {
            $diff['null'] = ['a' => $colA['Null'], 'b' => $colB['Null']];
        }
        if ($colA['Default'] !== $colB['Default']) {
            $diff['default'] = ['a' => $colA['Default'], 'b' => $colB['Default']];
        }
        if ($colA['Extra'] !== $colB['Extra']) {
            $diff['extra'] = ['a' => $colA['Extra'], 'b' => $colB['Extra']];
        }
        if ($colA['Key'] !== $colB['Key']) {
            $diff['key'] = ['a' => $colA['Key'], 'b' => $colB['Key']];
        }
        
        if (!empty($diff)) {
            $columnDifferences[$colName] = $diff;
        }
    }
    
    return [
        'tableName' => $tableName,
        'hasDifference' => !empty($missingColumnsA) || !empty($missingColumnsB) || !empty($columnDifferences),
        'missingColumnsA' => array_values($missingColumnsA),
        'missingColumnsB' => array_values($missingColumnsB),
        'columnDifferences' => $columnDifferences
    ];
}

/**
 * Get table data with pagination
 */
function getTableData($dbKey, $tableName, $limit = 100, $offset = 0, $orderBy = '', $orderDir = 'ASC') {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        
        $sql = "SELECT * FROM `{$tableName}`";
        
        if (!empty($orderBy)) {
            // Sanitize column name
            $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$orderDir}";
        }
        
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get comparison data for records
 * Compares rows based on FULL ROW DATA (including primary key)
 * Returns ALL matching/different records without limiting
 * 
 * @deprecated Use compareRecordsByNonPK() for deduplication sync
 */
function compareRecords($dbKeyA, $dbKeyB, $tableName, $primaryKeys) {
    try {
        $pdoA = DatabaseConnection::getConnection($dbKeyA);
        $pdoB = DatabaseConnection::getConnection($dbKeyB);
        
        // Get total counts
        $countA = getTableRowCount($dbKeyA, $tableName);
        $countB = getTableRowCount($dbKeyB, $tableName);
        
        // Get all columns for the table
        $structA = getTableStructure($dbKeyA, $tableName);
        $columns = [];
        foreach ($structA['columns'] as $col) {
            $columns[] = $col['Field'];
        }
        
        if (empty($columns)) {
            throw new Exception('No columns found in table');
        }
        
        // Build MD5 hash of ALL column values (full row comparison)
        $hashCols = [];
        foreach ($columns as $col) {
            $hashCols[] = "IFNULL(CONVERT(`{$col}` USING utf8mb4),'')";
        }
        
        // Use primary key for row identification
        $pkSelectCols = [];
        foreach ($primaryKeys as $pk) {
            $pkSelectCols[] = "`{$pk}`";
        }
        
        // Select ALL columns (not just PK) for insert/update operations
        $allSelectCols = [];
        foreach ($columns as $col) {
            $allSelectCols[] = "`{$col}`";
        }
        
        // Fetch all rows with full row hash from DB A
        $rowListA = [];
        $sqlA = "SELECT " . implode(', ', $allSelectCols) . ", MD5(CONCAT(" . implode(', ', $hashCols) . ")) as full_hash FROM `{$tableName}`";
        $stmtA = $pdoA->query($sqlA);
        while ($row = $stmtA->fetch()) {
            $pkValues = [];
            foreach ($primaryKeys as $pk) {
                $pkValues[] = $row[$pk];
            }
            $pkKey = implode('_', $pkValues);
            $rowListA[$pkKey] = [
                'hash' => $row['full_hash'],
                'data' => $row
            ];
        }
        
        // Fetch all rows with full row hash from DB B
        $rowListB = [];
        $sqlB = "SELECT " . implode(', ', $pkSelectCols) . ", MD5(CONCAT(" . implode(', ', $hashCols) . ")) as full_hash FROM `{$tableName}`";
        $stmtB = $pdoB->query($sqlB);
        while ($row = $stmtB->fetch()) {
            $pkValues = [];
            foreach ($primaryKeys as $pk) {
                $pkValues[] = $row[$pk];
            }
            $pkKey = implode('_', $pkValues);
            $rowListB[$pkKey] = [
                'hash' => $row['full_hash'],
                'data' => $row
            ];
        }
        
        // Find differences - compare by primary key existence AND full row data
        $missingInA = [];
        $missingInB = [];
        $differentData = [];
        $matched = [];
        
        foreach ($rowListA as $pk => $rowDataA) {
            if (!isset($rowListB[$pk])) {
                // Primary key exists in A but not in B
                $missingInB[] = [
                    'pk' => $pk,
                    'data' => $rowDataA['data']
                ];
            } elseif ($rowDataA['hash'] !== $rowListB[$pk]['hash']) {
                // Same primary key but different row data
                $differentData[] = [
                    'pk' => $pk,
                    'dataA' => $rowDataA['data'],
                    'dataB' => $rowListB[$pk]['data']
                ];
            } else {
                // Exact match
                $matched[] = $pk;
            }
        }
        
        foreach ($rowListB as $pk => $rowDataB) {
            if (!isset($rowListA[$pk])) {
                // Primary key exists in B but not in A
                $missingInA[] = [
                    'pk' => $pk,
                    'data' => $rowDataB['data']
                ];
            }
        }
        
        return [
            'totalA' => $countA,
            'totalB' => $countB,
            'missingInA' => count($missingInA),
            'missingInB' => count($missingInB),
            'differentData' => count($differentData),
            'matched' => count($matched),
            'missingInA_rows' => $missingInA,
            'missingInB_rows' => $missingInB,
            'differentData_rows' => $differentData,
            'primaryKeys' => $primaryKeys,
            'columns' => $columns
        ];
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'totalA' => 0,
            'totalB' => 0,
            'missingInA' => 0,
            'missingInB' => 0,
            'differentData' => 0,
            'matched' => 0
        ];
    }
}

/**
 * Get missing rows from a database
 */
function getMissingRows($dbKey, $tableName, $primaryKeys, $pkValues) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        
        $whereConditions = [];
        foreach ($primaryKeys as $idx => $pk) {
            $pkValue = $pkValues[$idx];
            $whereConditions[] = "`{$pk}` = " . $pdo->quote($pkValue);
        }
        
        $sql = "SELECT * FROM `{$tableName}` WHERE " . implode(' AND ', $whereConditions);
        $stmt = $pdo->query($sql);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Insert row from one DB to another
 * Automatically excludes auto-increment columns to allow MySQL to generate new values
 */
function insertRow($sourceDbKey, $targetDbKey, $tableName, $rowData) {
    try {
        $pdoTarget = DatabaseConnection::getConnection($targetDbKey);
        
        // Get table structure to identify auto-increment columns
        $autoIncrementColumns = getAutoIncrementColumns($targetDbKey, $tableName);
        
        // Filter out auto-increment columns from the insert
        $columns = [];
        $values = [];
        
        foreach ($rowData as $col => $val) {
            // Skip auto-increment columns (MySQL will auto-generate these)
            if (in_array($col, $autoIncrementColumns)) {
                continue;
            }
            $columns[] = $col;
            $values[] = $val;
        }
        
        if (empty($columns)) {
            return [
                'success' => false,
                'message' => 'No columns to insert (all columns are auto-increment)'
            ];
        }
        
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdoTarget->prepare($sql);
        $result = $stmt->execute($values);
        
        // Get the auto-generated ID if available
        $insertId = $pdoTarget->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Row inserted successfully',
            'insert_id' => $insertId
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get auto-increment columns for a table
 */
function getAutoIncrementColumns($dbKey, $tableName) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("DESCRIBE `{$tableName}`");
        $columns = $stmt->fetchAll();
        
        $autoIncColumns = [];
        foreach ($columns as $column) {
            if ($column['Extra'] === 'auto_increment') {
                $autoIncColumns[] = $column['Field'];
            }
        }
        
        return $autoIncColumns;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Log action
 */
function logAction($type, $message, $details = []) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $detailsStr = !empty($details) ? ' | ' . json_encode($details) : '';
    
    $logEntry = "[{$timestamp}] [{$type}] {$message}{$detailsStr}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Get logs
 */
function getLogs($limit = 100) {
    $logFile = __DIR__ . '/logs/app.log';
    
    if (!file_exists($logFile)) {
        return [];
    }
    
    $logs = [];
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $lines = array_slice($lines, -$limit);
    
    foreach (array_reverse($lines) as $line) {
        if (preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
            $logs[] = [
                'timestamp' => $matches[1],
                'type' => $matches[2],
                'message' => $matches[3]
            ];
        }
    }
    
    return $logs;
}

/**
 * Get table summary
 */
function getTableSummary($dbKey, $tableName) {
    try {
        $pdo = DatabaseConnection::getConnection($dbKey);
        
        $stmt = $pdo->query("DESCRIBE `{$tableName}`");
        $columns = $stmt->fetchAll();
        
        $rowCount = getTableRowCount($dbKey, $tableName);
        
        return [
            'name' => $tableName,
            'columns' => count($columns),
            'rows' => $rowCount,
            'size' => getTableSize($dbKey, $tableName)
        ];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get table size
 */
function getTableSize($dbKey, $tableName) {
    try {
        $config = DatabaseConfig::load();
        $dbName = $config[$dbKey]['name'];
        
        $pdo = DatabaseConnection::getConnection($dbKey);
        $stmt = $pdo->query("
            SELECT (data_length + index_length) as size 
            FROM information_schema.tables 
            WHERE table_schema = '{$dbName}' AND table_name = '{$tableName}'
        ");
        $result = $stmt->fetch();
        
        if ($result) {
            $size = $result['size'];
            if ($size > 1048576) {
                return round($size / 1048576, 2) . ' MB';
            } elseif ($size > 1024) {
                return round($size / 1024, 2) . ' KB';
            }
            return $size . ' B';
        }
        return 'Unknown';
    } catch (Exception $e) {
        return 'Unknown';
    }
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate database connection
 */
function validateConnection($host, $port, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return ['valid' => true];
    } catch (PDOException $e) {
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get comparison data for records by NON-PRIMARY KEY columns
 * Compares rows based on DATA ONLY (excluding primary key)
 * Used for deduplication sync - skips records where non-PK data already exists
 *
 * @param string $dbKeyA Source database key
 * @param string $dbKeyB Target database key
 * @param string $tableName Table name to compare
 * @param array $primaryKeys Primary key column names
 * @return array Comparison results with categorized records
 */
function compareRecordsByNonPK($dbKeyA, $dbKeyB, $tableName, $primaryKeys) {
    try {
        $pdoA = DatabaseConnection::getConnection($dbKeyA);
        $pdoB = DatabaseConnection::getConnection($dbKeyB);
        
        // Get total counts
        $countA = getTableRowCount($dbKeyA, $tableName);
        $countB = getTableRowCount($dbKeyB, $tableName);
        
        // Get all columns for the table
        $structA = getTableStructure($dbKeyA, $tableName);
        $columns = [];
        foreach ($structA['columns'] as $col) {
            $columns[] = $col['Field'];
        }
        
        if (empty($columns)) {
            throw new Exception('No columns found in table');
        }
        
        // Separate PK columns from non-PK columns
        $pkColumns = array_flip($primaryKeys);
        $nonPkColumns = array_diff($columns, $primaryKeys);
        
        if (empty($nonPkColumns)) {
            // If no non-PK columns, fall back to comparing by PK only
            return compareRecords($dbKeyA, $dbKeyB, $tableName, $primaryKeys);
        }
        
        // Build MD5 hash of NON-PRIMARY KEY column values only (data deduplication)
        $hashCols = [];
        foreach ($nonPkColumns as $col) {
            $hashCols[] = "IFNULL(CONVERT(`{$col}` USING utf8mb4),'')";
        }
        
        // Select ALL columns for insert operations (including PK for identification)
        $allSelectCols = [];
        foreach ($columns as $col) {
            $allSelectCols[] = "`{$col}`";
        }
        
        // Fetch all rows with non-PK data hash from DB A (source)
        $rowListA = [];
        $sqlA = "SELECT " . implode(', ', $allSelectCols) . ", MD5(CONCAT(" . implode(', ', $hashCols) . ")) as data_hash FROM `{$tableName}`";
        $stmtA = $pdoA->query($sqlA);
        while ($row = $stmtA->fetch()) {
            $pkValues = [];
            foreach ($primaryKeys as $pk) {
                $pkValues[] = $row[$pk];
            }
            $pkKey = implode('_', $pkValues);
            $rowListA[$pkKey] = [
                'data_hash' => $row['data_hash'],
                'data' => $row
            ];
        }
        
        // Fetch all rows with non-PK data hash from DB B (target)
        $rowListB = [];
        $sqlB = "SELECT " . implode(', ', $allSelectCols) . ", MD5(CONCAT(" . implode(', ', $hashCols) . ")) as data_hash FROM `{$tableName}`";
        $stmtB = $pdoB->query($sqlB);
        while ($row = $stmtB->fetch()) {
            $pkValues = [];
            foreach ($primaryKeys as $pk) {
                $pkValues[] = $row[$pk];
            }
            $pkKey = implode('_', $pkValues);
            $rowListB[$pkKey] = [
                'data_hash' => $row['data_hash'],
                'data' => $row
            ];
        }
        
        // Build a lookup by data hash (excluding PK) for deduplication
        $targetHashes = [];
        foreach ($rowListB as $pk => $rowDataB) {
            $targetHashes[$rowDataB['data_hash']] = true;
        }
        
        // Categorize rows from source (DB A)
        $missingInB = [];      // Records in A but not in B (by non-PK data) - need CREATE
        $alreadyExist = [];    // Records in A with same non-PK data in B - skip (duplicate)
        
        foreach ($rowListA as $pk => $rowDataA) {
            if (!isset($targetHashes[$rowDataA['data_hash']])) {
                // Non-PK data doesn't exist in target - need to CREATE
                $missingInB[] = [
                    'pk' => $pk,
                    'data' => $rowDataA['data']
                ];
            } else {
                // Non-PK data already exists in target - skip (duplicate)
                $alreadyExist[] = [
                    'pk' => $pk,
                    'data' => $rowDataA['data']
                ];
            }
        }
        
        return [
            'totalA' => $countA,
            'totalB' => $countB,
            'missingInA' => 0,  // Not relevant for this sync mode
            'missingInB' => count($missingInB),  // Records to CREATE
            'differentData' => 0,  // No UPDATE operations in this mode
            'matched' => count($alreadyExist),
            'missingInB_rows' => $missingInB,  // Rows to insert
            'alreadyExist_rows' => $alreadyExist,  // Rows that will be skipped
            'primaryKeys' => $primaryKeys,
            'columns' => $columns,
            'nonPkColumns' => array_values($nonPkColumns),
            'sync_mode' => 'deduplication'  // Indicates this is deduplication mode
        ];
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'totalA' => 0,
            'totalB' => 0,
            'missingInA' => 0,
            'missingInB' => 0,
            'differentData' => 0,
            'matched' => 0
        ];
    }
}

