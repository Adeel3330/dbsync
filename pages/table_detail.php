<?php
/**
 * DB Sync - Detailed Table View Page
 * Show both tables side by side with pagination and search
 */

$pageTitle = 'Table Details - DB Sync';
$currentPage = 'table_detail';

require_once '../templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-search me-2"></i>Detailed Table View</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadTableData()">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Table Selection -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Select Table</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Table Name</label>
                <select class="form-select" id="detailTableSelect" onchange="loadTableData()">
                    <option value="">-- Select a table --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Database</label>
                <select class="form-select" id="detailDbSelect" onchange="loadTableData()">
                    <option value="db_a">Database A</option>
                    <option value="db_b">Database B</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="detailSearch" placeholder="Search in results..." onkeyup="debounce(loadTableData, 300)">
            </div>
            <div class="col-md-2">
                <button class="btn btn-info w-100" onclick="loadTableData()">
                    <i class="fas fa-search me-1"></i> Search
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Data Display -->
<div id="tableDetailContent" style="display: none;">
    <!-- Info Bar -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <strong id="currentTableName"></strong>
                    <span class="text-muted ms-2" id="currentDbLabel"></span>
                </div>
                <div class="col-md-3">
                    <span class="badge bg-primary" id="rowCount">-</span> total rows
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-sm btn-success" onclick="exportCurrentTable()">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <select class="form-select form-select-sm d-inline-block w-auto" id="detailLimit" onchange="loadTableData()">
                <option value="25">25 rows</option>
                <option value="50" selected>50 rows</option>
                <option value="100">100 rows</option>
                <option value="250">250 rows</option>
            </select>
        </div>
        <nav>
            <ul class="pagination mb-0" id="detailPagination">
                <!-- Pagination items will be inserted here -->
            </ul>
        </nav>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark" id="detailTableHead">
                <!-- Headers will be inserted here -->
            </thead>
            <tbody id="detailTableBody">
                <!-- Data will be inserted here -->
            </tbody>
        </table>
    </div>

    <!-- Pagination Info -->
    <div class="text-center mt-3">
        <span class="text-muted" id="paginationInfo">Showing 0-0 of 0 rows</span>
    </div>
</div>

<!-- Initial State -->
<div id="tableDetailInitial">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-table fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Select a Table</h5>
            <p class="text-muted">Choose a table to view its data in detail</p>
        </div>
    </div>
</div>

<script>
// Current state
let currentOffset = 0;
let currentData = [];

// Load tables on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/get_summary.php')
        .then(response => response.json())
        .then(data => {
            console.log(data.data)
            if (data.success) {
                const select = document.getElementById('detailTableSelect');
                const tablesA = data.data.dbA.tables || [];
                const tablesB = data.data.dbB.tables || [];
                const commonTables = tablesA.filter(t => tablesB.includes(t));
                
                select.innerHTML = '<option value="">-- Select a table --</option>' +
                    commonTables.map(table => `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`).join('');
            }
        });
});

function loadTableData() {
    const tableName = document.getElementById('detailTableSelect').value;
    const dbKey = document.getElementById('detailDbSelect').value;
    const limit = document.getElementById('detailLimit').value;
    const search = document.getElementById('detailSearch').value;
    
    if (!tableName) return;
    
    showLoader('Loading table data...');
    
    fetch(`../api/get_table_data.php?table=${encodeURIComponent(tableName)}&db=${dbKey}&limit=${limit}&offset=${currentOffset}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                renderTableDetail(data.data, dbKey);
            } else {
                showToast(data.message || 'Failed to load table data', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showToast('Error loading table data: ' + error.message, 'error');
        });
}

function renderTableDetail(data, dbKey) {
    document.getElementById('tableDetailInitial').style.display = 'none';
    document.getElementById('tableDetailContent').style.display = 'block';
    
    currentData = data.rows;
    
    // Update info
    document.getElementById('currentTableName').textContent = document.getElementById('detailTableSelect').value;
    document.getElementById('currentDbLabel').textContent = dbKey === 'db_a' ? '(Database A)' : '(Database B)';
    document.getElementById('rowCount').textContent = data.rowCount.toLocaleString();
    
    // Render headers
    if (data.rows.length > 0) {
        const columns = Object.keys(data.rows[0]);
        document.getElementById('detailTableHead').innerHTML = '<tr>' + 
            columns.map(col => `<th>${escapeHtml(col)}</th>`).join('') + '</tr>';
        
        // Render body
        document.getElementById('detailTableBody').innerHTML = data.rows.map(row => 
            '<tr>' + columns.map(col => `<td>${escapeHtml(String(row[col] ?? 'NULL'))}</td>`).join('') + '</tr>'
        ).join('');
        
        // Update pagination info
        const start = data.offset + 1;
        const end = Math.min(data.offset + data.rows.length, data.total);
        document.getElementById('paginationInfo').textContent = `Showing ${start}-${end} of ${data.total} rows`;
        
        // Render pagination
        renderPagination(data.total, data.limit, data.offset);
    } else {
        document.getElementById('detailTableHead').innerHTML = '';
        document.getElementById('detailTableBody').innerHTML = '<tr><td class="text-center py-4">No data found</td></tr>';
        document.getElementById('paginationInfo').textContent = 'No rows';
        document.getElementById('detailPagination').innerHTML = '';
    }
}

function renderPagination(total, limit, offset) {
    const totalPages = Math.ceil(total / limit);
    const currentPage = Math.floor(offset / limit) + 1;
    
    let html = '';
    
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <button class="page-link" onclick="goToPage(${offset - limit})">&laquo;</button>
    </li>`;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <button class="page-link" onclick="goToPage(${(i - 1) * limit})">${i}</button>
            </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <button class="page-link" onclick="goToPage(${offset + limit})">&raquo;</button>
    </li>`;
    
    document.getElementById('detailPagination').innerHTML = html;
}

function goToPage(offset) {
    currentOffset = Math.max(0, offset);
    loadTableData();
}

function exportCurrentTable() {
    if (currentData.length > 0) {
        exportToCSV(currentData, document.getElementById('detailTableSelect').value + '.csv');
    } else {
        showToast('No data to export', 'warning');
    }
}
</script>

<?php
require_once '../templates/footer.php';
?>

