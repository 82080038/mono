<?php
/**
 * API Routes for Single Cooperative Application
 * KSP Lam Gabe Jaya - Single Cooperative Setup
 */

// Include required files
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../services/AuthService.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize services
$authService = new AuthService();

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// API version and endpoint
$apiVersion = $pathParts[0] ?? 'v1';
$endpoint = $pathParts[1] ?? '';
$resourceId = $pathParts[2] ?? null;

// Authentication middleware
function authenticate() {
    global $authService;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header required']);
        exit();
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    $validation = $authService->validateToken($token);
    
    if (!$validation['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => $validation['message']]);
        exit();
    }
    
    return $validation['user_id'];
}

// Permission check middleware
function checkPermission($userId, $permission) {
    global $authService;
    
    if (!$authService->hasPermission($userId, $permission)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit();
    }
}

// Route requests
switch ($endpoint) {
    case 'auth':
        handleAuthRoutes();
        break;
        
    case 'dashboard':
        $userId = authenticate();
        handleDashboardRoutes($userId, $resourceId);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}

/**
 * Authentication Routes
 */
function handleAuthRoutes() {
    global $authService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'register':
                    $result = $authService->registerUser($input);
                    break;
                    
                case 'login':
                    $result = $authService->loginUser(
                        $input['email'] ?? '',
                        $input['password'] ?? '',
                        $input['device_info'] ?? []
                    );
                    break;
                    
                case 'logout':
                    $sessionId = $input['session_id'] ?? '';
                    $result = ['success' => $authService->logoutUser($sessionId)];
                    break;
                    
                default:
                    http_response_code(400);
                    $result = ['success' => false, 'message' => 'Invalid action'];
                    break;
            }
            break;
            
        case 'GET':
            $userId = authenticate();
            
            switch ($action) {
                case 'profile':
                    $result = [
                        'success' => true,
                        'user' => $authService->getUserWithRoles($userId)
                    ];
                    break;
                    
                default:
                    http_response_code(400);
                    $result = ['success' => false, 'message' => 'Invalid action'];
                    break;
            }
            break;
            
        case 'PUT':
            $userId = authenticate();
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'profile':
                    $result = $authService->updateProfile($userId, $input);
                    break;
                    
                case 'password':
                    $result = $authService->changePassword(
                        $userId,
                        $input['current_password'] ?? '',
                        $input['new_password'] ?? ''
                    );
                    break;
                    
                default:
                    http_response_code(400);
                    $result = ['success' => false, 'message' => 'Invalid action'];
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Member Routes
 */
function handleMemberRoutes($userId, $memberId) {
    global $memberService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($memberId) {
                checkPermission($userId, 'members_read');
                $result = $memberService->getMember($memberId);
            } else {
                checkPermission($userId, 'members_read');
                $filters = $_GET;
                $result = $memberService->getMembers($filters);
            }
            break;
            
        case 'POST':
            checkPermission($userId, 'members_write');
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $memberService->createMember($input, $userId);
            break;
            
        case 'PUT':
            checkPermission($userId, 'members_write');
            if ($memberId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $memberService->updateMember($memberId, $input, $userId);
            } else {
                http_response_code(400);
                $result = ['success' => false, 'message' => 'Member ID required'];
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Loan Routes
 */
function handleLoanRoutes($userId, $loanId) {
    global $loanService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($loanId) {
                checkPermission($userId, 'loans_read');
                $result = $loanService->getLoan($loanId);
            } else {
                checkPermission($userId, 'loans_read');
                $filters = $_GET;
                $result = $loanService->getLoans($filters);
            }
            break;
            
        case 'POST':
            checkPermission($userId, 'loans_write');
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $loanService->createLoanApplication($input, $userId);
            break;
            
        case 'PUT':
            checkPermission($userId, 'loans_write');
            if ($loanId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $loanService->updateLoan($loanId, $input, $userId);
            } else {
                http_response_code(400);
                $result = ['success' => false, 'message' => 'Loan ID required'];
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Savings Routes
 */
function handleSavingsRoutes($userId, $savingsId) {
    global $savingsService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($savingsId) {
                checkPermission($userId, 'savings_read');
                $result = $savingsService->getSavingsAccount($savingsId);
            } else {
                checkPermission($userId, 'savings_read');
                $filters = $_GET;
                $result = $savingsService->getSavingsAccounts($filters);
            }
            break;
            
        case 'POST':
            checkPermission($userId, 'savings_write');
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($_GET['action'] === 'deposit') {
                $result = $savingsService->deposit($input, $userId);
            } elseif ($_GET['action'] === 'withdraw') {
                $result = $savingsService->withdraw($input, $userId);
            } else {
                $result = $savingsService->createSavingsAccount($input, $userId);
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Report Routes
 */
function handleReportRoutes($userId, $reportId) {
    checkPermission($userId, 'reports_read');
    
    $reportType = $_GET['type'] ?? 'daily';
    $filters = $_GET;
    
    switch ($reportType) {
        case 'daily':
            $result = generateDailyReport($filters);
            break;
            
        case 'members':
            $result = generateMembersReport($filters);
            break;
            
        case 'loans':
            $result = generateLoansReport($filters);
            break;
            
        case 'savings':
            $result = generateSavingsReport($filters);
            break;
            
        default:
            http_response_code(400);
            $result = ['success' => false, 'message' => 'Invalid report type'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Notification Routes
 */
function handleNotificationRoutes($userId, $notificationId) {
    global $notificationService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($notificationId) {
                $result = $notificationService->getNotification($notificationId);
            } else {
                $filters = $_GET;
                $result = $notificationService->getNotifications($userId, $filters);
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Location Routes
 */
function handleLocationRoutes($userId, $locationId) {
    global $locationService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            checkPermission($userId, 'location_track');
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $locationService->trackLocation($input, $userId);
            break;
            
        case 'GET':
            if ($_GET['action'] === 'nearby_members') {
                checkPermission($userId, 'location_view');
                $filters = $_GET;
                $result = $locationService->getNearbyMembers($filters, $userId);
            } else {
                http_response_code(400);
                $result = ['success' => false, 'message' => 'Invalid action'];
            }
            break;
            
        default:
            http_response_code(405);
            $result = ['success' => false, 'message' => 'Method not allowed'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Fraud Routes
 */
function handleFraudRoutes($userId, $fraudId) {
    global $fraudService;
    
    checkPermission($userId, 'fraud_view');
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    if ($fraudId) {
        $result = $fraudService->getFraudDetection($fraudId);
    } else {
        $filters = $_GET;
        $result = $fraudService->getFraudDetections($filters);
    }
    
    echo json_encode($result);
}

/**
 * Dashboard Routes
 */
function handleDashboardRoutes($userId, $dashboardId) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    $dashboardType = $_GET['type'] ?? 'overview';
    $filters = $_GET;
    
    switch ($dashboardType) {
        case 'overview':
            $result = generateOverviewDashboard($userId, $filters);
            break;
            
        case 'analytics':
            checkPermission($userId, 'analytics_view');
            $result = generateAnalyticsDashboard($filters);
            break;
            
        default:
            http_response_code(400);
            $result = ['success' => false, 'message' => 'Invalid dashboard type'];
            break;
    }
    
    echo json_encode($result);
}

/**
 * Helper Functions
 */
function generateDailyReport($filters) {
    return [
        'success' => true,
        'data' => [
            'date' => date('Y-m-d'),
            'total_members' => 150,
            'new_members' => 5,
            'total_savings' => 15000000,
            'total_loans' => 25000000,
            'outstanding_loans' => 18000000,
            'revenue' => 2500000,
            'expenses' => 800000
        ]
    ];
}

function generateMembersReport($filters) {
    return [
        'success' => true,
        'data' => [
            'total_members' => 150,
            'active_members' => 145,
            'new_members_this_month' => 8,
            'member_growth_rate' => 5.2
        ]
    ];
}

function generateLoansReport($filters) {
    return [
        'success' => true,
        'data' => [
            'total_loans' => 45,
            'active_loans' => 42,
            'overdue_loans' => 3,
            'total_amount' => 25000000,
            'outstanding_amount' => 18000000
        ]
    ];
}

function generateSavingsReport($filters) {
    return [
        'success' => true,
        'data' => [
            'total_savings' => 15000000,
            'total_deposits_today' => 500000,
            'total_withdrawals_today' => 200000,
            'savings_growth_rate' => 8.5
        ]
    ];
}

function generateOverviewDashboard($userId, $filters) {
    return [
        'success' => true,
        'data' => [
            'total_members' => 150,
            'active_loans' => 45,
            'total_savings' => 15000000,
            'outstanding_loans' => 18000000,
            'monthly_revenue' => 2500000,
            'pending_approvals' => 8,
            'overdue_loans' => 3,
            'fraud_alerts' => 2
        ]
    ];
}

function generateAnalyticsDashboard($filters) {
    return [
        'success' => true,
        'data' => [
            'member_growth' => [
                ['month' => 'Jan', 'count' => 120],
                ['month' => 'Feb', 'count' => 135],
                ['month' => 'Mar', 'count' => 150]
            ],
            'loan_performance' => [
                ['month' => 'Jan', 'amount' => 20000000],
                ['month' => 'Feb', 'amount' => 22000000],
                ['month' => 'Mar', 'amount' => 25000000]
            ]
        ]
    ];
}

// Error handling
function handleError($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => Config::APP_ENV === 'development' ? $exception->getMessage() : null
    ]);
}

// Set error handler
set_exception_handler('handleError');

?>
