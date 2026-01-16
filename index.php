<?php

/**
 * Database Comparison Tool - Web Dashboard
 * 
 * This is the main web interface for the DB Sync tool.
 * It provides a clean HTML view to compare databases and synchronize data.
 * 
 * @package DBSync
 * @version 1.0.0
 */

require_once __DIR__ . '/functions.php';

// Initialize session for messages
session_start();

// Set default values
$pageTitle = 'Database Comparison Tool';
$error = null;
$success = null;

// Check if form was submitted
$action = $_POST['action'] ?? $_GET['action'] ?? 'compare';

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db1 = getDB1Connection();
    $db2 = getDB2Connection();
    
    if (!$db1) {
        $error = 'Failed to connect to Database 1. Please check config.php';
    } elseif (!$db2) {
        $error = 'Failed to connect to Database 2. Please check config.php';
    } else {
        switch ($action) {
            case 'compare':
                $recordLimit = intval($_POST['record_limit'] ?? 1000);
                $comparisonReport = getFullComparisonReport($db1, $db2, $recordLimit);
                break;
                
            case 'insert_rows':
                $tableName = $_POST['table_name'] ?? '';
                $rowsJson = $_POST['rows_data'] ?? '[]';
                $rows = json_decode($rowsJson, true);
                
                if (!empty($tableName) && !empty($rows)) {
                    $insertResult = insertMissingRows($db1, $db2, $tableName, $rows);
                    if ($insertResult['failed'] > 0) {
                        $error = "Inserted {$insertResult['success']} of {$insertResult['total']} rows. {$insertResult['failed']} failed.";
                    } else {
                        $success = "Successfully inserted {$insertResult['success']} rows.";
                    }
                    // Store result for display
                    $_SESSION['last_insert_result'] = $insertResult;
                    $_SESSION['last_insert_table'] = $tableName;
                } else {
                    $error = 'Invalid table name or no rows to insert.';
                }
                break;
                
            case 'export_json':
                $report = $_SESSION['comparison_report'] ?? null;
                if ($report) {
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="comparison_report.json"');
                    echo exportToJSON($report);
                    exit;
                }
                break;
        }
        
        // Store report in session for export
        if (isset($comparisonReport)) {
            $_SESSION['comparison_report'] = $comparisonReport;
        }
    }
}

// Get last insert result if available
$lastInsertResult = $_SESSION['last_insert_result'] ?? null;
$lastInsertTable = $_SESSION['last_insert_table'] ?? null;
$comparisonReport = $_SESSION['comparison_report'] ?? null;

// Clear messages after display (optional - remove if you want persistent messages)
// unset($_SESSION['last_insert_result'], $_SESSION['last_insert_table']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --success-color: #16a34a;
            --warning-color: #ca8a04;
            --danger-color: #dc2626;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            font-size: 1.75rem;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .connection-status {
            display: flex;
            gap: 20px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-badge.connected {
            background: #dcfce7;
            color: var(--success-color);
        }
        
        .status-badge.disconnected {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text-color);
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-warning {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .summary-item {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-item .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .summary-item .label {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .summary-item.danger .number {
            color: var(--danger-color);
        }
        
        .summary-item.warning .number {
            color: var(--warning-color);
        }
        
        .summary-item.success .number {
            color: var(--success-color);
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .badge-warning {
            background: #fef9c3;
            color: #854d0e;
        }
        
        .badge-success {
            background: #dcfce7;
            color: var(--success-color);
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .diff-table {
            font-size: 0.8rem;
        }
        
        .diff-table th {
            background: #fff7ed;
        }
        
        .diff-value-db1 {
            color: var(--primary-color);
        }
        
        .diff-value-db2 {
            color: var(--success-color);
        }
        
        .expand-btn {
            background: none;
            border: 1px solid var(--border-color);
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .expand-btn:hover {
            background: #f1f5f9;
        }
        
        .hidden {
            display: none;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #64748b;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        
        .tab:hover {
            color: var(--primary-color);
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .insert-result {
            margin-top: 16px;
            padding: 16px;
            border-radius: 8px;
        }
        
        .insert-result.success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }
        
        .insert-result.partial {
            background: #fef9c3;
            border: 1px solid #fde047;
        }
        
        .insert-result.failed {
            background: #fee2e2;
            border: 1px solid #fecaca;
        }
        
        .error-detail {
            margin-top: 12px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        .nav-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        
        .nav-tab {
            padding: 8px 16px;
            background: #f1f5f9;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            color: #475569;
            transition: all 0.2s;
        }
        
        .nav-tab:hover {
            background: #e2e8f0;
        }
        
        .nav-tab.active {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🗄️ <?php echo APP_NAME; ?></h1>
            <p>Compare databases, identify differences, and synchronize data between MySQL databases.</p>
            
            <?php
            // Test database connections
            $connections = testConnections();
            ?>
            <div class="connection-status">
                <div class="status-badge <?php echo $connections['db1']['status'] ? 'connected' : 'disconnected'; ?>">
                    <span class="status-indicator"></span>
                    Database 1: <?php echo $connections['db1']['message']; ?>
                </div>
                <div class="status-badge <?php echo $connections['db2']['status'] ? 'connected' : 'disconnected'; ?>">
                    <span class="status-indicator"></span>
                    Database 2: <?php echo $connections['db2']['message']; ?>
                </div>
            </div>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Comparison Form -->
        <div class="card">
            <h2 class="card-title">🔍 Compare Databases</h2>
            <form method="post">
                <input type="hidden" name="action" value="compare">
                <div class="form-row">
                    <div class="form-group">
                        <label for="record_limit">Record Limit Per Table</label>
                        <input type="number" id="record_limit" name="record_limit" class="form-control" 
                               value="<?php echo intval($_POST['record_limit'] ?? 1000); ?>" min="1" max="100000">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            🔄 Start Comparison
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Insert Result Display -->
        <?php if ($lastInsertResult): ?>
            <div class="card">
                <h2 class="card-title">📤 Insert Result: <?php echo htmlspecialchars($lastInsertTable); ?></h2>
                <div class="insert-result <?php echo $lastInsertResult['failed'] > 0 ? 'partial' : 'success'; ?>">
                    <p><strong>Total Rows:</strong> <?php echo $lastInsertResult['total']; ?></p>
                    <p><strong>Successful:</strong> <?php echo $lastInsertResult['success']; ?></p>
                    <p><strong>Failed:</strong> <?php echo $lastInsertResult['failed']; ?></p>
                    <p><strong>Success Rate:</strong> <?php echo $lastInsertResult['success_rate']; ?>%</p>
                    
                    <?php if (!empty($lastInsertResult['inserted_ids'])): ?>
                        <p><strong>Inserted IDs:</strong> <?php echo implode(', ', $lastInsertResult['inserted_ids']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($lastInsertResult['errors'])): ?>
                        <div class="error-detail">
                            <strong>Errors:</strong>
                            <?php foreach ($lastInsertResult['errors'] as $err): ?>
                                <div style="margin-top: 8px;">
                                    Row <?php echo $err['row_index']; ?>: 
                                    <?php echo htmlspecialchars($err['message']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Comparison Results -->
        <?php if ($comparisonReport): ?>
            <div class="card">
                <div class="section-header">
                    <h2 class="card-title">📊 Comparison Results</h2>
                    <div style="display: flex; gap: 8px;">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="export_json">
                            <button type="submit" class="btn btn-primary btn-sm">
                                📥 Export JSON
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Summary -->
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="number"><?php echo $comparisonReport['summary']['total_tables_db1']; ?></div>
                        <div class="label">Tables in DB1</div>
                    </div>
                    <div class="summary-item">
                        <div class="number"><?php echo $comparisonReport['summary']['total_tables_db2']; ?></div>
                        <div class="label">Tables in DB2</div>
                    </div>
                    <div class="summary-item danger">
                        <div class="number"><?php echo $comparisonReport['summary']['missing_tables_db2']; ?></div>
                        <div class="label">Tables Missing in DB2</div>
                    </div>
                    <div class="summary-item danger">
                        <div class="number"><?php echo $comparisonReport['summary']['total_missing_rows']; ?></div>
                        <div class="label">Missing Rows (DB1→DB2)</div>
                    </div>
                    <div class="summary-item warning">
                        <div class="number"><?php echo $comparisonReport['summary']['total_different_rows']; ?></div>
                        <div class="label">Different Rows</div>
                    </div>
                    <div class="summary-item success">
                        <div class="number"><?php echo $comparisonReport['summary']['total_identical_rows']; ?></div>
                        <div class="label">Identical Rows</div>
                    </div>
                </div>
                
                <!-- Missing Tables Section -->
                <?php if (!empty($comparisonReport['tables']['missing_in_db2'])): ?>
                    <div class="section-header">
                        <h3 class="card-title" style="color: var(--danger-color);">⚠️ Tables Missing in Database 2</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Action Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comparisonReport['tables']['missing_in_db2'] as $table): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                        <td>
                                            <span class="badge badge-danger">Create table in DB2</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Table Comparison Tabs -->
                <div style="margin-top: 24px;">
                    <h3 class="card-title">📋 Detailed Table Comparison</h3>
                    
                    <?php if (!empty($comparisonReport['table_details'])): ?>
                        <div class="nav-tabs">
                            <button class="nav-tab active" onclick="showTableTab('all')">All Tables</button>
                            <?php foreach ($comparisonReport['table_details'] as $tableName => $details): ?>
                                <?php 
                                $hasIssues = !empty($details['columns']['missing_in_db2']) || 
                                            !empty($details['columns']['missing_in_db1']) ||
                                            !empty($details['columns']['type_mismatches']) ||
                                            !empty($details['columns']['null_mismatches']) ||
                                            !empty($details['records']['missing_in_db2']) ||
                                            !empty($details['records']['different_rows']);
                                ?>
                                <button class="nav-tab" onclick="showTableTab('<?php echo htmlspecialchars($tableName); ?>')">
                                    <?php echo htmlspecialchars($tableName); ?>
                                    <?php if ($hasIssues): ?>
                                        <span class="badge badge-warning">!</span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php foreach ($comparisonReport['table_details'] as $tableName => $details): ?>
                            <div id="tab-<?php echo htmlspecialchars($tableName); ?>" class="table-tab-content" style="display: none;">
                                <h4 style="margin-bottom: 12px;">Table: <code><?php echo htmlspecialchars($tableName); ?></code></h4>
                                
                                <!-- Column Comparison -->
                                <?php if (!empty($details['columns']['missing_in_db2']) || 
                                          !empty($details['columns']['missing_in_db1']) ||
                                          !empty($details['columns']['type_mismatches']) ||
                                          !empty($details['columns']['null_mismatches'])): ?>
                                    <div class="section-header">
                                        <h5 style="color: var(--warning-color);">📐 Column Differences</h5>
                                    </div>
                                    
                                    <?php if (!empty($details['columns']['missing_in_db2'])): ?>
                                        <p><strong>Columns missing in DB2:</strong></p>
                                        <div class="table-container">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Column</th>
                                                        <th>Type</th>
                                                        <th>Null</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($details['columns']['missing_in_db2'] as $col): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($col['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($col['type']); ?></td>
                                                            <td><?php echo htmlspecialchars($col['null']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($details['columns']['type_mismatches'])): ?>
                                        <p style="margin-top: 12px;"><strong>Type mismatches:</strong></p>
                                        <div class="table-container">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Column</th>
                                                        <th>DB1 Type</th>
                                                        <th>DB2 Type</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($details['columns']['type_mismatches'] as $col => $types): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($col); ?></td>
                                                            <td><?php echo htmlspecialchars($types['db1']); ?></td>
                                                            <td><?php echo htmlspecialchars($types['db2']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><span class="badge badge-success">All columns are identical</span></p>
                                <?php endif; ?>
                                
                                <!-- Record Comparison -->
                                <div class="section-header" style="margin-top: 20px;">
                                    <h5 style="color: var(--text-color);">📝 Record Comparison</h5>
                                    <?php if (!empty($details['records']['missing_in_db2'])): ?>
                                        <form method="post" onsubmit="return confirmInsert(<?php echo count($details['records']['missing_in_db2']); ?>, '<?php echo htmlspecialchars($tableName); ?>')">
                                            <input type="hidden" name="action" value="insert_rows">
                                            <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($tableName); ?>">
                                            <input type="hidden" name="rows_data" id="rows_data_<?php echo htmlspecialchars($tableName); ?>" value="">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                ➕ Insert <?php echo count($details['records']['missing_in_db2']); ?> Missing Rows
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="summary-grid" style="margin-bottom: 16px;">
                                    <div class="summary-item">
                                        <div class="number"><?php echo $details['records']['db1_count']; ?></div>
                                        <div class="label">Rows in DB1</div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="number"><?php echo $details['records']['db2_count']; ?></div>
                                        <div class="label">Rows in DB2</div>
                                    </div>
                                    <div class="summary-item danger">
                                        <div class="number"><?php echo count($details['records']['missing_in_db2']); ?></div>
                                        <div class="label">Missing in DB2</div>
                                    </div>
                                    <div class="summary-item warning">
                                        <div class="number"><?php echo count($details['records']['different_rows']); ?></div>
                                        <div class="label">Different</div>
                                    </div>
                                    <div class="summary-item success">
                                        <div class="number"><?php echo $details['records']['identical_count']; ?></div>
                                        <div class="label">Identical</div>
                                    </div>
                                </div>
                                
                                <!-- Missing Rows -->
                                <?php if (!empty($details['records']['missing_in_db2'])): ?>
                                    <h6 style="color: var(--danger-color); margin-bottom: 8px;">Rows to Insert from DB1 → DB2</h6>
                                    <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>PK</th>
                                                    <?php 
                                                    // Get column names from first row
                                                    $firstRow = reset($details['records']['missing_in_db2']);
                                                    foreach (array_keys($firstRow) as $col): 
                                                        if (in_array($col, $details['records']['primary_key'])) continue;
                                                    ?>
                                                        <th><?php echo htmlspecialchars($col); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($details['records']['missing_in_db2'] as $pk => $row): ?>
                                                    <tr>
                                                        <td><code><?php echo htmlspecialchars($pk); ?></code></td>
                                                        <?php foreach ($row as $col => $val): 
                                                            if (in_array($col, $details['records']['primary_key'])) continue;
                                                        ?>
                                                            <td><?php echo htmlspecialchars($val ?? 'NULL'); ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Different Rows -->
                                <?php if (!empty($details['records']['different_rows'])): ?>
                                    <h6 style="color: var(--warning-color); margin: 16px 0 8px;">Rows with Differences</h6>
                                    <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                                        <table class="diff-table">
                                            <thead>
                                                <tr>
                                                    <th>Column</th>
                                                    <th>DB1 Value</th>
                                                    <th>DB2 Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($details['records']['different_rows'] as $pk => $diffData): ?>
                                                    <tr style="background: #fffbeb;">
                                                        <td colspan="3">
                                                            <strong>Primary Key:</strong> <?php echo htmlspecialchars($pk); ?>
                                                        </td>
                                                    </tr>
                                                    <?php foreach ($diffData['differences'] as $col => $vals): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($col); ?></td>
                                                            <td class="diff-value-db1"><?php echo htmlspecialchars($vals['db1'] ?? 'NULL'); ?></td>
                                                            <td class="diff-value-db2"><?php echo htmlspecialchars($vals['db2'] ?? 'NULL'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No common tables found between the databases.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <script>
                let currentTableTab = 'all';
                
                function showTableTab(tableName) {
                    currentTableTab = tableName;
                    
                    // Update tab buttons
                    document.querySelectorAll('.nav-tab').forEach(tab => {
                        tab.classList.remove('active');
                        if (tab.textContent.toLowerCase().includes(tableName.toLowerCase()) || 
                            (tableName === 'all' && tab.textContent === 'All Tables')) {
                            tab.classList.add('active');
                        }
                    });
                    
                    // Show/hide table contents
                    document.querySelectorAll('.table-tab-content').forEach(content => {
                        if (tableName === 'all') {
                            content.style.display = 'block';
                        } else {
                            content.style.display = 'none';
                            if (content.id === 'tab-' + tableName) {
                                content.style.display = 'block';
                            }
                        }
                    });
                }
                
                function confirmInsert(count, tableName) {
                    if (!confirm('Are you sure you want to insert ' + count + ' rows into ' + tableName + '?')) {
                        return false;
                    }
                    
                    // Collect rows data
                    const rows = <?php echo json_encode($details['records']['missing_in_db2'] ?? []); ?>;
                    document.getElementById('rows_data_' + tableName).value = JSON.stringify(rows);
                    
                    return true;
                }
                
                // Show all tables by default
                showTableTab('all');
            </script>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer style="text-align: center; padding: 20px; color: #64748b; font-size: 0.85rem;">
            <p><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
            <p>Powered by PDO MySQL</p>
        </footer>
    </div>
</body>
</html>

