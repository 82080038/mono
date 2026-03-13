<?php
/**
 * Member Service - SaaS Koperasi Harian
 * 
 * Handles member registration, management, and related operations
 * with NIK validation, KTP OCR, and GPS geotagging
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class MemberService
{
    private $db;
    private $authService;
    private $ocrService;
    private $gpsService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->authService = new AuthService();
        $this->ocrService = new OCRService();
        $this->gpsService = new GPSService();
    }
    
    /**
     * Register New Member with Complete Validation
     */
    public function registerMember(array $memberData, int $tenantId): array
    {
        try {
            // Validate member data
            $this->validateMemberData($memberData);
            
            // Check NIK uniqueness
            if ($this->nikExists($memberData['nik'])) {
                throw new Exception('NIK already registered');
            }
            
            // Validate NIK format
            if (!$this->validateNIK($memberData['nik'])) {
                throw new Exception('Invalid NIK format');
            }
            
            // Process KTP OCR if provided
            $ktpData = null;
            if (isset($memberData['ktp_image'])) {
                $ktpData = $this->processKTPImage($memberData['ktp_image']);
                if ($ktpData['nik'] !== $memberData['nik']) {
                    throw new Exception('NIK mismatch with KTP image');
                }
            }
            
            // Validate GPS coordinates
            if (isset($memberData['gps_lat']) && isset($memberData['gps_lng'])) {
                $this->validateGPSCoordinates($memberData['gps_lat'], $memberData['gps_lng']);
            }
            
            // Create member user account
            $userData = [
                'name' => $memberData['name'],
                'email' => $memberData['email'] ?? null,
                'phone' => $memberData['phone'],
                'password' => $memberData['password'] ?? $this->generateDefaultPassword(),
                'tenant_id' => $tenantId,
                'role' => 'member'
            ];
            
            $userResult = $this->authService->register($userData);
            
            if (!$userResult['success']) {
                throw new Exception('Failed to create user account: ' . $userResult['error']);
            }
            
            // Create member record
            $memberId = $this->createMember([
                'uuid' => $this->generateUuid(),
                'nik' => $memberData['nik'],
                'name' => $memberData['name'],
                'phone' => $memberData['phone'],
                'email' => $memberData['email'] ?? null,
                'address' => $memberData['address'] ?? null,
                'gps_lat' => $memberData['gps_lat'] ?? null,
                'gps_lng' => $memberData['gps_lng'] ?? null,
                'location' => isset($memberData['gps_lat']) ? 
                    "POINT({$memberData['gps_lng']} {$memberData['gps_lat']})" : null,
                'photo_ktp_url' => $ktpData['ktp_url'] ?? null,
                'photo_selfie_url' => $memberData['selfie_url'] ?? null,
                'birth_date' => $memberData['birth_date'] ?? null,
                'gender' => $memberData['gender'] ?? null,
                'occupation' => $memberData['occupation'] ?? null,
                'monthly_income' => $memberData['monthly_income'] ?? 0,
                'family_members' => $memberData['family_members'] ?? 1,
                'education_level' => $memberData['education_level'] ?? null,
                'marital_status' => $memberData['marital_status'] ?? null,
                'emergency_contact_name' => $memberData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $memberData['emergency_contact_phone'] ?? null,
                'credit_score' => $this->calculateInitialCreditScore($memberData),
                'credit_limit' => $this->calculateInitialCreditLimit($memberData),
                'status' => 'active',
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create savings account
            $this->createSavingsAccount($memberId, $tenantId);
            
            // Send welcome notification
            $this->sendWelcomeNotification($memberId, $memberData);
            
            return [
                'success' => true,
                'member_id' => $memberId,
                'user_id' => $userResult['user_id'],
                'credit_score' => $this->calculateInitialCreditScore($memberData),
                'credit_limit' => $this->calculateInitialCreditLimit($memberData),
                'message' => 'Member registered successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Member registration failed'
            ];
        }
    }
    
    /**
     * Update Member Information
     */
    public function updateMember(int $memberId, array $updateData, int $tenantId): array
    {
        try {
            // Check if member exists and belongs to tenant
            $member = $this->getMemberById($memberId, $tenantId);
            
            if (!$member) {
                throw new Exception('Member not found');
            }
            
            // Validate update data
            $this->validateUpdateData($updateData);
            
            // Check NIK uniqueness if updating
            if (isset($updateData['nik']) && $updateData['nik'] !== $member['nik']) {
                if ($this->nikExists($updateData['nik'])) {
                    throw new Exception('NIK already registered');
                }
                if (!$this->validateNIK($updateData['nik'])) {
                    throw new Exception('Invalid NIK format');
                }
            }
            
            // Process KTP OCR if provided
            if (isset($updateData['ktp_image'])) {
                $ktpData = $this->processKTPImage($updateData['ktp_image']);
                $updateData['photo_ktp_url'] = $ktpData['ktp_url'];
                unset($updateData['ktp_image']);
            }
            
            // Update GPS location if provided
            if (isset($updateData['gps_lat']) && isset($updateData['gps_lng'])) {
                $this->validateGPSCoordinates($updateData['gps_lat'], $updateData['gps_lng']);
                $updateData['location'] = "POINT({$updateData['gps_lng']} {$updateData['gps_lat']})";
            }
            
            // Update member record
            $this->updateMemberRecord($memberId, $updateData);
            
            // Recalculate credit score if relevant data changed
            if ($this->shouldRecalculateCreditScore($updateData)) {
                $newCreditScore = $this->recalculateCreditScore($memberId);
                $this->updateMemberCreditScore($memberId, $newCreditScore);
            }
            
            return [
                'success' => true,
                'member_id' => $memberId,
                'message' => 'Member updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Member update failed'
            ];
        }
    }
    
    /**
     * Get Member by ID
     */
    public function getMemberById(int $memberId, int $tenantId): ?array
    {
        $sql = "SELECT * FROM members WHERE id = ? AND status != 'deleted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            return null;
        }
        
        // Sanitize sensitive data
        unset($member['deleted_at']);
        
        return $member;
    }
    
    /**
     * Get Members List with Filtering and Pagination
     */
    public function getMembersList(int $tenantId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ["status != 'deleted'"];
            $params = [];
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(name LIKE ? OR nik LIKE ? OR phone LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['credit_score_min'])) {
                $whereConditions[] = "credit_score >= ?";
                $params[] = $filters['credit_score_min'];
            }
            
            if (!empty($filters['credit_score_max'])) {
                $whereConditions[] = "credit_score <= ?";
                $params[] = $filters['credit_score_max'];
            }
            
            if (!empty($filters['area_id'])) {
                $whereConditions[] = "area_id = ?";
                $params[] = $filters['area_id'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM members WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get members data
            $sql = "SELECT * FROM members WHERE {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sanitize data
            foreach ($members as &$member) {
                unset($member['deleted_at']);
            }
            
            return [
                'success' => true,
                'data' => $members,
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
                'message' => 'Failed to get members list'
            ];
        }
    }
    
    /**
     * Deactivate Member
     */
    public function deactivateMember(int $memberId, int $tenantId, string $reason = ''): array
    {
        try {
            $member = $this->getMemberById($memberId, $tenantId);
            
            if (!$member) {
                throw new Exception('Member not found');
            }
            
            // Check if member has active loans
            if ($this->hasActiveLoans($memberId)) {
                throw new Exception('Cannot deactivate member with active loans');
            }
            
            // Update member status
            $this->updateMemberRecord($memberId, [
                'status' => 'inactive',
                'deactivation_reason' => $reason,
                'deactivated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send notification
            $this->sendDeactivationNotification($memberId, $reason);
            
            return [
                'success' => true,
                'message' => 'Member deactivated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Member deactivation failed'
            ];
        }
    }
    
    /**
     * Calculate Initial Credit Score
     */
    private function calculateInitialCreditScore(array $memberData): int
    {
        $score = 500; // Base score
        
        // Age factor
        if (isset($memberData['birth_date'])) {
            $age = $this->calculateAge($memberData['birth_date']);
            if ($age >= 25 && $age <= 45) {
                $score += 50;
            } elseif ($age >= 18 && $age < 25) {
                $score += 20;
            }
        }
        
        // Income factor
        if (isset($memberData['monthly_income']) && $memberData['monthly_income'] > 0) {
            if ($memberData['monthly_income'] >= 5000000) {
                $score += 100;
            } elseif ($memberData['monthly_income'] >= 3000000) {
                $score += 70;
            } elseif ($memberData['monthly_income'] >= 2000000) {
                $score += 40;
            } elseif ($memberData['monthly_income'] >= 1000000) {
                $score += 20;
            }
        }
        
        // Education factor
        $educationScores = [
            's3' => 80, 's2' => 70, 's1' => 60,
            'd3' => 50, 'd2' => 40, 'd1' => 30,
            'sma' => 20, 'smp' => 10, 'sd' => 5
        ];
        
        if (isset($memberData['education_level']) && isset($educationScores[$memberData['education_level']])) {
            $score += $educationScores[$memberData['education_level']];
        }
        
        // Marital status factor
        if (isset($memberData['marital_status'])) {
            if ($memberData['marital_status'] === 'married') {
                $score += 30;
            } elseif ($memberData['marital_status'] === 'single') {
                $score += 10;
            }
        }
        
        // Occupation factor
        $occupationScores = [
            'government' => 50, 'private_company' => 40,
            'entrepreneur' => 35, 'freelance' => 25,
            'farmer' => 20, 'laborer' => 15,
            'unemployed' => 0
        ];
        
        if (isset($memberData['occupation']) && isset($occupationScores[strtolower($memberData['occupation'])])) {
            $score += $occupationScores[strtolower($memberData['occupation'])];
        }
        
        // Ensure score is within valid range
        return max(100, min(999, $score));
    }
    
    /**
     * Calculate Initial Credit Limit
     */
    private function calculateInitialCreditLimit(array $memberData): float
    {
        $creditScore = $this->calculateInitialCreditScore($memberData);
        $monthlyIncome = $memberData['monthly_income'] ?? 0;
        
        // Base limit calculation
        $baseLimit = 0;
        
        if ($monthlyIncome > 0) {
            // Limit is 3x monthly income for good credit scores
            $multiplier = $creditScore >= 700 ? 3 : ($creditScore >= 500 ? 2 : 1);
            $baseLimit = $monthlyIncome * $multiplier;
        }
        
        // Apply credit score factor
        $scoreFactor = $creditScore / 500; // 500 is average score
        $adjustedLimit = $baseLimit * $scoreFactor;
        
        // Set minimum and maximum limits
        $minLimit = 500000; // Rp 500,000
        $maxLimit = 50000000; // Rp 50,000,000
        
        return max($minLimit, min($maxLimit, $adjustedLimit));
    }
    
    /**
     * Process KTP Image with OCR
     */
    private function processKTPImage(string $imageData): array
    {
        try {
            // Save image to temporary location
            $tempPath = $this->saveImageTemporarily($imageData);
            
            // Extract text using OCR
            $extractedData = $this->ocrService->extractKTPData($tempPath);
            
            // Save KTP image permanently
            $ktpUrl = $this->saveKTPImagePermanently($tempPath);
            
            // Clean up temporary file
            unlink($tempPath);
            
            return [
                'ktp_url' => $ktpUrl,
                'extracted_data' => $extractedData,
                'nik' => $extractedData['nik'] ?? null,
                'name' => $extractedData['name'] ?? null,
                'birth_date' => $extractedData['birth_date'] ?? null,
                'address' => $extractedData['address'] ?? null
            ];
            
        } catch (Exception $e) {
            throw new Exception('KTP OCR processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate NIK Format
     */
    private function validateNIK(string $nik): bool
    {
        // NIK should be 16 digits
        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            return false;
        }
        
        // Basic validation for province code (first 2 digits)
        $provinceCode = substr($nik, 0, 2);
        if ($provinceCode < 11 || $provinceCode > 94) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate GPS Coordinates
     */
    private function validateGPSCoordinates(float $lat, float $lng): void
    {
        if ($lat < -11 || $lat > 6) {
            throw new Exception('Invalid latitude for Indonesia');
        }
        
        if ($lng < 95 || $lng > 141) {
            throw new Exception('Invalid longitude for Indonesia');
        }
    }
    
    /**
     * Validate Member Data
     */
    private function validateMemberData(array $data): void
    {
        $required = ['nik', 'name', 'phone'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
            throw new Exception('Invalid phone number format');
        }
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (isset($data['birth_date'])) {
            $birthDate = DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            if (!$birthDate || $birthDate > new DateTime()) {
                throw new Exception('Invalid birth date');
            }
        }
        
        if (isset($data['gender']) && !in_array($data['gender'], ['male', 'female'])) {
            throw new Exception('Invalid gender');
        }
        
        if (isset($data['monthly_income']) && $data['monthly_income'] < 0) {
            throw new Exception('Monthly income cannot be negative');
        }
    }
    
    /**
     * Create Member Record
     */
    private function createMember(array $memberData): int
    {
        $fields = array_keys($memberData);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO members (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($memberData));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Create Savings Account
     */
    private function createSavingsAccount(int $memberId, int $tenantId): void
    {
        $accountData = [
            'uuid' => $this->generateUuid(),
            'member_id' => $memberId,
            'account_number' => $this->generateAccountNumber(),
            'account_type' => 'regular',
            'balance' => 0,
            'interest_rate' => 0,
            'status' => 'active'
        ];
        
        $fields = array_keys($accountData);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO savings_accounts (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($accountData));
    }
    
    /**
     * Generate UUID
     */
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
    
    /**
     * Generate Account Number
     */
    private function generateAccountNumber(): string
    {
        return 'SA' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate Default Password
     */
    private function generateDefaultPassword(): string
    {
        return 'KSP' . date('Ymd') . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate Age from Birth Date
     */
    private function calculateAge(string $birthDate): int
    {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        
        return $birth->diff($today)->y;
    }
    
    /**
     * Check if NIK Exists
     */
    private function nikExists(string $nik): bool
    {
        $sql = "SELECT id FROM members WHERE nik = ? AND status != 'deleted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nik]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if Member Has Active Loans
     */
    private function hasActiveLoans(int $memberId): bool
    {
        $sql = "SELECT id FROM loan_applications WHERE member_id = ? AND status IN ('approved', 'disbursed')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Send Welcome Notification
     */
    private function sendWelcomeNotification(int $memberId, array $memberData): void
    {
        // Implement notification logic (SMS, WhatsApp, Email)
        // This would integrate with the notification service
    }
    
    /**
     * Send Deactivation Notification
     */
    private function sendDeactivationNotification(int $memberId, string $reason): void
    {
        // Implement notification logic
    }
    
    /**
     * Save Image Temporarily
     */
    private function saveImageTemporarily(string $imageData): string
    {
        $tempPath = sys_get_temp_dir() . '/ktp_' . uniqid() . '.jpg';
        file_put_contents($tempPath, base64_decode($imageData));
        return $tempPath;
    }
    
    /**
     * Save KTP Image Permanently
     */
    private function saveKTPImagePermanently(string $tempPath): string
    {
        $filename = 'ktp_' . uniqid() . '.jpg';
        $uploadPath = env('UPLOAD_PATH', 'uploads') . '/ktp/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $finalPath = $uploadPath . $filename;
        copy($tempPath, $finalPath);
        
        return $finalPath;
    }
}

/**
 * Usage Examples:
 * 
 * $memberService = new MemberService();
 * 
 * // Register new member
 * $result = $memberService->registerMember([
 *     'nik' => '1234567890123456',
 *     'name' => 'John Doe',
 *     'phone' => '08123456789',
 *     'email' => 'john@example.com',
 *     'address' => 'Jl. Example No. 123',
 *     'gps_lat' => -6.2088,
 *     'gps_lng' => 106.8456,
 *     'birth_date' => '1990-01-01',
 *     'gender' => 'male',
 *     'occupation' => 'private_company',
 *     'monthly_income' => 5000000,
 *     'password' => 'securepassword'
 * ], $tenantId);
 * 
 * // Get members list
 * $members = $memberService->getMembersList($tenantId, [
 *     'search' => 'John',
 *     'status' => 'active'
 * ], 1, 20);
 */
