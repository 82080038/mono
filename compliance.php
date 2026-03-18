<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Monitoring - KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/Semua.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e74c3c;
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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

        .compliance-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .compliance-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .risk-indicator {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }

        .risk-indicator.low {
            background: var(--success-color);
        }

        .risk-indicator.medium {
            background: var(--warning-color);
        }

        .risk-indicator.high {
            background: var(--danger-color);
        }

        .risk-indicator.critical {
            background: var(--danger-color);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .compliance-metric {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .compliance-metric:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .compliance-metric h3 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .compliance-metric .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .compliance-metric .metric-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .audit-item {
            padding: 15px;
            border-left: 4px solid var(--info-color);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .audit-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }

        .status-badge.compliant {
            background: var(--success-color);
        }

        .status-badge.non-compliant {
            background: var(--danger-color);
        }

        .status-badge.pending {
            background: var(--warning-color);
        }

        .status-badge.in-progress {
            background: var(--info-color);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .alert-item {
            padding: 15px;
            border-left: 4px solid var(--danger-color);
            background: #f8d7da;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .alert-item:hover {
            background: #f5c6cb;
            transform: translateX(5px);
        }

        .alert-item.warning {
            border-left-color: var(--warning-color);
            background: #fff3cd;
        }

        .alert-item.warning:hover {
            background: #ffeaa7;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .compliance-metric {
                margin-bottom: 15px;
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
                                <h2><i class="fas fa-shield-alt"></i> Compliance Monitoring</h2>
                                <p class="text-muted">Monitor regulatory compliance and risk indicators</p>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="runComplianceCheck()">
                                    <i class="fas fa-play"></i> Run Compliance Check
                                </button>
                                <button class="btn btn-Berhasil" onclick="generateComplianceReport()">
                                    <i class="fas fa-File-alt"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="Kepatuhan-metric">
                    <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--Berhasil-color);"></i>
                    <div class="metric-value">94.5%</div>
                    <div class="metric-label">Overall Compliance</div>
                    <div class="mt-2">
                        <span class="Risiko-indicator low">Low Risk</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="Kepatuhan-metric">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--Peringatan-color);"></i>
                    <div class="metric-value">12</div>
                    <div class="metric-label">Pending Issues</div>
                    <div class="mt-2">
                        <span class="Risiko-indicator medium">Medium Risk</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="Kepatuhan-metric">
                    <i class="fas fa-clock" style="font-size: 3rem; color: var(--Info-color);"></i>
                    <div class="metric-value">3</div>
                    <div class="metric-label">Overdue Items</div>
                    <div class="mt-2">
                        <span class="Risiko-indicator high">High Risk</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="Kepatuhan-metric">
                    <i class="fas fa-ban" style="font-size: 3rem; color: var(--danger-color);"></i>
                    <div class="metric-value">1</div>
                    <div class="metric-label">Critical Issues</div>
                    <div class="mt-2">
                        <span class="Risiko-indicator critical">Critical Risk</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Areas -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks"></i> Compliance Areas</h5>
                    </div>
                    <div class="card-body">
                        <div id="complianceAreas">
                            <div class="Kepatuhan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-university text-primary"></i> Banking Regulations</h6>
                                        <p class="mb-1"><small class="text-muted">OJK Compliance | Last checked: 2024-10-15</small></p>
                                        <div class="mt-2">
                                            <span class="Status-badge compliant">Compliant</span>
                                            <span class="badge bg-Berhasil ms-2">100%</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComplianceDetail('banking')">View Details</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Kepatuhan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-File-Kontrak text-Peringatan"></i> Loan Portfolio</h6>
                                        <p class="mb-1"><small class="text-muted">Risk Assessment | Last checked: 2024-10-14</small></p>
                                        <div class="mt-2">
                                            <span class="Status-badge Menunggu">Pending</span>
                                            <span class="badge bg-Peringatan ms-2">85%</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComplianceDetail('Pinjaman')">View Details</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Kepatuhan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-shield-alt text-Berhasil"></i> Data Protection</h6>
                                        <p class="mb-1"><small class="text-muted">Privacy Policy | Last checked: 2024-10-13</small></p>
                                        <div class="mt-2">
                                            <span class="Status-badge compliant">Compliant</span>
                                            <span class="badge bg-Berhasil ms-2">95%</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComplianceDetail('Data')">View Details</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Kepatuhan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-calculator text-danger"></i> Financial Reporting</h6>
                                        <p class="mb-1"><small class="text-muted">Tax Compliance | Last checked: 2024-10-12</small></p>
                                        <div class="mt-2">
                                            <span class="Status-badge non-compliant">Non-Compliant</span>
                                            <span class="badge bg-danger ms-2">72%</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComplianceDetail('financial')">View Details</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Kepatuhan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Pengguna text-Info"></i> Member Protection</h6>
                                        <p class="mb-1"><small class="text-muted">Consumer Rights | Last checked: 2024-10-11</small></p>
                                        <div class="mt-2">
                                            <span class="Status-badge in-progress">In Progress</span>
                                            <span class="badge bg-Info ms-2">88%</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComplianceDetail('Anggota')">View Details</button>
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
                        <h5><i class="fas fa-exclamation-triangle"></i> Active Alerts</h5>
                    </div>
                    <div class="card-body">
                        <div id="activeAlerts">
                            <div class="Peringatan-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-ban"></i> Critical: Financial Reporting</h6>
                                        <p class="mb-1"><small class="text-muted">Tax compliance deadline exceeded</small></p>
                                        <div class="mt-2">
                                            <span class="Risiko-indicator critical">Critical</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-danger" onclick="handleAlert(1)">Handle</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Peringatan-item Peringatan">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-exclamation-triangle"></i> Warning: Loan Portfolio</h6>
                                        <p class="mb-1"><small class="text-muted">Risk assessment required</small></p>
                                        <div class="mt-2">
                                            <span class="Risiko-indicator high">High</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-Peringatan" onclick="handleAlert(2)">Handle</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Peringatan-item Peringatan">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Info-circle"></i> Info: Member Protection</h6>
                                        <p class="mb-1"><small class="text-muted">Policy update required</small></p>
                                        <div class="mt-2">
                                            <span class="Risiko-indicator medium">Medium</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-Info" onclick="handleAlert(3)">Handle</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Trail & Risk Assessment -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Riwayat"></i> Recent Audit Trail</h5>
                    </div>
                    <div class="card-body">
                        <div id="auditTrail">
                            <div class="Audit-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Pengguna-shield text-primary"></i> Admin Access</h6>
                                        <p class="mb-1"><small class="text-muted">User: admin@lamabejaya.coop | Action: Login</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Berhasil">Normal</span>
                                            <small class="text-muted ms-2">2024-10-17 10:30 AM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="Audit-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Edit text-Peringatan"></i> Data Modification</h6>
                                        <p class="mb-1"><small class="text-muted">User: teller@lamabejaya.coop | Action: Update Member</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Peringatan">Reviewed</span>
                                            <small class="text-muted ms-2">2024-10-17 09:45 AM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="Audit-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Unduh text-Info"></i> Report Export</h6>
                                        <p class="mb-1"><small class="text-muted">User: kasir@lamabejaya.coop | Action: Download Report</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-Info">Logged</span>
                                            <small class="text-muted ms-2">2024-10-17 08:15 AM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="Audit-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-trash text-danger"></i> Data Deletion</h6>
                                        <p class="mb-1"><small class="text-muted">User: admin@lamabejaya.coop | Action: Delete Transaction</small></p>
                                        <div class="mt-2">
                                            <span class="badge bg-danger">Investigated</span>
                                            <small class="text-muted ms-2">2024-10-16 04:30 PM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Grafik-line"></i> Risk Assessment</h5>
                    </div>
                    <div class="card-body">
                        <div class="Grafik-container">
                            <canvas id="riskChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Reports -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-File-alt"></i> Compliance Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="Tabel-responsive">
                            <table class="Tabel Tabel-striped">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Type</th>
                                        <th>Generated</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="fas fa-File-PDF text-danger"></i> Monthly Compliance Report</td>
                                        <td>Monthly</td>
                                        <td>2024-10-17 09:00 AM</td>
                                        <td><span class="Status-badge compliant">Compliant</span></td>
                                        <td><span class="badge bg-Berhasil">94.5%</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewReport(1)">View</button>
                                            <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(1)">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-File-Excel text-Berhasil"></i> Risk Assessment Report</td>
                                        <td>Weekly</td>
                                        <td>2024-10-15 02:30 PM</td>
                                        <td><span class="Status-badge Menunggu">Pending</span></td>
                                        <td><span class="badge bg-Peringatan">78.2%</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewReport(2)">View</button>
                                            <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(2)">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-File-csv text-Peringatan"></i> Audit Trail Report</td>
                                        <td>Daily</td>
                                        <td>2024-10-17 11:45 AM</td>
                                        <td><span class="Status-badge compliant">Compliant</span></td>
                                        <td><span class="badge bg-Berhasil">100%</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewReport(3)">View</button>
                                            <button class="btn btn-sm btn-outline-Berhasil" onclick="downloadReport(3)">Download</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
            loadComplianceData();
        });

        // Initialize charts
        function initializeCharts() {
            // Risk Assessment Chart
            const riskCtx = document.getElementById('riskChart').getContext('2d');
            new Chart(riskCtx, {
                type: 'radar',
                data: {
                    labels: ['Banking Regulations', 'Pinjaman Portfolio', 'Data Protection', 'Financial Reporting', 'Anggota Protection', 'Operational Risiko'],
                    datasets: [{
                        label: 'Current Risiko Level',
                        data: [15, 35, 20, 65, 25, 30],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.2)',
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#e74c3c'
                    }, {
                        label: 'Acceptable Risiko Level',
                        data: [25, 25, 25, 25, 25, 25],
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        pointBackgroundColor: '#27ae60',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#27ae60'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Load compliance data
        function loadComplianceData() {
            // Mock data already in HTML
            console.log('Kepatuhan Data loaded');
        }

        // Run compliance check
        function runComplianceCheck() {
            console.log('Running Kepatuhan check...');
            alert('Kepatuhan check initiated. Results will be available shortly.');
            
            // Simulate compliance check
            setTimeout(() => {
                alert('Kepatuhan check Selesai! 1 new issue identified.');
                loadComplianceData();
            }, 2000);
        }

        // Generate compliance report
        function generateComplianceReport() {
            console.log('Generating Kepatuhan Laporan...');
            alert('Kepatuhan Laporan generation started. You will be notified when complete.');
        }

        // View compliance detail
        function viewComplianceDetail(area) {
            console.log('Viewing Kepatuhan detail for:', area);
            alert(`Viewing compliance details for: ${area}`);
        }

        // Handle alert
        function handleAlert(alertId) {
            console.log('Handling Peringatan:', alertId);
            alert(`Handling alert ID: ${alertId}`);
        }

        // View report
        function viewReport(id) {
            console.log('Viewing Laporan:', id);
            alert(`Viewing compliance report ID: ${id}`);
        }

        // Download report
        function downloadReport(id) {
            console.log('Downloading Laporan:', id);
            alert(`Downloading compliance report ID: ${id}`);
        }
    </script>
</body>
</html>
