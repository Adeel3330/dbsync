<?php
/**
 * DB Sync - Initial Setup Script
 * Run this to initialize the application
 */

$pageTitle = 'Setup - DB Sync';
$currentPage = 'setup';

// Check if already configured
$configFile = __DIR__ . '/config/config.json';
$isConfigured = file_exists($configFile) && filesize($configFile) > 0;

if ($isConfigured && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-cog me-2"></i>DB Sync Setup</h4>
            </div>
            <div class="card-body">
                <?php if ($isConfigured): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Configuration already exists. <a href="index.php">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Welcome to DB Sync!</h5>
                        <p>This tool helps you compare two MySQL/MariaDB databases and synchronize missing data.</p>
                        <hr>
                        <p class="mb-0"><strong>Before you begin:</strong></p>
                        <ul class="mb-0">
                            <li>Ensure both databases are accessible</li>
                            <li>Have connection credentials ready</li>
                            <li>Ensure the log directory is writable</li>
                        </ul>
                    </div>
                    
                    <form id="setupForm">
                        <h5 class="mt-4 mb-3">Database A Configuration</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Host</label>
                                <input type="text" class="form-control" name="db_a[host]" value="localhost" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="db_a[port]" value="3306" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database Name</label>
                            <input type="text" class="form-control" name="db_a[name]" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="db_a[username]" value="root" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="db_a[password]">
                            </div>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Database B Configuration</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Host</label>
                                <input type="text" class="form-control" name="db_b[host]" value="localhost" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="db_b[port]" value="3306" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database Name</label>
                            <input type="text" class="form-control" name="db_b[name]" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="db_b[username]" value="root" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="db_b[password]">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save & Continue
                            </button>
                        </div>
                    </form>
                    
                    <div id="setupResult" class="mt-3"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('setupForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const config = {
        db_a: {
            host: formData.get('db_a[host]'),
            name: formData.get('db_a[name]'),
            username: formData.get('db_a[username]'),
            password: formData.get('db_a[password]'),
            port: parseInt(formData.get('db_a[port]'))
        },
        db_b: {
            host: formData.get('db_b[host]'),
            name: formData.get('db_b[name]'),
            username: formData.get('db_b[username]'),
            password: formData.get('db_b[password]'),
            port: parseInt(formData.get('db_b[port]'))
        }
    };
    
    showLoader('Testing connections and saving configuration...');
    
    fetch('api/save_config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(config)
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        const resultEl = document.getElementById('setupResult');
        
        if (data.success) {
            resultEl.innerHTML = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>' + data.message + '</div>';
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        } else {
            resultEl.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        hideLoader();
        document.getElementById('setupResult').innerHTML = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' + error.message + '</div>';
    });
});
</script>

<?php
require_once 'templates/footer.php';
?>

