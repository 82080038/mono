<?php
/**
 * Printer Service - SaaS Koperasi Harian
 * 
 * Handles Bluetooth printer integration for receipt printing
 * with multiple printer support and template management
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class PrinterService
{
    private $db;
    private $bluetoothManager;
    private $defaultTemplate = 'payment_receipt';
    private $printerProfiles = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->bluetoothManager = new BluetoothManager();
        $this->loadPrinterProfiles();
    }
    
    /**
     * Print Payment Receipt
     */
    public function printPaymentReceipt(array $paymentData, int $mantriId, int $printerId = null): array
    {
        try {
            // Get printer info
            $printer = $this->getPrinter($printerId, $mantriId);
            
            if (!$printer) {
                throw new Exception('Printer not found or not available');
            }
            
            // Generate receipt content
            $receiptContent = $this->generatePaymentReceipt($paymentData, $printer);
            
            // Connect to printer
            $connectionResult = $this->connectToPrinter($printer);
            
            if (!$connectionResult['success']) {
                throw new Exception('Failed to connect to printer: ' . $connectionResult['error']);
            }
            
            // Print receipt
            $printResult = $this->printContent($printer, $receiptContent);
            
            // Disconnect from printer
            $this->disconnectFromPrinter($printer);
            
            // Log print job
            $this->logPrintJob([
                'printer_id' => $printer['id'],
                'mantri_id' => $mantriId,
                'content_type' => 'payment_receipt',
                'content_data' => json_encode($paymentData),
                'status' => $printResult['success'] ? 'success' : 'failed',
                'error_message' => $printResult['success'] ? null : $printResult['error'],
                'printed_at' => date('Y-m-d H:i:s')
            ]);
            
            return $printResult;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to print payment receipt'
            ];
        }
    }
    
    /**
     * Print Loan Disbursement Receipt
     */
    public function printLoanDisbursementReceipt(array $loanData, int $mantriId, int $printerId = null): array
    {
        try {
            $printer = $this->getPrinter($printerId, $mantriId);
            
            if (!$printer) {
                throw new Exception('Printer not found or not available');
            }
            
            $receiptContent = $this->generateLoanDisbursementReceipt($loanData, $printer);
            
            $connectionResult = $this->connectToPrinter($printer);
            
            if (!$connectionResult['success']) {
                throw new Exception('Failed to connect to printer: ' . $connectionResult['error']);
            }
            
            $printResult = $this->printContent($printer, $receiptContent);
            
            $this->disconnectFromPrinter($printer);
            
            $this->logPrintJob([
                'printer_id' => $printer['id'],
                'mantri_id' => $mantriId,
                'content_type' => 'loan_disbursement',
                'content_data' => json_encode($loanData),
                'status' => $printResult['success'] ? 'success' : 'failed',
                'error_message' => $printResult['success'] ? null : $printResult['error'],
                'printed_at' => date('Y-m-d H:i:s')
            ]);
            
            return $printResult;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to print loan disbursement receipt'
            ];
        }
    }
    
    /**
     * Print Daily Summary Report
     */
    public function printDailySummary(array $summaryData, int $mantriId, int $printerId = null): array
    {
        try {
            $printer = $this->getPrinter($printerId, $mantriId);
            
            if (!$printer) {
                throw new Exception('Printer not found or not available');
            }
            
            $reportContent = $this->generateDailySummaryReport($summaryData, $printer);
            
            $connectionResult = $this->connectToPrinter($printer);
            
            if (!$connectionResult['success']) {
                throw new Exception('Failed to connect to printer: ' . $connectionResult['error']);
            }
            
            $printResult = $this->printContent($printer, $reportContent);
            
            $this->disconnectFromPrinter($printer);
            
            $this->logPrintJob([
                'printer_id' => $printer['id'],
                'mantri_id' => $mantriId,
                'content_type' => 'daily_summary',
                'content_data' => json_encode($summaryData),
                'status' => $printResult['success'] ? 'success' : 'failed',
                'error_message' => $printResult['success'] ? null : $printResult['error'],
                'printed_at' => date('Y-m-d H:i:s')
            ]);
            
            return $printResult;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to print daily summary'
            ];
        }
    }
    
    /**
     * Get Available Printers
     */
    public function getAvailablePrinters(int $mantriId): array
    {
        try {
            $sql = "SELECT * FROM printers WHERE mantri_id = ? AND status = 'active' ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantriId]);
            
            $printers = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $printers[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'model' => $row['model'],
                    'address' => $row['bluetooth_address'],
                    'status' => $row['status'],
                    'paper_width' => $row['paper_width'],
                    'last_used' => $row['last_used_at'],
                    'connection_status' => $this->testPrinterConnection($row['bluetooth_address'])
                ];
            }
            
            return [
                'success' => true,
                'printers' => $printers
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'printers' => []
            ];
        }
    }
    
    /**
     * Add Printer
     */
    public function addPrinter(array $printerData, int $mantriId): array
    {
        try {
            // Validate printer data
            $this->validatePrinterData($printerData);
            
            // Check if printer already exists
            if ($this->printerExists($printerData['bluetooth_address'])) {
                throw new Exception('Printer with this Bluetooth address already exists');
            }
            
            // Add printer to database
            $sql = "INSERT INTO printers (uuid, mantri_id, name, model, bluetooth_address, paper_width, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->generateUuid(),
                $mantriId,
                $printerData['name'],
                $printerData['model'],
                $printerData['bluetooth_address'],
                $printerData['paper_width'] ?? 58
            ]);
            
            $printerId = $this->db->lastInsertId();
            
            // Test connection
            $connectionTest = $this->testPrinterConnection($printerData['bluetooth_address']);
            
            return [
                'success' => true,
                'printer_id' => $printerId,
                'connection_test' => $connectionTest,
                'message' => 'Printer added successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to add printer'
            ];
        }
    }
    
    /**
     * Update Printer
     */
    public function updatePrinter(int $printerId, array $printerData, int $mantriId): array
    {
        try {
            // Check if printer exists and belongs to mantri
            $printer = $this->getPrinterById($printerId, $mantriId);
            
            if (!$printer) {
                throw new Exception('Printer not found');
            }
            
            // Validate printer data
            $this->validatePrinterData($printerData);
            
            // Update printer
            $sql = "UPDATE printers SET name = ?, model = ?, bluetooth_address = ?, paper_width = ?, updated_at = NOW() 
                    WHERE id = ? AND mantri_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $printerData['name'],
                $printerData['model'],
                $printerData['bluetooth_address'],
                $printerData['paper_width'] ?? 58,
                $printerId,
                $mantriId
            ]);
            
            return [
                'success' => true,
                'message' => 'Printer updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update printer'
            ];
        }
    }
    
    /**
     * Delete Printer
     */
    public function deletePrinter(int $printerId, int $mantriId): array
    {
        try {
            // Check if printer exists and belongs to mantri
            $printer = $this->getPrinterById($printerId, $mantriId);
            
            if (!$printer) {
                throw new Exception('Printer not found');
            }
            
            // Soft delete (mark as inactive)
            $sql = "UPDATE printers SET status = 'inactive', deleted_at = NOW() WHERE id = ? AND mantri_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$printerId, $mantriId]);
            
            return [
                'success' => true,
                'message' => 'Printer deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete printer'
            ];
        }
    }
    
    /**
     * Get Print History
     */
    public function getPrintHistory(int $mantriId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ["pj.mantri_id = ?"];
            $params = [$mantriId];
            
            if (!empty($filters['content_type'])) {
                $whereConditions[] = "pj.content_type = ?";
                $params[] = $filters['content_type'];
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "pj.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "pj.printed_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "pj.printed_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM print_jobs pj WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get print history
            $sql = "SELECT pj.*, p.name as printer_name, p.model as printer_model 
                    FROM print_jobs pj 
                    LEFT JOIN printers p ON pj.printer_id = p.id 
                    WHERE {$whereClause} 
                    ORDER BY pj.printed_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            $printJobs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $printJobs[] = [
                    'id' => $row['id'],
                    'printer_name' => $row['printer_name'],
                    'printer_model' => $row['printer_model'],
                    'content_type' => $row['content_type'],
                    'status' => $row['status'],
                    'error_message' => $row['error_message'],
                    'printed_at' => $row['printed_at']
                ];
            }
            
            return [
                'success' => true,
                'data' => $printJobs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit),
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $total)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'pagination' => []
            ];
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function generatePaymentReceipt(array $paymentData, array $printer): string
    {
        $template = $this->getReceiptTemplate('payment_receipt', $printer['paper_width']);
        
        $replacements = [
            '{company_name}' => $paymentData['company_name'],
            '{company_address}' => $paymentData['company_address'],
            '{company_phone}' => $paymentData['company_phone'],
            '{receipt_number}' => $paymentData['receipt_number'],
            '{date}' => date('d/m/Y', strtotime($paymentData['payment_date'])),
            '{time}' => date('H:i:s', strtotime($paymentData['payment_date'])),
            '{member_name}' => $paymentData['member_name'],
            '{member_phone}' => $paymentData['member_phone'],
            '{payment_amount}' => number_format($paymentData['amount'], 2, ',', '.'),
            '{payment_amount_words}' => $this->numberToWords($paymentData['amount']),
            '{payment_method}' => $paymentData['payment_method'],
            '{mantri_name}' => $paymentData['mantri_name'],
            '{loan_number}' => $paymentData['loan_number'] ?? '',
            '{installment_number}' => $paymentData['installment_number'] ?? '',
            '{remaining_balance}' => number_format($paymentData['remaining_balance'] ?? 0, 2, ',', '.'),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    private function generateLoanDisbursementReceipt(array $loanData, array $printer): string
    {
        $template = $this->getReceiptTemplate('loan_disbursement', $printer['paper_width']);
        
        $replacements = [
            '{company_name}' => $loanData['company_name'],
            '{company_address}' => $loanData['company_address'],
            '{company_phone}' => $loanData['company_phone'],
            '{receipt_number}' => $loanData['receipt_number'],
            '{date}' => date('d/m/Y', strtotime($loanData['disbursement_date'])),
            '{time}' => date('H:i:s', strtotime($loanData['disbursement_date'])),
            '{member_name}' => $loanData['member_name'],
            '{member_phone}' => $loanData['member_phone'],
            '{loan_number}' => $loanData['loan_number'],
            '{loan_amount}' => number_format($loanData['loan_amount'], 2, ',', '.'),
            '{loan_amount_words}' => $this->numberToWords($loanData['loan_amount']),
            '{loan_purpose}' => $loanData['loan_purpose'],
            '{interest_rate}' => number_format($loanData['interest_rate'], 2, ',', '.'),
            '{loan_term}' => $loanData['loan_term_days'] . ' hari',
            '{monthly_payment}' => number_format($loanData['monthly_payment'], 2, ',', '.'),
            '{disbursement_method}' => $loanData['disbursement_method'],
            '{mantri_name}' => $loanData['mantri_name'],
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    private function generateDailySummaryReport(array $summaryData, array $printer): string
    {
        $template = $this->getReceiptTemplate('daily_summary', $printer['paper_width']);
        
        $replacements = [
            '{company_name}' => $summaryData['company_name'],
            '{company_address}' => $summaryData['company_address'],
            '{report_date}' => date('d/m/Y', strtotime($summaryData['report_date'])),
            '{mantri_name}' => $summaryData['mantri_name'],
            '{total_transactions}' => $summaryData['total_transactions'],
            '{total_collected}' => number_format($summaryData['total_collected'], 2, ',', '.'),
            '{total_collected_words}' => $this->numberToWords($summaryData['total_collected']),
            '{target_amount}' => number_format($summaryData['target_amount'], 2, ',', '.'),
            '{achievement_percentage}' => number_format($summaryData['achievement_percentage'], 2, ',', '.'),
            '{successful_transactions}' => $summaryData['successful_transactions'],
            '{failed_transactions}' => $summaryData['failed_transactions'],
            '{start_time}' => $summaryData['start_time'],
            '{end_time}' => $summaryData['end_time'],
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    private function getReceiptTemplate(string $templateType, int $paperWidth): string
    {
        $templates = [
            'payment_receipt_58' => "
================================
       KSP LAM GABE JAYA
    Jl. Raya Pasar Minggu No. 123
    Telp: 021-12345678
================================
     BUKTI PEMBAYARAN
================================
No: {receipt_number}
Tanggal: {date} {time}
--------------------------------
Anggota: {member_name}
Telp: {member_phone}
{loan_number}
{installment_number}
--------------------------------
Jumlah Bayar: Rp {payment_amount}
Terbilang: {payment_amount_words}
Metode: {payment_method}
--------------------------------
Sisa Saldo: Rp {remaining_balance}
--------------------------------
Mantri: {mantri_name}
================================
     Terima Kasih
================================
            ",
            
            'payment_receipt_80' => "
========================================
         KSP LAM GABE JAYA
     Jl. Raya Pasar Minggu No. 123
     Telp: 021-12345678
     Website: www.ksplamgabejaya.id
========================================
           BUKTI PEMBAYARAN
========================================
No. Transaksi: {receipt_number}
Tanggal: {date} {time}
----------------------------------------
Data Anggota:
Nama: {member_name}
Telepon: {member_phone}
No. Pinjaman: {loan_number}
Angsuran Ke: {installment_number}
----------------------------------------
Detail Pembayaran:
Jumlah: Rp {payment_amount}
Terbilang: {payment_amount_words}
Metode Pembayaran: {payment_method}
Sisa Saldo: Rp {remaining_balance}
----------------------------------------
Dikumpulkan oleh: {mantri_name}
========================================
          Terima Kasih
========================================
            ",
            
            'loan_disbursement_58' => "
================================
       KSP LAM GABE JAYA
    Jl. Raya Pasar Minggu No. 123
    Telp: 021-12345678
================================
   BUKTI PENCAIRAN PINJAMAN
================================
No: {receipt_number}
Tanggal: {date} {time}
--------------------------------
Anggota: {member_name}
Telp: {member_phone}
--------------------------------
No. Pinjaman: {loan_number}
Jumlah: Rp {loan_amount}
Terbilang: {loan_amount_words}
Tujuan: {loan_purpose}
--------------------------------
Bunga: {interest_rate}%
Tenor: {loan_term}
Angsuran: Rp {monthly_payment}
Metode: {disbursement_method}
--------------------------------
Mantri: {mantri_name}
================================
     Terima Kasih
================================
            ",
            
            'daily_summary_58' => "
================================
       KSP LAM GABE JAYA
    Jl. Raya Pasar Minggu No. 123
    Telp: 021-12345678
================================
     LAPORAN HARIAN
================================
Tanggal: {report_date}
Mantri: {mantri_name}
--------------------------------
Total Transaksi: {total_transactions}
Total Terkumpul: Rp {total_collected}
Terbilang: {total_collected_words}
Target: Rp {target_amount}
Pencapaian: {achievement_percentage}%
--------------------------------
Berhasil: {successful_transactions}
Gagal: {failed_transactions}
--------------------------------
Jam: {start_time} - {end_time}
================================
     Terima Kasih
================================
            "
        ];
        
        $templateKey = $templateType . '_' . $paperWidth;
        
        return $templates[$templateKey] ?? $templates['payment_receipt_58'];
    }
    
    private function connectToPrinter(array $printer): array
    {
        try {
            // Use Bluetooth manager to connect
            $result = $this->bluetoothManager->connect($printer['bluetooth_address']);
            
            if ($result['success']) {
                // Update last used time
                $sql = "UPDATE printers SET last_used_at = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$printer['id']]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function disconnectFromPrinter(array $printer): void
    {
        try {
            $this->bluetoothManager->disconnect($printer['bluetooth_address']);
        } catch (Exception $e) {
            // Log error but don't throw
            error_log('Failed to disconnect from printer: ' . $e->getMessage());
        }
    }
    
    private function printContent(array $printer, string $content): array
    {
        try {
            // Send content to printer
            $result = $this->bluetoothManager->print($printer['bluetooth_address'], $content);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testPrinterConnection(string $bluetoothAddress): string
    {
        try {
            $result = $this->bluetoothManager->testConnection($bluetoothAddress);
            return $result['success'] ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            return 'error';
        }
    }
    
    private function getPrinter(?int $printerId, int $mantriId): ?array
    {
        if ($printerId) {
            // Get specific printer
            $sql = "SELECT * FROM printers WHERE id = ? AND mantri_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$printerId, $mantriId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            // Get default printer for mantri
            $sql = "SELECT * FROM printers WHERE mantri_id = ? AND status = 'active' ORDER BY last_used_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mantriId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }
    
    private function getPrinterById(int $printerId, int $mantriId): ?array
    {
        $sql = "SELECT * FROM printers WHERE id = ? AND mantri_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$printerId, $mantriId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function printerExists(string $bluetoothAddress): bool
    {
        $sql = "SELECT id FROM printers WHERE bluetooth_address = ? AND status != 'inactive'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bluetoothAddress]);
        return $stmt->fetch() !== false;
    }
    
    private function validatePrinterData(array $data): void
    {
        $required = ['name', 'model', 'bluetooth_address'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        // Validate Bluetooth address format
        if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data['bluetooth_address'])) {
            throw new Exception('Invalid Bluetooth address format');
        }
        
        // Validate paper width
        if (isset($data['paper_width']) && !in_array($data['paper_width'], [58, 80])) {
            throw new Exception('Paper width must be 58mm or 80mm');
        }
    }
    
    private function logPrintJob(array $jobData): void
    {
        $sql = "INSERT INTO print_jobs (printer_id, mantri_id, content_type, content_data, status, error_message, printed_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $jobData['printer_id'],
            $jobData['mantri_id'],
            $jobData['content_type'],
            $jobData['content_data'],
            $jobData['status'],
            $jobData['error_message'],
            $jobData['printed_at']
        ]);
    }
    
    private function loadPrinterProfiles(): void
    {
        $this->printerProfiles = [
            'Panda PRJ-58' => [
                'paper_width' => 58,
                'max_chars_per_line' => 32,
                'supports_graphics' => false,
                'commands' => [
                    'init' => "\x1B\x40",
                    'center' => "\x1B\x61\x01",
                    'left' => "\x1B\x61\x00",
                    'bold_on' => "\x1B\x45\x01",
                    'bold_off' => "\x1B\x45\x00",
                    'feed' => "\x0A",
                    'cut' => "\x1D\x56\x00"
                ]
            ],
            'Zjiang ZJ-58' => [
                'paper_width' => 58,
                'max_chars_per_line' => 32,
                'supports_graphics' => false,
                'commands' => [
                    'init' => "\x1B\x40",
                    'center' => "\x1B\x61\x01",
                    'left' => "\x1B\x61\x00",
                    'bold_on' => "\x1B\x45\x01",
                    'bold_off' => "\x1B\x45\x00",
                    'feed' => "\x0A",
                    'cut' => "\x1D\x56\x00"
                ]
            ],
            'Epson TM-T20' => [
                'paper_width' => 80,
                'max_chars_per_line' => 48,
                'supports_graphics' => true,
                'commands' => [
                    'init' => "\x1B\x40",
                    'center' => "\x1B\x61\x01",
                    'left' => "\x1B\x61\x00",
                    'bold_on' => "\x1B\x45\x01",
                    'bold_off' => "\x1B\x45\x00",
                    'feed' => "\x0A",
                    'cut' => "\x1D\x56\x00"
                ]
            ]
        ];
    }
    
    private function numberToWords(float $number): string
    {
        // Simple implementation for number to words
        // In production, use a proper library
        $units = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
        $teens = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas', 'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
        $tens = ['', 'sepuluh', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh', 'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'];
        
        if ($number == 0) {
            return 'nol';
        }
        
        if ($number < 10) {
            return $units[$number];
        }
        
        if ($number < 20) {
            return $teens[$number - 10];
        }
        
        if ($number < 100) {
            $ten = floor($number / 10);
            $unit = $number % 10;
            return $tens[$ten] . ($unit ? ' ' . $units[$unit] : '');
        }
        
        // For larger numbers, return a simplified version
        return number_format($number, 0, '.', ',');
    }
    
    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

/**
 * Usage Examples:
 * 
 * $printerService = new PrinterService();
 * 
 * // Print payment receipt
 * $result = $printerService->printPaymentReceipt([
 *     'company_name' => 'KSP LAM GABE JAYA',
 *     'receipt_number' => 'RCP20260311001',
 *     'payment_date' => '2026-03-11 16:30:00',
 *     'member_name' => 'Budi Santoso',
 *     'member_phone' => '08123456789',
 *     'amount' => 500000,
 *     'payment_method' => 'Tunai',
 *     'mantri_name' => 'Andi Pratama'
 * ], $mantriId);
 * 
 * // Get available printers
 * $printers = $printerService->getAvailablePrinters($mantriId);
 * 
 * // Add new printer
 * $result = $printerService->addPrinter([
 *     'name' => 'Panda PRJ-58',
 *     'model' => 'Panda PRJ-58',
 *     'bluetooth_address' => '00:11:22:33:44:55',
 *     'paper_width' => 58
 * ], $mantriId);
 */
