<?php
/**
 * DB Sync - Table Structure Comparison Page
 * Compare both databases and show structural differences
 */

$pageTitle = 'Structure Comparison - DB Sync';
$currentPage = 'structure';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-table me-2"></i>Table Structure Comparison</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="compareStructures()">
                <i class="fas fa-sync me-1"></i> Compare Now
            </button>
        </div>
    </div>
</div>

<!-- Filter Controls -->
<div class="filter-bar">
    <div class="row align-items-center">
        <div class="col-md-3">
            <label class="form-label mb-0">Pagination Limit</label>
            <select class="form-select form-select-sm" id="structureLimit">
                <option value="50">50 tables</option>
                <option value="100" selected>100 tables</option>
                <option value="200">200 tables</option>
                <option value="500">500 tables</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0">Offset</label>
            <input type="number" class="form-control form-control-sm" id="structureOffset" value="0" min="0">
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary btn-sm" onclick="compareStructures()">
                <i class="fas fa-search me-1"></i> Run Comparison
            </button>
        </div>
    </div>
</div>

<!-- Progress Indicator -->
<div id="structureProgress" class="mb-3" style="display: none;">
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
    </div>
    <small class="text-muted">Analyzing structures...</small>
</div>

<!-- Results Container -->
<div id="structureResults">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Ready to Compare</h5>
            <p class="text-muted">Click "Compare Now" to analyze table structures</p>
            <button class="btn btn-primary" onclick="compareStructures()">
                <i class="fas fa-play me-1"></i> Start Comparison
            </button>
        </div>
    </div>
</div>

<script>
// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Auto-run comparison on page load
    setTimeout(compareStructures, 500);
});
</script>

<?php
require_once '../templates/footer.php';
?>

