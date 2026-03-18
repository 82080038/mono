<?php
/**
 * Security Helper - KSP Lam Gabe Jaya
 * Enhanced security functions with proper CSRF validation
 */

class SecurityHelper {
    public static function sanitizeInput($input) {
        if (!is_string($input)) {
            return '';
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, "UTF-8");
    }
    
    public static function init() {
        // Initialize session for CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            error_log("CSRF Error: Tidak token in session");
            return false;
        }
        
        if (!is_string($token)) {
            error_log("CSRF Error: Token is not a string");
            return false;
        }
        
        $result = hash_equals($_SESSION['csrf_token'], $token);
        if (!$result) {
            error_log("CSRF Error: Token mismatch");
        }
        
        return $result;
    }
    
    public static function getCSRFToken() {
        self::init();
        return $_SESSION['csrf_token'] ?? '';
    }
    
    public static function logAuthAttempt($email, $success) {
        $log_message = sprintf(
            "[%s] Auth attempt: Email=%s, Berhasil=%s, ip=%s, user_agent=%s",
            date('Y-m-d H:i:s'),
            $email,
            $success ? 'true' : 'false',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        error_log($log_message);
    }
}

