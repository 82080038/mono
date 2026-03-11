<?php
/**
 * Loan Service - SaaS Koperasi Harian
 * 
 * Handles loan applications, approvals, disbursements, and repayments
 * with credit scoring, workflow management, and compliance
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class LoanService
{
    private $db;
    private $memberService;
    private $transactionService;
    private $notificationService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->memberService = new MemberService();
        $this->transactionService = new TransactionService();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * Submit New Loan Application
     */
    public function submitLoanApplication(array $loanData, int $memberId, int $tenantId): array
    {
        try {
            // Validate loan application data
            $this->validateLoanApplication($loanData);
            
            // Get member information
            $member = $this->memberService->getMemberById($memberId, $tenantId);
            
            if (!$member) {
                throw new Exception('Member not found');
            }
            
            // Check member eligibility
            $eligibilityCheck = $this->checkLoanEligibility($member, $loanData);
            
            if (!$eligibilityCheck['eligible']) {
                throw new Exception($eligibilityCheck['reason']);
            }
            
            // Calculate loan details
            $loanCalculations = $this->calculateLoanDetails($loanData);
            
            // Generate application number
            $applicationNumber = $this->generateApplicationNumber($tenantId);
            
            // Create loan application
            $loanApplicationId = $this->createLoanApplication([
                'uuid' => $this->generateUuid(),
                'member_id' => $memberId,
                'application_number' => $applicationNumber,
                'loan_amount' => $loanData['loan_amount'],
                'loan_purpose' => $loanData['loan_purpose'] ?? null,
                'loan_term_days' => $loanData['loan_term_days'],
                'interest_rate' => $loanCalculations['interest_rate'],
                'monthly_payment' => $loanCalculations['monthly_payment'],
                'collateral_description' => $loanData['collateral_description'] ?? null,
                'collateral_value' => $loanData['collateral_value'] ?? 0,
                'guarantor_name' => $loanData['guarantor_name'] ?? null,
                'guarantor_phone' => $loanData['guarantor_phone'] ?? null,
                'guarantor_relationship' => $loanData['guarantor_relationship'] ?? null,
                'status' => 'submitted',
                'applied_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create repayment schedule
            $this->createRepaymentSchedule($loanApplicationId, $loanCalculations);
            
            // Send notification to member
            $this->notificationService->sendLoanApplicationSubmitted($memberId, $applicationNumber);
            
            // Send notification to admin
            $this->notificationService->sendNewLoanApplicationAlert($tenantId, $loanApplicationId);
            
            return [
                'success' => true,
                'loan_application_id' => $loanApplicationId,
                'application_number' => $applicationNumber,
                'calculated_details' => $loanCalculations,
                'message' => 'Loan application submitted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Loan application submission failed'
            ];
        }
    }
    
    /**
     * Review and Approve/Reject Loan Application
     */
    public function reviewLoanApplication(int $loanApplicationId, array $reviewData, int $reviewerId, int $tenantId): array
    {
        try {
            // Get loan application
            $loanApplication = $this->getLoanApplicationById($loanApplicationId, $tenantId);
            
            if (!$loanApplication) {
                throw new Exception('Loan application not found');
            }
            
            if ($loanApplication['status'] !== 'under_review') {
                throw new Exception('Loan application is not under review');
            }
            
            // Validate review data
            $this->validateReviewData($reviewData);
            
            // Update application status
            $newStatus = $reviewData['action']; // 'approved' or 'rejected'
            $updateData = [
                'status' => $newStatus,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'reviewed_by' => $reviewerId,
                'review_notes' => $reviewData['review_notes'] ?? null
            ];
            
            if ($newStatus === 'approved') {
                $updateData['approved_at'] = date('Y-m-d H:i:s');
                $updateData['approved_by'] = $reviewerId;
            }
            
            $this->updateLoanApplication($loanApplicationId, $updateData);
            
            // Send notifications
            if ($newStatus === 'approved') {
                $this->notificationService->sendLoanApproved($loanApplication['member_id'], $loanApplication);
            } else {
                $this->notificationService->sendLoanRejected($loanApplication['member_id'], $loanApplication, $reviewData['review_notes'] ?? null);
            }
            
            // Log transaction
            $this->transactionService->logTransaction([
                'transaction_type' => 'loan_review',
                'amount' => 0,
                'member_id' => $loanApplication['member_id'],
                'loan_application_id' => $loanApplicationId,
                'reference_number' => $loanApplication['application_number'],
                'description' => "Loan application {$newStatus}",
                'created_by' => $reviewerId
            ]);
            
            return [
                'success' => true,
                'loan_application_id' => $loanApplicationId,
                'new_status' => $newStatus,
                'message' => "Loan application {$newStatus} successfully"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Loan application review failed'
            ];
        }
    }
    
    /**
     * Disburse Approved Loan
     */
    public function disburseLoan(int $loanApplicationId, array $disbursementData, int $processedBy, int $tenantId): array
    {
        try {
            // Get loan application
            $loanApplication = $this->getLoanApplicationById($loanApplicationId, $tenantId);
            
            if (!$loanApplication) {
                throw new Exception('Loan application not found');
            }
            
            if ($loanApplication['status'] !== 'approved') {
                throw new Exception('Loan application must be approved before disbursement');
            }
            
            // Validate disbursement data
            $this->validateDisbursementData($disbursementData);
            
            // Create disbursement record
            $disbursementId = $this->createLoanDisbursement([
                'uuid' => $this->generateUuid(),
                'loan_application_id' => $loanApplicationId,
                'disbursement_amount' => $loanApplication['loan_amount'],
                'disbursement_method' => $disbursementData['disbursement_method'],
                'bank_account_number' => $disbursementData['bank_account_number'] ?? null,
                'bank_account_name' => $disbursementData['bank_account_name'] ?? null,
                'bank_name' => $disbursementData['bank_name'] ?? null,
                'digital_wallet_type' => $disbursementData['digital_wallet_type'] ?? null,
                'digital_wallet_number' => $disbursementData['digital_wallet_number'] ?? null,
                'reference_number' => $disbursementData['reference_number'] ?? null,
                'status' => 'processing',
                'processed_by' => $processedBy
            ]);
            
            // Process disbursement based on method
            $disbursementResult = $this->processDisbursement($disbursementId, $disbursementData);
            
            if ($disbursementResult['success']) {
                // Update loan application status
                $this->updateLoanApplication($loanApplicationId, [
                    'status' => 'disbursed',
                    'disbursed_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update disbursement status
                $this->updateDisbursementStatus($disbursementId, 'completed', $processedBy);
                
                // Log transaction
                $this->transactionService->logTransaction([
                    'transaction_type' => 'loan_disbursement',
                    'amount' => $loanApplication['loan_amount'],
                    'member_id' => $loanApplication['member_id'],
                    'loan_application_id' => $loanApplicationId,
                    'reference_number' => $disbursementData['reference_number'] ?? null,
                    'description' => 'Loan disbursement',
                    'created_by' => $processedBy
                ]);
                
                // Send notifications
                $this->notificationService->sendLoanDisbursed($loanApplication['member_id'], $loanApplication, $disbursementData);
            }
            
            return [
                'success' => $disbursementResult['success'],
                'disbursement_id' => $disbursementId,
                'message' => $disbursementResult['message']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Loan disbursement failed'
            ];
        }
    }
    
    /**
     * Process Loan Payment
     */
    public function processLoanPayment(int $repaymentScheduleId, array $paymentData, int $processedBy, int $tenantId): array
    {
        try {
            // Get repayment schedule
            $schedule = $this->getRepaymentScheduleById($repaymentScheduleId, $tenantId);
            
            if (!$schedule) {
                throw new Exception('Repayment schedule not found');
            }
            
            if ($schedule['status'] === 'paid') {
                throw new Exception('This installment is already paid');
            }
            
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Get loan application
            $loanApplication = $this->getLoanApplicationById($schedule['loan_application_id'], $tenantId);
            
            // Calculate payment details
            $paymentDetails = $this->calculatePaymentDetails($schedule, $paymentData['amount']);
            
            // Process payment
            $transactionId = $this->transactionService->logTransaction([
                'transaction_type' => 'loan_payment',
                'amount' => $paymentData['amount'],
                'member_id' => $loanApplication['member_id'],
                'mantri_id' => $paymentData['mantri_id'] ?? null,
                'loan_application_id' => $loanApplication['id'],
                'repayment_schedule_id' => $repaymentScheduleId,
                'reference_number' => $paymentData['reference_number'] ?? null,
                'description' => 'Loan payment',
                'location' => isset($paymentData['gps_lat']) ? 
                    "POINT({$paymentData['gps_lng']} {$paymentData['gps_lat']})" : null,
                'created_by' => $processedBy
            ]);
            
            // Update repayment schedule
            $newPaidAmount = $schedule['paid_amount'] + $paymentData['amount'];
            $newStatus = $newPaidAmount >= $schedule['total_amount'] ? 'paid' : 'partial_paid';
            
            $this->updateRepaymentSchedule($repaymentScheduleId, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null
            ]);
            
            // Send receipt
            $this->notificationService->sendLoanPaymentReceipt($loanApplication['member_id'], $paymentDetails);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_details' => $paymentDetails,
                'message' => 'Loan payment processed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Loan payment processing failed'
            ];
        }
    }
    
    /**
     * Get Loan Applications List
     */
    public function getLoanApplicationsList(int $tenantId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(la.application_number LIKE ? OR m.name LIKE ? OR m.nik LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "la.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['member_id'])) {
                $whereConditions[] = "la.member_id = ?";
                $params[] = $filters['member_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "la.applied_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "la.applied_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM loan_applications la 
                         LEFT JOIN members m ON la.member_id = m.id 
                         {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get applications data
            $sql = "SELECT la.*, m.name as member_name, m.nik as member_nik, m.phone as member_phone 
                    FROM loan_applications la 
                    LEFT JOIN members m ON la.member_id = m.id 
                    {$whereClause} 
                    ORDER BY la.applied_at DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $applications,
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
                'message' => 'Failed to get loan applications list'
            ];
        }
    }
    
    /**
     * Check Loan Eligibility
     */
    private function checkLoanEligibility(array $member, array $loanData): array
    {
        // Check credit score
        if ($member['credit_score'] < 300) {
            return ['eligible' => false, 'reason' => 'Credit score too low'];
        }
        
        // Check existing loans
        if ($this->hasActiveLoans($member['id'])) {
            return ['eligible' => false, 'reason' => 'Member has active loans'];
        }
        
        // Check loan amount vs credit limit
        if ($loanData['loan_amount'] > $member['credit_limit']) {
            return ['eligible' => false, 'reason' => 'Loan amount exceeds credit limit'];
        }
        
        // Check age
        if (isset($member['birth_date'])) {
            $age = $this->calculateAge($member['birth_date']);
            if ($age < 18 || $age > 65) {
                return ['eligible' => false, 'reason' => 'Age not eligible for loan'];
            }
        }
        
        // Check monthly income vs loan payment
        if (isset($member['monthly_income']) && $member['monthly_income'] > 0) {
            $monthlyPayment = $loanData['loan_amount'] * 0.1; // Simplified calculation
            if ($monthlyPayment > $member['monthly_income'] * 0.3) {
                return ['eligible' => false, 'reason' => 'Monthly payment exceeds 30% of income'];
            }
        }
        
        return ['eligible' => true, 'reason' => 'Eligible for loan'];
    }
    
    /**
     * Calculate Loan Details
     */
    private function calculateLoanDetails(array $loanData): array
    {
        $loanAmount = $loanData['loan_amount'];
        $loanTermDays = $loanData['loan_term_days'];
        
        // Calculate interest rate based on loan amount and term
        $baseRate = 0.12; // 12% annual
        
        if ($loanAmount < 1000000) {
            $baseRate = 0.18; // 18% for small loans
        } elseif ($loanAmount < 5000000) {
            $baseRate = 0.15; // 15% for medium loans
        }
        
        if ($loanTermDays > 180) {
            $baseRate += 0.02; // Additional 2% for long terms
        }
        
        // Calculate monthly payment (simplified)
        $monthlyInterestRate = $baseRate / 12;
        $numberOfPayments = ceil($loanTermDays / 30);
        
        $monthlyPayment = $loanAmount * ($monthlyInterestRate * pow(1 + $monthlyInterestRate, $numberOfPayments)) / 
                         (pow(1 + $monthlyInterestRate, $numberOfPayments) - 1);
        
        return [
            'interest_rate' => $baseRate,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_interest' => round(($monthlyPayment * $numberOfPayments) - $loanAmount, 2),
            'total_payment' => round($monthlyPayment * $numberOfPayments, 2),
            'number_of_payments' => $numberOfPayments
        ];
    }
    
    /**
     * Create Repayment Schedule
     */
    private function createRepaymentSchedule(int $loanApplicationId, array $loanCalculations): void
    {
        $loanApplication = $this->getLoanApplicationById($loanApplicationId);
        $startDate = new DateTime($loanApplication['applied_at']);
        $startDate->modify('+1 month'); // First payment after 1 month
        
        for ($i = 1; $i <= $loanCalculations['number_of_payments']; $i++) {
            $dueDate = clone $startDate;
            $dueDate->modify('+' . ($i - 1) . ' month');
            
            $scheduleData = [
                'uuid' => $this->generateUuid(),
                'loan_application_id' => $loanApplicationId,
                'installment_number' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal_amount' => $loanCalculations['monthly_payment'] * 0.8, // 80% principal
                'interest_amount' => $loanCalculations['monthly_payment'] * 0.2, // 20% interest
                'total_amount' => $loanCalculations['monthly_payment'],
                'paid_amount' => 0,
                'status' => 'pending'
            ];
            
            $fields = array_keys($scheduleData);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO loan_repayment_schedules (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($scheduleData));
        }
    }
    
    /**
     * Helper Methods
     */
    
    private function validateLoanApplication(array $data): void
    {
        $required = ['loan_amount', 'loan_term_days'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if ($data['loan_amount'] < 100000 || $data['loan_amount'] > 100000000) {
            throw new Exception('Loan amount must be between Rp100,000 and Rp100,000,000');
        }
        
        if ($data['loan_term_days'] < 30 || $data['loan_term_days'] > 365) {
            throw new Exception('Loan term must be between 30 and 365 days');
        }
    }
    
    private function validateReviewData(array $data): void
    {
        if (empty($data['action']) || !in_array($data['action'], ['approved', 'rejected'])) {
            throw new Exception('Invalid action specified');
        }
        
        if ($data['action'] === 'rejected' && empty($data['review_notes'])) {
            throw new Exception('Review notes are required for rejection');
        }
    }
    
    private function validateDisbursementData(array $data): void
    {
        $required = ['disbursement_method'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!in_array($data['disbursement_method'], ['cash', 'bank_transfer', 'digital_wallet'])) {
            throw new Exception('Invalid disbursement method');
        }
        
        if ($data['disbursement_method'] === 'bank_transfer') {
            $bankRequired = ['bank_account_number', 'bank_account_name', 'bank_name'];
            foreach ($bankRequired as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required for bank transfer");
                }
            }
        }
        
        if ($data['disbursement_method'] === 'digital_wallet') {
            $walletRequired = ['digital_wallet_type', 'digital_wallet_number'];
            foreach ($walletRequired as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required for digital wallet");
                }
            }
        }
    }
    
    private function validatePaymentData(array $data): void
    {
        if (empty($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Payment amount is required and must be greater than 0');
        }
    }
    
    private function generateApplicationNumber(int $tenantId): string
    {
        $prefix = 'LOAN' . date('Ym');
        $sequence = $this->getSequenceNumber($tenantId, 'loan_application');
        
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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
    
    private function calculateAge(string $birthDate): int
    {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        
        return $birth->diff($today)->y;
    }
    
    private function hasActiveLoans(int $memberId): bool
    {
        $sql = "SELECT id FROM loan_applications WHERE member_id = ? AND status IN ('approved', 'disbursed')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        
        return $stmt->fetch() !== false;
    }
    
    private function getSequenceNumber(int $tenantId, string $type): int
    {
        // Implement sequence number generation
        return mt_rand(1, 9999);
    }
    
    private function getLoanApplicationById(int $loanApplicationId, ?int $tenantId = null): ?array
    {
        $sql = "SELECT * FROM loan_applications WHERE id = ?";
        $params = [$loanApplicationId];
        
        if ($tenantId) {
            // Add tenant check if needed
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getRepaymentScheduleById(int $scheduleId, ?int $tenantId = null): ?array
    {
        $sql = "SELECT * FROM loan_repayment_schedules WHERE id = ?";
        $params = [$scheduleId];
        
        if ($tenantId) {
            // Add tenant check if needed
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function calculatePaymentDetails(array $schedule, float $paymentAmount): array
    {
        return [
            'installment_number' => $schedule['installment_number'],
            'due_date' => $schedule['due_date'],
            'total_amount' => $schedule['total_amount'],
            'paid_amount' => $schedule['paid_amount'],
            'payment_amount' => $paymentAmount,
            'remaining_amount' => max(0, $schedule['total_amount'] - $schedule['paid_amount'] - $paymentAmount),
            'status' => $paymentAmount >= ($schedule['total_amount'] - $schedule['paid_amount']) ? 'paid' : 'partial_paid'
        ];
    }
    
    private function createLoanApplication(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO loan_applications (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    private function updateLoanApplication(int $loanApplicationId, array $data): void
    {
        $setClause = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $values[] = $loanApplicationId;
        
        $sql = "UPDATE loan_applications SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
    
    private function createLoanDisbursement(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO loan_disbursements (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    private function updateDisbursementStatus(int $disbursementId, string $status, int $processedBy): void
    {
        $sql = "UPDATE loan_disbursements SET status = ?, processed_at = NOW(), processed_by = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $processedBy, $disbursementId]);
    }
    
    private function updateRepaymentSchedule(int $scheduleId, array $data): void
    {
        $setClause = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $values[] = $scheduleId;
        
        $sql = "UPDATE loan_repayment_schedules SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
    
    private function processDisbursement(int $disbursementId, array $disbursementData): array
    {
        // Implement actual disbursement processing based on method
        // For now, return success
        return [
            'success' => true,
            'message' => 'Disbursement processed successfully'
        ];
    }
}

/**
 * Usage Examples:
 * 
 * $loanService = new LoanService();
 * 
 * // Submit loan application
 * $result = $loanService->submitLoanApplication([
 *     'loan_amount' => 5000000,
 *     'loan_term_days' => 90,
 *     'loan_purpose' => 'Modal usaha',
 *     'collateral_description' => 'BPKB Motor',
 *     'collateral_value' => 3000000
 * ], $memberId, $tenantId);
 * 
 * // Process loan payment
 * $result = $loanService->processLoanPayment($repaymentScheduleId, [
 *     'amount' => 500000,
 *     'reference_number' => 'PAY001',
 *     'mantri_id' => 1,
 *     'gps_lat' => -6.2088,
 *     'gps_lng' => 106.8456
 * ], $processedBy, $tenantId);
 */
