<?php
/**
 * DB Sync - Logs & Issues Page
 * Show all activity logs and errors
 */

$pageTitle = 'Logs & Issues - DB Sync';
$currentPage = 'logs';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Logs & Issues</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadLogs()">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Log Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-value" id="logTotal">-</div>
            <div class="stat-label">Total Logs</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-value" id="logErrors">-</div>
            <div class="stat-label">Errors</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-value" id="logSuccess">-</div>
            <div class="stat-label">Success</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-value" id="logInfo">-</div>
            <div class="stat-label">Info</div>
        </div>
    </div>
</div>

<!-- Filter Controls -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Logs</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Log Type</label>
                <select class="form-select" id="logType" onchange="loadLogs()">
                    <option value="">All Types</option>
                    <option value="ERROR">Errors</option>
                    <option value="SUCCESS">Success</option>
                    <option value="WARNING">Warnings</option>
                    <option value="INFO">Info</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Number of Logs</label>
                <select class="form-select" id="logLimit" onchange="loadLogs()">
                    <option value="50">50 logs</option>
                    <option value="100" selected>100 logs</option>
                    <option value="200">200 logs</option>
                    <option value="500">500 logs</option>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" onclick="loadLogs()">
                    <i class="fas fa-search me-1"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Activity Log</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive" id="logsTable">
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                <p class="text-muted">Loading logs...</p>
            </div>
        </div>
    </div>
</div>

<!-- Log Types Legend -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Log Type Definitions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-danger me-2">ERROR</span>
                    <small>Database errors, constraint violations, insert failures</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2">SUCCESS</span>
                    <small>Successful operations, completed inserts</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning text-dark me-2">WARNING</span>
                    <small>Potential issues, non-critical problems</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">INFO</span>
                    <small>General information, start/end of operations</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load logs on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(loadLogs, 500);
});
</script>

<?php
require_once '../templates/footer.php';
?>

