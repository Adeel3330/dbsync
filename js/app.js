/**
 * DB Sync - JavaScript Application
 */

// Global variables
let currentPage = 'dashboard';
let configModal = null;
let insertModal = null;
let pendingInsert = null;
let dbNames = { db_a: 'Database A', db_b: 'Database B' };

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    configModal = new bootstrap.Modal(document.getElementById('configModal'));
    insertModal = new bootstrap.Modal(document.getElementById('insertModal'));
    
    // Load initial config
    loadConfig();
    
    // Check database connections
    checkConnections();
});

// Show loader
function showLoader(message = 'Processing...') {
    document.getElementById('loaderText').textContent = message;
    document.getElementById('globalLoader').style.display = 'flex';
}

// Hide loader
function hideLoader() {
    document.getElementById('globalLoader').style.display = 'none';
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    const toastId = 'toast_' + Date.now();
    
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    const icon = {
        'success': 'fa-check',
        'error': 'fa-times',
        'warning': 'fa-exclamation',
        'info': 'fa-info'
    }[type] || 'fa-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-header ${bgClass} text-white">
                <i class="fas ${icon} me-2"></i>
                <strong class="me-auto">DB Sync</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toast = new bootstrap.Toast(document.getElementById(toastId));
    toast.show();
    
    // Remove after hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Show config modal
function showConfigModal() {
    configModal.show();
}

// Load configuration
function loadConfig() {
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
               console.log(data)
            if (data.success && data.data) {
                const select = document.getElementById('tableSelect');
                if (select) {
                    // Get tables from the correct API response structure
                    const tablesA = data.data.dbA?.tables || [];
                    const tablesB = data.data.dbB?.tables || [];
                    
                    // Get common tables (exist in both databases)
                    const commonTables = tablesA.filter(t => tablesB.includes(t));
                    console.log(commonTables)
                    // Build dropdown options
                    if (commonTables.length > 0) {
                        select.innerHTML = '<option value="">-- Select a table --</option>' +
                            commonTables.map(table => 
                                `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`
                            ).join('');
                        
                        // Trigger change to load data
                        select.dispatchEvent(new Event('change'));
                    } else {
                        select.innerHTML = '<option value="">No common tables found</option>';
                        showToast('No common tables found between databases', 'warning');
                    }
                }
            } else {
                showToast(data.message || 'Failed to load tables', 'error');
            }
        })
        .catch(error => {
            console.log('Config may need to be saved');
            showToast(data.error || 'Failed to load dropdown','error')
        });
}

// Save c
// onfiguration
function saveConfig() {
    const form = document.getElementById('configForm');
    const formData = new FormData(form);
    const config = {
        db_a: {
            host: formData.get('db_a[host]'),
            name: formData.get('db_a[name]'),
            username: formData.get('db_a[username]'),
            password: formData.get('db_a[password]'),
            port: parseInt(formData.get('db_a[port]')) || 3306
        },
        db_b: {
            host: formData.get('db_b[host]'),
            name: formData.get('db_b[name]'),
            username: formData.get('db_b[username]'),
            password: formData.get('db_b[password]'),
            port: parseInt(formData.get('db_b[port]')) || 3306
        }
    };
    
    showLoader('Saving configuration...');
    
    fetch('../api/save_config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(config)
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        if (data.success) {
            showToast('Configuration saved successfully', 'success');
            configModal.hide();
            checkConnections();
        } else {
            showToast(data.message || 'Failed to save configuration', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showToast('Error saving configuration: ' + error.message, 'error');
    });
}

// Test database connection
function testConnection(dbKey) {
    const resultId = dbKey === 'db_a' ? 'dbATestResult' : 'dbBTestResult';
    const resultEl = document.getElementById(resultId);
    
    resultEl.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Testing connection...';
    
    fetch('../api/test_connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ db_key: dbKey })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultEl.innerHTML = `<div class="alert alert-success py-2 mb-0"><i class="fas fa-check me-1"></i> ${data.message} (MySQL ${data.version})</div>`;
            showToast(`${dbKey.toUpperCase()} connected successfully`, 'success');
            checkConnections();
        } else {
            resultEl.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times me-1"></i> ${data.message}</div>`;
            showToast(`Connection failed for ${dbKey.toUpperCase()}`, 'error');
        }
    })
    .catch(error => {
        resultEl.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times me-1"></i> Error: ${error.message}</div>`;
    });
}

// Check database connections
function checkConnections() {
    Promise.all([
        testConnectionSilent('db_a'),
        testConnectionSilent('db_b')
    ]).then(([statusA, statusB]) => {
        updateConnectionStatus('dbAStatus', statusA);
        updateConnectionStatus('dbBStatus', statusB);
    });
}

// Test connection silently
function testConnectionSilent(dbKey) {
    return fetch('../api/test_connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ db_key: dbKey })
    })
    .then(response => response.json())
    .then(data => {
        return {
            connected: data.success,
            message: data.message,
            version: data.version || null
        };
    })
    .catch(() => {
        return { connected: false, message: 'Connection failed' };
    });
}

// Update connection status display
function updateConnectionStatus(elementId, status) {
    const el = document.getElementById(elementId);
    if (status.connected) {
        el.innerHTML = `<span class="connection-indicator connected"></span>Connected (${status.message})`;
        el.classList.remove('text-muted');
        el.classList.add('text-success');
    } else {
        el.innerHTML = `<span class="connection-indicator disconnected"></span>${status.message}`;
        el.classList.remove('text-success');
        el.classList.add('text-muted');
    }
}

// Store database names globally when loading summary
function storeDbNames(data) {
    if (data && data.dbNames) {
        dbNames = {
            db_a: data.dbNames.db_a || 'Database A',
            db_b: data.dbNames.db_b || 'Database B'
        };
    }
}

// Get dashboard summary
function getDashboardSummary() {
    showLoader('Loading summary...');
    
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                storeDbNames(data.data);
                updateDashboardCards(data.data);
            } else {
                showToast(data.message || 'Failed to load summary', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading summary: ' + error.message, 'error');
        });
}

// Update dashboard cards
function updateDashboardCards(data) {
    // Update DB A stats - tables is now an array
    document.getElementById('dbATables').textContent = data.dbA.tables?.length || 0;
    document.getElementById('dbAConnected').textContent = data.dbA.connected ? 'Connected' : 'Disconnected';
    
    // Update DB B stats - tables is now an array
    document.getElementById('dbBTables').textContent = data.dbB.tables?.length || 0;
    document.getElementById('dbBConnected').textContent = data.dbB.connected ? 'Connected' : 'Disconnected';
    
    // Update summary
    document.getElementById('missingTables').textContent = data.summary.missingTables;
    document.getElementById('matchedTables').textContent = data.summary.matchedTables;
    document.getElementById('mismatchedTables').textContent = data.summary.mismatchedTables;
    document.getElementById('totalDifferences').textContent = data.summary.totalDifferences;
}

// Compare structures
function compareStructures() {
    showLoader('Comparing structures...');
    
    const limit = document.getElementById('structureLimit')?.value || 100;
    const offset = document.getElementById('structureOffset')?.value || 0;
    
    fetch(`../api/compare_structures.php?limit=${limit}&offset=${offset}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                renderStructureComparison(data.data);
            } else {
                showToast(data.message || 'Failed to compare structures', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error comparing structures: ' + error.message, 'error');
        });
}

// Render structure comparison
function renderStructureComparison(data) {
    const container = document.getElementById('structureResults');
    if (!container) return;
    
    let html = '';
    
    // Missing in A
    if (data.missingInA.length > 0) {
        html += `
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Tables Missing in Database A (${data.missingInA.length})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-dark"><tr><th>Table Name</th></tr></thead>
                            <tbody>
                                ${data.missingInA.map(table => `<tr class="row-missing"><td>${escapeHtml(table)}</td></tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Missing in B
    if (data.missingInB.length > 0) {
        html += `
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Tables Missing in Database B (${data.missingInB.length})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-dark"><tr><th>Table Name</th></tr></thead>
                            <tbody>
                                ${data.missingInB.map(table => `<tr class="row-missing"><td>${escapeHtml(table)}</td></tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Structure differences
    if (data.structureDifferences.length > 0) {
        html += `
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Tables with Structural Differences (${data.structureDifferences.length})</h5>
                </div>
                <div class="card-body">
        `;
        
        data.structureDifferences.forEach(diff => {
            html += `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>${escapeHtml(diff.tableName)}</strong>
                    </div>
                    <div class="card-body">
            `;
            
            // Missing columns
            if (diff.missingColumnsA.length > 0 || diff.missingColumnsB.length > 0) {
                html += `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-danger">Missing in A:</h6>
                            <ul class="list-unstyled mb-0">
                                ${diff.missingColumnsA.map(col => `<li><code>${escapeHtml(col)}</code></li>`).join('')}
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">Missing in B:</h6>
                            <ul class="list-unstyled mb-0">
                                ${diff.missingColumnsB.map(col => `<li><code>${escapeHtml(col)}</code></li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `;
            }
            
            // Column differences
            if (Object.keys(diff.columnDifferences).length > 0) {
                html += `
                    <h6>Column Differences:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Column</th>
                                    <th>Property</th>
                                    <th>DB A</th>
                                    <th>DB B</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                Object.entries(diff.columnDifferences).forEach(([colName, diffs]) => {
                    Object.entries(diffs).forEach(([prop, values]) => {
                        html += `
                            <tr class="row-different">
                                <td>${escapeHtml(colName)}</td>
                                <td>${escapeHtml(prop)}</td>
                                <td>${escapeHtml(String(values.a))}</td>
                                <td>${escapeHtml(String(values.b))}</td>
                            </tr>
                        `;
                    });
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            html += `</div></div>`;
        });
        
        html += `</div></div>`;
    }
    
    // Common tables
    html += `
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Matching Tables (${data.commonTables.length})</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    ${data.commonTables.slice(0, 20).map(table => `
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="badge bg-success w-100 py-2">${escapeHtml(table)}</div>
                        </div>
                    `).join('')}
                    ${data.commonTables.length > 20 ? `<div class="col-12"><small class="text-muted">...and ${data.commonTables.length - 20} more</small></div>` : ''}
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html || '<div class="alert alert-info">No comparison data available. Please configure both databases first.</div>';
}

// Load tables for dropdown
function loadTablesDropdown() {
    showLoader('Loading tables...');
    
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            console.log(data)
            if (data.success && data.data) {
                // Store database names for use in insert modal
                storeDbNames(data.data);
                
                const select = document.getElementById('tableSelect');
                if (select) {
                    // Get tables from the correct API response structure
                    const tablesA = data.data.dbA?.tables || [];
                    const tablesB = data.data.dbB?.tables || [];
                    
                    // Get common tables (exist in both databases)
                    const commonTables = tablesA.filter(t => tablesB.includes(t));
                    console.log(commonTables)
                    // Build dropdown options
                    if (commonTables.length > 0) {
                        select.innerHTML = '<option value="">-- Select a table --</option>' +
                            commonTables.map(table => 
                                `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`
                            ).join('');
                        
                        // Trigger change to load data
                        select.dispatchEvent(new Event('change'));
                    } else {
                        select.innerHTML = '<option value="">No common tables found</option>';
                        showToast('No common tables found between databases', 'warning');
                    }
                }
            } else {
                showToast(data.message || 'Failed to load tables', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading tables: ' + error.message, 'error');
        });
}

// Compare records for selected table
function compareRecordsForTable() {
    const select = document.getElementById('tableSelect');
    const comparisonSummary = document.getElementById('comparisonSummary');
    const initialState = document.getElementById('initialState');
    const tableName = select?.value;
    
    if (!tableName) return;
    
    showLoader('Comparing records...');
    
    fetch(`../api/compare_records.php?table=${encodeURIComponent(tableName)}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                comparisonSummary.style.display = 'block'
                initialState.style.display = 'none'
                renderRecordComparison(data.data, tableName);

            } else {
                comparisonSummary.style.display = 'none'
                initialState.style.display = 'block'
                showToast(data.message || 'Failed to compare records', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            comparisonSummary.style.display = 'none'
                initialState.style.display = 'block'
            showToast('Error comparing records: ' + error.message, 'error');
        });
}

// Render record comparison
function renderRecordComparison(data, tableName) {
    console.log(data,tableName)
    const container = document.getElementById('recordResults');
    if (!container) return;
    
    // Update summary
    document.getElementById('totalA').textContent = data.totalA;
    document.getElementById('totalB').textContent = data.totalB;
    document.getElementById('matched').textContent = data.matched;
    document.getElementById('missingInA').textContent = data.missingInA;
    document.getElementById('missingInB').textContent = data.missingInB;
    document.getElementById('differentData').textContent = data.differentData;
    
    let html = `
        <div class="alert alert-info mb-3">
            <strong>Primary Keys:</strong> ${data.primaryKeys.join(', ')}<br>
            <strong>Columns:</strong> ${data.columns?.join(', ') || 'N/A'}
        </div>
    `;
    
    // Missing in A (exist in B but not in A)
    if (data.missingInA > 0 && data.missingInA_rows && data.missingInA_rows.length > 0) {
        html += `
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-trash me-2"></i>Rows Missing in DB A (from DB B): ${data.missingInA}</h5>
                </div>
                <div class="card-body">
                    ${renderRowsTable(data.missingInA_rows, data.columns, 'db_a', 'db_b', tableName, 'missing')}
                </div>
            </div>
        `;
    }
    
    // Missing in B (exist in A but not in B)
    if (data.missingInB > 0 && data.missingInB_rows && data.missingInB_rows.length > 0) {
        html += `
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-trash me-2"></i>Rows Missing in DB B (from DB A): ${data.missingInB}</h5>
                </div>
                <div class="card-body">
                    ${renderRowsTable(data.missingInB_rows, data.columns, 'db_a', 'db_b', tableName, 'missing')}
                </div>
            </div>
        `;
    }
    
    // Different data
    if (data.differentData > 0 && data.differentData_rows && data.differentData_rows.length > 0) {
        html += `
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Rows with Different Data: ${data.differentData}</h5>
                </div>
                <div class="card-body">
                    ${renderDifferentRowsTable(data.differentData_rows, data.columns, 'db_a', 'db_b', tableName)}
                </div>
            </div>
        `;
    }
    
    // Matched rows
    html += `
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check me-2"></i>Matching Rows: ${data.matched}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">These rows have identical data in both databases.</p>
            </div>
        </div>
    `;
    console.log(html,container)
    container.innerHTML = html;

    // display show
    
    // Store data globally for insert
    window.recordComparisonData = data;
}

// Render rows table with checkboxes for bulk selection
function renderRowsTable(rows, columns, sourceDb, targetDb, tableName, type) {
    console.log(rows)
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No rows to display.</div>';
    }
    
    const displayColumns = columns || Object.keys(rows[0]);
    
    let html = `
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAllMissing" onchange="toggleSelectAllMissing()">
                <label class="form-check-label fw-bold" for="selectAllMissing">Select All</label>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="missingRowsTable">
                <thead class="table-dark">
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="selectAllHeader" onchange="toggleSelectAll(this)">
                        </th>
                        ${displayColumns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    rows.forEach((row, index) => {
        const rowId = `missing_row_${index}`;
        html += `<tr class="row-missing">`;
        html += `<td><input type="checkbox" class="form-check-input row-checkbox" id="${rowId}" data-index="${index}"></td>`;
        displayColumns.forEach(col => {
            const value = row[col] !== undefined ? escapeHtml(String(row[col])) : '<em class="text-muted">NULL</em>';
            html += `<td>${value}</td>`;
        });
        html += `
            <td>
                <button class="btn btn-sm btn-success" onclick="insertMissingRow('${sourceDb}', '${targetDb}', '${escapeHtml(tableName)}', ${index}, '${type}')">
                    <i class="fas fa-plus me-1"></i>Insert
                </button>
            </td>
        </tr>`;
    });
    
    html += `</tbody></table></div>`;
    
    // Add bulk insert button
    html += `
        <div class="mt-3">
            <button class="btn btn-success" onclick="bulkInsertMissing('${sourceDb}', '${targetDb}', '${escapeHtml(tableName)}', '${type}')">
                <i class="fas fa-plus-circle me-1"></i>Insert Selected Rows
            </button>
            <span class="text-muted ms-2" id="selectedCount">0 selected</span>
        </div>
    `;
    
    console.log(html)
    
    return html;
}

// Toggle select all checkboxes
function toggleSelectAll(sourceDb, targetDb, tableName, type) {
    const checkbox = sourceDb; // This is actually the checkbox element
    const table = checkbox.closest('table');
    const checkboxes = table.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
}

// Toggle select all from header checkbox
function toggleSelectAllMissing() {
    const headerCheckbox = document.getElementById('selectAllMissing');
    const table = document.getElementById('missingRowsTable');
    if (table) {
        const checkboxes = table.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = headerCheckbox.checked);
        updateSelectedCount();
    }
}

// Update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const countEl = document.getElementById('selectedCount');
    if (countEl) {
        countEl.textContent = `${checkboxes.length} selected`;
    }
}

// Add event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateSelectedCount();
        }
    });
});

// Bulk insert missing rows
function bulkInsertMissing(sourceDb, targetDb, tableName, type) {
    if (!window.recordComparisonData) {
        showToast('Comparison data not found', 'error');
        return;
    }
    
    // Get selected rows
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Please select at least one row', 'warning');
        return;
    }
    
    // Collect selected row data
    const selectedRows = [];
    checkboxes.forEach(cb => {
        const index = parseInt(cb.dataset.index);
        let rowData;
        if (type === 'missing') {
            if (sourceDb === 'db_a') {
                rowData = window.recordComparisonData.missingInB_rows[index];
            } else {
                rowData = window.recordComparisonData.missingInA_rows[index];
            }
        }
        if (rowData) {
            selectedRows.push(rowData);
        }
    });
    
    if (selectedRows.length === 0) {
        showToast('No valid rows selected', 'error');
        return;
    }
    
    // Confirm bulk insert
    if (!confirm(`Are you sure you want to insert ${selectedRows.length} rows from ${dbNames[sourceDb] || sourceDb} to ${dbNames[targetDb] || targetDb}?`)) {
        return;
    }
    
    // Perform bulk insert via API
    showLoader(`Inserting ${selectedRows.length} rows...`);
    
    fetch('../api/insert_row.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            table: tableName,
            source_db: sourceDb,
            target_db: targetDb,
            rows: selectedRows,
            bulk: true
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            const successCount = data.data?.success?.length || 0;
            const failedCount = data.data?.failed?.length || 0;
            
            showToast(`Inserted ${successCount} rows successfully${failedCount > 0 ? `, ${failedCount} failed` : ''}`, 
                      failedCount > 0 ? 'warning' : 'success');
            
            // Reload comparison
            compareRecordsForTable();
        } else {
            showToast('Insert failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showToast('Insert error: ' + error.message, 'error');
    });
}

// Render different rows table (side by side comparison)
function renderDifferentRowsTable(rows, columns, sourceDb, targetDb, tableName) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No different rows to display.</div>';
    }
    
    const displayColumns = columns || Object.keys(rows[0].dataA || {});
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="differentRowsTable">
                <thead class="table-dark">
                    <tr>
                        <th>Column</th>
                        <th class="bg-primary text-white">DB A Value</th>
                        <th class="bg-info text-white">DB B Value</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    rows.forEach((rowDiff, rowIndex) => {
        const dataA = rowDiff.dataA;
        const dataB = rowDiff.dataB;
        
        html += `<tr class="row-different"><td colspan="3" class="bg-light text-center"><strong>Row #${rowIndex + 1} (PK: ${escapeHtml(rowDiff.pk)})</strong></td></tr>`;
        
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
    });
    
    html += `</tbody></table></div>`;
    
    return html;
}

// Insert missing row
function insertMissingRow(sourceDb, targetDb, tableName, rowIndex, type) {
    if (!window.recordComparisonData) {
        showToast('Comparison data not found', 'error');
        return;
    }
    
    let rowData;
    if (type === 'missing') {
        if (sourceDb === 'db_a') {
            rowData = window.recordComparisonData.missingInB_rows[rowIndex];
        } else {
            rowData = window.recordComparisonData.missingInA_rows[rowIndex];
        }
    }
    
    if (!rowData) {
        showToast('Row data not found', 'error');
        return;
    }
    
    pendingInsert = {
        sourceDb,
        targetDb,
        tableName,
        rowData: rowData
    };
    // Update modal with actual database names
    document.getElementById('insertSourceDb').textContent = dbNames[sourceDb] || sourceDb.toUpperCase();
    document.getElementById('insertTargetDb').textContent = dbNames[targetDb] || targetDb.toUpperCase();
    
    // Build table
    const columns = Object.keys(rowData);
    document.getElementById('insertRowHeader').innerHTML = columns.map(col => `<th>${escapeHtml(col)}</th>`).join('');
    document.getElementById('insertRowBody').innerHTML = `
        <tr>${columns.map(col => `<td>${escapeHtml(String(rowData[col] || 'NULL'))}</td>`).join('')}</tr>
    `;
    
    insertModal.show();
}

// Load missing rows
function loadMissingRows(sourceDb, tableName, targetDb) {
    showLoader('Loading missing rows...');
    
    fetch(`../api/get_missing_rows.php?source=${sourceDb}&table=${encodeURIComponent(tableName)}&target=${targetDb}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                const wrapperId = sourceDb === 'db_a' ? 'missingInBWrapper' : 'missingInAWrapper';
                renderMissingRowsTable(data.data, wrapperId, sourceDb, targetDb, tableName);
            } else {
                showToast(data.message || 'Failed to load missing rows', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading missing rows: ' + error.message, 'error');
        });
}

// Render missing rows table
function renderMissingRowsTable(rows, wrapperId, sourceDb, targetDb, tableName) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper || rows.length === 0) {
        wrapper.innerHTML = '<div class="alert alert-info">No rows found.</div>';
        return;
    }
    
    const columns = Object.keys(rows[0]);
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        ${columns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    rows.forEach((row, index) => {
        html += `<tr class="row-missing">`;
        columns.forEach(col => {
            html += `<td>${escapeHtml(String(row[col] || 'NULL'))}</td>`;
        });
        html += `
            <td>
                <button class="btn btn-sm btn-success" onclick="prepareInsert('${sourceDb}', '${targetDb}', '${escapeHtml(tableName)}', ${index})">
                    <i class="fas fa-plus me-1"></i>Insert
                </button>
            </td>
        </tr>`;
    });
    
    html += `</tbody></table></div>`;
    wrapper.innerHTML = html;
    
    // Store rows globally for insert
    window.missingRowsData = { rows, sourceDb, targetDb, tableName };
}

// Prepare insert
function prepareInsert(sourceDb, targetDb, tableName, rowIndex) {
    if (!window.missingRowsData || !window.missingRowsData.rows[rowIndex]) {
        showToast('Row data not found', 'error');
        return;
    }
    
    const row = window.missingRowsData.rows[rowIndex];
    
    pendingInsert = {
        sourceDb,
        targetDb,
        tableName,
        rowData: row
    };
    
    // Update modal with actual database names
    document.getElementById('insertSourceDb').textContent = dbNames[sourceDb] || sourceDb.toUpperCase();
    document.getElementById('insertTargetDb').textContent = dbNames[targetDb] || targetDb.toUpperCase();
    
    // Build table
    const columns = Object.keys(row);
    document.getElementById('insertRowHeader').innerHTML = columns.map(col => `<th>${escapeHtml(col)}</th>`).join('');
    document.getElementById('insertRowBody').innerHTML = `
        <tr>${columns.map(col => `<td>${escapeHtml(String(row[col] || 'NULL'))}</td>`).join('')}</tr>
    `;
    
    insertModal.show();
}

// Confirm insert
function confirmInsert() {
    if (!pendingInsert) return;
    
    showLoader('Inserting row...');
    
    fetch('../api/insert_row.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            table: pendingInsert.tableName,
            source_db: pendingInsert.sourceDb,
            target_db: pendingInsert.targetDb,
            row_data: pendingInsert.rowData
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        insertModal.hide();
        
        if (data.success) {
            showToast('Row inserted successfully!', 'success');
            // Reload comparison
            compareRecordsForTable();
        } else {
            showToast('Insert failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoader();
        insertModal.hide();
        showToast('Insert error: ' + error.message, 'error');
    });
    
    pendingInsert = null;
}

// Export to CSV
function exportToCSV(data, filename) {
    if (!data || data.length === 0) {
        showToast('No data to export', 'warning');
        return;
    }
    
    const columns = Object.keys(data[0]);
    const csvContent = [
        columns.join(','),
        ...data.map(row => columns.map(col => {
            const value = row[col] !== null ? String(row[col]) : '';
            return `"${value.replace(/"/g, '""')}"`;
        }).join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.click();
    
    showToast('Export completed', 'success');
}

// Escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#039;');
}

// Get logs
function loadLogs() {
    showLoader('Loading logs...');
    
    const limit = document.getElementById('logLimit')?.value || 100;
    const type = document.getElementById('logType')?.value || '';
    
    fetch(`../api/get_logs.php?limit=${limit}&type=${encodeURIComponent(type)}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                renderLogs(data.data);
            } else {
                showToast(data.message || 'Failed to load logs', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading logs: ' + error.message, 'error');
        });
}

// Render logs
function renderLogs(data) {
    const container = document.getElementById('logsTable');
    if (!container) return;
    
    // Update counts
    document.getElementById('logTotal').textContent = data.counts.total;
    document.getElementById('logErrors').textContent = data.counts.ERROR;
    document.getElementById('logSuccess').textContent = data.counts.SUCCESS;
    document.getElementById('logInfo').textContent = data.counts.INFO;
    
    if (data.logs.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No logs found.</div>';
        return;
    }
    
    let html = `
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    data.logs.forEach(log => {
        const typeClass = {
            'ERROR': 'danger',
            'SUCCESS': 'success',
            'WARNING': 'warning',
            'INFO': 'primary'
        }[log.type] || 'secondary';
        
        html += `
            <tr>
                <td><small>${escapeHtml(log.timestamp)}</small></td>
                <td><span class="badge bg-${typeClass}">${escapeHtml(log.type)}</span></td>
                <td>${escapeHtml(log.message)}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Format bytes
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Export all functions for global use
window.showLoader = showLoader;
window.hideLoader = hideLoader;
window.showToast = showToast;
window.showConfigModal = showConfigModal;
window.saveConfig = saveConfig;
window.testConnection = testConnection;
window.getDashboardSummary = getDashboardSummary;
window.compareStructures = compareStructures;
window.compareRecordsForTable = compareRecordsForTable;
window.insertMissingRow = insertMissingRow;
window.loadMissingRows = loadMissingRows;
window.prepareInsert = prepareInsert;
window.confirmInsert = confirmInsert;
window.exportToCSV = exportToCSV;
window.loadLogs = loadLogs;
window.debounce = debounce;

// =====================================================
// SYNC RECORDS FUNCTIONS
// =====================================================

/**
 * Preview sync operations for selected table
 */
function previewSync() {
    const sourceDb = document.getElementById('sourceDb')?.value || 'db_a';
    const targetDb = document.getElementById('targetDb')?.value || 'db_b';
    const tableName = document.getElementById('tableSelect')?.value;
    
    if (!tableName) {
        const previewSection = document.getElementById('previewSection');
        const initialState = document.getElementById('initialState');
        if (previewSection) previewSection.style.display = 'none';
        if (initialState) initialState.style.display = 'block';
        return;
    }
    
    showLoader('Generating preview...');
    
    fetch(`../api/sync_records.php?table=${encodeURIComponent(tableName)}&source=${sourceDb}&target=${targetDb}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            
            if (data.success) {
                window.syncPreviewData = data.data;
                renderSyncPreview(data.data, sourceDb, targetDb);
            } else {
                showToast(data.message || 'Failed to generate preview', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error generating preview: ' + error.message, 'error');
        });
}

/**
 * Render sync preview with CREATE and UPDATE counts
 */
function renderSyncPreview(data, sourceDb, targetDb) {
    const previewSection = document.getElementById('previewSection');
    const initialState = document.getElementById('initialState');
    
    if (previewSection) {
        previewSection.style.display = 'block';
    }
    if (initialState) {
        initialState.style.display = 'none';
    }
    
    // Update summary cards
    const sourceRowsEl = document.getElementById('previewSourceRows');
    const targetRowsEl = document.getElementById('previewTargetRows');
    const createEl = document.getElementById('previewCreate');
    const updateEl = document.getElementById('previewUpdate');
    
    if (sourceRowsEl) sourceRowsEl.textContent = data.counts.source;
    if (targetRowsEl) targetRowsEl.textContent = data.counts.target;
    if (createEl) createEl.textContent = data.sync_preview.create_count;
    if (updateEl) updateEl.textContent = data.sync_preview.update_count;
    
    // Build preview tables
    const container = document.getElementById('previewTables');
    if (!container) return;
    
    let html = '';
    const sourceName = sourceDb === 'db_a' ? 'DB A' : 'DB B';
    const targetName = targetDb === 'db_a' ? 'DB A' : 'DB B';
    
    // CREATE Preview
    if (data.sync_preview.create_count > 0) {
        html += `
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>CREATE Operations (${data.sync_preview.create_count} records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-success"><i class="fas fa-info-circle me-1"></i> These records exist in <strong>${sourceName}</strong> but NOT in <strong>${targetName}</strong></p>
                    ${renderSyncPreviewTable(data.preview_rows.to_create, data.columns, 'create')}
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="card mb-4 border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>CREATE Operations (0 records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">No new records to create. All records in source already exist in target.</p>
                </div>
            </div>
        `;
    }
    
    // UPDATE Preview
    if (data.sync_preview.update_count > 0) {
        html += `
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>UPDATE Operations (${data.sync_preview.update_count} records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-warning"><i class="fas fa-info-circle me-1"></i> These records exist in <strong>BOTH</strong> databases but have <strong>different data</strong></p>
                    ${renderSyncUpdatePreviewTable(data.preview_rows.to_update, data.columns)}
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="card mb-4 border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>UPDATE Operations (0 records)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">No records to update. All matching records have identical data.</p>
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

/**
 * Render preview table for CREATE operations
 */
function renderSyncPreviewTable(rows, columns, type) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No records to display.</div>';
    }
    
    const displayColumns = columns.slice(0, 5);
    const hasMore = columns.length > 5;
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        ${displayColumns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                        ${hasMore ? '<th>...</th>' : ''}
                    </tr>
                </thead>
                <tbody>
    `;
    
    rows.forEach((row) => {
        html += `<tr class="${type === 'create' ? 'table-success' : 'table-warning'}">`;
        displayColumns.forEach(col => {
            const value = row[col] !== undefined ? escapeHtml(String(row[col])) : '<em class="text-muted">NULL</em>';
            html += `<td>${value}</td>`;
        });
        if (hasMore) {
            html += `<td><em class="text-muted">+${columns.length - 5} more columns</em></td>`;
        }
        html += '</tr>';
    });
    
    html += `</tbody></table></div>`;
    
    return html;
}

/**
 * Render UPDATE preview with side-by-side comparison
 */
function renderSyncUpdatePreviewTable(rows, columns) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info">No records to display.</div>';
    }
    
    const displayColumns = columns.slice(0, 5);
    const hasMore = columns.length > 5;
    const sourceDbSelect = document.getElementById('sourceDb');
    const targetDbSelect = document.getElementById('targetDb');
    const sourceName = sourceDbSelect?.options[sourceDbSelect?.selectedIndex]?.text || 'Source';
    const targetName = targetDbSelect?.options[targetDbSelect?.selectedIndex]?.text || 'Target';
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
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
            html += `<tr><td>...</td><td colspan="2" class="text-muted text-center">+${columns.length - 5} more columns</td></tr>`;
        }
    });
    
    html += `</tbody></table></div>`;
    
    return html;
}

// Helper function to properly hide modal and cleanup
function hideSyncModal() {
    const modalEl = document.getElementById('syncProgressModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
        // Remove any remaining backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(bp => bp.remove());
        // Remove body modal classes
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }
    hideLoader();
}

/**
 * Execute the sync operation
 */
// function executeSync() {
//     if (!window.syncPreviewData) {
//         showToast('Please generate a preview first', 'warning');
//         return;
//     }
    
//     const sourceDb = document.getElementById('sourceDb')?.value;
//     const targetDb = document.getElementById('targetDb')?.value;
//     const tableName = document.getElementById('tableSelect')?.value;
//     const createMissing = document.getElementById('optCreate')?.checked;
//     const updateExisting = document.getElementById('optUpdate')?.checked;
//     const dryRun = document.getElementById('optDryRun')?.checked;
    
//     if (!createMissing && !updateExisting) {
//         showToast('Please select at least one sync option', 'warning');
//         return;
//     }
    
//     const totalOps = (createMissing ? window.syncPreviewData.sync_preview.create_count : 0) + 
//                      (updateExisting ? window.syncPreviewData.sync_preview.update_count : 0);
    
//     const confirmMsg = dryRun 
//         ? `This is a DRY RUN - no changes will be made.\n\nPreview: ${window.syncPreviewData.sync_preview.create_count} CREATE, ${window.syncPreviewData.sync_preview.update_count} UPDATE operations would be executed.\n\nContinue?`
//         : `This will ${createMissing ? 'CREATE ' + window.syncPreviewData.sync_preview.create_count + ' new records' : ''}${createMissing && updateExisting ? ' and ' : ''}${updateExisting ? 'UPDATE ' + window.syncPreviewData.sync_preview.update_count + ' existing records' : ''}.\n\nAre you sure you want to continue?`;
    
//     if (!confirm(confirmMsg)) {
//         return;
//     }
    
//     // Show progress modal
//     const progressModal = document.getElementById('syncProgressModal');
//     if (progressModal) {
//         new bootstrap.Modal(progressModal).show();
//         updateSyncProgress(0, 'Starting sync...');
//     }
    
//     fetch('../api/sync_records.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json'
//         },
//         body: JSON.stringify({
//             table: tableName,
//             source_db: sourceDb,
//             target_db: targetDb,
//             options: {
//                 create_missing: createMissing,
//                 update_existing: updateExisting,
//                 dry_run: dryRun
//             }
//         })
//     })
//     .then(response => response.json())
//     .then(data => {
//         hideSyncModal();
        
//         if (data.success) {
//             const result = data.data;
//             const createSuccess = result.create.success;
//             const createFailed = result.create.failed;
//             const updateSuccess = result.update.success;
//             const updateFailed = result.update.failed;
            
//             let msg = `Sync completed!\n`;
//             if (createMissing) {
//                 msg += `CREATE: ${createSuccess} success${createFailed > 0 ? ', ' + createFailed + ' failed' : ''}\n`;
//             }
//             if (updateExisting) {
//                 msg += `UPDATE: ${updateSuccess} success${updateFailed > 0 ? ', ' + updateFailed + ' failed' : ''}`;
//             }
            
//             showToast(msg, createFailed + updateFailed > 0 ? 'warning' : 'success');
            
//             // Refresh preview
//             previewSync();
//         } else {
//             showToast('Sync failed: ' + data.message, 'error');
//         }
//     })
//     .catch(error => {
//         hideSyncModal();
//         showToast('Sync error: ' + error.message, 'error');
//     });
// }

/**
 * Update sync progress bar
 */
// function updateSyncProgress(percent, message) {
//     const bar = document.getElementById('syncProgressBar');
//     if (bar) {
//         bar.style.width = percent + '%';
//         bar.textContent = percent + '%';
//     }
//     const detailsEl = document.getElementById('syncProgressDetails');
//     if (detailsEl) {
//         detailsEl.innerHTML = message;
//     }
// }

/**
 * Load tables for sync dropdown
 */
function loadTablesForSync() {
    showLoader('Loading tables...');
    
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success && data.data) {
                const select = document.getElementById('tableSelect');
                if (select) {
                    const tablesA = data.data.dbA?.tables || [];
                    const tablesB = data.data.dbB?.tables || [];
                    
                    // Get common tables
                    const commonTables = tablesA.filter(t => tablesB.includes(t));
                    
                    select.innerHTML = '<option value="">-- Select a table --</option>' +
                        commonTables.map(table => `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`).join('');
                    
                    window.commonTables = commonTables;
                }
            } else {
                showToast('Failed to load tables', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading tables: ' + error.message, 'error');
        });
}


// window.executeSync = executeSync;
window.loadTablesForSync = loadTablesForSync;
// window.updateSyncProgress = updateSyncProgress;
window.renderSyncPreview = renderSyncPreview;
window.selectAllTables = selectAllTables;
window.selectNoneTables = selectNoneTables;
window.toggleSelectAllTables = toggleSelectAllTables;
window.updateSelectedCount = updateSelectedCount;
window.formatNumber = formatNumber;

