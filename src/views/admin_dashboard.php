<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - KSP SaaS Koperasi Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-university"></i> KSP SaaS</h4>
            <small>Koperasi Harian Management</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#members">
                    <i class="fas fa-users"></i> Anggota
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#loans">
                    <i class="fas fa-hand-holding-usd"></i> Pinjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#transactions">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#mantris">
                    <i class="fas fa-user-tie"></i> Mantri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
        <header class="top-header">
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-link" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger">3</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Pinjaman baru menunggu approval</a></li>
                            <li><a class="dropdown-item" href="#">Mantri melebihi batas tunai</a></li>
                            <li><a class="dropdown-item" href="#">Sistem backup berhasil</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown ms-3">
                        <button class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> Admin User
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Profil</a></li>
                            <li><a class="dropdown-item" href="#">Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Dashboard Overview</h1>
                <p class="text-muted">Ringkasan kinerja koperasi hari ini</p>
            </div>

            <!-- KPI Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="kpi-card bg-primary">
                        <div class="kpi-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="kpi-content">
                            <h3>1,234</h3>
                            <p>Total Anggota</p>
                            <small><i class="fas fa-arrow-up"></i> +12% bulan ini</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="kpi-card bg-success">
                        <div class="kpi-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="kpi-content">
                            <h3>Rp 45.6M</h3>
                            <p>Total Pinjaman</p>
                            <small><i class="fas fa-arrow-up"></i> +8% bulan ini</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="kpi-card bg-warning">
                        <div class="kpi-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="kpi-content">
                            <h3>Rp 12.3M</h3>
                            <p>Penagihan Hari Ini</p>
                            <small><i class="fas fa-arrow-up"></i> 85% target</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="kpi-card bg-danger">
                        <div class="kpi-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-content">
                            <h3>23</h3>
                            <p>Tunggakan > 3 Hari</p>
                            <small><i class="fas fa-arrow-down"></i> -5% minggu ini</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Arus Kas Harian</h5>
                            <div class="card-actions">
                                <select class="form-select form-select-sm">
                                    <option>7 Hari Terakhir</option>
                                    <option>30 Hari Terakhir</option>
                                    <option>3 Bulan Terakhir</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="cashFlowChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Status Pinjaman</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="loanStatusChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Section -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Pinjaman Menunggu Approval</h5>
                            <a href="#" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No. Aplikasi</th>
                                            <th>Nama</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>LOAN202603001</td>
                                            <td>Budi Santoso</td>
                                            <td>Rp 5,000,000</td>
                                            <td><span class="badge bg-warning">Review</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success">Approve</button>
                                                <button class="btn btn-sm btn-danger">Reject</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>LOAN202603002</td>
                                            <td>Siti Nurhaliza</td>
                                            <td>Rp 3,000,000</td>
                                            <td><span class="badge bg-warning">Review</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success">Approve</button>
                                                <button class="btn btn-sm btn-danger">Reject</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>LOAN202603003</td>
                                            <td>Ahmad Fauzi</td>
                                            <td>Rp 7,500,000</td>
                                            <td><span class="badge bg-warning">Review</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-success">Approve</button>
                                                <button class="btn btn-sm btn-danger">Reject</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Posisi Mantri</h5>
                            <a href="#" class="btn btn-sm btn-primary">Lihat Peta</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mantri</th>
                                            <th>Area</th>
                                            <th>Target</th>
                                            <th>Terkumpul</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Andi Pratama</td>
                                            <td>Pasar Minggu</td>
                                            <td>Rp 10,000,000</td>
                                            <td>Rp 8,500,000</td>
                                            <td><span class="badge bg-success">Normal</span></td>
                                        </tr>
                                        <tr>
                                            <td>Budi Susilo</td>
                                            <td>Kemang</td>
                                            <td>Rp 8,000,000</td>
                                            <td>Rp 7,800,000</td>
                                            <td><span class="badge bg-success">Normal</span></td>
                                        </tr>
                                        <tr>
                                            <td>Chandra Dewi</td>
                                            <td>Cilandak</td>
                                            <td>Rp 12,000,000</td>
                                            <td>Rp 11,500,000</td>
                                            <td><span class="badge bg-warning">Warning</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Aksi Cepat</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100 mb-3">
                                        <i class="fas fa-user-plus"></i> Tambah Anggota
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-success w-100 mb-3">
                                        <i class="fas fa-plus-circle"></i> Ajukan Pinjaman
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-warning w-100 mb-3">
                                        <i class="fas fa-file-invoice"></i> Buat Laporan
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-info w-100 mb-3">
                                        <i class="fas fa-download"></i> Export Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../public/js/dashboard.js"></script>
    
    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Cash Flow Chart
            const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
            new Chart(cashFlowCtx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Pemasukan',
                        data: [12, 19, 15, 25, 22, 30, 28],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Pengeluaran',
                        data: [8, 12, 10, 14, 18, 15, 20],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });

            // Loan Status Chart
            const loanStatusCtx = document.getElementById('loanStatusChart').getContext('2d');
            new Chart(loanStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Lunas', 'Tunggakan', 'Ditolak'],
                    datasets: [{
                        data: [65, 20, 10, 5],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });

        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html>
