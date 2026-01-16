#!/usr/bin/env php
<?php
/**
 * DB Sync CLI Tool
 * 
 * Command-line interface for database comparison and synchronization.
 * 
 * @package DBSync
 * @version 1.0.0
 */

require_once __DIR__ . '/functions.php';

// Parse command line arguments
$options = getopt('', [
    'compare::',
    'insert::',
    'tables::',
    'report::',
    'export::',
    'help',
    'limit:'
]);

// Display help
if (isset($options['help']) || empty($options)) {
    echo <<<HELP
🗄️ DB Sync - Database Comparison CLI Tool
==========================================

Usage: php sync.php [options]

Options:
  --compare[=table]    Compare databases (all tables or specific table)
  --tables             List all tables in both databases
  --insert=table       Insert missing rows for specified table
  --report             Generate full comparison report
  --export=filename    Export report to JSON file
  --limit=N            Limit records per table (default: 1000)
  --help               Show this help message

Examples:
  php sync.php --tables
  php sync.php --compare
  php sync.php --compare=users
  php sync.php --report
  php sync.php --insert=users --limit=100
  php sync.php --export=report.json

HELP;
    exit(0);
}

// Check database connections
$db1 = getDB1Connection();
$db2 = getDB2Connection();

if (!$db1 || !$db2) {
    echo "❌ Error: Database connection failed\n";
    exit(1);
}

echo "✅ Connected to both databases\n\n";

$recordLimit = intval($options['limit'] ?? 1000);

// Handle different commands
if (isset($options['tables'])) {
    // List tables
    echo "📋 Tables in Database 1:\n";
    $tables1 = getTables($db1);
    foreach ($tables1 as $table) {
        $count = countTableRows($db1, $table);
        echo "  - {$table} ({$count} rows)\n";
    }
    
    echo "\n📋 Tables in Database 2:\n";
    $tables2 = getTables($db2);
    foreach ($tables2 as $table) {
        $count = countTableRows($db2, $table);
        echo "  - {$table} ({$count} rows)\n";
    }
    
    $missingIn2 = array_diff($tables1, $tables2);
    $missingIn1 = array_diff($tables2, $tables1);
    
    if (!empty($missingIn2)) {
        echo "\n⚠️  Tables missing in Database 2:\n";
        foreach ($missingIn2 as $table) {
            echo "  - {$table}\n";
        }
    }
}

elseif (isset($options['compare'])) {
    // Compare databases
    $tableName = $options['compare'] ?: null;
    
    if ($tableName) {
        // Compare specific table
        echo "🔍 Comparing table: {$tableName}\n\n";
        
        // Columns
        $colCompare = compareTableColumns($db1, $db2, $tableName);
        echo "📐 Column Comparison:\n";
        echo "  Columns in DB1: " . count($colCompare['columns_db1']) . "\n";
        echo "  Columns in DB2: " . count($colCompare['columns_db2']) . "\n";
        
        if (!empty($colCompare['missing_in_db2'])) {
            echo "  Missing in DB2: " . count($colCompare['missing_in_db2']) . "\n";
        }
        
        // Records
        $recCompare = compareTableRecords($db1, $db2, $tableName, $recordLimit);
        echo "\n📝 Record Comparison:\n";
        echo "  Rows in DB1: " . $recCompare['db1_count'] . "\n";
        echo "  Rows in DB2: " . $recCompare['db2_count'] . "\n";
        echo "  Missing in DB2: " . count($recCompare['missing_in_db2']) . "\n";
        echo "  Different: " . count($recCompare['different_rows']) . "\n";
        echo "  Identical: " . $recCompare['identical_count'] . "\n";
    } else {
        // Compare all tables
        echo "🔍 Comparing all tables...\n\n";
        
        $report = getFullComparisonReport($db1, $db2, $recordLimit);
        
        echo "📊 Summary:\n";
        echo "  Tables in DB1: " . $report['summary']['total_tables_db1'] . "\n";
        echo "  Tables in DB2: " . $report['summary']['total_tables_db2'] . "\n";
        echo "  Missing Tables in DB2: " . $report['summary']['missing_tables_db2'] . "\n";
        echo "  Total Missing Rows: " . $report['summary']['total_missing_rows'] . "\n";
        echo "  Total Different Rows: " . $report['summary']['total_different_rows'] . "\n";
        echo "  Total Identical Rows: " . $report['summary']['total_identical_rows'] . "\n";
        
        if (!empty($report['tables']['missing_in_db2'])) {
            echo "\n⚠️  Missing Tables in DB2:\n";
            foreach ($report['tables']['missing_in_db2'] as $table) {
                echo "  - {$table}\n";
            }
        }
        
        echo "\n📋 Table Details:\n";
        foreach ($report['table_details'] as $table => $details) {
            $missingRows = count($details['records']['missing_in_db2']);
            $different = count($details['records']['different_rows']);
            $status = $missingRows > 0 || $different > 0 ? '⚠️' : '✅';
            echo "  {$status} {$table}: {$missingRows} missing, {$different} different\n";
        }
    }
}

elseif (isset($options['insert'])) {
    // Insert missing rows
    $tableName = $options['insert'];
    
    if (empty($tableName)) {
        echo "❌ Error: Table name required\n";
        exit(1);
    }
    
    echo "🔄 Comparing table: {$tableName}\n";
    $recCompare = compareTableRecords($db1, $db2, $tableName, 0);
    
    $missingRows = $recCompare['missing_in_db2'];
    
    if (empty($missingRows)) {
        echo "✅ No missing rows to insert\n";
        exit(0);
    }
    
    echo "📤 Inserting " . count($missingRows) . " missing rows...\n";
    
    $rows = array_values($missingRows);
    $result = insertMissingRows($db1, $db2, $tableName, $rows);
    
    echo "\n📊 Insert Results:\n";
    echo "  Total: {$result['total']}\n";
    echo "  Success: {$result['success']}\n";
    echo "  Failed: {$result['failed']}\n";
    echo "  Success Rate: {$result['success_rate']}%\n";
    
    if (!empty($result['errors'])) {
        echo "\n❌ Errors:\n";
        foreach ($result['errors'] as $error) {
            echo "  Row {$error['row_index']}: {$error['message']}\n";
        }
    }
    
    // Log the action
    logSyncAction('insert', $tableName, $result, $result['failed'] === 0);
}

elseif (isset($options['report'])) {
    // Generate full report
    echo "📊 Generating full comparison report...\n\n";
    
    $report = getFullComparisonReport($db1, $db2, $recordLimit);
    
    echo "Generated: {$report['generated_at']}\n\n";
    
    echo "=== SUMMARY ===\n";
    echo "Tables in DB1: {$report['summary']['total_tables_db1']}\n";
    echo "Tables in DB2: {$report['summary']['total_tables_db2']}\n";
    echo "Missing in DB2: {$report['summary']['missing_tables_db2']}\n";
    echo "Missing in DB1: {$report['summary']['missing_tables_db1']}\n";
    echo "Total Missing Rows: {$report['summary']['total_missing_rows']}\n";
    echo "Total Different Rows: {$report['summary']['total_different_rows']}\n";
    echo "Total Identical Rows: {$report['summary']['total_identical_rows']}\n\n";
    
    echo "=== MISSING TABLES ===\n";
    if (!empty($report['tables']['missing_in_db2'])) {
        foreach ($report['tables']['missing_in_db2'] as $table) {
            echo "- {$table} (missing in DB2)\n";
        }
    }
    if (!empty($report['tables']['missing_in_db1'])) {
        foreach ($report['tables']['missing_in_db1'] as $table) {
            echo "- {$table} (missing in DB1)\n";
        }
    }
    
    echo "\n=== TABLE DETAILS ===\n";
    foreach ($report['table_details'] as $table => $details) {
        $missing = count($details['records']['missing_in_db2']);
        $different = count($details['records']['different_rows']);
        echo "{$table}: {$missing} missing, {$different} different\n";
    }
}

elseif (isset($options['export'])) {
    // Export to JSON
    $filename = $options['export'];
    
    echo "📊 Generating report and exporting to {$filename}...\n";
    
    $report = getFullComparisonReport($db1, $db2, $recordLimit);
    $json = exportToJSON($report, true);
    
    if (file_put_contents($filename, $json) !== false) {
        echo "✅ Report exported to {$filename}\n";
    } else {
        echo "❌ Failed to write file\n";
        exit(1);
    }
}

echo "\n✨ Done!\n";

