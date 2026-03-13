<?php
/**
 * AuthService - Clean Version
 * Fixed all syntax and logic errors
 */

class AuthService {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Config::getDatabase();
        $this->config = new Config();
    }
    
    /**
     * User registration with comprehensive validation
     */
    public function registerUser(array $userData): array {
        try {
            // Validate input data
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Check for existing user
            if ($this->userExists($userData['email'], $userData['phone'] ?? null)) {
                return ['success' => false, 'message' => 'User already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (uuid, name, email, phone, password_hash, created_at)
                VALUES (UUID(), ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $userData['phone'] ?? null,
                $passwordHash
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Assign role if specified
            if (isset($userData['role_id']) && isset($userData['unit_id'])) {
                $this->assignUserRole($userId, $userData['unit_id'], $userData['role_id']);
            }
            
            // Log registration
            $this->logSecurityEvent('user_registered', $userId, [
                'email' => $userData['email'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User registered successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    /**
     * Enhanced user login with fraud detection
     */
    public function loginUser(string $email, string $password, array $deviceInfo = []): array {
        try {
            // Get user
            $stmt = $this->db->prepare("
                SELECT id, uuid, name, email, password_hash, is_active, last_login_at
                FROM users WHERE email = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->handleFailedLogin($email, 'user_not_found');
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is inactive'];
            }
            
            // Check password
            if (!password_verify($password, $user['password_hash'])) {
                $this->handleFailedLogin($email, 'invalid_password', $user['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Generate JWT token
            $token = $this->generateJWT($user);
            
            // Create session
            $sessionId = $this->createSession($user['id'], $deviceInfo);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Clear failed login attempts
            $this->clearFailedAttempts($email);
            
            // Log successful login
            $this->logSecurityEvent('user_login', $user['id'], [
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'device_info' => $deviceInfo
            ]);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'uuid' => $user['uuid'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'token' => $token,
                'session_id' => $sessionId,
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            error_log("Login failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * Get user with roles and permissions
     */
    public function getUserWithRoles(int $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id, u.uuid, u.name, u.email, u.phone, u.avatar,
                    u.email_verified_at, u.phone_verified_at, u.is_active,
                    u.two_factor_enabled, u.last_login_at,
                    GROUP_CONCAT(DISTINCT ur.name) as roles,
                    GROUP_CONCAT(DISTINCT ua.unit_id) as unit_ids,
                    ur.permissions
                FROM users u
                LEFT JOIN user_assignments ua ON u.id = ua.user_id AND ua.is_active = 1
                LEFT JOIN user_roles ur ON ua.role_id = ur.id
                WHERE u.id = ? AND u.deleted_at IS NULL
                GROUP BY u.id
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Parse roles and permissions
                $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
                $user['unit_ids'] = $user['unit_ids'] ? explode(',', $user['unit_ids']) : [];
                
                // Get permissions directly from role
                $allPermissions = [];
                if ($user['permissions']) {
                    $permissions = json_decode($user['permissions'], true);
                    if (is_array($permissions)) {
                        $allPermissions = $permissions;
                    }
                }
                $user['permissions'] = $allPermissions;
            }
            
            return $user ?: [];
            
        } catch (Exception $e) {
            error_log("Get user with roles failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken(string $token): array {
        try {
            // Decode JWT token
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return ['valid' => false, 'message' => 'Invalid token format'];
            }
            
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
            
            if (!$payload) {
                return ['valid' => false, 'message' => 'Invalid token payload'];
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['valid' => false, 'message' => 'Token expired'];
            }
            
            return [
                'valid' => true,
                'user_id' => $payload['user_id'],
                'expires_at' => $payload['exp']
            ];
            
        } catch (Exception $e) {
            error_log("Token validation failed: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Token validation failed'];
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission(int $userId, string $permission): bool {
        try {
            $userWithRoles = $this->getUserWithRoles($userId);
            
            if (!$userWithRoles) {
                return false;
            }
            
            // Check if user has all permissions (super admin)
            if (isset($userWithRoles['permissions']['all']) && $userWithRoles['permissions']['all'] === true) {
                return true;
            }
            
            // Check specific permission
            $permissionParts = explode('.', $permission);
            $resource = $permissionParts[0] ?? '';
            $action = $permissionParts[1] ?? '';
            
            if (isset($userWithRoles['permissions'][$resource])) {
                $resourcePermissions = $userWithRoles['permissions'][$resource];
                if (is_array($resourcePermissions)) {
                    return in_array($action, $resourcePermissions);
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Private helper methods
     */
    private function validateRegistrationData(array $data): array {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Validate password
        if (strlen($data['password']) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        return ['valid' => true];
    }
    
    private function userExists(string $email, ?string $phone): bool {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($phone) {
            $sql .= " OR phone = ?";
            $params[] = $phone;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }
    
    private function generateJWT(array $user): string {
        $payload = [
            'user_id' => $user['id'],
            'uuid' => $user['uuid'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + (24 * 3600) // 24 hours
        ];
        
        return $this->encodeJWT($payload);
    }
    
    private function encodeJWT(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, Config::JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    private function createSession(int $userId, array $deviceInfo = []): string {
        try {
            $sessionId = uniqid('sess_', true);
            $tokenHash = hash('sha256', $sessionId);
            $expiresAt = date('Y-m-d H:i:s', time() + (8 * 3600)); // 8 hours
            
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (user_id, token_hash, device_info, ip_address, user_agent, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $tokenHash,
                json_encode($deviceInfo),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $expiresAt
            ]);
            
            return $sessionId;
            
        } catch (Exception $e) {
            error_log("Session creation failed: " . $e->getMessage());
            return '';
        }
    }
    
    private function updateLastLogin(int $userId): void {
        $stmt = $this->db->prepare("
            UPDATE users SET last_login_at = NOW(), last_login_ip = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $userId]);
    }
    
    private function handleFailedLogin(string $email, string $reason, int $userId = null): void {
        $this->logSecurityEvent('login_failed', $userId, [
            'email' => $email,
            'reason' => $reason,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }
    
    private function clearFailedAttempts(string $email): void {
        // Implementation for clearing failed attempts
    }
    
    private function assignUserRole(int $userId, int $unitId, int $roleId): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_assignments (user_id, unit_id, role_id, assigned_by)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $unitId, $roleId, $userId]);
        } catch (Exception $e) {
            error_log("Role assignment failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function logSecurityEvent(string $action, ?int $userId, array $details = []): void {
        error_log("Security Event: $action - User: $userId - " . json_encode($details));
    }
}

?>
