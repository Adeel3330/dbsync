<?php
/**
 * Demo Database Setup Script
 * 
 * This script creates sample databases and tables for testing the DB Sync tool.
 * Run this script to set up demo databases before using the comparison tool.
 * 
 * @package DBSync
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';

/**
 * Create demo databases and tables
 */
function setupDemoDatabases() {
    echo "Setting up demo databases...\n\n";
    
    $db1 = null;
    $db2 = null;
    
    try {
        // Connect to MySQL server (without database)
        $dsn = "mysql:host=" . DB1_HOST . ";port=" . DB1_PORT . ";charset=" . DB1_CHARSET;
        $pdo = new PDO($dsn, DB1_USER, DB1_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Drop and create databases
        echo "Creating Database 1: " . DB1_NAME . "\n";
        $pdo->exec("DROP DATABASE IF EXISTS `" . DB1_NAME . "`");
        $pdo->exec("CREATE DATABASE `" . DB1_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        echo "Creating Database 2: " . DB2_NAME . "\n";
        $pdo->exec("DROP DATABASE IF EXISTS `" . DB2_NAME . "`");
        $pdo->exec("CREATE DATABASE `" . DB2_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to DB1
        echo "\nSetting up tables in Database 1...\n";
        $db1 = getDB1Connection();
        
        // Create users table
        $db1->exec("
            CREATE TABLE `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `email` VARCHAR(100) NOT NULL,
                `full_name` VARCHAR(100),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('active', 'inactive') DEFAULT 'active'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'users' table\n";
        
        // Create products table
        $db1->exec("
            CREATE TABLE `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `sku` VARCHAR(20) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `stock` INT DEFAULT 0,
                `category_id` INT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'products' table\n";
        
        // Create categories table
        $db1->exec("
            CREATE TABLE `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL,
                `description` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'categories' table\n";
        
        // Create orders table
        $db1->exec("
            CREATE TABLE `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `total` DECIMAL(10,2) NOT NULL,
                `status` VARCHAR(20) DEFAULT 'pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'orders' table\n";
        
        // Insert sample data into DB1
        echo "\nInserting sample data into Database 1...\n";
        
        // Users
        $db1->exec("INSERT INTO `users` (`username`, `email`, `full_name`, `status`) VALUES
            ('john_doe', 'john@example.com', 'John Doe', 'active'),
            ('jane_smith', 'jane@example.com', 'Jane Smith', 'active'),
            ('bob_wilson', 'bob@example.com', 'Bob Wilson', 'inactive'),
            ('alice_brown', 'alice@example.com', 'Alice Brown', 'active'),
            ('charlie_davis', 'charlie@example.com', 'Charlie Davis', 'active')
        ");
        echo "  - Added 5 users\n";
        
        // Categories
        $db1->exec("INSERT INTO `categories` (`name`, `description`) VALUES
            ('Electronics', 'Electronic devices and gadgets'),
            ('Books', 'Books and publications'),
            ('Clothing', 'Apparel and accessories'),
            ('Food', 'Food and beverages')
        ");
        echo "  - Added 4 categories\n";
        
        // Products
        $db1->exec("INSERT INTO `products` (`sku`, `name`, `price`, `stock`, `category_id`) VALUES
            ('ELEC-001', 'Laptop', 999.99, 50, 1),
            ('ELEC-002', 'Smartphone', 599.99, 100, 1),
            ('BOOK-001', 'PHP Programming', 49.99, 200, 2),
            ('BOOK-002', 'MySQL Handbook', 39.99, 150, 2),
            ('CLOTH-001', 'T-Shirt', 19.99, 500, 3),
            ('CLOTH-002', 'Jeans', 49.99, 200, 3)
        ");
        echo "  - Added 6 products\n";
        
        // Orders
        $db1->exec("INSERT INTO `orders` (`user_id`, `total`, `status`) VALUES
            (1, 1049.98, 'completed'),
            (1, 49.99, 'completed'),
            (2, 599.99, 'pending'),
            (3, 69.98, 'completed'),
            (4, 999.99, 'shipped')
        ");
        echo "  - Added 5 orders\n";
        
        // Connect to DB2 with some differences
        echo "\nSetting up tables in Database 2 (with differences)...\n";
        $db2 = getDB2Connection();
        
        // Create same tables but with differences
        $db2->exec("
            CREATE TABLE `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `email` VARCHAR(100) NOT NULL,
                `full_name` VARCHAR(100),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'users' table (with extra enum value)\n";
        
        $db2->exec("
            CREATE TABLE `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `sku` VARCHAR(20) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `stock` INT DEFAULT 0,
                `category_id` INT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "  - Created 'products' table\n";
        
        // Note: DB2 is MISSING the 'categories' table (will be detected)
        // Note: DB2 is MISSING the 'orders' table (will be detected)
        
        // Insert different data into DB2
        echo "\nInserting sample data into Database 2...\n";
        
        // Users - same users but different status for bob_wilson
        $db2->exec("INSERT INTO `users` (`username`, `email`, `full_name`, `status`) VALUES
            ('john_doe', 'john@example.com', 'John Doe', 'active'),
            ('jane_smith', 'jane@example.com', 'Jane Smith', 'active'),
            ('bob_wilson', 'bob@example.com', 'Bob Wilson', 'suspended'),  -- Different status!
            ('eve_miller', 'eve@example.com', 'Eve Miller', 'active')  -- Extra user (missing in DB1)
        ");
        echo "  - Added 4 users (1 different status, 1 extra)\n";
        
        // Products - same products but different prices
        $db2->exec("INSERT INTO `products` (`sku`, `name`, `price`, `stock`, `category_id`) VALUES
            ('ELEC-001', 'Laptop', 1099.99, 45, 1),  -- Different price!
            ('ELEC-002', 'Smartphone', 599.99, 100, 1),
            ('BOOK-001', 'PHP Programming', 49.99, 200, 2),
            ('BOOK-002', 'MySQL Handbook', 39.99, 150, 2),
            ('CLOTH-001', 'T-Shirt', 19.99, 500, 3),
            ('CLOTH-002', 'Jeans', 54.99, 180, 3),  -- Different price!
            ('FOOD-001', 'Coffee Beans', 15.99, 300, NULL)  -- Extra product (missing in DB1)
        ");
        echo "  - Added 7 products (2 different prices, 1 extra)\n";
        
        echo "\n✅ Demo databases setup complete!\n";
        echo "\nDifferences you can explore:\n";
        echo "  1. DB2 is missing 'categories' table\n";
        echo "  2. DB2 is missing 'orders' table\n";
        echo "  3. 'users' table has different enum values\n";
        echo "  4. 'users' table has different data (bob_wilson status, eve_miller)\n";
        echo "  5. 'products' table has different prices for some items\n";
        echo "  6. Different number of rows in tables\n";
        
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "Make sure your MySQL server is running and credentials are correct in config.php\n";
        exit(1);
    }
}

/**
 * Drop demo databases
 */
function dropDemoDatabases() {
    echo "Dropping demo databases...\n";
    
    try {
        $dsn = "mysql:host=" . DB1_HOST . ";port=" . DB1_PORT . ";charset=" . DB1_CHARSET;
        $pdo = new PDO($dsn, DB1_USER, DB1_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $pdo->exec("DROP DATABASE IF EXISTS `" . DB1_NAME . "`");
        echo "  - Dropped database: " . DB1_NAME . "\n";
        
        $pdo->exec("DROP DATABASE IF EXISTS `" . DB2_NAME . "`");
        echo "  - Dropped database: " . DB2_NAME . "\n";
        
        echo "\n✅ Demo databases dropped successfully!\n";
        
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Handle command line arguments
$action = $argv[1] ?? 'setup';

switch ($action) {
    case 'setup':
        setupDemoDatabases();
        break;
    case 'drop':
        dropDemoDatabases();
        break;
    case 'recreate':
        dropDemoDatabases();
        setupDemoDatabases();
        break;
    default:
        echo "Usage: php demo_setup.php [setup|drop|recreate]\n";
        echo "  setup   - Create demo databases (default)\n";
        echo "  drop    - Remove demo databases\n";
        echo "  recreate - Drop and recreate demo databases\n";
        exit(1);
}

