<?php
/**
 * API Routes - SaaS Koperasi Harian
 * 
 * RESTful API endpoints for all system functionality
 * with authentication, authorization, and rate limiting
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

// Include required services
require_once '../src/services/AuthService.php';
require_once '../src/services/MemberService.php';
require_once '../src/services/LoanService.php';
require_once '../src/services/TransactionService.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize services
$authService = new AuthService();
$memberService = new MemberService();
$loanService = new LoanService();
$transactionService = new TransactionService();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// API Version
$apiVersion = $pathParts[0] ?? 'v1';

// Response helper function
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Authentication middleware
function authenticate($authService) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!$authHeader) {
        jsonResponse(['error' => 'Authorization header required'], 401);
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    $tokenResult = $authService->verifyToken($token);
    
    if (!$tokenResult['success']) {
        jsonResponse(['error' => 'Invalid token'], 401);
    }
    
    return $tokenResult['user'];
}

// Rate limiting middleware
function rateLimit($limit = 100, $window = 3600) {
    $clientIp = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit_{$clientIp}";
    
    // Simple rate limiting (in production, use Redis or similar)
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
    }
    
    $rateData = $_SESSION[$key];
    
    if (time() > $rateData['reset_time']) {
        $_SESSION[$key] = ['count' => 1, 'reset_time' => time() + $window];
    } else {
        $_SESSION[$key]['count']++;
        
        if ($_SESSION[$key]['count'] > $limit) {
            jsonResponse(['error' => 'Rate limit exceeded'], 429);
        }
    }
}

// Apply rate limiting to all API requests
rateLimit();

// Route handling based on path
try {
    switch ($apiVersion) {
        case 'v1':
            handleV1Routes($method, $pathParts, $authService, $memberService, $loanService, $transactionService);
            break;
        default:
            jsonResponse(['error' => 'API version not supported'], 404);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function handleV1Routes($method, $pathParts, $authService, $memberService, $loanService, $transactionService) {
    $endpoint = $pathParts[1] ?? '';
    
    // Public endpoints (no authentication required)
    $publicEndpoints = ['auth/login', 'auth/register'];
    
    if (!in_array($endpoint, $publicEndpoints)) {
        $user = authenticate($authService);
    }
    
    switch ($endpoint) {
        // Authentication endpoints
        case 'auth':
            handleAuthRoutes($method, $pathParts, $authService);
            break;
            
        // Member endpoints
        case 'members':
            handleMemberRoutes($method, $pathParts, $memberService, $user ?? null);
            break;
            
        // Loan endpoints
        case 'loans':
            handleLoanRoutes($method, $pathParts, $loanService, $user ?? null);
            break;
            
        // Transaction endpoints
        case 'transactions':
            handleTransactionRoutes($method, $pathParts, $transactionService, $user ?? null);
            break;
            
        // Dashboard endpoints
        case 'dashboard':
            handleDashboardRoutes($method, $pathParts, $transactionService, $user ?? null);
            break;
            
        default:
            jsonResponse(['error' => 'Endpoint not found'], 404);
    }
}

// Authentication routes
function handleAuthRoutes($method, $pathParts, $authService) {
    $action = $pathParts[2] ?? '';
    
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'login':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $authService->login($data);
                    jsonResponse($result, $result['success'] ? 200 : 401);
                    break;
                    
                case 'register':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $authService->register($data);
                    jsonResponse($result, $result['success'] ? 201 : 400);
                    break;
                    
                case 'refresh':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $authService->refreshToken($data['refresh_token']);
                    jsonResponse($result, $result['success'] ? 200 : 401);
                    break;
                    
                default:
                    jsonResponse(['error' => 'Auth action not found'], 404);
            }
            break;
            
        case 'POST':
            if ($action === 'logout') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $authService->logout($data['token']);
                jsonResponse($result, $result['success'] ? 200 : 401);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

// Member routes
function handleMemberRoutes($method, $pathParts, $memberService, $user) {
    $memberId = $pathParts[2] ?? null;
    $action = $pathParts[3] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($memberId) {
                // Get specific member
                $member = $memberService->getMemberById($memberId, $user['tenant_id']);
                if ($member) {
                    jsonResponse(['success' => true, 'data' => $member]);
                } else {
                    jsonResponse(['error' => 'Member not found'], 404);
                }
            } else {
                // Get members list
                $filters = $_GET;
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                
                $result = $memberService->getMembersList($user['tenant_id'], $filters, $page, $limit);
                jsonResponse($result);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $memberService->registerMember($data, $user['tenant_id']);
            jsonResponse($result, $result['success'] ? 201 : 400);
            break;
            
        case 'PUT':
            if ($memberId) {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $memberService->updateMember($memberId, $data, $user['tenant_id']);
                jsonResponse($result, $result['success'] ? 200 : 400);
            } else {
                jsonResponse(['error' => 'Member ID required'], 400);
            }
            break;
            
        case 'DELETE':
            if ($memberId && $action === 'deactivate') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $memberService->deactivateMember($memberId, $user['tenant_id'], $data['reason'] ?? '');
                jsonResponse($result, $result['success'] ? 200 : 400);
            } else {
                jsonResponse(['error' => 'Invalid member action'], 400);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

// Loan routes
function handleLoanRoutes($method, $pathParts, $loanService, $user) {
    $loanId = $pathParts[2] ?? null;
    $action = $pathParts[3] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($loanId) {
                // Get specific loan
                $loan = $loanService->getLoanApplicationById($loanId, $user['tenant_id']);
                if ($loan) {
                    jsonResponse(['success' => true, 'data' => $loan]);
                } else {
                    jsonResponse(['error' => 'Loan not found'], 404);
                }
            } else {
                // Get loans list
                $filters = $_GET;
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                
                $result = $loanService->getLoanApplicationsList($user['tenant_id'], $filters, $page, $limit);
                jsonResponse($result);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $loanService->submitLoanApplication($data, $data['member_id'], $user['tenant_id']);
            jsonResponse($result, $result['success'] ? 201 : 400);
            break;
            
        case 'PUT':
            if ($loanId && $action === 'review') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $loanService->reviewLoanApplication($loanId, $data, $user['id'], $user['tenant_id']);
                jsonResponse($result, $result['success'] ? 200 : 400);
            } elseif ($loanId && $action === 'disburse') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $loanService->disburseLoan($loanId, $data, $user['id'], $user['tenant_id']);
                jsonResponse($result, $result['success'] ? 200 : 400);
            } elseif ($loanId && $action === 'payment') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $loanService->processLoanPayment($loanId, $data, $user['id'], $user['tenant_id']);
                jsonResponse($result, $result['success'] ? 200 : 400);
            } else {
                jsonResponse(['error' => 'Invalid loan action'], 400);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

// Transaction routes
function handleTransactionRoutes($method, $pathParts, $transactionService, $user) {
    $transactionId = $pathParts[2] ?? null;
    $action = $pathParts[3] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($transactionId) {
                if ($action === 'chain') {
                    // Get transaction chain
                    $result = $transactionService->getTransactionChain($transactionId);
                    jsonResponse($result);
                } else {
                    // Get specific transaction
                    $transaction = $transactionService->getTransactionById($transactionId);
                    if ($transaction) {
                        jsonResponse(['success' => true, 'data' => $transaction]);
                    } else {
                        jsonResponse(['error' => 'Transaction not found'], 404);
                    }
                }
            } else {
                // Get transactions list
                $filters = $_GET;
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                
                $result = $transactionService->getTransactionsList($user['tenant_id'], $filters, $page, $limit);
                jsonResponse($result);
            }
            break;
            
        case 'POST':
            if ($transactionId && $action === 'correction') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $transactionService->createTransactionCorrection($transactionId, $data, $user['id']);
                jsonResponse($result, $result['success'] ? 201 : 400);
            } else {
                jsonResponse(['error' => 'Invalid transaction action'], 400);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

// Dashboard routes
function handleDashboardRoutes($method, $pathParts, $transactionService, $user) {
    $action = $pathParts[2] ?? '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'cash-flow':
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $result = $transactionService->getDailyCashFlow($user['tenant_id'], $date);
                    jsonResponse($result);
                    break;
                    
                case 'mantri-position':
                    $mantriId = (int)($_GET['mantri_id'] ?? 0);
                    if ($mantriId) {
                        $result = $transactionService->getMantriCashPosition($mantriId, $user['tenant_id']);
                        jsonResponse($result);
                    } else {
                        jsonResponse(['error' => 'Mantri ID required'], 400);
                    }
                    break;
                    
                default:
                    // Get dashboard overview
                    $today = date('Y-m-d');
                    $cashFlow = $transactionService->getDailyCashFlow($user['tenant_id'], $today);
                    
                    jsonResponse([
                        'success' => true,
                        'data' => [
                            'date' => $today,
                            'cash_flow' => $cashFlow,
                            'summary' => [
                                'total_members' => 1234, // Get from database
                                'total_loans' => 45600000, // Get from database
                                'daily_collection' => $cashFlow['cash_in'] ?? 0,
                                'overdue_count' => 23 // Get from database
                            ]
                        ]
                    ]);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
}

// Error handling
function handleError($errno, $errstr, $errfile, $errline) {
    jsonResponse(['error' => 'Internal server error'], 500);
}

// Set error handler
set_error_handler('handleError');

// Exception handling
function handleException($exception) {
    jsonResponse(['error' => $exception->getMessage()], 500);
}

// Set exception handler
set_exception_handler('handleException');
?>
