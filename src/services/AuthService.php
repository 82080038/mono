<?php
/**
 * Authentication Service - SaaS Koperasi Harian
 * 
 * Handles user authentication, authorization, and session management
 * with multi-tenant support and security features
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class AuthService
{
    private $db;
    private $jwtSecret;
    private $jwtTTL;
    private $refreshJwtTTL;
    private $bcryptRounds;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwtSecret = env('JWT_SECRET');
        $this->jwtTTL = env('JWT_TTL', 3600);
        $this->refreshJwtTTL = env('REFRESH_JWT_TTL', 20160);
        $this->bcryptRounds = env('BCRYPT_ROUNDS', 12);
    }
    
    /**
     * User Registration with Multi-Tenant Support
     */
    public function register(array $userData): array
    {
        try {
            // Validate input data
            $this->validateRegistrationData($userData);
            
            // Check if user already exists
            if ($this->userExists($userData['email'], $userData['phone'] ?? null)) {
                throw new Exception('User already exists');
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, [
                'cost' => $this->bcryptRounds
            ]);
            
            // Create user
            $userId = $this->createUser([
                'uuid' => $this->generateUuid(),
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'password_hash' => $passwordHash,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'two_factor_enabled' => false,
                'is_active' => true
            ]);
            
            // Create tenant user association if tenant_id provided
            if (isset($userData['tenant_id'])) {
                $this->createTenantUser([
                    'tenant_id' => $userData['tenant_id'],
                    'user_id' => $userId,
                    'role' => $userData['role'] ?? 'member',
                    'permissions' => json_encode($userData['permissions'] ?? []),
                    'is_active' => true
                ]);
            }
            
            // Generate tokens
            $tokens = $this->generateTokens($userId);
            
            // Create session
            $this->createSession($userId, $tokens['access_token']);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'tokens' => $tokens,
                'message' => 'User registered successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Registration failed'
            ];
        }
    }
    
    /**
     * User Login with Multi-Factor Authentication
     */
    public function login(array $credentials): array
    {
        try {
            // Find user by email or phone
            $user = $this->findUser($credentials['email'] ?? $credentials['phone']);
            
            if (!$user) {
                throw new Exception('Invalid credentials');
            }
            
            // Verify password
            if (!password_verify($credentials['password'], $user['password_hash'])) {
                throw new Exception('Invalid credentials');
            }
            
            // Check if user is active
            if (!$user['is_active']) {
                throw new Exception('Account is deactivated');
            }
            
            // Check two-factor authentication
            if ($user['two_factor_enabled']) {
                return $this->handleTwoFactorAuth($user, $credentials);
            }
            
            // Generate tokens
            $tokens = $this->generateTokens($user['id']);
            
            // Create session
            $this->createSession($user['id'], $tokens['access_token'], $credentials);
            
            // Update last login
            $this->updateLastLogin($user['id'], $credentials);
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'tokens' => $tokens,
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Login failed'
            ];
        }
    }
    
    /**
     * Logout User
     */
    public function logout(string $token): array
    {
        try {
            // Validate token
            $payload = $this->validateToken($token);
            
            // Deactivate session
            $this->deactivateSession($token);
            
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Logout failed'
            ];
        }
    }
    
    /**
     * Refresh Token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            // Validate refresh token
            $payload = $this->validateToken($refreshToken, 'refresh');
            
            // Get user
            $user = $this->getUserById($payload['user_id']);
            
            if (!$user || !$user['is_active']) {
                throw new Exception('Invalid token');
            }
            
            // Generate new tokens
            $tokens = $this->generateTokens($user['id']);
            
            // Create new session
            $this->createSession($user['id'], $tokens['access_token']);
            
            // Deactivate old refresh token
            $this->deactivateSession($refreshToken);
            
            return [
                'success' => true,
                'tokens' => $tokens,
                'message' => 'Token refreshed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Token refresh failed'
            ];
        }
    }
    
    /**
     * Verify Token and Get User
     */
    public function verifyToken(string $token): array
    {
        try {
            $payload = $this->validateToken($token);
            $user = $this->getUserById($payload['user_id']);
            
            if (!$user || !$user['is_active']) {
                throw new Exception('Invalid token');
            }
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'payload' => $payload
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Token verification failed'
            ];
        }
    }
    
    /**
     * Check User Permissions
     */
    public function checkPermission(int $userId, string $permission, int $tenantId = null): bool
    {
        try {
            // Get user roles and permissions
            $userPermissions = $this->getUserPermissions($userId, $tenantId);
            
            // Check if user has the required permission
            return in_array($permission, $userPermissions) || 
                   in_array('*', $userPermissions) || // Super admin
                   in_array($permission . ':all', $userPermissions);
            
        } catch (Exception $e) {
            error_log("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enable Two-Factor Authentication
     */
    public function enableTwoFactor(int $userId): array
    {
        try {
            // Generate secret
            $secret = $this->generateTwoFactorSecret();
            
            // Update user
            $this->updateUser($userId, [
                'two_factor_secret' => $secret,
                'two_factor_enabled' => true
            ]);
            
            // Generate QR code
            $qrCode = $this->generateTwoFactorQR($userId, $secret);
            
            return [
                'success' => true,
                'secret' => $secret,
                'qr_code' => $qrCode,
                'message' => 'Two-factor authentication enabled'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to enable two-factor authentication'
            ];
        }
    }
    
    /**
     * Verify Two-Factor Code
     */
    public function verifyTwoFactor(int $userId, string $code): bool
    {
        try {
            $user = $this->getUserById($userId);
            
            if (!$user || !$user['two_factor_enabled']) {
                return false;
            }
            
            return $this->validateTwoFactorCode($user['two_factor_secret'], $code);
            
        } catch (Exception $e) {
            error_log("Two-factor verification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Private Methods
     */
    
    private function validateRegistrationData(array $userData): void
    {
        $required = ['name', 'email', 'password'];
        
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }
        
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($userData['password']) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        if (isset($userData['phone']) && !preg_match('/^[0-9]{10,15}$/', $userData['phone'])) {
            throw new Exception('Invalid phone number format');
        }
    }
    
    private function userExists(string $email, ?string $phone): bool
    {
        $sql = "SELECT id FROM users WHERE email = ? OR phone = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $phone]);
        
        return $stmt->fetch() !== false;
    }
    
    private function createUser(array $userData): int
    {
        $sql = "INSERT INTO users (uuid, name, email, phone, password_hash, email_verified_at, phone_verified_at, two_factor_enabled, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userData['uuid'],
            $userData['name'],
            $userData['email'],
            $userData['phone'],
            $userData['password_hash'],
            $userData['email_verified_at'],
            $userData['phone_verified_at'],
            $userData['two_factor_enabled'],
            $userData['is_active']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function createTenantUser(array $tenantUserData): void
    {
        $sql = "INSERT INTO tenant_users (tenant_id, user_id, role, permissions, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $tenantUserData['tenant_id'],
            $tenantUserData['user_id'],
            $tenantUserData['role'],
            $tenantUserData['permissions'],
            $tenantUserData['is_active']
        ]);
    }
    
    private function generateTokens(int $userId): array
    {
        $payload = [
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + $this->jwtTTL,
            'type' => 'access'
        ];
        
        $accessToken = $this->generateJWT($payload);
        
        $refreshPayload = [
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + $this->refreshJwtTTL,
            'type' => 'refresh'
        ];
        
        $refreshToken = $this->generateJWT($refreshPayload);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwtTTL,
            'refresh_expires_in' => $this->refreshJwtTTL
        ];
    }
    
    private function generateJWT(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = base64_encode($header);
        
        $payload = json_encode($payload);
        $payload = base64_encode($payload);
        
        $signature = hash_hmac('sha256', $header . "." . $payload, $this->jwtSecret, true);
        $signature = base64_encode($signature);
        
        return $header . "." . $payload . "." . $signature;
    }
    
    private function validateToken(string $token, string $type = 'access'): array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        $header = base64_decode($parts[0]);
        $payload = base64_decode($parts[1]);
        $signature = $parts[2];
        
        $payloadArray = json_decode($payload, true);
        
        if (!$payloadArray) {
            throw new Exception('Invalid token payload');
        }
        
        // Check token type
        if ($payloadArray['type'] !== $type) {
            throw new Exception('Invalid token type');
        }
        
        // Check expiration
        if ($payloadArray['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $this->jwtSecret, true);
        $expectedSignature = base64_encode($expectedSignature);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        return $payloadArray;
    }
    
    private function createSession(int $userId, string $token, array $requestInfo = []): void
    {
        $sql = "INSERT INTO user_sessions (user_id, token_hash, device_info, ip_address, user_agent, expires_at, created_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            hash('sha256', $token),
            json_encode($requestInfo['device_info'] ?? []),
            $requestInfo['ip_address'] ?? null,
            $requestInfo['user_agent'] ?? null,
            $this->jwtTTL
        ]);
    }
    
    private function deactivateSession(string $token): void
    {
        $sql = "UPDATE user_sessions SET is_active = 0 WHERE token_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([hash('sha256', $token)]);
    }
    
    private function findUser(string $identifier): ?array
    {
        $sql = "SELECT * FROM users WHERE email = ? OR phone = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $identifier]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getUserById(int $userId): ?array
    {
        $sql = "SELECT * FROM users WHERE id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getUserPermissions(int $userId, ?int $tenantId): array
    {
        $sql = "SELECT permissions FROM tenant_users WHERE user_id = ? AND tenant_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $tenantId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return json_decode($result['permissions'], true) ?: [];
        }
        
        return [];
    }
    
    private function updateLastLogin(int $userId, array $requestInfo): void
    {
        $sql = "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestInfo['ip_address'] ?? null, $userId]);
    }
    
    private function sanitizeUserData(array $user): array
    {
        unset($user['password_hash']);
        unset($user['two_factor_secret']);
        
        return $user;
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
    
    private function generateTwoFactorSecret(): string
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
    }
    
    private function generateTwoFactorQR(int $userId, string $secret): string
    {
        $appName = env('APP_NAME', 'KSP SaaS');
        $user = $this->getUserById($userId);
        
        $otpauthUrl = sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s',
            $appName,
            $user['email'],
            $secret,
            $appName
        );
        
        return $this->generateQRCode($otpauthUrl);
    }
    
    private function generateQRCode(string $data): string
    {
        // Implement QR code generation library
        // For now, return placeholder
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
    
    private function validateTwoFactorCode(string $secret, string $code): bool
    {
        // Implement TOTP validation
        // For now, return placeholder
        return true;
    }
    
    private function handleTwoFactorAuth(array $user, array $credentials): array
    {
        // Implement two-factor authentication flow
        return [
            'success' => false,
            'requires_two_factor' => true,
            'message' => 'Two-factor authentication required'
        ];
    }
    
    private function updateUser(int $userId, array $data): void
    {
        $setClause = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $values[] = $userId;
        
        $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
}

/**
 * Usage Examples:
 * 
 * $auth = new AuthService();
 * 
 * // Register user
 * $result = $auth->register([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'phone' => '08123456789',
 *     'password' => 'securepassword',
 *     'tenant_id' => 1,
 *     'role' => 'member'
 * ]);
 * 
 * // Login user
 * $result = $auth->login([
 *     'email' => 'john@example.com',
 *     'password' => 'securepassword',
 *     'ip_address' => '192.168.1.1',
 *     'user_agent' => 'Mozilla/5.0...'
 * ]);
 * 
 * // Check permission
 * $hasPermission = $auth->checkPermission($userId, 'loan.create', $tenantId);
 */
