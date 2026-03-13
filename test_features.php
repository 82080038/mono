<?php
/**
 * Advanced Feature Testing for All Roles
 * Test actual operations and permissions
 */

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/src/services/AuthService.php';

echo "🚀 ADVANCED FEATURE TESTING\n";
echo "==========================\n\n";

try {
    $db = Config::getDatabase();
    $auth = new AuthService();
    
    // Test login for each existing user
    $testUsers = [
        ['email' => 'admin@lamgabejaya.coop', 'password' => 'admin123', 'role' => 'Super Admin'],
        ['email' => 'test_super_admin@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Test Super Admin'],
        ['email' => 'test_admin@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Test Admin'],
        ['email' => 'test_mantri@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Test Mantri'],
        ['email' => 'test_member@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Test Member']
    ];
    
    foreach ($testUsers as $testUser) {
        echo "🔍 Testing: {$testUser['role']} ({$testUser['email']})\n";
        echo str_repeat("=", 60) . "\n";
        
        $loginResult = $auth->loginUser($testUser['email'], $testUser['password']);
        
        if ($loginResult['success']) {
            echo "✅ Login successful\n";
            $userWithRoles = $auth->getUserWithRoles($loginResult['user']['id']);
            
            // Test actual database operations
            testDatabaseOperations($db, $userWithRoles, $testUser['role']);
            testPermissionChecks($auth, $loginResult['user']['id'], $testUser['role']);
            testBusinessLogic($db, $userWithRoles, $testUser['role']);
            
        } else {
            echo "❌ Login failed: {$loginResult['message']}\n";
        }
        
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
    
    echo "🎉 ADVANCED TESTING COMPLETED!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function testDatabaseOperations($db, $userWithRoles, $roleName) {
    echo "📊 Database Operations Test:\n";
    
    try {
        // Test member operations
        $stmt = $db->query("SELECT COUNT(*) as count FROM members");
        $memberCount = $stmt->fetch()['count'];
        echo "  👥 Members: $memberCount records\n";
        
        // Test loan operations
        $stmt = $db->query("SELECT COUNT(*) as count FROM loans");
        $loanCount = $stmt->fetch()['count'];
        echo "  💰 Loans: $loanCount records\n";
        
        // Test savings operations
        $stmt = $db->query("SELECT COUNT(*) as count FROM savings_accounts");
        $savingsCount = $stmt->fetch()['count'];
        echo "  🏦 Savings: $savingsCount records\n";
        
        // Test user operations
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        echo "  👤 Users: $userCount records\n";
        
        // Test unit operations
        $stmt = $db->query("SELECT COUNT(*) as count FROM units");
        $unitCount = $stmt->fetch()['count'];
        echo "  🏢 Units: $unitCount records\n";
        
        echo "  ✅ Database access: SUCCESS\n";
        
    } catch (Exception $e) {
        echo "  ❌ Database access failed: " . $e->getMessage() . "\n";
    }
}

function testPermissionChecks($auth, $userId, $roleName) {
    echo "🔐 Permission Checks:\n";
    
    // Test various permissions
    $permissions = [
        'users.read' => 'View Users',
        'users.write' => 'Create/Edit Users',
        'users.delete' => 'Delete Users',
        'members.read' => 'View Members',
        'members.write' => 'Create/Edit Members',
        'members.delete' => 'Delete Members',
        'loans.read' => 'View Loans',
        'loans.write' => 'Create/Edit Loans',
        'loans.delete' => 'Delete Loans',
        'savings.read' => 'View Savings',
        'savings.write' => 'Create/Edit Savings',
        'savings.delete' => 'Delete Savings',
        'reports.read' => 'View Reports',
        'reports.write' => 'Generate Reports',
        'system.admin' => 'System Administration'
    ];
    
    foreach ($permissions as $permission => $description) {
        $hasPermission = $auth->hasPermission($userId, $permission);
        $status = $hasPermission ? "✅" : "❌";
        echo "  $status $description\n";
    }
}

function testBusinessLogic($db, $userWithRoles, $roleName) {
    echo "💼 Business Logic Test:\n";
    
    try {
        // Test loan products availability
        $stmt = $db->query("SELECT name, interest_rate_monthly FROM loan_products WHERE is_active = 1");
        $loanProducts = $stmt->fetchAll();
        echo "  💳 Available Loan Products:\n";
        foreach ($loanProducts as $product) {
            echo "    - {$product['name']}: " . ($product['interest_rate_monthly'] * 100) . "%/bulan\n";
        }
        
        // Test savings products availability
        $stmt = $db->query("SELECT name, interest_rate_monthly FROM savings_products WHERE is_active = 1");
        $savingsProducts = $stmt->fetchAll();
        echo "  🏦 Available Savings Products:\n";
        foreach ($savingsProducts as $product) {
            echo "    - {$product['name']}: " . ($product['interest_rate_monthly'] * 100) . "%/bulan\n";
        }
        
        // Test cooperative info
        $stmt = $db->query("SELECT name, phone, email FROM cooperatives LIMIT 1");
        $coop = $stmt->fetch();
        echo "  🏢 Cooperative Info:\n";
        echo "    - Name: {$coop['name']}\n";
        echo "    - Phone: {$coop['phone']}\n";
        echo "    - Email: {$coop['email']}\n";
        
        // Test unit info
        $stmt = $db->query("SELECT name, type, is_active FROM units");
        $units = $stmt->fetchAll();
        echo "  📍 Units:\n";
        foreach ($units as $unit) {
            $status = $unit['is_active'] ? 'Active' : 'Inactive';
            echo "    - {$unit['name']} ({$unit['type']}): $status\n";
        }
        
        echo "  ✅ Business logic: SUCCESS\n";
        
    } catch (Exception $e) {
        echo "  ❌ Business logic failed: " . $e->getMessage() . "\n";
    }
}

?>
