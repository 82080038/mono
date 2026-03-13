<?php
/**
 * QRIS Service - SaaS Koperasi Harian
 * 
 * QRIS payment gateway integration with merchant management,
 * transaction processing, and settlement handling
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class QRIService
{
    private $db;
    private $qrisConfig;
    private $httpClient;
    private $encryptionService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->httpClient = new HttpClient();
        $this->encryptionService = new EncryptionService();
        $this->initializeQRISConfig();
    }
    
    /**
     * Create QRIS Payment
     */
    public function createQRISPayment(array $paymentData): array
    {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Generate unique transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Calculate QRIS fee
            $qrisFee = $this->calculateQRISFee($paymentData['amount']);
            
            // Prepare QRIS request data
            $qrisRequest = [
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'transaction_id' => $transactionId,
                'amount' => $paymentData['amount'],
                'currency' => 'IDR',
                'description' => $paymentData['description'] ?? 'Pembayaran KSP',
                'customer_info' => $paymentData['customer_info'] ?? [],
                'expiry_time' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                'callback_url' => $this->qrisConfig['callback_url'],
                'notification_url' => $this->qrisConfig['notification_url']
            ];
            
            // Sign request
            $qrisRequest['signature'] = $this->signRequest($qrisRequest);
            
            // Send to QRIS API
            $response = $this->httpClient->post($this->qrisConfig['api_url'] . '/payments', $qrisRequest);
            
            if (!$response['success']) {
                throw new Exception('QRIS API request failed: ' . $response['error']);
            }
            
            $qrisResponse = json_decode($response['body'], true);
            
            // Validate QRIS response
            if (!$this->validateQRISResponse($qrisResponse)) {
                throw new Exception('Invalid QRIS response received');
            }
            
            // Save payment record
            $paymentRecord = [
                'uuid' => $this->generateUuid(),
                'transaction_id' => $transactionId,
                'qris_transaction_id' => $qrisResponse['transaction_id'],
                'member_id' => $paymentData['member_id'] ?? null,
                'loan_application_id' => $paymentData['loan_application_id'] ?? null,
                'repayment_schedule_id' => $paymentData['repayment_schedule_id'] ?? null,
                'amount' => $paymentData['amount'],
                'qris_fee' => $qrisFee,
                'net_amount' => $paymentData['amount'] - $qrisFee,
                'currency' => 'IDR',
                'description' => $paymentData['description'] ?? 'Pembayaran KSP',
                'qr_code' => $qrisResponse['qr_code'],
                'qr_url' => $qrisResponse['qr_url'],
                'status' => 'pending',
                'expiry_time' => $qrisRequest['expiry_time'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveQRISPayment($paymentRecord);
            
            // Log transaction
            $this->logQRISTransaction($paymentRecord, 'payment_created');
            
            return [
                'success' => true,
                'payment_id' => $paymentRecord['uuid'],
                'transaction_id' => $transactionId,
                'qr_code' => $qrisResponse['qr_code'],
                'qr_url' => $qrisResponse['qr_url'],
                'amount' => $paymentData['amount'],
                'qris_fee' => $qrisFee,
                'net_amount' => $paymentRecord['net_amount'],
                'expiry_time' => $paymentRecord['expiry_time'],
                'status' => 'pending'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create QRIS payment'
            ];
        }
    }
    
    /**
     * Check QRIS Payment Status
     */
    public function checkPaymentStatus(string $paymentId): array
    {
        try {
            // Get payment record
            $payment = $this->getQRISPayment($paymentId);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // If payment is already completed, return status
            if (in_array($payment['status'], ['completed', 'failed', 'expired'])) {
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
            
            // Check with QRIS API
            $request = [
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'transaction_id' => $payment['qris_transaction_id'],
                'signature' => $this->signRequest(['merchant_id' => $this->qrisConfig['merchant_id'], 'transaction_id' => $payment['qris_transaction_id']])
            ];
            
            $response = $this->httpClient->get($this->qrisConfig['api_url'] . '/payments/' . $payment['qris_transaction_id'], $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to check payment status: ' . $response['error']);
            }
            
            $qrisResponse = json_decode($response['body'], true);
            
            // Update payment status
            $updatedPayment = $this->updatePaymentStatus($paymentId, $qrisResponse);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_id' => $payment['transaction_id'],
                'status' => $updatedPayment['status'],
                'amount' => $payment['amount'],
                'paid_amount' => $updatedPayment['paid_amount'] ?? 0,
                'paid_at' => $updatedPayment['paid_at'] ?? null,
                'updated_at' => $updatedPayment['updated_at']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to check payment status'
            ];
        }
    }
    
    /**
     * Process QRIS Callback
     */
    public function processCallback(array $callbackData): array
    {
        try {
            // Validate callback signature
            if (!$this->validateCallbackSignature($callbackData)) {
                throw new Exception('Invalid callback signature');
            }
            
            // Get payment by QRIS transaction ID
            $payment = $this->getPaymentByQRISTransactionId($callbackData['transaction_id']);
            
            if (!$payment) {
                throw new Exception('Payment not found for QRIS transaction');
            }
            
            // Update payment status
            $updatedPayment = $this->updatePaymentStatus($payment['uuid'], $callbackData);
            
            // Process payment completion
            if ($updatedPayment['status'] === 'completed') {
                $this->processPaymentCompletion($updatedPayment);
            }
            
            // Log callback
            $this->logQRISCallback($callbackData, $updatedPayment);
            
            return [
                'success' => true,
                'payment_id' => $payment['uuid'],
                'status' => $updatedPayment['status'],
                'message' => 'Callback processed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process callback'
            ];
        }
    }
    
    /**
     * Get QRIS Settlement Report
     */
    public function getSettlementReport(string $date): array
    {
        try {
            // Get settlement data from QRIS API
            $request = [
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'date' => $date,
                'signature' => $this->signRequest(['merchant_id' => $this->qrisConfig['merchant_id'], 'date' => $date])
            ];
            
            $response = $this->httpClient->get($this->qrisConfig['api_url'] . '/settlements', $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to get settlement report: ' . $response['error']);
            }
            
            $settlementData = json_decode($response['body'], true);
            
            // Process settlement data
            $settlementReport = [
                'date' => $date,
                'total_transactions' => $settlementData['total_transactions'] ?? 0,
                'total_amount' => $settlementData['total_amount'] ?? 0,
                'total_fees' => $settlementData['total_fees'] ?? 0,
                'net_amount' => $settlementData['net_amount'] ?? 0,
                'transactions' => []
            ];
            
            // Process individual transactions
            foreach ($settlementData['transactions'] ?? [] as $transaction) {
                $settlementReport['transactions'][] = [
                    'transaction_id' => $transaction['transaction_id'],
                    'amount' => $transaction['amount'],
                    'fee' => $transaction['fee'],
                    'net_amount' => $transaction['net_amount'],
                    'settlement_time' => $transaction['settlement_time'],
                    'payment_method' => $transaction['payment_method']
                ];
            }
            
            // Save settlement report
            $this->saveSettlementReport($settlementReport);
            
            return [
                'success' => true,
                'settlement_report' => $settlementReport
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get settlement report'
            ];
        }
    }
    
    /**
     * Get QRIS Merchant Status
     */
    public function getMerchantStatus(): array
    {
        try {
            // Get merchant status from QRIS API
            $request = [
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'signature' => $this->signRequest(['merchant_id' => $this->qrisConfig['merchant_id']])
            ];
            
            $response = $this->httpClient->get($this->qrisConfig['api_url'] . '/merchant/status', $request);
            
            if (!$response['success']) {
                throw new Exception('Failed to get merchant status: ' . $response['error']);
            }
            
            $merchantData = json_decode($response['body'], true);
            
            return [
                'success' => true,
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'merchant_name' => $merchantData['merchant_name'],
                'status' => $merchantData['status'],
                'is_active' => $merchantData['is_active'],
                'daily_limit' => $merchantData['daily_limit'],
                'monthly_limit' => $merchantData['monthly_limit'],
                'current_daily_volume' => $merchantData['current_daily_volume'],
                'current_monthly_volume' => $merchantData['current_monthly_volume'],
                'last_settlement_date' => $merchantData['last_settlement_date'],
                'supported_payment_methods' => $merchantData['supported_payment_methods']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get merchant status'
            ];
        }
    }
    
    /**
     * Process Cross-Border Payment
     */
    public function processCrossBorderPayment(array $paymentData): array
    {
        try {
            // Validate cross-border payment data
            $this->validateCrossBorderPaymentData($paymentData);
            
            // Get exchange rate
            $exchangeRate = $this->getExchangeRate($paymentData['from_currency'], $paymentData['to_currency']);
            
            // Convert amount
            $convertedAmount = $paymentData['amount'] * $exchangeRate['rate'];
            
            // Calculate cross-border fees
            $crossBorderFee = $this->calculateCrossBorderFee($convertedAmount);
            
            // Prepare cross-border payment request
            $request = [
                'merchant_id' => $this->qrisConfig['merchant_id'],
                'transaction_id' => $this->generateTransactionId(),
                'amount' => $convertedAmount,
                'original_amount' => $paymentData['amount'],
                'from_currency' => $paymentData['from_currency'],
                'to_currency' => $paymentData['to_currency'],
                'exchange_rate' => $exchangeRate['rate'],
                'cross_border_fee' => $crossBorderFee,
                'recipient_info' => $paymentData['recipient_info'],
                'description' => $paymentData['description'] ?? 'Cross-border payment',
                'signature' => $this->signRequest($paymentData)
            ];
            
            // Send to QRIS cross-border API
            $response = $this->httpClient->post($this->qrisConfig['cross_border_api_url'] . '/payments', $request);
            
            if (!$response['success']) {
                throw new Exception('Cross-border payment failed: ' . $response['error']);
            }
            
            $responseData = json_decode($response['body'], true);
            
            // Save cross-border payment record
            $this->saveCrossBorderPayment($request, $responseData);
            
            return [
                'success' => true,
                'transaction_id' => $request['transaction_id'],
                'original_amount' => $paymentData['amount'],
                'converted_amount' => $convertedAmount,
                'exchange_rate' => $exchangeRate['rate'],
                'cross_border_fee' => $crossBorderFee,
                'status' => $responseData['status'],
                'tracking_id' => $responseData['tracking_id']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process cross-border payment'
            ];
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function initializeQRISConfig(): void
    {
        $this->qrisConfig = [
            'merchant_id' => env('QRIS_MERCHANT_ID'),
            'client_key' => env('QRIS_CLIENT_KEY'),
            'secret_key' => env('QRIS_SECRET_KEY'),
            'environment' => env('QRIS_ENVIRONMENT', 'sandbox'),
            'api_url' => env('QRIS_ENVIRONMENT') === 'production' 
                ? 'https://api.qris.id/v1.0' 
                : 'https://api-sandbox.qris.id/v1.0',
            'cross_border_api_url' => env('QRIS_ENVIRONMENT') === 'production'
                ? 'https://api.qris.id/v1.0/cross-border'
                : 'https://api-sandbox.qris.id/v1.0/cross-border',
            'callback_url' => env('APP_URL') . '/api/qris/callback',
            'notification_url' => env('APP_URL') . '/api/qris/notification',
            'fee_percentage' => 0.7, // 0.7% QRIS fee
            'cross_border_fee_percentage' => 2.5 // 2.5% cross-border fee
        ];
    }
    
    private function validatePaymentData(array $data): void
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
        
        if ($data['amount'] > 10000000) {
            throw new Exception('Maximum amount is Rp 10,000,000');
        }
    }
    
    private function validateCrossBorderPaymentData(array $data): void
    {
        $required = ['amount', 'from_currency', 'to_currency', 'recipient_info'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required for cross-border payment");
            }
        }
        
        if (!in_array($data['from_currency'], ['IDR', 'USD', 'EUR', 'SGD'])) {
            throw new Exception('Unsupported source currency');
        }
        
        if (!in_array($data['to_currency'], ['IDR', 'USD', 'EUR', 'SGD'])) {
            throw new Exception('Unsupported target currency');
        }
    }
    
    private function calculateQRISFee(float $amount): float
    {
        return $amount * ($this->qrisConfig['fee_percentage'] / 100);
    }
    
    private function calculateCrossBorderFee(float $amount): float
    {
        return $amount * ($this->qrisConfig['cross_border_fee_percentage'] / 100);
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
        
        // Add secret key
        $signatureString .= $this->qrisConfig['secret_key'];
        
        // Generate SHA-256 signature
        return hash('sha256', $signatureString);
    }
    
    private function validateQRISResponse(array $response): bool
    {
        $requiredFields = ['transaction_id', 'qr_code', 'qr_url', 'status'];
        
        foreach ($requiredFields as $field) {
            if (!isset($response[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validateCallbackSignature(array $callbackData): bool
    {
        $expectedSignature = $callbackData['signature'];
        unset($callbackData['signature']);
        
        $calculatedSignature = $this->signRequest($callbackData);
        
        return hash_equals($expectedSignature, $calculatedSignature);
    }
    
    private function generateTransactionId(): string
    {
        return 'QRIS' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
    
    private function getExchangeRate(string $fromCurrency, string $toCurrency): array
    {
        // In production, integrate with real exchange rate API
        $rates = [
            'IDR_USD' => 0.000064,
            'IDR_EUR' => 0.000059,
            'IDR_SGD' => 0.000087,
            'USD_IDR' => 15625,
            'EUR_IDR' => 16949,
            'SGD_IDR' => 11494
        ];
        
        $key = $fromCurrency . '_' . $toCurrency;
        
        if (!isset($rates[$key])) {
            throw new Exception('Exchange rate not available for ' . $key);
        }
        
        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'rate' => $rates[$key],
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Placeholder methods for database operations
    private function saveQRISPayment(array $paymentData): void {}
    private function getQRISPayment(string $paymentId): array { return []; }
    private function getPaymentByQRISTransactionId(string $qrisTransactionId): array { return []; }
    private function updatePaymentStatus(string $paymentId, array $qrisResponse): array { return []; }
    private function processPaymentCompletion(array $payment): void {}
    private function saveSettlementReport(array $settlementReport): void {}
    private function saveCrossBorderPayment(array $request, array $response): void {}
    private function logQRISTransaction(array $payment, string $action): void {}
    private function logQRISCallback(array $callbackData, array $payment): void {}
}

/**
 * Usage Examples:
 * 
 * $qrisService = new QRIService();
 * 
 * // Create QRIS payment
 * $payment = $qrisService->createQRISPayment([
 *     'amount' => 500000,
 *     'description' => 'Pembayaran Pinjaman',
 *     'member_id' => 123,
 *     'loan_application_id' => 456
 * ]);
 * 
 * // Check payment status
 * $status = $qrisService->checkPaymentStatus($payment['payment_id']);
 * 
 * // Get merchant status
 * $merchantStatus = $qrisService->getMerchantStatus();
 * 
 * // Process cross-border payment
 * $crossBorder = $qrisService->processCrossBorderPayment([
 *     'amount' => 1000000,
 *     'from_currency' => 'IDR',
 *     'to_currency' => 'USD',
 *     'recipient_info' => ['name' => 'John Doe', 'account' => '123456789']
 * ]);
 */
