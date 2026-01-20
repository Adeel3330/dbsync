<?php
/**
 * DB Sync - Dashboard / Home Page
 * Quick summary view only - fast loading
 */

$pageTitle = 'Dashboard - DB Sync';
$currentPage = 'dashboard';

require_once 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="getDashboardSummary()">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Quick Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-value" id="dbATables">-</div>
            <div class="stat-label">Tables in DB A</div>
            <small class="text-white-50" id="dbAConnected">Not connected</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-value" id="dbBTables">-</div>
            <div class="stat-label">Tables in DB B</div>
            <small class="text-white-50" id="dbBConnected">Not connected</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-value" id="missingTables">-</div>
            <div class="stat-label">Missing Tables</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-value" id="matchedTables">-</div>
            <div class="stat-label">Matched Tables</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Comparison Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="summary-box danger">
                            <h3 class="mb-0" id="mismatchedTables">-</h3>
                            <small class="text-muted">Mismatched Tables</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box warning">
                            <h3 class="mb-0" id="totalDifferences">-</h3>
                            <small class="text-muted">Total Differences</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box success">
                            <h3 class="mb-0" id="matchedTables2">-</h3>
                            <small class="text-muted">Identical Tables</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pages/structure.php" class="btn btn-primary">
                        <i class="fas fa-table me-2"></i>Compare Structures
                    </a>
                    <a href="pages/records.php" class="btn btn-success">
                        <i class="fas fa-list me-2"></i>Compare Records
                    </a>
                    <a href="pages/sync.php" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-2"></i>Sync Records
                    </a>
                    <a href="pages/table_detail.php" class="btn btn-info">
                        <i class="fas fa-search me-2"></i>View Table Details
                    </a>
                    <a href="pages/logs.php" class="btn btn-secondary">
                        <i class="fas fa-history me-2"></i>View Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection Status -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Database A Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-primary me-2">A</span>
                    <span id="dbAStatus">Checking...</span>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="testConnection('db_a')">
                    <i class="fas fa-plug me-1"></i>Test Connection
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Database B Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-success me-2">B</span>
                    <span id="dbBStatus">Checking...</span>
                </div>
                <button class="btn btn-sm btn-outline-success" onclick="testConnection('db_b')">
                    <i class="fas fa-plug me-1"></i>Test Connection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About This Dashboard</h5>
    </div>
    <div class="card-body">
        <p class="mb-2">This dashboard provides a <strong>quick summary</strong> of both databases without loading full data.</p>
        <ul class="mb-0">
            <li><strong>Structure Comparison:</strong> Compare table structures, columns, and indexes</li>
            <li><strong>Record Comparison:</strong> Compare actual data rows between databases</li>
            <li><strong>Table Details:</strong> View and compare table data side-by-side</li>
            <li><strong>Logs:</strong> View activity logs and error messages</li>
        </ul>
    </div>
</div>

<script>
// Load dashboard summary on page load
document.addEventListener('DOMContentLoaded', function() {
    getDashboardSummary();
});
</script>

<?php
require_once 'templates/footer.php';
?>

