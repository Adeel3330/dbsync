<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'DB Sync - Database Comparison Tool'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Loading Spinner -->
    <div id="globalLoader" class="loader-overlay" style="display: none;">
        <div class="spinner-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-light" id="loaderText">Processing...</p>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-database me-2"></i>DB Sync
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="../index.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'structure' ? 'active' : ''; ?>" href="../pages/structure.php">
                            <i class="fas fa-table me-1"></i> Structure
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'records' ? 'active' : ''; ?>" href="../pages/records.php">
                            <i class="fas fa-list me-1"></i> Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'logs' ? 'active' : ''; ?>" href="../pages/logs.php">
                            <i class="fas fa-clipboard-list me-1"></i> Logs
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button class="btn btn-outline-light btn-sm" onclick="showConfigModal()">
                        <i class="fas fa-cog me-1"></i> Settings
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse show">
                <div class="position-sticky pt-3">
                    <div class="mb-4 px-3">
                        <h6 class="text-muted text-uppercase small fw-bold">Database Status</h6>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary me-2">A</span>
                            <small id="dbAStatus" class="text-muted">Not connected</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">B</span>
                            <small id="dbBStatus" class="text-muted">Not connected</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="../index.php">
                                <i class="fas fa-home me-2"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'structure' ? 'active' : ''; ?>" href="../pages/structure.php">
                                <i class="fas fa-columns me-2"></i> Table Structure
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'records' ? 'active' : ''; ?>" href="../pages/records.php">
                                <i class="fas fa-list me-2"></i> Compare Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'table_detail' ? 'active' : ''; ?>" href="../pages/table_detail.php">
                                <i class="fas fa-search me-2"></i> Table Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'logs' ? 'active' : ''; ?>" href="../pages/logs.php">
                                <i class="fas fa-history me-2"></i> Activity Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

