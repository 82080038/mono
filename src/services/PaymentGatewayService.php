<?php
/**
 * Payment Gateway Service - SaaS Koperasi Harian
 * 
 * Third-party payment services integration with digital wallets,
 * e-commerce platforms, and payment aggregators
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class PaymentGatewayService
{
    private $db;
    private $gatewayConfigs;
    private $httpClient;
    private $encryptionService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->httpClient = new HttpClient();
        $this->encryptionService = new EncryptionService();
        $this->initializeGatewayConfigs();
    }
    
    /**
     * Create Digital Wallet Payment
     */
    public function createDigitalWalletPayment(array $paymentData): array
    {
        try {
            $walletType = $paymentData['wallet_type'];
            
            if (!$this->isSupportedWallet($walletType)) {
                throw new Exception('Unsupported wallet type: ' . $walletType);
            }
            
            // Validate payment data
            $this->validateDigitalWalletData($paymentData);
            
            // Get wallet configuration
            $walletConfig = $this->gatewayConfigs['digital_wallets'][$walletType];
            
            // Generate transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Prepare wallet-specific request
            $request = $this->prepareWalletRequest($paymentData, $walletConfig, $transactionId);
            
            // Send to wallet API
            $response = $this->httpClient->post($walletConfig['api_url'] . '/payments', $request);
            
            if (!$response['success']) {
                throw new Exception('Digital wallet payment failed: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save payment record
            $paymentRecord = [
                'uuid' => $this->generateUuid(),
                'transaction_id' => $transactionId,
                'wallet_transaction_id' => $responseData['transaction_id'],
                'wallet_type' => $walletType,
                'member_id' => $paymentData['member_id'] ?? null,
                'loan_application_id' => $paymentData['loan_application_id'] ?? null,
                'repayment_schedule_id' => $paymentData['repayment_schedule_id'] ?? null,
                'amount' => $paymentData['amount'],
                'wallet_fee' => $this->calculateWalletFee($paymentData['amount'], $walletType),
                'net_amount' => $paymentData['amount'] - $this->calculateWalletFee($paymentData['amount'], $walletType),
                'currency' => 'IDR',
                'description' => $paymentData['description'] ?? 'Pembayaran KSP',
                'customer_phone' => $paymentData['customer_phone'],
                'status' => 'pending',
                'expiry_time' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveDigitalWalletPayment($paymentRecord);
            
            return [
                'success' => true,
                'payment_id' => $paymentRecord['uuid'],
                'transaction_id' => $transactionId,
                'wallet_type' => $walletType,
                'wallet_transaction_id' => $responseData['transaction_id'],
                'payment_url' => $responseData['payment_url'] ?? null,
                'qr_code' => $responseData['qr_code'] ?? null,
                'deep_link' => $responseData['deep_link'] ?? null,
                'amount' => $paymentData['amount'],
                'wallet_fee' => $paymentRecord['wallet_fee'],
                'net_amount' => $paymentRecord['net_amount'],
                'expiry_time' => $paymentRecord['expiry_time'],
                'status' => 'pending'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create digital wallet payment'
            ];
        }
    }
    
    /**
     * Create E-commerce Payment
     */
    public function createEcommercePayment(array $paymentData): array
    {
        try {
            $platform = $paymentData['platform'];
            
            if (!$this->isSupportedPlatform($platform)) {
                throw new Exception('Unsupported e-commerce platform: ' . $platform);
            }
            
            // Validate e-commerce payment data
            $this->validateEcommerceData($paymentData);
            
            // Get platform configuration
            $platformConfig = $this->gatewayConfigs['ecommerce'][$platform];
            
            // Generate transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Prepare platform-specific request
            $request = $this->prepareEcommerceRequest($paymentData, $platformConfig, $transactionId);
            
            // Send to platform API
            $response = $this->httpClient->post($platformConfig['api_url'] . '/payments', $request);
            
            if (!$response['success']) {
                throw new Exception('E-commerce payment failed: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save payment record
            $paymentRecord = [
                'uuid' => $this->generateUuid(),
                'transaction_id' => $transactionId,
                'platform_transaction_id' => $responseData['transaction_id'],
                'platform' => $platform,
                'member_id' => $paymentData['member_id'] ?? null,
                'loan_application_id' => $paymentData['loan_application_id'] ?? null,
                'repayment_schedule_id' => $paymentData['repayment_schedule_id'] ?? null,
                'amount' => $paymentData['amount'],
                'platform_fee' => $this->calculateEcommerceFee($paymentData['amount'], $platform),
                'net_amount' => $paymentData['amount'] - $this->calculateEcommerceFee($paymentData['amount'], $platform),
                'currency' => 'IDR',
                'description' => $paymentData['description'] ?? 'Pembayaran KSP',
                'customer_email' => $paymentData['customer_email'],
                'callback_url' => $paymentData['callback_url'] ?? null,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveEcommercePayment($paymentRecord);
            
            return [
                'success' => true,
                'payment_id' => $paymentRecord['uuid'],
                'transaction_id' => $transactionId,
                'platform' => $platform,
                'platform_transaction_id' => $responseData['transaction_id'],
                'payment_url' => $responseData['payment_url'],
                'redirect_url' => $responseData['redirect_url'],
                'amount' => $paymentData['amount'],
                'platform_fee' => $paymentRecord['platform_fee'],
                'net_amount' => $paymentRecord['net_amount'],
                'status' => 'pending'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create e-commerce payment'
            ];
        }
    }
    
    /**
     * Process Payment Callback
     */
    public function processPaymentCallback(array $callbackData): array
    {
        try {
            $paymentType = $callbackData['payment_type'] ?? '';
            
            switch ($paymentType) {
                case 'digital_wallet':
                    return $this->processDigitalWalletCallback($callbackData);
                    
                case 'ecommerce':
                    return $this->processEcommerceCallback($callbackData);
                    
                case 'payment_aggregator':
                    return $this->processAggregatorCallback($callbackData);
                    
                default:
                    throw new Exception('Unknown payment type: ' . $paymentType);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process payment callback'
            ];
        }
    }
    
    /**
     * Get Payment Status
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            // Get payment record
            $payment = $this->getPaymentRecord($paymentId);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // If payment is already completed, return status
            if (in_array($payment['status'], ['completed', 'failed', 'expired', 'cancelled'])) {
                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'transaction_id' => $payment['transaction_id'],
                    'status' => $payment['status'],
                    'amount' => $payment['amount'],
                    'paid_amount' => $payment['paid_amount'] ?? 0,
                    'paid_at' => $payment['paid_at'] ?? null,
                    'updated_at' => $payment['updated_at']
                ];
            }
            
            // Check with payment provider
            if ($payment['wallet_type']) {
                $status = $this->checkDigitalWalletStatus($payment);
            } elseif ($payment['platform']) {
                $status = $this->checkEcommerceStatus($payment);
            } else {
                $status = $this->checkAggregatorStatus($payment);
            }
            
            return $status;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get payment status'
            ];
        }
    }
    
    /**
     * Get Payment Gateway Dashboard
     */
    public function getPaymentGatewayDashboard(int $tenantId): array
    {
        try {
            $dashboard = [
                'overview' => [],
                'digital_wallets' => [],
                'ecommerce' => [],
                'aggregators' => [],
                'transaction_summary' => [],
                'settlement_summary' => []
            ];
            
            // Get overview statistics
            $dashboard['overview'] = [
                'total_transactions' => $this->getTotalTransactions($tenantId),
                'total_volume' => $this->getTotalVolume($tenantId),
                'success_rate' => $this->getSuccessRate($tenantId),
                'average_transaction_amount' => $this->getAverageTransactionAmount($tenantId),
                'pending_transactions' => $this->getPendingTransactions($tenantId),
                'failed_transactions' => $this->getFailedTransactions($tenantId)
            ];
            
            // Get digital wallet statistics
            $dashboard['digital_wallets'] = $this->getDigitalWalletStats($tenantId);
            
            // Get e-commerce statistics
            $dashboard['ecommerce'] = $this->getEcommerceStats($tenantId);
            
            // Get aggregator statistics
            $dashboard['aggregators'] = $this->getAggregatorStats($tenantId);
            
            // Get transaction summary
            $dashboard['transaction_summary'] = $this->getTransactionSummary($tenantId, 30);
            
            // Get settlement summary
            $dashboard['settlement_summary'] = $this->getSettlementSummary($tenantId, 30);
            
            return $dashboard;
            
        } catch (Exception $e) {
            throw new Exception('Failed to get payment gateway dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Create Payment Link
     */
    public function createPaymentLink(array $linkData): array
    {
        try {
            // Validate payment link data
            $this->validatePaymentLinkData($linkData);
            
            // Generate unique link ID
            $linkId = $this->generateLinkId();
            
            // Create payment link record
            $paymentLink = [
                'uuid' => $this->generateUuid(),
                'link_id' => $linkId,
                'member_id' => $linkData['member_id'] ?? null,
                'loan_application_id' => $linkData['loan_application_id'] ?? null,
                'amount' => $linkData['amount'],
                'description' => $linkData['description'] ?? 'Pembayaran KSP',
                'customer_name' => $linkData['customer_name'] ?? null,
                'customer_email' => $linkData['customer_email'] ?? null,
                'customer_phone' => $linkData['customer_phone'] ?? null,
                'expiry_date' => $linkData['expiry_date'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
                'allowed_payment_methods' => $linkData['allowed_payment_methods'] ?? ['all'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->savePaymentLink($paymentLink);
            
            // Generate payment URL
            $paymentUrl = $this->generatePaymentUrl($linkId);
            
            return [
                'success' => true,
                'link_id' => $linkId,
                'payment_url' => $paymentUrl,
                'amount' => $linkData['amount'],
                'description' => $linkData['description'] ?? 'Pembayaran KSP',
                'expiry_date' => $paymentLink['expiry_date'],
                'status' => 'active'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create payment link'
            ];
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function initializeGatewayConfigs(): void
    {
        $this->gatewayConfigs = [
            'digital_wallets' => [
                'gopay' => [
                    'name' => 'GoPay',
                    'api_url' => 'https://api.gopay.com/v2.0',
                    'client_id' => env('GOPAY_CLIENT_ID'),
                    'client_secret' => env('GOPAY_CLIENT_SECRET'),
                    'fee_percentage' => 0.7,
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ],
                'ovo' => [
                    'name' => 'OVO',
                    'api_url' => 'https://api.ovo.id/v1.0',
                    'client_id' => env('OVO_CLIENT_ID'),
                    'client_secret' => env('OVO_CLIENT_SECRET'),
                    'fee_percentage' => 0.85,
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ],
                'dana' => [
                    'name' => 'DANA',
                    'api_url' => 'https://api.dana.id/v1.0',
                    'client_id' => env('DANA_CLIENT_ID'),
                    'client_secret' => env('DANA_CLIENT_SECRET'),
                    'fee_percentage' => 0.8,
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ],
                'shopeepay' => [
                    'name' => 'ShopeePay',
                    'api_url' => 'https://api.shopeepay.com/v1.0',
                    'client_id' => env('SHOPEEPAY_CLIENT_ID'),
                    'client_secret' => env('SHOPEEPAY_CLIENT_SECRET'),
                    'fee_percentage' => 0.75,
                    'min_amount' => 1000,
                    'max_amount' => 10000000
                ]
            ],
            'ecommerce' => [
                'tokopedia' => [
                    'name' => 'Tokopedia',
                    'api_url' => 'https://api.tokopedia.com/v1.0',
                    'client_id' => env('TOKOPEDIA_CLIENT_ID'),
                    'client_secret' => env('TOKOPEDIA_CLIENT_SECRET'),
                    'fee_percentage' => 1.0,
                    'min_amount' => 1000,
                    'max_amount' => 50000000
                ],
                'shopee' => [
                    'name' => 'Shopee',
                    'api_url' => 'https://api.shopee.com/v1.0',
                    'client_id' => env('SHOPE_CLIENT_ID'),
                    'client_secret' => env('SHOPE_CLIENT_SECRET'),
                    'fee_percentage' => 1.2,
                    'min_amount' => 1000,
                    'max_amount' => 50000000
                ],
                'bukalapak' => [
                    'name' => 'Bukalapak',
                    'api_url' => 'https://api.bukalapak.com/v1.0',
                    'client_id' => env('BUKALAPAK_CLIENT_ID'),
                    'client_secret' => env('BUKALAPAK_CLIENT_SECRET'),
                    'fee_percentage' => 1.1,
                    'min_amount' => 1000,
                    'max_amount' => 50000000
                ]
            ],
            'aggregators' => [
                'midtrans' => [
                    'name' => 'Midtrans',
                    'api_url' => 'https://api.midtrans.com/v2.0',
                    'server_key' => env('MIDTRANS_SERVER_KEY'),
                    'client_key' => env('MIDTRANS_CLIENT_KEY'),
                    'fee_percentage' => 2.0,
                    'min_amount' => 1000,
                    'max_amount' => 100000000
                ],
                'xendit' => [
                    'name' => 'Xendit',
                    'api_url' => 'https://api.xendit.co/v1.0',
                    'secret_key' => env('XENDIT_SECRET_KEY'),
                    'api_key' => env('XENDIT_API_KEY'),
                    'fee_percentage' => 1.8,
                    'min_amount' => 1000,
                    'max_amount' => 100000000
                ]
            ]
        ];
    }
    
    private function validateDigitalWalletData(array $data): void
    {
        $required = ['wallet_type', 'customer_phone', 'amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        $walletConfig = $this->gatewayConfigs['digital_wallets'][$data['wallet_type']];
        
        if ($data['amount'] < $walletConfig['min_amount']) {
            throw new Exception('Minimum amount is Rp ' . number_format($walletConfig['min_amount']));
        }
        
        if ($data['amount'] > $walletConfig['max_amount']) {
            throw new Exception('Maximum amount is Rp ' . number_format($walletConfig['max_amount']));
        }
    }
    
    private function validateEcommerceData(array $data): void
    {
        $required = ['platform', 'customer_email', 'amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        $platformConfig = $this->gatewayConfigs['ecommerce'][$data['platform']];
        
        if ($data['amount'] < $platformConfig['min_amount']) {
            throw new Exception('Minimum amount is Rp ' . number_format($platformConfig['min_amount']));
        }
        
        if ($data['amount'] > $platformConfig['max_amount']) {
            throw new Exception('Maximum amount is Rp ' . number_format($platformConfig['max_amount']));
        }
    }
    
    private function validatePaymentLinkData(array $data): void
    {
        $required = ['amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if ($data['amount'] < 1000) {
            throw new Exception('Minimum amount is Rp 1,000');
        }
        
        if (isset($data['expiry_date']) && !strtotime($data['expiry_date'])) {
            throw new Exception('Invalid expiry date format');
        }
    }
    
    private function isSupportedWallet(string $walletType): bool
    {
        return array_key_exists($walletType, $this->gatewayConfigs['digital_wallets']);
    }
    
    private function isSupportedPlatform(string $platform): bool
    {
        return array_key_exists($platform, $this->gatewayConfigs['ecommerce']);
    }
    
    private function calculateWalletFee(float $amount, string $walletType): float
    {
        $config = $this->gatewayConfigs['digital_wallets'][$walletType];
        return $amount * ($config['fee_percentage'] / 100);
    }
    
    private function calculateEcommerceFee(float $amount, string $platform): float
    {
        $config = $this->gatewayConfigs['ecommerce'][$platform];
        return $amount * ($config['fee_percentage'] / 100);
    }
    
    private function prepareWalletRequest(array $paymentData, array $walletConfig, string $transactionId): array
    {
        return [
            'client_id' => $walletConfig['client_id'],
            'transaction_id' => $transactionId,
            'amount' => $paymentData['amount'],
            'currency' => 'IDR',
            'description' => $paymentData['description'] ?? 'Pembayaran KSP',
            'customer_phone' => $paymentData['customer_phone'],
            'callback_url' => env('APP_URL') . '/api/payment/callback',
            'signature' => $this->signRequest($paymentData, $walletConfig)
        ];
    }
    
    private function prepareEcommerceRequest(array $paymentData, array $platformConfig, string $transactionId): array
    {
        return [
            'client_id' => $platformConfig['client_id'],
            'transaction_id' => $transactionId,
            'amount' => $paymentData['amount'],
            'currency' => 'IDR',
            'description' => $paymentData['description'] ?? 'Pembayaran KSP',
            'customer_email' => $paymentData['customer_email'],
            'callback_url' => $paymentData['callback_url'] ?? env('APP_URL') . '/api/payment/callback',
            'signature' => $this->signRequest($paymentData, $platformConfig)
        ];
    }
    
    private function signRequest(array $data, array $config): string
    {
        ksort($data);
        
        $signatureString = '';
        foreach ($data as $key => $value) {
            if ($key !== 'signature') {
                $signatureString .= $key . '=' . $value . '&';
            }
        }
        $signatureString = rtrim($signatureString, '&');
        $signatureString .= $config['client_secret'];
        
        return hash('sha256', $signatureString);
    }
    
    private function generateTransactionId(): string
    {
        return 'PAY' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    private function generateLinkId(): string
    {
        return 'LINK' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
    
    private function generatePaymentUrl(string $linkId): string
    {
        return env('APP_URL') . '/payment/' . $linkId;
    }
    
    // Placeholder methods for database operations
    private function saveDigitalWalletPayment(array $paymentData): void {}
    private function saveEcommercePayment(array $paymentData): void {}
    private function getPaymentRecord(string $paymentId): array { return []; }
    private function processDigitalWalletCallback(array $callbackData): array { return ['success' => true]; }
    private function processEcommerceCallback(array $callbackData): array { return ['success' => true]; }
    private function processAggregatorCallback(array $callbackData): array { return ['success' => true]; }
    private function checkDigitalWalletStatus(array $payment): array { return ['success' => true]; }
    private function checkEcommerceStatus(array $payment): array { return ['success' => true]; }
    private function checkAggregatorStatus(array $payment): array { return ['success' => true]; }
    private function getTotalTransactions(int $tenantId): int { return 0; }
    private function getTotalVolume(int $tenantId): float { return 0; }
    private function getSuccessRate(int $tenantId): float { return 0.0; }
    private function getAverageTransactionAmount(int $tenantId): float { return 0.0; }
    private function getPendingTransactions(int $tenantId): int { return 0; }
    private function getFailedTransactions(int $tenantId): int { return 0; }
    private function getDigitalWalletStats(int $tenantId): array { return []; }
    private function getEcommerceStats(int $tenantId): array { return []; }
    private function getAggregatorStats(int $tenantId): array { return []; }
    private function getTransactionSummary(int $tenantId, int $days): array { return []; }
    private function getSettlementSummary(int $tenantId, int $days): array { return []; }
    private function savePaymentLink(array $linkData): void {}
}

/**
 * Usage Examples:
 * 
 * $paymentGatewayService = new PaymentGatewayService();
 * 
 * // Create digital wallet payment
 * $walletPayment = $paymentGatewayService->createDigitalWalletPayment([
 *     'wallet_type' => 'gopay',
 *     'customer_phone' => '08123456789',
 *     'amount' => 500000,
 *     'member_id' => 123
 * ]);
 * 
 * // Create e-commerce payment
 * $ecommercePayment = $paymentGatewayService->createEcommercePayment([
 *     'platform' => 'tokopedia',
 *     'customer_email' => 'customer@example.com',
 *     'amount' => 1000000,
 *     'member_id' => 123
 * ]);
 * 
 * // Create payment link
 * $paymentLink = $paymentGatewayService->createPaymentLink([
 *     'amount' => 500000,
 *     'description' => 'Pembayaran Pinjaman',
 *     'customer_email' => 'customer@example.com'
 * ]);
 * 
 * // Get payment gateway dashboard
 * $dashboard = $paymentGatewayService->getPaymentGatewayDashboard(1);
 */
