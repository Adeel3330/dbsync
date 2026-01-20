<?php
/**
 * API: Save Configuration
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['db_a']) || !isset($input['db_b'])) {
        throw new Exception('Invalid configuration data');
    }
    
    // Validate and sanitize
    $config = [
        'db_a' => [
            'host' => sanitize($input['db_a']['host'] ?? 'localhost'),
            'name' => sanitize($input['db_a']['name'] ?? ''),
            'username' => sanitize($input['db_a']['username'] ?? 'root'),
            'password' => $input['db_a']['password'] ?? '',
            'port' => (int)($input['db_a']['port'] ?? 3306),
            'charset' => 'utf8mb4'
        ],
        'db_b' => [
            'host' => sanitize($input['db_b']['host'] ?? 'localhost'),
            'name' => sanitize($input['db_b']['name'] ?? ''),
            'username' => sanitize($input['db_b']['username'] ?? 'root'),
            'password' => $input['db_b']['password'] ?? '',
            'port' => (int)($input['db_b']['port'] ?? 3306),
            'charset' => 'utf8mb4'
        ]
    ];
    
    // Validate required fields
    if (empty($config['db_a']['name'])) {
        throw new Exception('Database A name is required');
    }
    if (empty($config['db_b']['name'])) {
        throw new Exception('Database B name is required');
    }
    
    // Test both connections before saving
    logAction('INFO', 'Testing database connections before save');
    
    $testA = testDatabaseConnection('db_a');
    if (!$testA['success']) {
        throw new Exception('Database A connection failed: ' . $testA['message']);
    }
    
    $testB = testDatabaseConnection('db_b');
    if (!$testB['success']) {
        throw new Exception('Database B connection failed: ' . $testB['message']);
    }
    
    // Save configuration
    if (DatabaseConfig::save($config)) {
        logAction('SUCCESS', 'Configuration saved successfully');
        $response['success'] = true;
        $response['message'] = 'Configuration saved and connections verified';
    } else {
        throw new Exception('Failed to save configuration file');
    }
    
} catch (Exception $e) {
    logAction('ERROR', $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

