<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/Semua.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8e44ad;
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
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
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

        .payment-method {
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .payment-method i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .payment-method.active {
            background: var(--primary-color);
            color: white;
        }

        .payment-method.active i {
            color: white;
        }

        .transaction-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .transaction-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .status-success {
            color: var(--success-color);
            font-weight: bold;
        }

        .status-pending {
            color: var(--warning-color);
            font-weight: bold;
        }

        .status-failed {
            color: var(--danger-color);
            font-weight: bold;
        }

        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .stats-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .gateway-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .gateway-status.online {
            background: var(--success-color);
            color: white;
        }

        .gateway-status.offline {
            background: var(--danger-color);
            color: white;
        }

        .gateway-status.maintenance {
            background: var(--warning-color);
            color: white;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .stats-card {
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
                                <h2><i class="fas fa-Kredit-card"></i> Payment Gateway</h2>
                                <p class="text-muted">Kelola pembayaran online dan integrasi gateway</p>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="showPaymentModal()">
                                    <i class="fas fa-plus"></i> Process Payment
                                </button>
                                <button class="btn btn-Berhasil" onclick="showIntegrationModal()">
                                    <i class="fas fa-cog"></i> Configure Gateway
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-money-bill-wave" style="font-size: 3rem; color: var(--primary-color);"></i>
                    <h3 id="totalTransactions">0</h3>
                    <p class="mb-0">Total Transactions</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-Grafik-line" style="font-size: 3rem; color: var(--Berhasil-color);"></i>
                    <h3 id="successRate">0%</h3>
                    <p class="mb-0">Success Rate</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-clock" style="font-size: 3rem; color: var(--Peringatan-color);"></i>
                    <h3 id="pendingPayments">0</h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger-color);"></i>
                    <h3 id="failedPayments">0</h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-wallet"></i> Payment Methods</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('bank_transfer')">
                                    <i class="fas fa-university"></i>
                                    <h6>Bank Transfer</h6>
                                    <p class="mb-0"><small>Virtual Account</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('gopay')">
                                    <i class="fas fa-wallet"></i>
                                    <h6>GoPay</h6>
                                    <p class="mb-0"><small>E-wallet</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('ovo')">
                                    <i class="fas fa-wallet"></i>
                                    <h6>OVO</h6>
                                    <p class="mb-0"><small>E-wallet</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('dana')">
                                    <i class="fas fa-wallet"></i>
                                    <h6>DANA</h6>
                                    <p class="mb-0"><small>E-wallet</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('credit_card')">
                                    <i class="fas fa-Kredit-card"></i>
                                    <h6>Credit Card</h6>
                                    <p class="mb-0"><small>Visa/Mastercard</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('qris')">
                                    <i class="fas fa-qrcode"></i>
                                    <h6>QRIS</h6>
                                    <p class="mb-0"><small>QR Payment</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('cash')">
                                    <i class="fas fa-money-bill"></i>
                                    <h6>Cash</h6>
                                    <p class="mb-0"><small>Over the Counter</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="Pembayaran-method" onclick="selectPaymentMethod('debit_card')">
                                    <i class="fas fa-Kredit-card"></i>
                                    <h6>Debit Card</h6>
                                    <p class="mb-0"><small>ATM/Debit</small></p>
                                    <span class="gateway-Status online">Online</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-Riwayat"></i> Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentTransactions">
                            <div class="Transaksi-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-university text-primary"></i> Bank Transfer - Ahmad Wijaya</h6>
                                        <p class="mb-1"><small class="text-muted">VA: 1234567890 | Amount: Rp 5,000,000</small></p>
                                        <div class="mt-2">
                                            <span class="Status-Berhasil">Success</span>
                                            <span class="badge bg-primary ms-2">Loan Payment</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">10:30 AM</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewTransaction(1)">View</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Transaksi-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-wallet text-Berhasil"></i> GoPay - Siti Nurhaliza</h6>
                                        <p class="mb-1"><small class="text-muted">Phone: 08123456789 | Amount: Rp 750,000</small></p>
                                        <div class="mt-2">
                                            <span class="Status-Berhasil">Success</span>
                                            <span class="badge bg-Info ms-2">Savings Deposit</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">09:45 AM</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewTransaction(2)">View</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Transaksi-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-Kredit-card text-Peringatan"></i> Credit Card - Budi Santoso</h6>
                                        <p class="mb-1"><small class="text-muted">Card: ****-****-****-1234 | Amount: Rp 2,500,000</small></p>
                                        <div class="mt-2">
                                            <span class="Status-Menunggu">Pending</span>
                                            <span class="badge bg-Peringatan ms-2">Loan Disbursement</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">08:15 AM</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewTransaction(3)">View</button>
                                    </div>
                                </div>
                            </div>
                            <div class="Transaksi-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-qrcode text-Info"></i> QRIS - Diana Putri</h6>
                                        <p class="mb-1"><small class="text-muted">QR ID: QR20241017001 | Amount: Rp 1,000,000</small></p>
                                        <div class="mt-2">
                                            <span class="Status-failed">Failed</span>
                                            <span class="badge bg-danger ms-2">Payment Timeout</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">07:30 AM</small><br>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewTransaction(4)">View</button>
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
                        <h5><i class="fas fa-Grafik-pie"></i> Payment Statistics</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentChart" height="200"></canvas>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-cog"></i> Gateway Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="Daftar-Grup">
                            <div class="Daftar-Grup-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Bank Transfer</h6>
                                    <small class="text-muted">Virtual Account</small>
                                </div>
                                <span class="gateway-Status online">Online</span>
                            </div>
                            <div class="Daftar-Grup-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">E-wallet</h6>
                                    <small class="text-muted">GoPay, OVO, DANA</small>
                                </div>
                                <span class="gateway-Status online">Online</span>
                            </div>
                            <div class="Daftar-Grup-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Card Payment</h6>
                                    <small class="text-muted">Credit/Debit Card</small>
                                </div>
                                <span class="gateway-Status online">Online</span>
                            </div>
                            <div class="Daftar-Grup-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">QRIS</h6>
                                    <small class="text-muted">QR Code Payment</small>
                                </div>
                                <span class="gateway-Status online">Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-Judul">Process Payment</h5>
                    <button type="button" class="btn-Tutup" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="Formulir-label">Select Member</label>
                        <select class="Formulir-Pilih" id="memberSelect">
                            <option value="">Choose member...</option>
                            <option value="1">Ahmad Wijaya - Member ID: 001</option>
                            <option value="2">Siti Nurhaliza - Member ID: 002</option>
                            <option value="3">Budi Santoso - Member ID: 003</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Payment Type</label>
                        <select class="Formulir-Pilih" id="paymentType">
                            <option value="loan_payment">Loan Payment</option>
                            <option value="savings_deposit">Savings Deposit</option>
                            <option value="loan_disbursement">Loan Disbursement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Amount</label>
                        <div class="input-Grup">
                            <span class="input-Grup-text">Rp</span>
                            <input type="Nomor" class="Formulir-control" id="paymentAmount" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Payment Method</label>
                        <select class="Formulir-Pilih" id="paymentMethod">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gopay">GoPay</option>
                            <option value="ovo">OVO</option>
                            <option value="dana">DANA</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="qris">QRIS</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Description</label>
                        <textarea class="Formulir-control" id="paymentDescription" rows="3" placeholder="Enter Pembayaran Deskripsi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()">Process Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Modal -->
    <div class="modal fade" id="integrationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-Judul">Configure Payment Gateway</h5>
                    <button type="button" class="btn-Tutup" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="Formulir-label">Gateway Provider</label>
                        <select class="Formulir-Pilih" id="gatewayProvider">
                            <option value="midtrans">Midtrans</option>
                            <option value="xendit">Xendit</option>
                            <option value="ipaymu">iPaymu</option>
                            <option value="manual">Manual Configuration</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Merchant ID</label>
                        <input type="text" class="Formulir-control" id="merchantId" placeholder="Enter merchant ID...">
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">API Key</label>
                        <input type="Kata Sandi" class="Formulir-control" id="apiKey" placeholder="Enter API key...">
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Server Key</label>
                        <input type="Kata Sandi" class="Formulir-control" id="serverKey" placeholder="Enter Server key...">
                    </div>
                    <div class="mb-3">
                        <label class="Formulir-label">Environment</label>
                        <select class="Formulir-Pilih" id="environment">
                            <option value="sandbox">Sandbox</option>
                            <option value="production">Production</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="Formulir-check">
                            <input class="Formulir-check-input" type="checkbox" id="enableNotifications">
                            <label class="Formulir-check-label" for="enableNotifications">
                                Enable payment notifications
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="configureGateway()">Configure</button>
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
            loadPaymentStats();
            loadRecentTransactions();
            initializeChart();
        });

        // Load payment statistics
        function loadPaymentStats() {
            // Mock data for development
            document.getElementById('totalTransactions').textContent = '2,456';
            document.getElementById('successRate').textContent = '94.5%';
            document.getElementById('pendingPayments').textContent = '23';
            document.getElementById('failedPayments').textContent = '12';
        }

        // Load recent transactions
        function loadRecentTransactions() {
            // Mock data already in HTML
            console.log('Recent Transaksi loaded');
        }

        // Initialize chart
        function initializeChart() {
            const ctx = document.getElementById('paymentChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Bank Transfer', 'E-wallet', 'Card Pembayaran', 'QRIS', 'Cash'],
                    datasets: [{
                        data: [856, 623, 412, 287, 278],
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
        }

        // Select payment method
        function selectPaymentMethod(method) {
            // Update active state
            document.querySelectorAll('.Pembayaran-method').forEach(el => {
                el.classList.remove('Aktif');
            });
            event.currentTarget.classList.add('Aktif');
            
            console.log('Selected Pembayaran method:', method);
        }

        // Show payment modal
        function showPaymentModal() {
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }

        // Show integration modal
        function showIntegrationModal() {
            const modal = new bootstrap.Modal(document.getElementById('integrationModal'));
            modal.show();
        }

        // Process payment
        function processPayment() {
            const member = document.getElementById('memberSelect').value;
            const type = document.getElementById('paymentType').value;
            const amount = document.getElementById('paymentAmount').value;
            const method = document.getElementById('paymentMethod').value;
            const description = document.getElementById('paymentDescription').value;
            
            // Mock payment processing
            console.log('Memproses Pembayaran:', { member, type, amount, method, description });
            alert('Pembayaran processed successfully!');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            
            // Refresh transactions
            loadRecentTransactions();
        }

        // Configure gateway
        function configureGateway() {
            const provider = document.getElementById('gatewayProvider').value;
            const merchantId = document.getElementById('merchantId').value;
            const apiKey = document.getElementById('apiKey').value;
            const serverKey = document.getElementById('serverKey').value;
            const environment = document.getElementById('environment').value;
            const notifications = document.getElementById('enableNotifications').checked;
            
            // Mock configuration
            console.log('Configuring gateway:', { provider, merchantId, apiKey, serverKey, environment, notifications });
            alert('Pembayaran gateway configured successfully!');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('integrationModal')).hide();
        }

        // View transaction
        function viewTransaction(id) {
            console.log('Viewing Transaksi:', id);
            alert(`Viewing transaction ID: ${id}`);
        }
    </script>
</body>
</html>
