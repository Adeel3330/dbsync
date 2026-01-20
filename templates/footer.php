            </main>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div class="modal fade" id="configModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-database me-2"></i>Database Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="configForm">
                        <div class="row">
                            <!-- Database A -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-database me-2"></i>Database A</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Host</label>
                                            <input type="text" class="form-control" name="db_a[host]" value="localhost" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Database Name</label>
                                            <input type="text" class="form-control" name="db_a[name]" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="db_a[username]" value="root" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="db_a[password]">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Port</label>
                                            <input type="number" class="form-control" name="db_a[port]" value="3306">
                                        </div>
                                        <button type="button" class="btn btn-outline-primary w-100" onclick="testConnection('db_a')">
                                            <i class="fas fa-plug me-1"></i>Test Connection A
                                        </button>
                                        <div id="dbATestResult" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Database B -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-database me-2"></i>Database B</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Host</label>
                                            <input type="text" class="form-control" name="db_b[host]" value="localhost" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Database Name</label>
                                            <input type="text" class="form-control" name="db_b[name]" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="db_b[username]" value="root" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="db_b[password]">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Port</label>
                                            <input type="number" class="form-control" name="db_b[port]" value="3306">
                                        </div>
                                        <button type="button" class="btn btn-outline-success w-100" onclick="testConnection('db_b')">
                                            <i class="fas fa-plug me-1"></i>Test Connection B
                                        </button>
                                        <div id="dbBTestResult" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveConfig()">
                        <i class="fas fa-save me-1"></i>Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Insert Row Modal -->
    <div class="modal fade" id="insertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Insert Missing Row</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You are about to insert this row from <strong id="insertSourceDb"></strong> to <strong id="insertTargetDb"></strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="insertRowTable">
                            <thead class="table-dark">
                                <tr id="insertRowHeader"></tr>
                            </thead>
                            <tbody id="insertRowBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="confirmInsert()">
                        <i class="fas fa-check me-1"></i>Confirm Insert
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="../js/app.js"></script>
</body>
</html>
