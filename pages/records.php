<?php
/**
 * DB Sync - Record Comparison Page
 * Compare all records of selected table from both DBs
 */

$pageTitle = 'Record Comparison - DB Sync';
$currentPage = 'records';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-list me-2"></i>Record Comparison</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="compareRecordsForTable()">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Table Selection -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Select Table to Compare</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-8">
                <label class="form-label">Table Name</label>
                <select class="form-select" id="tableSelect" onchange="compareRecordsForTable()">
                    <option value="">-- Select a table --</option>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" onclick="compareRecordsForTable()">
                    <i class="fas fa-search me-1"></i> Compare Records
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Comparison Summary -->
<div id="comparisonSummary" style="display: none;">
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-card primary">
                <div class="stat-value" id="totalA">-</div>
                <div class="stat-label">Rows in DB A</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card success">
                <div class="stat-value" id="totalB">-</div>
                <div class="stat-label">Rows in DB B</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card success">
                <div class="stat-value" id="matched">-</div>
                <div class="stat-label">Matched Rows</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card danger">
                <div class="stat-value" id="missingInA">-</div>
                <div class="stat-label">Missing in A</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card danger">
                <div class="stat-value" id="missingInB">-</div>
                <div class="stat-label">Missing in B</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card warning">
                <div class="stat-value" id="differentData">-</div>
                <div class="stat-label">Different Data</div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Legend</h6>
            <div class="d-flex gap-4">
                <span><span class="badge bg-danger me-1">RED</span> Missing rows (need insert)</span>
                <span><span class="badge bg-warning text-dark me-1">ORANGE</span> Different data</span>
                <span><span class="badge bg-success me-1">GREEN</span> Matching rows</span>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="recordResults"></div>
</div>

<!-- Initial State -->
<div id="initialState">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-list fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Select a Table</h5>
            <p class="text-muted">Choose a table from the dropdown above to compare records</p>
        </div>
    </div>
</div>

<script>
// Load tables on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load tables from summary
    showLoader('Loading tables...');
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success && data.data) {
                // Store database names for use in insert modal
                if (data.data.dbNames) {
                    dbNames = {
                        db_a: data.data.dbNames.db_a || 'Database A',
                        db_b: data.data.dbNames.db_b || 'Database B'
                    };
                }
                
                const select = document.getElementById('tableSelect');
                const tablesA = data.data.dbA?.tables || [];
                const tablesB = data.data.dbB?.tables || [];
                const commonTables = tablesA.filter(t => tablesB.includes(t));
                
                select.innerHTML = '<option value="">-- Select a table --</option>' +
                    commonTables.map(table => `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`).join('');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading tables: ' + error.message, 'error');
        });
});
</script>

<?php
require_once '../templates/footer.php';
?>

