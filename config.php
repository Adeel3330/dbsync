<?php
/**
 * Database Configuration
 * 
 * This file handles database connection configuration for the DB Sync tool.
 * Update the values below to match your database credentials.
 * 
 * @package DBSync
 * @version 1.0.0
 */

// Database 1 Configuration (Source)
define('DB1_HOST', 'localhost');
define('DB1_PORT', '3306');
define('DB1_NAME', 'cfms_circuit_mirpur_new');
define('DB1_USER', 'root');
define('DB1_PASS', 'root');
define('DB1_CHARSET', 'utf8mb4');

// Database 2 Configuration (Target)
define('DB2_HOST', 'localhost');
define('DB2_PORT', '3306');
define('DB2_NAME', 'cfms_circuit_courts');
define('DB2_USER', 'root');
define('DB2_PASS', 'root');
define('DB2_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'DB Sync - Database Comparison Tool');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', true); // Set to false in production
define('MAX_ROWS_PER_PAGE', 100); // Pagination limit

/**
 * Get PDO connection for Database 1
 * 
 * @return PDO|null Returns PDO connection or null on failure
 */
function getDB1Connection() {
    try {
        $dsn = "mysql:host=" . DB1_HOST . ";port=" . DB1_PORT . ";dbname=" . DB1_NAME . ";charset=" . DB1_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];
        return new PDO($dsn, DB1_USER, DB1_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("DB1 Connection Error: " . $e->getMessage());
        }
        return null;
    }
}

/**
 * Get PDO connection for Database 2
 * 
 * @return PDO|null Returns PDO connection or null on failure
 */
function getDB2Connection() {
    try {
        $dsn = "mysql:host=" . DB2_HOST . ";port=" . DB2_PORT . ";dbname=" . DB2_NAME . ";charset=" . DB2_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];
        return new PDO($dsn, DB2_USER, DB2_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("DB2 Connection Error: " . $e->getMessage());
        }
        return null;
    }
}

/**
 * Test database connections
 * 
 * @return array Returns status of both connections
 */
function testConnections() {
    $result = [
        'db1' => ['status' => false, 'message' => ''],
        'db2' => ['status' => false, 'message' => '']
    ];
    
    try {
        $db1 = getDB1Connection();
        if ($db1) {
            $result['db1']['status'] = true;
            $result['db1']['message'] = 'Connected successfully';
        } else {
            $result['db1']['message'] = 'Connection failed';
        }
    } catch (Exception $e) {
        $result['db1']['message'] = $e->getMessage();
    }
    
    try {
        $db2 = getDB2Connection();
        if ($db2) {
            $result['db2']['status'] = true;
            $result['db2']['message'] = 'Connected successfully';
        } else {
            $result['db2']['message'] = 'Connection failed';
        }
    } catch (Exception $e) {
        $result['db2']['message'] = $e->getMessage();
    }
    
    return $result;
}

