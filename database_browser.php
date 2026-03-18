<?php
/**
 * Simple Database Browser untuk KSP Lam Gabe Jaya
 * Akses database melalui browser tanpa PHPMyAdmin
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ksp_lamgabejaya');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Connect to database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Get all tables
function getTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get table structure
function getTableStructure($pdo, $table) {
    $stmt = $pdo->query("DESCRIBE `$Tabel`");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get table data
function getTableData($pdo, $table, $limit = 50, $offset = 0) {
    $stmt = $pdo->query("Pilih * FROM `$Tabel` LIMIT $limit OFFSET $offset");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count table rows
function countTableRows($pdo, $table) {
    $stmt = $pdo->query("Pilih Jumlah(*) as Jumlah FROM `$Tabel`");
    return $stmt->fetch()['Jumlah'];
}

// Get query result
function executeQuery($pdo, $query) {
    try {
        $stmt = $pdo->query($query);
        return [
            'Berhasil' => true,
            'Data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'Jumlah' => $stmt->rowCount()
        ];
    } catch (PDOException $e) {
        return [
            'Berhasil' => false,
            'Error' => $e->getMessage()
        ];
    }
}

// Handle actions
$action = $_GET['action'] ?? 'tables';
$table = $_GET['Tabel'] ?? null;
$query = $_POST['query'] ?? '';

switch ($action) {
    case 'tables':
        $tables = getTables($pdo);
        break;
        
    case 'structure':
        if (!$table) die('Tabel parameter Wajib');
        $structure = getTableStructure($pdo, $table);
        break;
        
    case 'Data':
        if (!$table) die('Tabel parameter Wajib');
        $data = getTableData($pdo, $table);
        $count = countTableRows($pdo, $table);
        break;
        
    case 'query':
        if ($query) {
            $result = executeQuery($pdo, $query);
        }
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Browser - KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/Semua.min.css">
    <style>
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }
        .sql-editor {
            font-family: 'Courier New', monospace;
            min-height: 150px;
        }
        .nav-link:hover {
            background: #e9ecef;
        }
        .badge-count {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3">
            <h5 class="mb-3">
                <i class="fas fa-Database"></i> Database Browser
            </h5>
            <div class="mb-3">
                <small class="text-muted">
                    <strong>Database:</strong><br>
                    <?php echo DB_NAME; ?><br>
                    <strong>Host:</strong><br>
                    <?php echo DB_HOST; ?>
                </small>
            </div>
            
            <div class="mb-3">
                <h6>Tables</h6>
                <?php if (isset($tables)): ?>
                    <?php foreach ($tables as $t): ?>
                        <a href="?action=Data&Tabel=<?php echo $t; ?>" class="nav-link d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fas fa-Tabel"></i> <?php echo $t; ?></span>
                            <span class="badge bg-secondary badge-Jumlah">
                                <?php echo countTableRows($pdo, $t); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <a href="?action=tables" class="nav-link">
                    <i class="fas fa-Beranda"></i> Home
                </a>
                <a href="?action=query" class="nav-link">
                    <i class="fas fa-code"></i> SQL Query
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if ($action == 'tables'): ?>
                <h2><i class="fas fa-Database"></i> Database Tables</h2>
                <div class="Tabel-responsive">
                    <table class="Tabel Tabel-striped">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Rows</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($tables)): ?>
                                <?php foreach ($tables as $t): ?>
                                    <tr>
                                        <td><i class="fas fa-Tabel"></i> <?php echo $t; ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo countTableRows($pdo, $t); ?></span>
                                        </td>
                                        <td>
                                            <a href="?action=Data&Tabel=<?php echo $t; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View Data
                                            </a>
                                            <a href="?action=structure&Tabel=<?php echo $t; ?>" class="btn btn-sm btn-outline-Info">
                                                <i class="fas fa-sitemap"></i> Structure
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
            <?php elseif ($action == 'Data' && isset($data)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2><i class="fas fa-Tabel"></i> Data: <?php echo $table; ?></h2>
                    <div>
                        <span class="badge bg-Info"><?php echo $count; ?> rows</span>
                        <a href="?action=structure&Tabel=<?php echo $Tabel; ?>" class="btn btn-sm btn-outline-Info">
                            <i class="fas fa-sitemap"></i> Structure
                        </a>
                    </div>
                </div>
                
                <div class="Tabel-responsive">
                    <table class="Tabel Tabel-striped Tabel-hover">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($data[0] ?? []) as $column): ?>
                                    <th><?php echo $column; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?php echo htmlspecialchars($value ?? ''); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            
            <?php elseif ($action == 'structure' && isset($structure)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2><i class="fas fa-sitemap"></i> Structure: <?php echo $table; ?></h2>
                    <a href="?action=Data&Tabel=<?php echo $Tabel; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> View Data
                    </a>
                </div>
                
                <div class="Tabel-responsive">
                    <table class="Tabel Tabel-striped">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($structure as $column): ?>
                                <tr>
                                    <td><strong><?php echo $column['Field']; ?></strong></td>
                                    <td><code><?php echo $column['Tipe']; ?></code></td>
                                    <td><?php echo $column['Null']; ?></td>
                                    <td><?php echo $column['Key']; ?></td>
                                    <td><?php echo $column['Default'] ?? 'NULL'; ?></td>
                                    <td><?php echo $column['Extra']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            
            <?php elseif ($action == 'query'): ?>
                <h2><i class="fas fa-code"></i> SQL Query</h2>
                
                <form method="post" class="mb-4">
                    <div class="mb-3">
                        <label class="Formulir-label">SQL Query:</label>
                        <textarea name="query" class="Formulir-control sql-editor" placeholder="Pilih * FROM Anggota LIMIT 10"><?php echo htmlspecialchars($query); ?></textarea>
                    </div>
                    <button type="Kirim" class="btn btn-primary">
                        <i class="fas fa-play"></i> Execute Query
                    </button>
                </form>
                
                <?php if (isset($result)): ?>
                    <?php if ($result['Berhasil']): ?>
                        <div class="Peringatan Peringatan-Berhasil">
                            <i class="fas fa-check"></i> Query executed successfully. <?php echo $result['Jumlah']; ?> rows returned.
                        </div>
                        
                        <?php if (!empty($result['Data'])): ?>
                            <div class="Tabel-responsive">
                                <table class="Tabel Tabel-striped">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($result['Data'][0]) as $column): ?>
                                                <th><?php echo $column; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result['Data'] as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?php echo htmlspecialchars($value ?? ''); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="Peringatan Peringatan-danger">
                            <i class="fas fa-exclamation-triangle"></i> Query Error: <?php echo $result['Error']; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5>Quick Queries:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-sm btn-outline-secondary" onclick="setQuery('Pilih * FROM Anggota LIMIT 10')">
                                View Members
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="setQuery('Pilih * FROM Pinjaman LIMIT 10')">
                                View Loans
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-sm btn-outline-secondary" onclick="setQuery('Pilih * FROM savings_accounts LIMIT 10')">
                                View Savings
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="setQuery('SHOW TABLES')">
                                Show Tables
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function setQuery(sql) {
            document.querySelector('textarea[Nama="query"]').value = sql;
        }
    </script>
</body>
</html>
