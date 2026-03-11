<?php
/**
 * Sync Service - SaaS Koperasi Harian
 * 
 * Handles offline-to-online data synchronization with conflict resolution
 * and last-write-wins logic for field operations
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class SyncService
{
    private $db;
    private $conflictResolutionStrategy = 'last_write_wins';
    private $maxRetryAttempts = 3;
    private $syncBatchSize = 50;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Sync All Pending Data
     */
    public function syncAll(): array
    {
        try {
            $results = [
                'success' => true,
                'synced' => 0,
                'failed' => 0,
                'conflicts' => 0,
                'details' => []
            ];
            
            // Get pending sync items
            $pendingItems = $this->getPendingSyncItems();
            
            if (empty($pendingItems)) {
                $results['details'][] = 'No pending items to sync';
                return $results;
            }
            
            // Process items in batches
            $batches = array_chunk($pendingItems, $this->syncBatchSize);
            
            foreach ($batches as $batch) {
                $batchResult = $this->syncBatch($batch);
                
                $results['synced'] += $batchResult['synced'];
                $results['failed'] += $batchResult['failed'];
                $results['conflicts'] += $batchResult['conflicts'];
                $results['details'] = array_merge($results['details'], $batchResult['details']);
            }
            
            // Update sync status
            $this->updateSyncStatus();
            
            return $results;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0,
                'failed' => 0,
                'conflicts' => 0
            ];
        }
    }
    
    /**
     * Sync Specific Table
     */
    public function syncTable(string $tableName, array $recordIds = []): array
    {
        try {
            $results = [
                'success' => true,
                'synced' => 0,
                'failed' => 0,
                'conflicts' => 0,
                'details' => []
            ];
            
            $whereClause = "table_name = ?";
            $params = [$tableName];
            
            if (!empty($recordIds)) {
                $placeholders = str_repeat('?,', count($recordIds) - 1) . '?';
                $whereClause .= " AND record_id IN ($placeholders)";
                $params = array_merge($params, $recordIds);
            }
            
            $sql = "SELECT * FROM sync_queue WHERE $whereClause ORDER BY created_at ASC LIMIT ?";
            $params[] = $this->syncBatchSize;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $items = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $row;
            }
            
            if (empty($items)) {
                $results['details'][] = "No pending items for table: $tableName";
                return $results;
            }
            
            $batchResult = $this->syncBatch($items);
            
            $results['synced'] = $batchResult['synced'];
            $results['failed'] = $batchResult['failed'];
            $results['conflicts'] = $batchResult['conflicts'];
            $results['details'] = $batchResult['details'];
            
            return $results;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0,
                'failed' => 0,
                'conflicts' => 0
            ];
        }
    }
    
    /**
     * Add Item to Sync Queue
     */
    public function addToSyncQueue(string $tableName, int $recordId, string $action, array $data): void
    {
        try {
            $sql = "INSERT INTO sync_queue (table_name, record_id, action, data, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    action = VALUES(action),
                    data = VALUES(data),
                    created_at = VALUES(created_at),
                    attempts = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tableName, $recordId, $action, json_encode($data)]);
            
        } catch (Exception $e) {
            throw new Exception('Failed to add to sync queue: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle Conflict Resolution
     */
    public function resolveConflict(int $syncQueueId, array $resolution): array
    {
        try {
            // Get sync queue item
            $item = $this->getSyncQueueItem($syncQueueId);
            
            if (!$item) {
                throw new Exception('Sync queue item not found');
            }
            
            $data = json_decode($item['data'], true);
            $tableName = $item['table_name'];
            $recordId = $item['record_id'];
            
            switch ($resolution['strategy']) {
                case 'use_local':
                    // Force local version to server
                    $result = $this->forceLocalToServer($tableName, $recordId, $data);
                    break;
                    
                case 'use_server':
                    // Use server version and discard local
                    $result = $this->useServerVersion($tableName, $recordId);
                    break;
                    
                case 'merge':
                    // Attempt to merge data
                    $result = $this->mergeData($tableName, $recordId, $data, $resolution['merge_data']);
                    break;
                    
                default:
                    throw new Exception('Invalid conflict resolution strategy');
            }
            
            if ($result['success']) {
                // Mark as resolved
                $this->markSyncItemResolved($syncQueueId);
                
                return [
                    'success' => true,
                    'message' => 'Conflict resolved successfully',
                    'resolution' => $resolution['strategy']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'message' => 'Failed to resolve conflict'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Conflict resolution failed'
            ];
        }
    }
    
    /**
     * Get Sync Status
     */
    public function getSyncStatus(): array
    {
        try {
            $sql = "SELECT 
                        table_name,
                        COUNT(*) as pending_count,
                        MIN(created_at) as oldest_item,
                        MAX(created_at) as newest_item
                    FROM sync_queue 
                    GROUP BY table_name
                    ORDER BY pending_count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $tableStatus = [];
            $totalPending = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tableStatus[] = [
                    'table' => $row['table_name'],
                    'pending_count' => (int)$row['pending_count'],
                    'oldest_item' => $row['oldest_item'],
                    'newest_item' => $row['newest_item']
                ];
                $totalPending += (int)$row['pending_count'];
            }
            
            // Get sync statistics
            $stats = $this->getSyncStatistics();
            
            return [
                'total_pending' => $totalPending,
                'table_status' => $tableStatus,
                'statistics' => $stats,
                'last_sync' => $this->getLastSyncTime()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total_pending' => 0,
                'table_status' => [],
                'statistics' => [],
                'last_sync' => null
            ];
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function getPendingSyncItems(): array
    {
        $sql = "SELECT * FROM sync_queue WHERE attempts < ? ORDER BY created_at ASC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->maxRetryAttempts, $this->syncBatchSize]);
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    private function syncBatch(array $items): array
    {
        $results = [
            'synced' => 0,
            'failed' => 0,
            'conflicts' => 0,
            'details' => []
        ];
        
        foreach ($items as $item) {
            try {
                $syncResult = $this->syncItem($item);
                
                if ($syncResult['success']) {
                    $results['synced']++;
                    $this->markSyncItemCompleted($item['id']);
                    $results['details'][] = "Synced {$item['table_name']} #{$item['record_id']}";
                } else {
                    $results['failed']++;
                    $this->incrementSyncAttempts($item['id']);
                    $results['details'][] = "Failed to sync {$item['table_name']} #{$item['record_id']}: {$syncResult['error']}";
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $this->incrementSyncAttempts($item['id']);
                $results['details'][] = "Error syncing {$item['table_name']} #{$item['record_id']}: {$e->getMessage()}";
            }
        }
        
        return $results;
    }
    
    private function syncItem(array $item): array
    {
        $tableName = $item['table_name'];
        $recordId = $item['record_id'];
        $action = $item['action'];
        $data = json_decode($item['data'], true);
        
        try {
            switch ($tableName) {
                case 'transactions':
                    return $this->syncTransaction($recordId, $action, $data);
                    
                case 'members':
                    return $this->syncMember($recordId, $action, $data);
                    
                case 'loan_applications':
                    return $this->syncLoanApplication($recordId, $action, $data);
                    
                case 'loan_repayment_schedules':
                    return $this->syncRepaymentSchedule($recordId, $action, $data);
                    
                default:
                    throw new Exception("Unsupported table: $tableName");
            }
            
        } catch (Exception $e) {
            // Check if it's a conflict
            if ($this->isConflict($e)) {
                $this->logConflict($item['id'], $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Conflict detected',
                    'conflict' => true
                ];
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function syncTransaction(int $recordId, string $action, array $data): array
    {
        switch ($action) {
            case 'create':
                // Check if transaction already exists
                $existing = $this->getTransactionByUuid($data['uuid']);
                
                if ($existing) {
                    // Conflict: transaction already exists
                    return $this->handleTransactionConflict($existing, $data);
                }
                
                // Create new transaction
                return $this->createTransaction($data);
                
            case 'update':
                // Updates should not happen for immutable transactions
                throw new Exception('Transaction updates are not allowed');
                
            case 'delete':
                // Deletes should not happen for immutable transactions
                throw new Exception('Transaction deletes are not allowed');
                
            default:
                throw new Exception("Unsupported action: $action");
        }
    }
    
    private function syncMember(int $recordId, string $action, array $data): array
    {
        switch ($action) {
            case 'create':
                // Check if member already exists
                $existing = $this->getMemberByUuid($data['uuid']);
                
                if ($existing) {
                    // Conflict: member already exists
                    return $this->handleMemberConflict($existing, $data);
                }
                
                // Create new member
                return $this->createMember($data);
                
            case 'update':
                // Check if member exists
                $existing = $this->getMemberByUuid($data['uuid']);
                
                if (!$existing) {
                    throw new Exception('Member not found for update');
                }
                
                // Check for conflicts
                if ($this->hasMemberConflict($existing, $data)) {
                    return $this->handleMemberConflict($existing, $data);
                }
                
                // Apply last-write-wins
                return $this->updateMember($existing['id'], $data);
                
            default:
                throw new Exception("Unsupported action: $action");
        }
    }
    
    private function syncLoanApplication(int $recordId, string $action, array $data): array
    {
        switch ($action) {
            case 'create':
                // Check if loan application already exists
                $existing = $this->getLoanApplicationByUuid($data['uuid']);
                
                if ($existing) {
                    return $this->handleLoanApplicationConflict($existing, $data);
                }
                
                return $this->createLoanApplication($data);
                
            case 'update':
                $existing = $this->getLoanApplicationByUuid($data['uuid']);
                
                if (!$existing) {
                    throw new Exception('Loan application not found for update');
                }
                
                if ($this->hasLoanApplicationConflict($existing, $data)) {
                    return $this->handleLoanApplicationConflict($existing, $data);
                }
                
                return $this->updateLoanApplication($existing['id'], $data);
                
            default:
                throw new Exception("Unsupported action: $action");
        }
    }
    
    private function syncRepaymentSchedule(int $recordId, string $action, array $data): array
    {
        switch ($action) {
            case 'update':
                $existing = $this->getRepaymentScheduleByUuid($data['uuid']);
                
                if (!$existing) {
                    throw new Exception('Repayment schedule not found for update');
                }
                
                if ($this->hasRepaymentScheduleConflict($existing, $data)) {
                    return $this->handleRepaymentScheduleConflict($existing, $data);
                }
                
                return $this->updateRepaymentSchedule($existing['id'], $data);
                
            default:
                throw new Exception("Unsupported action: $action");
        }
    }
    
    private function handleTransactionConflict(array $existing, array $new): array
    {
        // For transactions, we use the one with the earlier timestamp
        $existingTime = strtotime($existing['created_at']);
        $newTime = strtotime($new['created_at']);
        
        if ($newTime < $existingTime) {
            // New transaction is older, use it
            return $this->createTransaction($new);
        } else {
            // Keep existing transaction
            return [
                'success' => true,
                'message' => 'Existing transaction kept (older timestamp)'
            ];
        }
    }
    
    private function handleMemberConflict(array $existing, array $new): array
    {
        // Use last-write-wins for members
        $existingTime = strtotime($existing['updated_at']);
        $newTime = strtotime($new['updated_at']);
        
        if ($newTime > $existingTime) {
            // New data is more recent, use it
            return $this->updateMember($existing['id'], $new);
        } else {
            // Keep existing data
            return [
                'success' => true,
                'message' => 'Existing member data kept (more recent)'
            ];
        }
    }
    
    private function handleLoanApplicationConflict(array $existing, array $new): array
    {
        // Use last-write-wins for loan applications
        $existingTime = strtotime($existing['updated_at']);
        $newTime = strtotime($new['updated_at']);
        
        if ($newTime > $existingTime) {
            return $this->updateLoanApplication($existing['id'], $new);
        } else {
            return [
                'success' => true,
                'message' => 'Existing loan application kept (more recent)'
            ];
        }
    }
    
    private function handleRepaymentScheduleConflict(array $existing, array $new): array
    {
        // For repayment schedules, use the higher paid amount
        if ($new['paid_amount'] > $existing['paid_amount']) {
            return $this->updateRepaymentSchedule($existing['id'], $new);
        } else {
            return [
                'success' => true,
                'message' => 'Existing repayment schedule kept (higher paid amount)'
            ];
        }
    }
    
    private function isConflict(Exception $e): bool
    {
        $message = $e->getMessage();
        
        // Check for common conflict indicators
        $conflictIndicators = [
            'Duplicate entry',
            'already exists',
            'conflict',
            'violation'
        ];
        
        foreach ($conflictIndicators as $indicator) {
            if (stripos($message, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function logConflict(int $syncQueueId, string $conflictMessage): void
    {
        $sql = "INSERT INTO sync_conflicts (sync_queue_id, conflict_message, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$syncQueueId, $conflictMessage]);
    }
    
    private function markSyncItemCompleted(int $syncQueueId): void
    {
        $sql = "DELETE FROM sync_queue WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$syncQueueId]);
    }
    
    private function markSyncItemResolved(int $syncQueueId): void
    {
        $sql = "DELETE FROM sync_queue WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$syncQueueId]);
    }
    
    private function incrementSyncAttempts(int $syncQueueId): void
    {
        $sql = "UPDATE sync_queue SET attempts = attempts + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$syncQueueId]);
    }
    
    private function updateSyncStatus(): void
    {
        $sql = "INSERT INTO sync_status (sync_time, items_synced, items_failed, items_conflicted) 
                VALUES (NOW(), ?, ?, ?)";
        
        // Get counts from this sync session
        $synced = $this->getSyncedCount();
        $failed = $this->getFailedCount();
        $conflicted = $this->getConflictedCount();
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$synced, $failed, $conflicted]);
    }
    
    private function getSyncStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_synced,
                    AVG(items_synced) as avg_synced,
                    MAX(sync_time) as last_sync
                FROM sync_status 
                WHERE sync_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_synced' => (int)($result['total_synced'] ?? 0),
            'avg_synced' => round($result['avg_synced'] ?? 0, 2),
            'last_sync' => $result['last_sync'] ?? null
        ];
    }
    
    private function getLastSyncTime(): ?string
    {
        $sql = "SELECT MAX(sync_time) as last_sync FROM sync_status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_sync'] ?? null;
    }
    
    // Placeholder methods for actual data operations
    private function getTransactionByUuid(string $uuid): ?array { return null; }
    private function getMemberByUuid(string $uuid): ?array { return null; }
    private function getLoanApplicationByUuid(string $uuid): ?array { return null; }
    private function getRepaymentScheduleByUuid(string $uuid): ?array { return null; }
    private function createTransaction(array $data): array { return ['success' => true]; }
    private function createMember(array $data): array { return ['success' => true]; }
    private function createLoanApplication(array $data): array { return ['success' => true]; }
    private function updateMember(int $id, array $data): array { return ['success' => true]; }
    private function updateLoanApplication(int $id, array $data): array { return ['success' => true]; }
    private function updateRepaymentSchedule(int $id, array $data): array { return ['success' => true]; }
    private function hasMemberConflict(array $existing, array $new): bool { return false; }
    private function hasLoanApplicationConflict(array $existing, array $new): bool { return false; }
    private function hasRepaymentScheduleConflict(array $existing, array $new): bool { return false; }
    private function forceLocalToServer(string $table, int $id, array $data): array { return ['success' => true]; }
    private function useServerVersion(string $table, int $id): array { return ['success' => true]; }
    private function mergeData(string $table, int $id, array $local, array $merge): array { return ['success' => true]; }
    private function getSyncQueueItem(int $id): ?array { return null; }
    private function getSyncedCount(): int { return 0; }
    private function getFailedCount(): int { return 0; }
    private function getConflictedCount(): int { return 0; }
}

/**
 * Usage Examples:
 * 
 * $syncService = new SyncService();
 * 
 * // Sync all pending data
 * $result = $syncService->syncAll();
 * 
 * // Sync specific table
 * $result = $syncService->syncTable('transactions', [1, 2, 3]);
 * 
 * // Add item to sync queue
 * $syncService->addToSyncQueue('transactions', 123, 'create', $transactionData);
 * 
 * // Get sync status
 * $status = $syncService->getSyncStatus();
 * 
 * // Resolve conflict
 * $resolution = $syncService->resolveConflict(456, [
 *     'strategy' => 'use_local',
 *     'merge_data' => ['field1' => 'value1']
 * ]);
 */
