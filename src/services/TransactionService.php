<?php
/**
 * Transaction Service - SaaS Koperasi Harian
 * 
 * Handles all financial transactions with immutable logging,
 * blockchain-style audit trails, and multi-tenant support
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class TransactionService
{
    private $db;
    private $authService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->authService = new AuthService();
    }
    
    /**
     * Log Transaction with Immutable Hash
     */
    public function logTransaction(array $transactionData): string
    {
        try {
            // Generate transaction hash
            $transactionHash = $this->generateTransactionHash($transactionData);
            
            // Get previous transaction hash for chain
            $previousHash = $this->getLastTransactionHash($transactionData['tenant_id'] ?? 1);
            
            // Create transaction record
            $transactionId = $this->createTransaction([
                'uuid' => $this->generateUuid(),
                'tenant_id' => $transactionData['tenant_id'] ?? 1,
                'transaction_type' => $transactionData['transaction_type'],
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency'] ?? 'IDR',
                'member_id' => $transactionData['member_id'] ?? null,
                'mantri_id' => $transactionData['mantri_id'] ?? null,
                'loan_application_id' => $transactionData['loan_application_id'] ?? null,
                'repayment_schedule_id' => $transactionData['repayment_schedule_id'] ?? null,
                'reference_number' => $transactionData['reference_number'] ?? null,
                'description' => $transactionData['description'] ?? null,
                'location' => $transactionData['location'] ?? null,
                'transaction_hash' => $transactionHash,
                'previous_hash' => $previousHash,
                'created_by' => $transactionData['created_by']
            ]);
            
            // Log audit trail
            $this->logAuditTrail([
                'tenant_id' => $transactionData['tenant_id'] ?? 1,
                'user_id' => $transactionData['created_by'],
                'action' => 'transaction_created',
                'resource_type' => 'transaction',
                'resource_id' => $transactionId,
                'old_values' => null,
                'new_values' => json_encode($transactionData),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return $transactionId;
            
        } catch (Exception $e) {
            throw new Exception('Transaction logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create Transaction Correction (Immutable)
     */
    public function createTransactionCorrection(int $originalTransactionId, array $correctionData, int $createdBy): string
    {
        try {
            // Get original transaction
            $originalTransaction = $this->getTransactionById($originalTransactionId);
            
            if (!$originalTransaction) {
                throw new Exception('Original transaction not found');
            }
            
            // Validate correction data
            $this->validateCorrectionData($correctionData);
            
            // Create correction record
            $correctionId = $this->createCorrection([
                'uuid' => $this->generateUuid(),
                'original_transaction_id' => $originalTransactionId,
                'correction_amount' => $correctionData['correction_amount'],
                'correction_reason' => $correctionData['correction_reason'],
                'approved_by' => $correctionData['approved_by'] ?? $createdBy,
                'approved_at' => date('Y-m-d H:i:s'),
                'created_by' => $createdBy
            ]);
            
            // Create new transaction for correction
            $correctionTransactionId = $this->logTransaction([
                'tenant_id' => $originalTransaction['tenant_id'],
                'transaction_type' => 'correction',
                'amount' => $correctionData['correction_amount'],
                'member_id' => $originalTransaction['member_id'],
                'mantri_id' => $originalTransaction['mantri_id'],
                'reference_number' => 'CORR-' . $originalTransaction['reference_number'],
                'description' => 'Correction for transaction ' . $originalTransaction['reference_number'] . ': ' . $correctionData['correction_reason'],
                'created_by' => $createdBy
            ]);
            
            return $correctionId;
            
        } catch (Exception $e) {
            throw new Exception('Transaction correction failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Transaction by ID
     */
    public function getTransactionById(int $transactionId): ?array
    {
        $sql = "SELECT * FROM transactions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get Transactions List with Filtering
     */
    public function getTransactionsList(int $tenantId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ["t.tenant_id = ?"];
            $params = [$tenantId];
            
            if (!empty($filters['transaction_type'])) {
                $whereConditions[] = "t.transaction_type = ?";
                $params[] = $filters['transaction_type'];
            }
            
            if (!empty($filters['member_id'])) {
                $whereConditions[] = "t.member_id = ?";
                $params[] = $filters['member_id'];
            }
            
            if (!empty($filters['mantri_id'])) {
                $whereConditions[] = "t.mantri_id = ?";
                $params[] = $filters['mantri_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "t.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "t.created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $whereConditions[] = "t.amount >= ?";
                $params[] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $whereConditions[] = "t.amount <= ?";
                $params[] = $filters['amount_max'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(t.reference_number LIKE ? OR t.description LIKE ? OR m.name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM transactions t 
                         LEFT JOIN members m ON t.member_id = m.id 
                         WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get transactions data
            $sql = "SELECT t.*, m.name as member_name, m.phone as member_phone, 
                           man.name as mantri_name, man.phone as mantri_phone
                    FROM transactions t 
                    LEFT JOIN members m ON t.member_id = m.id 
                    LEFT JOIN mantris man ON t.mantri_id = man.id 
                    WHERE {$whereClause} 
                    ORDER BY t.created_at DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $transactions,
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
                'message' => 'Failed to get transactions list'
            ];
        }
    }
    
    /**
     * Get Transaction Chain for Verification
     */
    public function getTransactionChain(int $transactionId): array
    {
        try {
            $transaction = $this->getTransactionById($transactionId);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            $chain = [];
            $currentTransaction = $transaction;
            
            // Build chain backwards
            while ($currentTransaction) {
                array_unshift($chain, [
                    'id' => $currentTransaction['id'],
                    'hash' => $currentTransaction['transaction_hash'],
                    'previous_hash' => $currentTransaction['previous_hash'],
                    'created_at' => $currentTransaction['created_at']
                ]);
                
                if ($currentTransaction['previous_hash']) {
                    $currentTransaction = $this->getTransactionByHash($currentTransaction['previous_hash']);
                } else {
                    break;
                }
            }
            
            // Verify chain integrity
            $chainValid = $this->verifyTransactionChain($chain);
            
            return [
                'success' => true,
                'chain' => $chain,
                'chain_valid' => $chainValid,
                'message' => $chainValid ? 'Transaction chain is valid' : 'Transaction chain integrity compromised'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get transaction chain'
            ];
        }
    }
    
    /**
     * Get Daily Cash Flow Report
     */
    public function getDailyCashFlow(int $tenantId, string $date): array
    {
        try {
            $sql = "SELECT 
                        transaction_type,
                        COUNT(*) as transaction_count,
                        SUM(amount) as total_amount,
                        AVG(amount) as average_amount
                    FROM transactions 
                    WHERE tenant_id = ? AND DATE(created_at) = ?
                    GROUP BY transaction_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tenantId, $date]);
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate cash flow
            $cashIn = 0;
            $cashOut = 0;
            
            foreach ($transactions as $transaction) {
                if (in_array($transaction['transaction_type'], ['loan_payment', 'deposit'])) {
                    $cashIn += $transaction['total_amount'];
                } elseif (in_array($transaction['transaction_type'], ['loan_disbursement', 'withdrawal'])) {
                    $cashOut += $transaction['total_amount'];
                }
            }
            
            $netCashFlow = $cashIn - $cashOut;
            
            return [
                'success' => true,
                'date' => $date,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_cash_flow' => $netCashFlow,
                'transaction_breakdown' => $transactions,
                'total_transactions' => array_sum(array_column($transactions, 'transaction_count'))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get daily cash flow'
            ];
        }
    }
    
    /**
     * Get Mantri Cash Position
     */
    public function getMantriCashPosition(int $mantriId, int $tenantId): array
    {
        try {
            // Get cash collected today
            $sql = "SELECT 
                        COALESCE(SUM(amount), 0) as cash_collected,
                        COUNT(*) as transaction_count
                    FROM transactions 
                    WHERE tenant_id = ? AND mantri_id = ? 
                    AND DATE(created_at) = CURDATE()
                    AND transaction_type = 'loan_payment'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tenantId, $mantriId]);
            $cashCollected = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get cash limit
            $mantriSql = "SELECT max_cash_limit, current_cash_on_hand FROM mantris WHERE id = ?";
            $mantriStmt = $this->db->prepare($mantriSql);
            $mantriStmt->execute([$mantriId]);
            $mantri = $mantriStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mantri) {
                throw new Exception('Mantri not found');
            }
            
            $cashUtilization = ($mantri['current_cash_on_hand'] / $mantri['max_cash_limit']) * 100;
            
            return [
                'success' => true,
                'mantri_id' => $mantriId,
                'cash_limit' => $mantri['max_cash_limit'],
                'current_cash_on_hand' => $mantri['current_cash_on_hand'],
                'cash_collected_today' => $cashCollected['cash_collected'],
                'transactions_today' => $cashCollected['transaction_count'],
                'cash_utilization' => round($cashUtilization, 2),
                'remaining_capacity' => $mantri['max_cash_limit'] - $mantri['current_cash_on_hand'],
                'status' => $cashUtilization >= 90 ? 'critical' : ($cashUtilization >= 70 ? 'warning' : 'normal')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get mantri cash position'
            ];
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function generateTransactionHash(array $transactionData): string
    {
        $hashData = [
            'tenant_id' => $transactionData['tenant_id'] ?? 1,
            'transaction_type' => $transactionData['transaction_type'],
            'amount' => $transactionData['amount'],
            'member_id' => $transactionData['member_id'] ?? null,
            'mantri_id' => $transactionData['mantri_id'] ?? null,
            'created_by' => $transactionData['created_by'],
            'timestamp' => date('Y-m-d H:i:s.u')
        ];
        
        return hash('sha256', json_encode($hashData));
    }
    
    private function getLastTransactionHash(int $tenantId): ?string
    {
        $sql = "SELECT transaction_hash FROM transactions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['transaction_hash'] : null;
    }
    
    private function getTransactionByHash(string $hash): ?array
    {
        $sql = "SELECT * FROM transactions WHERE transaction_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hash]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function verifyTransactionChain(array $chain): bool
    {
        for ($i = 1; $i < count($chain); $i++) {
            $current = $chain[$i];
            $previous = $chain[$i - 1];
            
            if ($current['previous_hash'] !== $previous['hash']) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validateCorrectionData(array $data): void
    {
        if (empty($data['correction_amount'])) {
            throw new Exception('Correction amount is required');
        }
        
        if (empty($data['correction_reason'])) {
            throw new Exception('Correction reason is required');
        }
        
        if (!is_numeric($data['correction_amount'])) {
            throw new Exception('Correction amount must be numeric');
        }
    }
    
    private function createTransaction(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO transactions (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    private function createCorrection(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO transaction_corrections (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    private function logAuditTrail(array $auditData): void
    {
        $fields = array_keys($auditData);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO audit_logs (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($auditData));
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
 * $transactionService = new TransactionService();
 * 
 * // Log transaction
 * $transactionId = $transactionService->logTransaction([
 *     'tenant_id' => 1,
 *     'transaction_type' => 'loan_payment',
 *     'amount' => 500000,
 *     'member_id' => 123,
 *     'mantri_id' => 456,
 *     'reference_number' => 'PAY001',
 *     'description' => 'Loan payment for installment #1',
 *     'created_by' => 789
 * ]);
 * 
 * // Get transactions list
 * $transactions = $transactionService->getTransactionsList(1, [
 *     'transaction_type' => 'loan_payment',
 *     'date_from' => '2026-03-01',
 *     'date_to' => '2026-03-31'
 * ], 1, 20);
 * 
 * // Get daily cash flow
 * $cashFlow = $transactionService->getDailyCashFlow(1, '2026-03-11');
 * 
 * // Get mantri cash position
 * $cashPosition = $transactionService->getMantriCashPosition(456, 1);
 */
