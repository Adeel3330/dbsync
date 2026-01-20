<?php
/**
 * Database Configuration
 * Stores database connection settings
 */

class DatabaseConfig {
    private static $configFile = __DIR__ . '/config.json';
    private static $defaultConfig = [
        'db_a' => [
            'host' => 'localhost',
            'name' => 'cfms_circuit_courts',
            'username' => 'root',
            'password' => 'root',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ],
        'db_b' => [
            'host' => 'localhost',
            'name' => 'cfms_circuit_mirpur_new',
            'username' => 'root',
            'password' => 'root',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ],
        'settings' => [
            'pagination_limit' => 100,
            'batch_size' => 1000,
            'max_execution_time' => 300,
            'memory_limit' => '512M'
        ]
    ];

    /**
     * Load configuration from file
     */
    public static function load() {
        if (file_exists(self::$configFile)) {
            $content = file_get_contents(self::$configFile);
            $config = json_decode($content, true);
            if ($config) {
                return array_merge(self::$defaultConfig, $config);
            }
        }
        return self::$defaultConfig;
    }

    /**
     * Save configuration to file
     */
    public static function save($config) {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return file_put_contents(self::$configFile, $json) !== false;
    }

    /**
     * Get database connection array for PDO
     */
    public static function getPDOConfig($dbKey = 'db_a') {
        $config = self::load();
        $db = $config[$dbKey];
        
        return [
            'host' => $db['host'],
            'dbname' => $db['name'],
            'username' => $db['username'],
            'password' => $db['password'],
            'port' => $db['port'],
            'charset' => $db['charset'] ?? 'utf8mb4'
        ];
    }

    /**
     * Get DSN string for PDO
     */
    public static function getDSN($dbKey = 'db_a') {
        $config = self::load();
        $db = $config[$dbKey];
        
        return "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    }
}

/**
 * Database Connection Manager
 */
class DatabaseConnection {
    private static $connections = [];

    /**
     * Get PDO connection
     */
    public static function getConnection($dbKey = 'db_a') {
        if (isset(self::$connections[$dbKey]) && self::$connections[$dbKey] !== null) {
            return self::$connections[$dbKey];
        }

        try {
            $config = DatabaseConfig::getPDOConfig($dbKey);
            $dsn = DatabaseConfig::getDSN($dbKey);
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            self::$connections[$dbKey] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Connection failed for {$dbKey}: " . $e->getMessage());
        }
    }

    /**
     * Close connection
     */
    public static function close($dbKey = null) {
        if ($dbKey === null) {
            self::$connections = [];
        } elseif (isset(self::$connections[$dbKey])) {
            self::$connections[$dbKey] = null;
        }
    }

    /**
     * Close all connections
     */
    public static function closeAll() {
        self::$connections = [];
    }
}

