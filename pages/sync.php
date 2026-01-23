<?php
/**
 * DB Sync - Sync Records Page
 * Migrate all records from source DB to target DB
 * Supports single and multiple table selection
 * Shows CREATE vs UPDATE operations needed
 */

$pageTitle = 'Sync Records - DB Sync';
$currentPage = 'sync';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-sync-alt me-2"></i>Sync Records</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewSyncDB()">
                <i class="fas fa-eye me-1"></i> Refresh Preview
            </button>
        </div>
    </div>
</div>

<!-- Sync Configuration -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Sync Configuration</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-3">
                <label class="form-label">Source Database</label>
                <select class="form-select" id="sourceDb">
                    <option value="db_a">Database A</option>
                    <option value="db_b">Database B</option>
                </select>
            </div>
            <div class="col-md-1 text-center">
                <i class="fas fa-arrow-right fa-2x text-muted"></i>
            </div>
            <div class="col-md-3">
                <label class="form-label">Target Database</label>
                <select class="form-select" id="targetDb">
                    <option value="db_b">Database B</option>
                    <option value="db_a">Database A</option>
                </select>
            </div>
            <div class="col-md-5">
                <button class="btn btn-primary w-100" onclick="previewSyncDB()">
                    <i class="fas fa-search me-1"></i> Preview Selected Tables
                </button>
            </div>
        </div>
        
        <!-- Table Selection -->
        <div class="row mt-3">
            <div class="col-md-12">
                <label class="form-label fw-bold">Select Tables to Sync:</label>
                <div class="table-selection-container">
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="selectAllTables()">Select All</button>
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="selectNoneTables()">Select None</button>
                        <span class="text-muted"><span id="selectedCount">0</span> tables selected</span>
                    </div>
                    <div class="table-select-wrapper">
                        <table class="table table-bordered table-sm table-select">
                            <thead class="table-dark">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAllTablesCheck" onchange="toggleSelectAllTables(this)">
                                    </th>
                                    <th>Table Name</th>
                                    <th>Rows in Source</th>
                                    <th>Rows in Target</th>
                                </tr>
                            </thead>
                            <tbody id="tablesList">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Loading tables...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sync Options -->
        <div class="row mt-3">
            <div class="col-md-12">
                <label class="form-label fw-bold">Sync Options:</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="optCreate" checked>
                    <label class="form-check-label" for="optCreate">
                        <span class="badge bg-success">CREATE</span> Missing records (new)
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="optUpdate" checked>
                    <label class="form-check-label" for="optUpdate">
                        <span class="badge bg-warning text-dark">UPDATE</span> Existing records (modified)
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="optDryRun">
                    <label class="form-check-label" for="optDryRun">
                        <span class="badge bg-info">Dry Run</span> Preview only (no changes)
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Results -->
<div id="previewSection" style="display: none;">
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-card primary">
                <div class="stat-value" id="previewTables">-</div>
                <div class="stat-label">Tables</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card primary">
                <div class="stat-value" id="previewSourceRows">-</div>
                <div class="stat-label">Total Source Rows</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card success">
                <div class="stat-value" id="previewTargetRows">-</div>
                <div class="stat-label">Total Target Rows</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-value" id="previewCreate">-</div>
                <div class="stat-label">To CREATE (New)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-value" id="previewUpdate">-</div>
                <div class="stat-label">To UPDATE (Modified)</div>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Legend</h6>
            <div class="d-flex gap-4 flex-wrap">
                <span><span class="badge bg-success me-1">CREATE</span> Records that exist in source but NOT in target (will be inserted)</span>
                <span><span class="badge bg-warning text-dark me-1">UPDATE</span> Records that exist in both but have different data (will be updated)</span>
                <span><span class="badge bg-secondary me-1">SKIP</span> Records that are identical in both databases</span>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="card mb-4">
        <div class="card-body text-center">
            <button class="btn btn-success btn-lg me-2" id="btnSync" onclick="executeSyncDB()">
                <i class="fas fa-play me-2"></i>Execute Syncs
            </button>
            <button class="btn btn-outline-secondary btn-lg" onclick="previewSyncDB()">
                <i class="fas fa-sync me-1"></i> Refresh Preview
            </button>
            <div class="mt-2">
                <small class="text-muted" id="syncWarning"></small>
            </div>
        </div>
    </div>
    
    <!-- Table-level Preview -->
    <div id="tablePreviews"></div>
</div>

<!-- Initial State -->
<div id="initialState">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-sync-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Configure Sync</h5>
            <p class="text-muted">Select source, target databases and one or more tables to preview sync operations</p>
        </div>
    </div>
</div>

<!-- Sync Progress Modal -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-sync fa-spin me-2"></i>Sync in Progress</h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                         id="syncProgressBar" style="width: 0%">0%</div>
                </div>
                <div id="syncProgressDetails"></div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
<script>
let syncPreviewData = null;
let commonTables = [];
let tableRowCounts = {};

// Load tables on page load
document.addEventListener('DOMContentLoaded', function() {
    // debugger;
    console.log("Before calling loadTablesForSync");
loadTablesForSyncDB();
console.log("After calling loadTablesForSync");
    
    // Update target dropdown when source changes
    document.getElementById('sourceDb').addEventListener('change', function() {
        const sourceDb = this.value;
        const targetDb = sourceDb === 'db_a' ? 'db_b' : 'db_a';
        document.getElementById('targetDb').value = targetDb;
        previewSyncDB();
    });
    
    document.getElementById('targetDb').addEventListener('change', function() {
        previewSyncDB();
    });
});

// Load tables from both databases
function loadTablesForSyncDB() {
    console.log("here2")
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            console.log(data)
            if (data.success && data.data) {
                const tablesA = data.data.dbA?.tables || [];
                const tablesB = data.data.dbB?.tables || [];
                
                // Get common tables
                commonTables = tablesA.filter(t => tablesB.includes(t));
                console.log(tablesA)
                // Build table list with checkboxes
                const tbody = document.getElementById('tablesList');
                
                if (commonTables.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No common tables found</td></tr>';
                    return;
                }
                
                // Load row counts for each table
                loadTableRowCounts(tablesA, tablesB);
                
            } else {
                showToast('Failed to load tables', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading tables: ' + error.message, 'error');
        });
}

// Load row counts for tables
function loadTableRowCounts(tablesA, tablesB) {
    // For now, just render the table list without counts (counts will be in preview)
    const tbody = document.getElementById('tablesList');
    
    tbody.innerHTML = commonTables.map(table => `
        <tr>
            <td>
                <input type="checkbox" class="form-check-input table-checkbox" value="${escapeHtml(table)}" onchange="updateSelectedCountDb()">
            </td>
            <td><strong>${escapeHtml(table)}</strong></td>
            <td class="row-count-source" data-table="${escapeHtml(table)}">-</td>
            <td class="row-count-target" data-table="${escapeHtml(table)}">-</td>
        </tr>
    `).join('');
}

// Load row counts for preview
function loadRowCountsForPreview(sourceDb, targetDb) {
    const promises = commonTables.map(table => {
        return fetch(`../api/get_summary.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const tablesA = data.data.dbA?.tables || [];
                    const tablesB = data.data.dbB?.tables || [];
                    
                    // Just use table names for now
                    return { table, hasA: tablesA.includes(table), hasB: tablesB.includes(table) };
                }
                return { table, hasA: true, hasB: true };
            })
            .catch(() => ({ table, hasA: true, hasB: true }));
    });
    
    return Promise.all(promises);
}

// Preview sync operations for multiple tables
function previewSyncDB() {
    const sourceDb = document.getElementById('sourceDb').value;
    const targetDb = document.getElementById('targetDb').value;
    
    // Get selected tables
    const selectedCheckboxes = document.querySelectorAll('.table-checkbox:checked');
    const selectedTables = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedTables.length === 0) {
        showToast('Please select at least one table', 'warning');
        return;
    }
    
    showLoader('Generating preview...');
    
    const tablesParam = selectedTables.join(',');
    fetch(`../api/sync_records.php?tables=${encodeURIComponent(tablesParam)}&source=${sourceDb}&target=${targetDb}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            
            if (data.success) {
                syncPreviewData = data.data;
                renderMultiTablePreview(data.data, sourceDb, targetDb);
            } else {
                showToast(data.message || 'Failed to generate preview', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error generating preview: ' + error.message, 'error');
        });
}

// Render multi-table preview
function renderMultiTablePreview(data, sourceDb, targetDb) {
    const previewSection = document.getElementById('previewSection');
    const initialState = document.getElementById('initialState');
    
    previewSection.style.display = 'block';
    initialState.style.display = 'none';
    
    // Update summary cards
    document.getElementById('previewTables').textContent = data.summary.total_tables;
    document.getElementById('previewSourceRows').textContent = formatNumber(data.summary.total_source_rows);
    document.getElementById('previewTargetRows').textContent = formatNumber(data.summary.total_target_rows);
    document.getElementById('previewCreate').textContent = formatNumber(data.summary.total_create);
    document.getElementById('previewUpdate').textContent = formatNumber(data.summary.total_update);
    
    // Update warning text
    const warningEl = document.getElementById('syncWarning');
    const totalOps = data.summary.total_create + data.summary.total_update;
    warningEl.textContent = `This will ${document.getElementById('optDryRun').checked ? 'simulate' : 'execute'} ${formatNumber(totalOps)} database operations across ${data.summary.total_tables} tables`;
    
    // Build table-level previews
    const container = document.getElementById('tablePreviews');
    let html = '';
    
    html += `
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Table-by-Table Preview</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Table</th>
                                <th class="text-center">Source Rows</th>
                                <th class="text-center">Target Rows</th>
                                <th class="text-center"><span class="badge bg-success">CREATE</span></th>
                                <th class="text-center"><span class="badge bg-warning text-dark">UPDATE</span></th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    data.tables.forEach(table => {
        const tableData = data.table_previews[table];
        
        if (tableData && !tableData.error) {
            const createCount = tableData.sync_preview.create_count;
            const updateCount = tableData.sync_preview.update_count;
            const hasChanges = createCount > 0 || updateCount > 0;
            
            html += `
                <tr>
                    <td><strong>${escapeHtml(table)}</strong></td>
                    <td class="text-center">${formatNumber(tableData.counts.source)}</td>
                    <td class="text-center">${formatNumber(tableData.counts.target)}</td>
                    <td class="text-center">${createCount > 0 ? `<span class="badge bg-success">${formatNumber(createCount)}</span>` : '-'}</td>
                    <td class="text-center">${updateCount > 0 ? `<span class="badge bg-warning text-dark">${formatNumber(updateCount)}</span>` : '-'}</td>
                    <td>${hasChanges ? '<span class="badge bg-primary">Needs Sync</span>' : '<span class="badge bg-secondary">Up to Date</span>'}</td>
                </tr>
            `;
        } else {
            html += `
                <tr class="table-danger">
                    <td><strong>${escapeHtml(table)}</strong></td>
                    <td colspan="4" class="text-danger">${tableData?.error || 'Error loading table data'}</td>
                </tr>
            `;
        }
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Add detailed preview for tables with changes
    data.tables.forEach(table => {
        const tableData = data.table_previews[table];
        
        if (tableData && !tableData.error && (tableData.sync_preview.create_count > 0 || tableData.sync_preview.update_count > 0)) {
            html += renderTableDetailPreview(tableData, sourceDb, targetDb);
        }
    });
    
    container.innerHTML = html;
}

// Render detailed preview for a single table
function renderTableDetailPreview(tableData, sourceDb, targetDb) {
    let html = '';
    const sourceName = sourceDb === 'db_a' ? 'DB A' : 'DB B';
    const targetName = targetDb === 'db_a' ? 'DB A' : 'DB B';
    
    // CREATE section
    if (tableData.sync_preview.create_count > 0) {
        html += `
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>${escapeHtml(tableData.table)}: CREATE (${tableData.sync_preview.create_count} records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-success"><i class="fas fa-info-circle me-1"></i> New records to insert from <strong>${sourceName}</strong></p>
                    ${renderPreviewTable(tableData.preview_rows.to_create, tableData.columns)}
                </div>
            </div>
        `;
    }
    
    // UPDATE section
    if (tableData.sync_preview.update_count > 0) {
        html += `
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>${escapeHtml(tableData.table)}: UPDATE (${tableData.sync_preview.update_count} records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-warning"><i class="fas fa-info-circle me-1"></i> Records with different data</p>
                    ${renderUpdatePreviewTable(tableData.preview_rows.to_update, tableData.columns, sourceName, targetName)}
                </div>
            </div>
        `;
    }
    
    return html;
}

// Render preview table for CREATE
function renderPreviewTable(rows, columns) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No records to display.</div>';
    }
    
    const displayColumns = columns.slice(0, 4);
    const hasMore = columns.length > 4;
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead class="table-dark">
                    <tr>
                        ${displayColumns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                        ${hasMore ? '<th>...</th>' : ''}
                    </tr>
                </thead>
                <tbody>
    `;
    
    rows.forEach((row) => {
        html += `<tr class="table-success">`;
        displayColumns.forEach(col => {
            const value = row[col] !== undefined ? escapeHtml(String(row[col])) : '<em class="text-muted">NULL</em>';
            html += `<td>${value}</td>`;
        });
        if (hasMore) {
            html += `<td><em class="text-muted">+${columns.length - 4} more</em></td>`;
        }
        html += '</tr>';
    });
    
    html += `</tbody></table></div>`;
    
    return html;
}

// Render UPDATE preview with side-by-side comparison
function renderUpdatePreviewTable(rows, columns, sourceName, targetName) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No records to display.</div>';
    }
    
    const displayColumns = columns.slice(0, 4);
    const hasMore = columns.length > 4;
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Column</th>
                        <th class="bg-primary text-white">${escapeHtml(sourceName)}</th>
                        <th class="bg-info text-white">${escapeHtml(targetName)}</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    rows.forEach((rowDiff, index) => {
        const dataA = rowDiff.dataA;
        const dataB = rowDiff.dataB;
        
        // Header row for this record
        const pkValue = rowDiff.pk || 'N/A';
        html += `<tr class="table-warning"><td colspan="3" class="text-center fw-bold">Record #${index + 1} (PK: ${escapeHtml(pkValue)})</td></tr>`;
        
        displayColumns.forEach(col => {
            const valA = dataA[col] !== undefined ? escapeHtml(String(dataA[col])) : '<em class="text-muted">NULL</em>';
            const valB = dataB[col] !== undefined ? escapeHtml(String(dataB[col])) : '<em class="text-muted">NULL</em>';
            const isDifferent = String(dataA[col]) !== String(dataB[col]);
            
            html += `<tr>
                <td>${escapeHtml(col)}</td>
                <td class="${isDifferent ? 'bg-warning' : ''}">${valA}</td>
                <td class="${isDifferent ? 'bg-warning' : ''}">${valB}</td>
            </tr>`;
        });
        
        if (hasMore) {
            html += `<tr><td>...</td><td colspan="2" class="text-muted text-center">+${columns.length - 4} more columns</td></tr>`;
        }
    });
    
    html += `</tbody></table></div>`;
    
    return html;
}

// Select all tables
function selectAllTables() {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = true);
    updateSelectedCountDb();
}

// Select none tables
function selectNoneTables() {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCountDb();
}

// Toggle select all tables
function toggleSelectAllTables(source) {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = source.checked);
    updateSelectedCountDb();
}

// Update selected count
function updateSelectedCountDb() {
    // alert("H")
    const count = document.querySelectorAll('.table-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.table-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllTablesCheck');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = count === allCheckboxes.length;
        selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}



// Execute sync for multiple tables
function executeSyncDB() {
    // alert(syncPreviewData,"here")
    // if (!syncPreviewData) {
    //     showToast('Please generate a preview first', 'warning');
    //     return;
    // }
    
    const sourceDb = document.getElementById('sourceDb').value;
    const targetDb = document.getElementById('targetDb').value;
    const createMissing = document.getElementById('optCreate').checked;
    const updateExisting = document.getElementById('optUpdate').checked;
    const dryRun = document.getElementById('optDryRun').checked;
    
    if (!createMissing && !updateExisting) {
        showToast('Please select at least one sync option', 'warning');
        return;
    }
    
    // Get selected tables
    const selectedCheckboxes = document.querySelectorAll('.table-checkbox:checked');
    const selectedTables = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedTables.length === 0) {
        showToast('Please select at least one table', 'warning');
        return;
    }
    
    const totalOps = syncPreviewData.summary.total_create + syncPreviewData.summary.total_update;
    
    const confirmMsg = dryRun 
        ? `This is a DRY RUN - no changes will be made.\n\nPreview: ${syncPreviewData.summary.total_create} CREATE, ${syncPreviewData.summary.total_update} UPDATE operations across ${selectedTables.length} tables.\n\nContinue?`
        : `This will ${createMissing ? 'CREATE ' + formatNumber(syncPreviewData.summary.total_create) + ' new records' : ''}${createMissing && updateExisting ? ' and ' : ''}${updateExisting ? 'UPDATE ' + formatNumber(syncPreviewData.summary.total_update) + ' existing records' : ''} across ${selectedTables.length} tables.\n\nAre you sure you want to continue?`;
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    // Show progress modal

    // console.log("hi");return;
    updateSyncProgress(0, 'Starting sync...');
    showLoader()
    fetch('../api/sync_records.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tables: selectedTables,
            source_db: sourceDb,
            target_db: targetDb,
            options: {
                create_missing: createMissing,
                update_existing: updateExisting,
                dry_run: dryRun
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader()

        
        if (data.success) {
            const result = data.data;
            const summary = result.summary;
            
            let msg = `Sync completed!\n`;
            msg += `Tables: ${summary.total_tables}\n`;
            msg += `CREATE: ${summary.create_success} success${summary.create_failed > 0 ? ', ' + summary.create_failed + ' failed' : ''}\n`;
            msg += `UPDATE: ${summary.update_success} success${summary.update_failed > 0 ? ', ' + summary.update_failed + ' failed' : ''}`;
            
            showToast(msg, (summary.create_failed + summary.update_failed) > 0 ? 'warning' : 'success');
            
            // Refresh preview
            previewSyncDB();
        } else {
            showToast('Sync failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoader()
         
        showToast('Sync error: ' + error.message, 'error');
    });
}

// Update sync progress
function updateSyncProgress(percent, message) {
    const bar = document.getElementById('syncProgressBar');
    if (bar) {
        bar.style.width = percent + '%';
        bar.textContent = percent + '%';
    }
    const detailsEl = document.getElementById('syncProgressDetails');
    if (detailsEl) {
        detailsEl.innerHTML = message;
    }
}

// Format number with thousand separators
function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return num.toLocaleString();
}
</script>

<?php
require_once '../templates/footer.php';
?>

