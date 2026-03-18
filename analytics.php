<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/Semua.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #34495e;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
        }

        .main-content {
            padding: 20px;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .metric-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .metric-card h3 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .metric-card .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-card .metric-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .metric-card .metric-change {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .metric-change.positive {
            color: var(--success-color);
        }

        .metric-change.negative {
            color: var(--danger-color);
        }

        .report-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .report-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .date-range-picker {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .kpi-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .kpi-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .kpi-card .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .kpi-card .kpi-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .metric-card {
                margin-bottom: 15px;
            }
            
            .date-range-picker {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-Grafik-line"></i> Analytics Dashboard</h2>
                                <p class="text-muted">Business Intelligence & Advanced Analytics</p>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="refreshData()">
                                    <i class="fas fa-sync"></i> Refresh Data
                                </button>
                                <button class="btn btn-Berhasil" onclick="showReportModal()">
                                    <i class="fas fa-File-alt"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="Filter-section">
            <div class="row">
                <div class="col-md-8">
                    <div class="Tanggal-range-picker">
                        <label class="Formulir-label mb-0">Date Range:</label>
                        <input type="Tanggal" class="Formulir-control" id="startDate" value="2024-10-01">
                        <span>to</span>
                        <input type="Tanggal" class="Formulir-control" id="endDate" value="2024-10-31">
                        <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                        <button class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="Ekspor-buttons">
                        <button class="btn btn-Berhasil" onclick="exportReport('PDF')">
                            <i class="fas fa-File-PDF"></i> PDF
                        </button>
                        <button class="btn btn-Info" onclick="exportReport('Excel')">
                            <i class="fas fa-File-Excel"></i> Excel
                        </button>
                        <button class="btn btn-Peringatan" onclick="exportReport('csv')">
                            <i class="fas fa-File-csv"></i> CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <h4><i class="fas fa-coins"></i> Total Assets</h4>
                    <div class="kpi-value">Rp 45.7J</div>
                    <div class="kpi-label">As of October 2024</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +12.5% from last month
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <h4><i class="fas fa-Pengguna"></i> Active Members</h4>
                    <div class="kpi-value">1,245</div>
                    <div class="kpi-label">Registered members</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +8.3% from last month
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <h4><i class="fas fa-hand-holding-usd"></i> Loan Portfolio</h4>
                    <div class="kpi-value">Rp 28.3J</div>
                    <div class="kpi-label">Outstanding loans</div>
                    <div class="metric-change negative">
                        <i class="fas fa-arrow-down"></i> -2.1% from last month
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <h4><i class="fas fa-Grafik-line"></i> Profit Margin</h4>
                    <div class="kpi-value">18.7%</div>
                    <div class="kpi-label">Net profit ratio</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +3.2% from last month
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Grafik-Area"></i> Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="Grafik-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Grafik-pie"></i> Revenue Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="Grafik-container">
                            <canvas id="revenueDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Grafik-bar"></i> Monthly Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="Grafik-container">
                            <canvas id="monthlyPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Grafik-line"></i> Growth Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="Grafik-container">
                            <canvas id="growthMetricsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-File-alt"></i> Recent Reports</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentReports">
                            <div class="Laporan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-PDF text-danger"></i> Monthly Financial Report - October 2024</h6>
                                        <p class="mb-1"><small class="text-muted">Generated: 2024-11-01 09:00 AM | Size: 2.3 MB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-primary">Financial</span>
                                            <span class="badge bg-Berhasil">Completed</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewReport(1)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(1)">Download</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Laporan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-Excel text-Berhasil"></i> Member Analytics Report - Q3 2024</h6>
                                        <p class="mb-1"><small class="text-muted">Generated: 2024-10-15 02:30 PM | Size: 1.8 MB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Info">Analytics</span>
                                            <span class="badge bg-Berhasil">Completed</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewReport(2)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(2)">Download</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Laporan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-csv text-Peringatan"></i> Loan Portfolio Analysis - October 2024</h6>
                                        <p class="mb-1"><small class="text-muted">Generated: 2024-10-31 11:45 AM | Size: 856 KB</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Peringatan">Loan</span>
                                            <span class="badge bg-Berhasil">Completed</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewReport(3)">View</button>
                                        <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(3)">Download</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-trophy"></i> Top Performers</h5>
                    </div>
                    <div class="card-body">
                        <div class="Daftar-Grup">
                            <div class="Daftar-Grup-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Ahmad Wijaya</h6>
                                        <small class="text-muted">Teller - 45 transactions</small>
                                    </div>
                                    <span class="badge bg-Berhasil">Top</span>
                                </div>
                            </div>
                            <div class="Daftar-Grup-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Siti Nurhaliza</h6>
                                        <small class="text-muted">Surveyor - 38 surveys</small>
                                    </div>
                                    <span class="badge bg-primary">2nd</span>
                                </div>
                            </div>
                            <div class="Daftar-Grup-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Budi Santoso</h6>
                                        <small class="text-muted">Kasir - Rp 12.5M processed</small>
                                    </div>
                                    <span class="badge bg-Info">3rd</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-Judul">Generate Custom Report</h5>
                    <button type="button" class="btn-Tutup" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="Formulir-label">Report Type</label>
                        <select class="Formulir-Pilih" id="reportType">
                            <option value="financial">Financial Report</option>
                            <option value="Anggota">Member Analytics</option>
                            <option value="Pinjaman">Loan Portfolio</option>
                            <option value="Performa">Performance Metrics</option>
                            <option value="custom">Custom Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Date Range</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="Tanggal" class="Formulir-control" id="reportStartDate">
                            </div>
                            <div class="col-md-6">
                                <input type="Tanggal" class="Formulir-control" id="reportEndDate">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Report Format</label>
                        <select class="Formulir-Pilih" id="reportFormat">
                            <option value="PDF">PDF</option>
                            <option value="Excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Include Sections</label>
                        <div class="Formulir-check">
                            <input class="Formulir-check-input" type="checkbox" id="includeSummary" checked>
                            <label class="Formulir-check-label" for="includeSummary">
                                Executive Summary
                            </label>
                        </div>
                        <div class="Formulir-check">
                            <input class="Formulir-check-input" type="checkbox" id="includeCharts" checked>
                            <label class="Formulir-check-label" for="includeCharts">
                                Charts and Graphs
                            </label>
                        </div>
                        <div class="Formulir-check">
                            <input class="Formulir-check-input" type="checkbox" id="includeDetails">
                            <label class="Formulir-check-label" for="includeDetails">
                                Detailed Data
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateReport()">Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/Grafik.js"></script>
    
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadRecentReports();
        });

        // Initialize charts
        function initializeCharts() {
            // Revenue Trend Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'Pendapatan',
                        data: [12.5, 13.2, 14.8, 15.3, 16.7, 17.2, 18.5, 19.3, 20.1, 21.4],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Laba',
                        data: [2.1, 2.3, 2.8, 3.1, 3.5, 3.8, 4.2, 4.5, 4.8, 5.1],
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4
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

            // Revenue Distribution Chart
            const distributionCtx = document.getElementById('revenueDistributionChart').getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pinjaman', 'Simpanan', 'Fees', 'Investments', 'Other'],
                    datasets: [{
                        data: [45, 25, 15, 10, 5],
                        backgroundColor: [
                            '#3498db',
                            '#27ae60',
                            '#f39c12',
                            '#e74c3c',
                            '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Monthly Performance Chart
            const performanceCtx = document.getElementById('monthlyPerformanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'New Anggota',
                        data: [45, 52, 48, 61, 58, 72, 68, 75, 82, 89],
                        backgroundColor: '#3498db'
                    }, {
                        label: 'New Pinjaman',
                        data: [23, 28, 25, 32, 29, 35, 31, 38, 42, 45],
                        backgroundColor: '#27ae60'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Growth Metrics Chart
            const growthCtx = document.getElementById('growthMetricsChart').getContext('2d');
            new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'Anggota Growth',
                        data: [100, 105, 112, 118, 125, 134, 142, 151, 162, 175],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Pendapatan Growth',
                        data: [100, 108, 115, 123, 132, 141, 152, 163, 175, 188],
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4
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
        }

        // Load recent reports
        function loadRecentReports() {
            // Mock data already in HTML
            console.log('Recent Laporan loaded');
        }

        // Apply filters
        function applyFilters() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            console.log('Applying filters:', { startDate, endDate });
            alert(`Filters applied: ${startDate} to ${endDate}`);
            
            // Refresh data with filters
            refreshData();
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('startDate').value = '2024-10-01';
            document.getElementById('endDate').value = '2024-10-31';
            
            console.log('Filters Reset');
            refreshData();
        }

        // Refresh data
        function refreshData() {
            console.log('Refreshing Data...');
            // Simulate data refresh
            setTimeout(() => {
                alert('Data refreshed successfully!');
            }, 1000);
        }

        // Show report modal
        function showReportModal() {
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            modal.show();
        }

        // Generate report
        function generateReport() {
            const type = document.getElementById('reportType').value;
            const startDate = document.getElementById('reportStartDate').value;
            const endDate = document.getElementById('reportEndDate').value;
            const format = document.getElementById('reportFormat').value;
            const includeSummary = document.getElementById('includeSummary').checked;
            const includeCharts = document.getElementById('includeCharts').checked;
            const includeDetails = document.getElementById('includeDetails').checked;
            
            console.log('Generating Laporan:', { type, startDate, endDate, format, includeSummary, includeCharts, includeDetails });
            alert('Laporan generation started! You will be notified when complete.');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        }

        // Export report
        function exportReport(format) {
            console.log('Exporting Laporan as:', format);
            alert(`Exporting report as ${format.toUpperCase()}...`);
        }

        // View report
        function viewReport(id) {
            console.log('Viewing Laporan:', id);
            alert(`Viewing report ID: ${id}`);
        }

        // Download report
        function downloadReport(id) {
            console.log('Downloading Laporan:', id);
            alert(`Downloading report ID: ${id}`);
        }
    </script>
</body>
</html>
