<?php
/**
 * DB Sync - Reports Page
 * Generate detailed comparison reports with PDF export
 */

$pageTitle = 'DBs Reports - DB Sync';
$currentPage = 'reports';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-alt me-2"></i>DBs Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateReport()">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="exportToPDF()" id="pdfBtn" disabled>
            <i class="fas fa-file-pdf me-1"></i> Export PDF
        </button>
    </div>
</div>

<!-- Report Controls -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Report Options</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Database A</label>
                <input type="text" class="form-control" id="dbAName" placeholder="Database A" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">Database B</label>
                <input type="text" class="form-control" id="dbBName" placeholder="Database B" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Status</label>
                <select class="form-select" id="reportFilter" onchange="filterReport()">
                    <option value="all">All Differences</option>
                    <option value="missing_tables">Missing Tables Only</option>
                    <option value="missing_records">Missing Records Only</option>
                    <option value="structure">Structure Differences Only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="generateReport()">
                    <i class="fas fa-file-alt me-1"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div id="reportSummary" style="display: none;">
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-card primary">
                <div class="stat-value" id="totalTablesA">-</div>
                <div class="stat-label">Tables in DB A</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card success">
                <div class="stat-value" id="totalTablesB">-</div>
                <div class="stat-label">Tables in DB B</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card danger">
                <div class="stat-value" id="missingTablesA">-</div>
                <div class="stat-label">Tables Missing in A</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card danger">
                <div class="stat-value" id="missingTablesB">-</div>
                <div class="stat-label">Tables Missing in B</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card warning">
                <div class="stat-value" id="missingRecordsA">-</div>
                <div class="stat-label">Records Missing in A</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card warning">
                <div class="stat-value" id="missingRecordsB">-</div>
                <div class="stat-label">Records Missing in B</div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report -->
<div id="reportContent" style="display: none;">
    <!-- Missing Tables Section -->
    <div class="card mb-4" id="missingTablesSection">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Missing Tables</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-danger">Tables in DB B but NOT in DB A</h6>
                    <div id="missingInA"></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger">Tables in DB A but NOT in DB B</h6>
                    <div id="missingInB"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables with Differences -->
    <div id="tablesReport"></div>
</div>

<!-- Initial State -->
<div id="initialState">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Generate Report</h5>
            <p class="text-muted">Click "Generate Report" to compare databases and create a detailed report</p>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="loadingState" style="display: none;">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Generating report... This may take a while for large databases.</p>
    </div>
</div>

<script>
// Store report data globally
let reportData = null;
// let dbNames = { db_a: 'Database A', db_b: 'Database B' };

// Generate report
function generateReport() {
    showLoader('Generating report...');
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('initialState').style.display = 'none';
    document.getElementById('reportContent').style.display = 'none';
    document.getElementById('reportSummary').style.display = 'none';
    
    fetch('../api/generate_report.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            
            if (data.success && data.data) {
                reportData = data.data;
                
                // Store database names
                dbNames = {
                    db_a: data.data.databases.db_a.name,
                    db_b: data.data.databases.db_b.name
                };
                
                document.getElementById('dbAName').value = dbNames.db_a;
                document.getElementById('dbBName').value = dbNames.db_b;
                
                renderReport(data.data);
            } else {
                showToast(data.message || 'Failed to generate report', 'error');
                document.getElementById('initialState').style.display = 'block';
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error generating report: ' + error.message, 'error');
            document.getElementById('initialState').style.display = 'block';
        });
}

// Render report
function renderReport(data) {
    document.getElementById('loadingState').style.display = 'none';
    document.getElementById('reportContent').style.display = 'block';
    document.getElementById('reportSummary').style.display = 'block';
    document.getElementById('pdfBtn').disabled = false;
    
    // Update summary cards
    document.getElementById('totalTablesA').textContent = data.summary.total_tables_a;
    document.getElementById('totalTablesB').textContent = data.summary.total_tables_b;
    document.getElementById('missingTablesA').textContent = data.summary.missing_in_a;
    document.getElementById('missingTablesB').textContent = data.summary.missing_in_b;
    document.getElementById('missingRecordsA').textContent = data.summary.total_missing_records_a.toLocaleString();
    document.getElementById('missingRecordsB').textContent = data.summary.total_missing_records_b.toLocaleString();
    
    // Render missing tables
    renderMissingTables(data.missing_tables);
    
    // Render tables with differences
    renderTablesWithDifferences(data.tables);
}

// Render missing tables
function renderMissingTables(missingTables) {
    const inA = document.getElementById('missingInA');
    const inB = document.getElementById('missingInB');
    
    // Tables missing in A (exist in B only)
    if (missingTables.in_a && missingTables.in_a.length > 0) {
        inA.innerHTML = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-danger">
                        <tr>
                            <th>Table Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${missingTables.in_a.map(t => `
                            <tr class="table-danger">
                                <td><code>${escapeHtml(t.table_name)}</code></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Total: ${missingTables.in_a.length} tables</p>
        `;
    } else {
        inA.innerHTML = '<p class="text-success"><i class="fas fa-check me-1"></i>No missing tables</p>';
    }
    
    // Tables missing in B (exist in A only)
    if (missingTables.in_b && missingTables.in_b.length > 0) {
        inB.innerHTML = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-danger">
                        <tr>
                            <th>Table Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${missingTables.in_b.map(t => `
                            <tr class="table-danger">
                                <td><code>${escapeHtml(t.table_name)}</code></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Total: ${missingTables.in_b.length} tables</p>
        `;
    } else {
        inB.innerHTML = '<p class="text-success"><i class="fas fa-check me-1"></i>No missing tables</p>';
    }
}

// Render tables with differences
function renderTablesWithDifferences(tables) {
    const container = document.getElementById('tablesReport');
    
    if (!tables || tables.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <h5>No Differences Found</h5>
                    <p class="text-muted">All common tables are identical between the databases.</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    tables.forEach((table, index) => {
        const hasStructureDiff = table.structure_differences && Object.keys(table.structure_differences).length > 0;
        const missingInA = table.records.missing_in_a;
        const missingInB = table.records.missing_in_b;
        const different = table.records.different || 0;
        
        html += `
            <div class="card mb-4 table-report-card" data-filter="${getTableFilterType(table)}">
                <div class="card-header ${hasStructureDiff ? 'bg-warning' : (missingInA > 0 || missingInB > 0 ? 'bg-info' : 'bg-secondary')} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>${escapeHtml(table.table_name)}</h5>
                        <span class="badge bg-light text-dark">${table.records.count_a.toLocaleString()} vs ${table.records.count_b.toLocaleString()} rows</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Quick Stats -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div class="stat-value">${table.records.matched.toLocaleString()}</div>
                                <div class="stat-label">Matched</div>
                            </div>
                        </div>
                        ${missingInA > 0 ? `
                        <div class="col-md-3">
                            <div class="stat-card danger">
                                <div class="stat-value">${missingInA.toLocaleString()}</div>
                                <div class="stat-label">Missing in ${dbNames.db_a}</div>
                            </div>
                        </div>
                        ` : ''}
                        ${missingInB > 0 ? `
                        <div class="col-md-3">
                            <div class="stat-card danger">
                                <div class="stat-value">${missingInB.toLocaleString()}</div>
                                <div class="stat-label">Missing in ${dbNames.db_b}</div>
                            </div>
                        </div>
                        ` : ''}
                        ${different > 0 ? `
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <div class="stat-value">${different.toLocaleString()}</div>
                                <div class="stat-label">Different</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Missing Records -->
                    ${(table.missing_records.in_a.length > 0 || table.missing_records.in_b.length > 0) ? `
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6 class="text-danger">Missing in ${dbNames.db_a} (Primary Keys)</h6>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-danger">
                                        <tr>
                                            ${Object.keys(table.missing_records.in_a[0] || {}).map(k => `<th>${escapeHtml(k)}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${table.missing_records.in_a.map(row => `
                                            <tr class="table-danger">
                                                ${Object.values(row).map(v => `<td>${escapeHtml(String(v))}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            ${table.missing_records.in_a_truncated ? `<p class="text-muted small">... and ${table.missing_records.in_a_truncated} more</p>` : ''}
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">Missing in ${dbNames.db_b} (Primary Keys)</h6>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-danger">
                                        <tr>
                                            ${Object.keys(table.missing_records.in_b[0] || {}).map(k => `<th>${escapeHtml(k)}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${table.missing_records.in_b.map(row => `
                                            <tr class="table-danger">
                                                ${Object.values(row).map(v => `<td>${escapeHtml(String(v))}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            ${table.missing_records.in_b_truncated ? `<p class="text-muted small">... and ${table.missing_records.in_b_truncated} more</p>` : ''}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Structure Differences -->
                    ${hasStructureDiff ? `
                    <div class="mt-3">
                        <h6 class="text-warning">Structure Differences</h6>
                        ${table.structure_differences.missing_columns_in_a && table.structure_differences.missing_columns_in_a.length > 0 ? `
                            <p class="text-danger">Missing in ${dbNames.db_a}: ${table.structure_differences.missing_columns_in_a.join(', ')}</p>
                        ` : ''}
                        ${table.structure_differences.missing_columns_in_b && table.structure_differences.missing_columns_in_b.length > 0 ? `
                            <p class="text-danger">Missing in ${dbNames.db_b}: ${table.structure_differences.missing_columns_in_b.join(', ')}</p>
                        ` : ''}
                        ${table.structure_differences.column_differences ? `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Column</th>
                                            <th>Difference Type</th>
                                            <th>DB A</th>
                                            <th>DB B</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${Object.entries(table.structure_differences.column_differences).map(([col, diff]) => `
                                            <tr>
                                                <td>${escapeHtml(col)}</td>
                                                <td>${Object.keys(diff).join(', ')}</td>
                                                <td>${Object.values(diff).map(d => d.a).join(', ')}</td>
                                                <td>${Object.values(diff).map(d => d.b).join(', ')}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Get filter type for table
function getTableFilterType(table) {
    const hasStructure = table.structure_differences && Object.keys(table.structure_differences).length > 0;
    const hasMissing = table.records.missing_in_a > 0 || table.records.missing_in_b > 0;
    
    if (hasStructure && hasMissing) return 'all';
    if (hasStructure) return 'structure';
    if (hasMissing) return 'missing_records';
    return 'all';
}

// Filter report
function filterReport() {
    const filter = document.getElementById('reportFilter').value;
    const cards = document.querySelectorAll('.table-report-card');
    
    cards.forEach(card => {
        if (filter === 'all') {
            card.style.display = 'block';
        } else {
            card.style.display = card.dataset.filter === filter ? 'block' : 'none';
        }
    });
}

// Export to PDF
function exportToPDF() {
    if (!reportData) {
        showToast('Please generate a report first', 'warning');
        return;
    }
    
    // Open print dialog which can save as PDF
    const printWindow = window.open('', '_blank');
    
    const content = generatePDFContent(reportData);
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Comparison Report - ${new Date().toLocaleDateString()}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-size: 12px; padding: 20px; }
                .card { margin-bottom: 15px; break-inside: avoid; }
                .table-danger { background-color: #f8d7da; }
                .table-warning { background-color: #fff3cd; }
                .table-success { background-color: #d1e7dd; }
                @media print {
                    .no-print { display: none; }
                    .card { break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            ${content}
            <script>window.onload = function() { window.print(); }<\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Generate PDF content
function generatePDFContent(data) {
    const dbAName = data.databases.db_a.name;
    const dbBName = data.databases.db_b.name;
    
    let html = `
        <div class="text-center mb-4">
            <h1>Database Comparison Report</h1>
            <p class="text-muted">Generated: ${data.generated_at}</p>
            <p><strong>${escapeHtml(dbAName)}</strong> vs <strong>${escapeHtml(dbBName)}</strong></p>
        </div>
        
        <!-- Summary -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <h4>${data.summary.total_tables_a}</h4>
                        <small>Tables in DB A</small>
                    </div>
                    <div class="col-md-2">
                        <h4>${data.summary.total_tables_b}</h4>
                        <small>Tables in DB B</small>
                    </div>
                    <div class="col-md-2">
                        <h4 class="text-danger">${data.summary.missing_in_a}</h4>
                        <small>Missing in A</small>
                    </div>
                    <div class="col-md-2">
                        <h4 class="text-danger">${data.summary.missing_in_b}</h4>
                        <small>Missing in B</small>
                    </div>
                    <div class="col-md-2">
                        <h4 class="text-warning">${data.summary.total_missing_records_a.toLocaleString()}</h4>
                        <small>Records Missing in A</small>
                    </div>
                    <div class="col-md-2">
                        <h4 class="text-warning">${data.summary.total_missing_records_b.toLocaleString()}</h4>
                        <small>Records Missing in B</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Missing Tables
    if (data.missing_tables.in_a.length > 0 || data.missing_tables.in_b.length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Missing Tables</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-danger">In ${escapeHtml(dbBName)} but NOT in ${escapeHtml(dbAName)}</h6>
                            <ul>
                                ${data.missing_tables.in_a.map(t => `<li><code>${escapeHtml(t.table_name)}</code></li>`).join('')}
                            </ul>
                            ${data.missing_tables.in_a.length === 0 ? '<p class="text-success">None</p>' : ''}
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">In ${escapeHtml(dbAName)} but NOT in ${escapeHtml(dbBName)}</h6>
                            <ul>
                                ${data.missing_tables.in_b.map(t => `<li><code>${escapeHtml(t.table_name)}</code></li>`).join('')}
                            </ul>
                            ${data.missing_tables.in_b.length === 0 ? '<p class="text-success">None</p>' : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Tables with differences
    if (data.tables && data.tables.length > 0) {
        html += `<h3 class="mt-4 mb-3">Tables with Differences</h3>`;
        
        data.tables.forEach(table => {
            const hasStructure = table.structure_differences && Object.keys(table.structure_differences).length > 0;
            
            html += `
                <div class="card mb-3">
                    <div class="card-header ${hasStructure ? 'bg-warning' : 'bg-info'} text-white">
                        <strong>${escapeHtml(table.table_name)}</strong>
                        <span class="badge bg-light text-dark float-end">
                            ${table.records.count_a.toLocaleString()} vs ${table.records.count_b.toLocaleString()} rows
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Matched:</strong> ${table.records.matched.toLocaleString()}</div>
                            ${table.records.missing_in_a > 0 ? `<div class="col-md-3 text-danger"><strong>Missing in ${escapeHtml(dbAName)}:</strong> ${table.records.missing_in_a.toLocaleString()}</div>` : ''}
                            ${table.records.missing_in_b > 0 ? `<div class="col-md-3 text-danger"><strong>Missing in ${escapeHtml(dbBName)}:</strong> ${table.records.missing_in_b.toLocaleString()}</div>` : ''}
                            ${table.records.different > 0 ? `<div class="col-md-3 text-warning"><strong>Different:</strong> ${table.records.different.toLocaleString()}</div>` : ''}
                        </div>
                        
                        ${hasStructure ? `
                        <div class="mt-2">
                            <strong>Structure Differences:</strong>
                            ${table.structure_differences.missing_columns_in_a?.length > 0 ? 
                                `<p class="text-danger small">Missing in ${escapeHtml(dbAName)}: ${table.structure_differences.missing_columns_in_a.join(', ')}</p>` : ''}
                            ${table.structure_differences.missing_columns_in_b?.length > 0 ? 
                                `<p class="text-danger small">Missing in ${escapeHtml(dbBName)}: ${table.structure_differences.missing_columns_in_b.join(', ')}</p>` : ''}
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p class="mb-0">No differences found in common tables.</p>
                </div>
            </div>
        `;
    }
    
    return html;
}

// Load summary on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                dbNames = {
                    db_a: data.data.databases?.db_a?.name || 'Database A',
                    db_b: data.data.databases?.db_b?.name || 'Database B'
                };
                document.getElementById('dbAName').value = dbNames.db_a;
                document.getElementById('dbBName').value = dbNames.db_b;
            }
        });
});
</script>

<?php
require_once '../templates/footer.php';
?>

