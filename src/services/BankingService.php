<?php
/**
 * Banking Service - SaaS Koperasi Harian
 * 
 * Kop-Bank integration with virtual account management,
 * direct debit setup, and credit facilities
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class BankingService
{
    private $db;
    private $bankConfig;
    private $httpClient;
    private $encryptionService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->httpClient = new HttpClient();
        $this->encryptionService = new EncryptionService();
        $this->initializeBankConfig();
    }
    
    /**
     * Create Virtual Account
     */
    public function createVirtualAccount(array $vaData): array
    {
        try {
            // Validate VA data
            $this->validateVAData($vaData);
            
            // Generate unique VA number
            $vaNumber = $this->generateVANumber();
            
            // Prepare Kop-Bank API request
            $request = [
                'bank_code' => $vaData['bank_code'],
                'customer_name' => $vaData['customer_name'],
                'customer_email' => $vaData['customer_email'] ?? null,
                'customer_phone' => $vaData['customer_phone'],
                'virtual_account_number' => $vaNumber,
                'amount' => $vaData['amount'] ?? null,
                'expiry_date' => $vaData['expiry_date'] ?? null,
                'description' => $vaData['description'] ?? 'Pembayaran KSP',
                'callback_url' => $this->bankConfig['callback_url'],
                'signature' => $this->signRequest($vaData)
            ];
            
            // Send to Kop-Bank API
            $response = $this->httpClient->post($this->bankConfig['api_url'] . '/virtual-accounts', $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to create virtual account: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save VA record
            $vaRecord = [
                'uuid' => $this->generateUuid(),
                'member_id' => $vaData['member_id'] ?? null,
                'loan_application_id' => $vaData['loan_application_id'] ?? null,
                'bank_code' => $vaData['bank_code'],
                'virtual_account_number' => $vaNumber,
                'customer_name' => $vaData['customer_name'],
                'customer_email' => $vaData['customer_email'] ?? null,
                'customer_phone' => $vaData['customer_phone'],
                'amount' => $vaData['amount'] ?? null,
                'expiry_date' => $vaData['expiry_date'] ?? null,
                'description' => $vaData['description'] ?? 'Pembayaran KSP',
                'status' => 'active',
                'kop_bank_va_id' => $responseData['va_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveVirtualAccount($vaRecord);
            
            return [
                'success' => true,
                'va_id' => $vaRecord['uuid'],
                'virtual_account_number' => $vaNumber,
                'bank_code' => $vaData['bank_code'],
                'bank_name' => $this->getBankName($vaData['bank_code']),
                'customer_name' => $vaData['customer_name'],
                'amount' => $vaData['amount'] ?? null,
                'expiry_date' => $vaData['expiry_date'] ?? null,
                'status' => 'active'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create virtual account'
            ];
        }
    }
    
    /**
     * Setup Direct Debit
     */
    public function setupDirectDebit(array $directDebitData): array
    {
        try {
            // Validate direct debit data
            $this->validateDirectDebitData($directDebitData);
            
            // Get member's bank account
            $bankAccount = $this->getMemberBankAccount($directDebitData['member_id']);
            
            if (!$bankAccount) {
                throw new Exception('Member bank account not found');
            }
            
            // Prepare direct debit request
            $request = [
                'customer_id' => $directDebitData['member_id'],
                'account_number' => $bankAccount['account_number'],
                'account_name' => $bankAccount['account_name'],
                'bank_code' => $bankAccount['bank_code'],
                'amount' => $directDebitData['amount'],
                'frequency' => $directDebitData['frequency'], // monthly, weekly, daily
                'start_date' => $directDebitData['start_date'],
                'end_date' => $directDebitData['end_date'] ?? null,
                'description' => $directDebitData['description'] ?? 'Auto-debit KSP',
                'max_attempts' => $directDebitData['max_attempts'] ?? 3,
                'signature' => $this->signRequest($directDebitData)
            ];
            
            // Send to Kop-Bank API
            $response = $this->httpClient->post($this->bankConfig['api_url'] . '/direct-debit', $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to setup direct debit: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save direct debit record
            $ddRecord = [
                'uuid' => $this->generateUuid(),
                'member_id' => $directDebitData['member_id'],
                'bank_account_id' => $bankAccount['id'],
                'amount' => $directDebitData['amount'],
                'frequency' => $directDebitData['frequency'],
                'start_date' => $directDebitData['start_date'],
                'end_date' => $directDebitData['end_date'] ?? null,
                'description' => $directDebitData['description'] ?? 'Auto-debit KSP',
                'max_attempts' => $directDebitData['max_attempts'] ?? 3,
                'status' => 'active',
                'kop_bank_dd_id' => $responseData['dd_id'],
                'next_debit_date' => $this->calculateNextDebitDate($directDebitData['frequency'], $directDebitData['start_date']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveDirectDebit($ddRecord);
            
            return [
                'success' => true,
                'dd_id' => $ddRecord['uuid'],
                'amount' => $directDebitData['amount'],
                'frequency' => $directDebitData['frequency'],
                'start_date' => $directDebitData['start_date'],
                'next_debit_date' => $ddRecord['next_debit_date'],
                'status' => 'active'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to setup direct debit'
            ];
        }
    }
    
    /**
     * Apply for Credit Facility
     */
    public function applyForCreditFacility(array $creditData): array
    {
        try {
            // Validate credit facility data
            $this->validateCreditFacilityData($creditData);
            
            // Get member information
            $member = $this->getMemberInfo($creditData['member_id']);
            
            if (!$member) {
                throw new Exception('Member not found');
            }
            
            // Prepare credit facility request
            $request = [
                'customer_id' => $creditData['member_id'],
                'customer_name' => $member['name'],
                'customer_nik' => $member['nik'],
                'customer_phone' => $member['phone'],
                'customer_address' => $member['address'],
                'facility_type' => $creditData['facility_type'], // working_capital, investment, emergency
                'requested_amount' => $creditData['requested_amount'],
                'purpose' => $creditData['purpose'],
                'term_months' => $creditData['term_months'],
                'collateral_type' => $creditData['collateral_type'] ?? null,
                'collateral_value' => $creditData['collateral_value'] ?? null,
                'monthly_income' => $member['monthly_income'],
                'credit_score' => $member['credit_score'],
                'signature' => $this->signRequest($creditData)
            ];
            
            // Send to Kop-Bank API
            $response = $this->httpClient->post($this->bankConfig['api_url'] . '/credit-facilities', $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to apply for credit facility: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save credit facility record
            $creditRecord = [
                'uuid' => $this->generateUuid(),
                'member_id' => $creditData['member_id'],
                'facility_type' => $creditData['facility_type'],
                'requested_amount' => $creditData['requested_amount'],
                'approved_amount' => $responseData['approved_amount'] ?? null,
                'purpose' => $creditData['purpose'],
                'term_months' => $creditData['term_months'],
                'interest_rate' => $responseData['interest_rate'] ?? null,
                'collateral_type' => $creditData['collateral_type'] ?? null,
                'collateral_value' => $creditData['collateral_value'] ?? null,
                'status' => $responseData['status'] ?? 'pending',
                'kop_bank_credit_id' => $responseData['credit_id'],
                'application_date' => date('Y-m-d H:i:s'),
                'approved_date' => $responseData['approved_date'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveCreditFacility($creditRecord);
            
            return [
                'success' => true,
                'credit_id' => $creditRecord['uuid'],
                'requested_amount' => $creditData['requested_amount'],
                'approved_amount' => $responseData['approved_amount'] ?? null,
                'status' => $creditRecord['status'],
                'interest_rate' => $responseData['interest_rate'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to apply for credit facility'
            ];
        }
    }
    
    /**
     * Process Banking Callback
     */
    public function processBankingCallback(array $callbackData): array
    {
        try {
            // Validate callback signature
            if (!$this->validateCallbackSignature($callbackData)) {
                throw new Exception('Invalid callback signature');
            }
            
            $callbackType = $callbackData['type'] ?? '';
            $result = ['success' => false];
            
            switch ($callbackType) {
                case 'virtual_account_payment':
                    $result = $this->processVAPaymentCallback($callbackData);
                    break;
                    
                case 'direct_debit':
                    $result = $this->processDirectDebitCallback($callbackData);
                    break;
                    
                case 'credit_facility':
                    $result = $this->processCreditFacilityCallback($callbackData);
                    break;
                    
                default:
                    throw new Exception('Unknown callback type: ' . $callbackType);
            }
            
            // Log callback
            $this->logBankingCallback($callbackData, $result);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process banking callback'
            ];
        }
    }
    
    /**
     * Get Transaction History
     */
    public function getTransactionHistory(int $memberId, array $filters = []): array
    {
        try {
            // Get transaction history from database
            $transactions = $this->getMemberTransactions($memberId, $filters);
            
            // Format transactions
            $formattedTransactions = [];
            foreach ($transactions as $transaction) {
                $formattedTransactions[] = [
                    'id' => $transaction['uuid'],
                    'type' => $transaction['type'],
                    'amount' => $transaction['amount'],
                    'status' => $transaction['status'],
                    'description' => $transaction['description'],
                    'transaction_date' => $transaction['transaction_date'],
                    'bank_reference' => $transaction['bank_reference'],
                    'created_at' => $transaction['created_at']
                ];
            }
            
            return [
                'success' => true,
                'transactions' => $formattedTransactions,
                'total_count' => count($formattedTransactions)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transactions' => []
            ];
        }
    }
    
    /**
     * Get Account Balance
     */
    public function getAccountBalance(int $memberId): array
    {
        try {
            // Get member's virtual accounts
            $virtualAccounts = $this->getMemberVirtualAccounts($memberId);
            
            $balances = [];
            foreach ($virtualAccounts as $va) {
                // Get balance from Kop-Bank API
                $request = [
                    'virtual_account_number' => $va['virtual_account_number'],
                    'signature' => $this->signRequest(['virtual_account_number' => $va['virtual_account_number']])
                ];
                
                $response = $this->httpClient->get($this->bankConfig['api_url'] . '/virtual-accounts/balance', $request);
                
                if ($response['success']) {
                    $balanceData = json_decode($response['body'], true);
                    $balances[] = [
                        'va_id' => $va['uuid'],
                        'virtual_account_number' => $va['virtual_account_number'],
                        'bank_code' => $va['bank_code'],
                        'bank_name' => $this->getBankName($va['bank_code']),
                        'balance' => $balanceData['balance'],
                        'available_balance' => $balanceData['available_balance'],
                        'last_updated' => $balanceData['last_updated']
                    ];
                }
            }
            
            return [
                'success' => true,
                'balances' => $balances,
                'total_balance' => array_sum(array_column($balances, 'balance'))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balances' => []
            ];
        }
    }
    
    /**
     * Get Banking Dashboard
     */
    public function getBankingDashboard(int $tenantId): array
    {
        try {
            $dashboard = [
                'overview' => [],
                'virtual_accounts' => [],
                'direct_debits' => [],
                'credit_facilities' => [],
                'transaction_summary' => [],
                'compliance_status' => []
            ];
            
            // Get overview statistics
            $dashboard['overview'] = [
                'total_virtual_accounts' => $this->getTotalVirtualAccounts($tenantId),
                'active_direct_debits' => $this->getActiveDirectDebitsCount($tenantId),
                'approved_credit_facilities' => $this->getApprovedCreditFacilities($tenantId),
                'total_transaction_volume' => $this->getTotalTransactionVolume($tenantId),
                'pending_applications' => $this->getPendingApplications($tenantId)
            ];
            
            // Get recent virtual accounts
            $dashboard['virtual_accounts'] = $this->getRecentVirtualAccounts($tenantId, 10);
            
            // Get active direct debits
            $dashboard['direct_debits'] = $this->getActiveDirectDebits($tenantId, 10);
            
            // Get credit facilities
            $dashboard['credit_facilities'] = $this->getCreditFacilities($tenantId, 10);
            
            // Get transaction summary
            $dashboard['transaction_summary'] = $this->getTransactionSummary($tenantId, 30);
            
            // Get compliance status
            $dashboard['compliance_status'] = $this->getBankingComplianceStatus($tenantId);
            
            return $dashboard;
            
        } catch (Exception $e) {
            throw new Exception('Failed to get banking dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function initializeBankConfig(): void
    {
        $this->bankConfig = [
            'client_id' => env('KOP_BANK_CLIENT_ID'),
            'client_secret' => env('KOP_BANK_CLIENT_SECRET'),
            'api_url' => env('KOP_BANK_API_URL', 'https://api.kopbank.co.id/v1'),
            'environment' => env('KOP_BANK_ENVIRONMENT', 'sandbox'),
            'callback_url' => env('APP_URL') . '/api/banking/callback',
            'supported_banks' => [
                'BCA' => 'Bank Central Asia',
                'BNI' => 'Bank Negara Indonesia',
                'BRI' => 'Bank Rakyat Indonesia',
                'MANDIRI' => 'Bank Mandiri',
                'CIMB' => 'CIMB Niaga',
                'DANAMON' => 'Bank Danamon',
                'PERMATA' => 'Bank Permata'
            ]
        ];
    }
    
    private function validateVAData(array $data): void
    {
        $required = ['bank_code', 'customer_name', 'customer_phone'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!array_key_exists($data['bank_code'], $this->bankConfig['supported_banks'])) {
            throw new Exception('Unsupported bank code');
        }
        
        if (isset($data['amount']) && $data['amount'] < 10000) {
            throw new Exception('Minimum amount is Rp 10,000');
        }
    }
    
    private function validateDirectDebitData(array $data): void
    {
        $required = ['member_id', 'amount', 'frequency', 'start_date'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!in_array($data['frequency'], ['daily', 'weekly', 'monthly'])) {
            throw new Exception('Invalid frequency. Must be daily, weekly, or monthly');
        }
        
        if ($data['amount'] < 10000) {
            throw new Exception('Minimum amount is Rp 10,000');
        }
        
        if (!strtotime($data['start_date'])) {
            throw new Exception('Invalid start date format');
        }
    }
    
    private function validateCreditFacilityData(array $data): void
    {
        $required = ['member_id', 'facility_type', 'requested_amount', 'purpose', 'term_months'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!in_array($data['facility_type'], ['working_capital', 'investment', 'emergency'])) {
            throw new Exception('Invalid facility type');
        }
        
        if ($data['requested_amount'] < 1000000) {
            throw new Exception('Minimum amount is Rp 1,000,000');
        }
        
        if ($data['term_months'] < 1 || $data['term_months'] > 60) {
            throw new Exception('Term must be between 1 and 60 months');
        }
    }
    
    private function signRequest(array $data): string
    {
        // Sort data alphabetically
        ksort($data);
        
        // Create signature string
        $signatureString = '';
        foreach ($data as $key => $value) {
            if ($key !== 'signature') {
                $signatureString .= $key . '=' . $value . '&';
            }
        }
        $signatureString = rtrim($signatureString, '&');
        
        // Add client secret
        $signatureString .= $this->bankConfig['client_secret'];
        
        // Generate SHA-256 signature
        return hash('sha256', $signatureString);
    }
    
    private function validateCallbackSignature(array $callbackData): bool
    {
        $expectedSignature = $callbackData['signature'] ?? '';
        unset($callbackData['signature']);
        
        $calculatedSignature = $this->signRequest($callbackData);
        
        return hash_equals($expectedSignature, $calculatedSignature);
    }
    
    private function generateVANumber(): string
    {
        // Generate unique VA number based on bank code and timestamp
        $prefix = '88' . date('Ymd');
        $suffix = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        return $prefix . $suffix;
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
    
    private function getBankName(string $bankCode): string
    {
        return $this->bankConfig['supported_banks'][$bankCode] ?? $bankCode;
    }
    
    private function calculateNextDebitDate(string $frequency, string $startDate): string
    {
        $date = new DateTime($startDate);
        
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    private function processVAPaymentCallback(array $callbackData): array
    {
        // Process virtual account payment callback
        $va = $this->getVirtualAccountByNumber($callbackData['virtual_account_number']);
        
        if (!$va) {
            throw new Exception('Virtual account not found');
        }
        
        // Update VA status and create transaction record
        $this->updateVirtualAccountPayment($va['uuid'], $callbackData);
        
        return [
            'success' => true,
            'type' => 'virtual_account_payment',
            'va_id' => $va['uuid'],
            'amount' => $callbackData['amount'],
            'transaction_id' => $callbackData['transaction_id']
        ];
    }
    
    private function processDirectDebitCallback(array $callbackData): array
    {
        // Process direct debit callback
        $dd = $this->getDirectDebitById($callbackData['dd_id']);
        
        if (!$dd) {
            throw new Exception('Direct debit not found');
        }
        
        // Update direct debit status and create transaction record
        $this->updateDirectDebitStatus($dd['uuid'], $callbackData);
        
        return [
            'success' => true,
            'type' => 'direct_debit',
            'dd_id' => $dd['uuid'],
            'amount' => $callbackData['amount'],
            'status' => $callbackData['status']
        ];
    }
    
    private function processCreditFacilityCallback(array $callbackData): array
    {
        // Process credit facility callback
        $credit = $this->getCreditFacilityById($callbackData['credit_id']);
        
        if (!$credit) {
            throw new Exception('Credit facility not found');
        }
        
        // Update credit facility status
        $this->updateCreditFacilityStatus($credit['uuid'], $callbackData);
        
        return [
            'success' => true,
            'type' => 'credit_facility',
            'credit_id' => $credit['uuid'],
            'status' => $callbackData['status'],
            'approved_amount' => $callbackData['approved_amount'] ?? null
        ];
    }
    
    // Placeholder methods for database operations
    private function saveVirtualAccount(array $vaData): void {}
    private function saveDirectDebit(array $ddData): void {}
    private function saveCreditFacility(array $creditData): void {}
    private function getMemberBankAccount(int $memberId): array { return []; }
    private function getMemberInfo(int $memberId): array { return []; }
    private function getVirtualAccountByNumber(string $vaNumber): array { return []; }
    private function updateVirtualAccountPayment(string $vaId, array $callbackData): void {}
    private function getDirectDebitById(string $ddId): array { return []; }
    private function updateDirectDebitStatus(string $ddId, array $callbackData): void {}
    private function getCreditFacilityById(string $creditId): array { return []; }
    private function updateCreditFacilityStatus(string $creditId, array $callbackData): void {}
    private function getMemberTransactions(int $memberId, array $filters): array { return []; }
    private function getMemberVirtualAccounts(int $memberId): array { return []; }
    private function getTotalVirtualAccounts(int $tenantId): int { return 0; }
    private function getActiveDirectDebitsCount(int $tenantId): int { return 0; }
    private function getApprovedCreditFacilities(int $tenantId): int { return 0; }
    private function getTotalTransactionVolume(int $tenantId): float { return 0; }
    private function getPendingApplications(int $tenantId): int { return 0; }
    private function getRecentVirtualAccounts(int $tenantId, int $limit): array { return []; }
    private function getActiveDirectDebits(int $tenantId, int $limit): array { return []; }
    private function getCreditFacilities(int $tenantId, int $limit): array { return []; }
    private function getTransactionSummary(int $tenantId, int $days): array { return []; }
    private function getBankingComplianceStatus(int $tenantId): array { return []; }
    private function logBankingCallback(array $callbackData, array $result): void {}
}

/**
 * Usage Examples:
 * 
 * $bankingService = new BankingService();
 * 
 * // Create virtual account
 * $va = $bankingService->createVirtualAccount([
 *     'bank_code' => 'BCA',
 *     'customer_name' => 'John Doe',
 *     'customer_phone' => '08123456789',
 *     'amount' => 500000,
 *     'member_id' => 123
 * ]);
 * 
 * // Setup direct debit
 * $dd = $bankingService->setupDirectDebit([
 *     'member_id' => 123,
 *     'amount' => 1000000,
 *     'frequency' => 'monthly',
 *     'start_date' => '2026-04-01'
 * ]);
 * 
 * // Apply for credit facility
 * $credit = $bankingService->applyForCreditFacility([
 *     'member_id' => 123,
 *     'facility_type' => 'working_capital',
 *     'requested_amount' => 50000000,
 *     'purpose' => 'Modal usaha',
 *     'term_months' => 12
 * ]);
 */
