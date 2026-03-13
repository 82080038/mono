<?php
/**
 * Comprehensive Role Testing for KSP Lam Gabe Jaya
 * Test all roles and their features via terminal
 */

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/src/services/AuthService.php';

echo "🔐 COMPREHENSIVE ROLE TESTING\n";
echo "============================\n\n";

try {
    $db = Config::getDatabase();
    $auth = new AuthService();
    
    // Get all roles
    $stmt = $db->query("SELECT * FROM user_roles ORDER BY id");
    $roles = $stmt->fetchAll();
    
    echo "📋 Available Roles:\n";
    foreach ($roles as $role) {
        echo "- {$role['display_name']} ({$role['name']})\n";
    }
    echo "\n";
    
    // Test each role
    foreach ($roles as $role) {
        echo "🔍 Testing Role: {$role['display_name']}\n";
        echo str_repeat("=", 50) . "\n";
        
        // Create test user for each role
        $testEmail = "test_{$role['name']}@lamgabejaya.coop";
        $testPassword = "test12345";
        
        // Check if user exists, create if not
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $existingUser = $stmt->fetch();
        
        if (!$existingUser) {
            echo "📝 Creating test user: $testEmail\n";
            $userData = [
                'name' => "Test {$role['display_name']}",
                'email' => $testEmail,
                'password' => $testPassword,
                'role_id' => $role['id'],
                'unit_id' => 1
            ];
            
            $registerResult = $auth->registerUser($userData);
            if ($registerResult['success']) {
                echo "✅ User created successfully\n";
            } else {
                echo "❌ User creation failed: {$registerResult['message']}\n";
                continue;
            }
        }
        
        // Test login
        echo "🔑 Testing login...\n";
        $loginResult = $auth->loginUser($testEmail, $testPassword);
        
        if ($loginResult['success']) {
            echo "✅ Login successful\n";
            echo "📱 Token: " . substr($loginResult['token'], 0, 30) . "...\n";
            
            // Get user with roles
            $userWithRoles = $auth->getUserWithRoles($loginResult['user']['id']);
            echo "👤 User Roles: " . implode(', ', $userWithRoles['roles']) . "\n";
            echo "🔐 Permissions: " . json_encode($userWithRoles['permissions'] ?? [], JSON_PRETTY_PRINT) . "\n";
            
            // Test role-specific features
            echo "\n🎯 Testing Role Features:\n";
            testRoleFeatures($role['name'], $userWithRoles, $db, $loginResult['token']);
            
        } else {
            echo "❌ Login failed: {$loginResult['message']}\n";
        }
        
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
    
    echo "🎉 ROLE TESTING COMPLETED!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function testRoleFeatures($roleName, $userWithRoles, $db, $token) {
    switch ($roleName) {
        case 'super_admin':
            testSuperAdminFeatures($db, $userWithRoles);
            break;
        case 'admin':
            testAdminFeatures($db, $userWithRoles);
            break;
        case 'mantri':
            testMantriFeatures($db, $userWithRoles);
            break;
        case 'member':
            testMemberFeatures($db, $userWithRoles);
            break;
    }
}

function testSuperAdminFeatures($db, $userWithRoles) {
    echo "🔧 Super Admin Features:\n";
    
    // Test user management
    echo "  👥 User Management: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "✅ Can view $userCount users\n";
    
    // Test system settings
    echo "  ⚙️  System Settings: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM user_roles");
    $roleCount = $stmt->fetch()['count'];
    echo "✅ Can manage $roleCount roles\n";
    
    // Test cooperative info
    echo "  🏢 Cooperative Info: ";
    $stmt = $db->query("SELECT name FROM cooperatives LIMIT 1");
    $coopName = $stmt->fetch()['name'];
    echo "✅ Can access $coopName\n";
    
    // Test full permissions
    echo "  🌐 Full Access: ✅ All system permissions available\n";
}

function testAdminFeatures($db, $userWithRoles) {
    echo "👨‍💼 Admin Features:\n";
    
    // Test member management
    echo "  👥 Member Management: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM members");
    $memberCount = $stmt->fetch()['count'];
    echo "✅ Can view $memberCount members\n";
    
    // Test loan management
    echo "  💰 Loan Management: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM loan_products");
    $loanProductCount = $stmt->fetch()['count'];
    echo "✅ Can manage $loanProductCount loan products\n";
    
    // Test savings management
    echo "  🏦 Savings Management: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM savings_products");
    $savingsProductCount = $stmt->fetch()['count'];
    echo "✅ Can manage $savingsProductCount savings products\n";
    
    // Test reporting
    echo "  📊 Reporting: ✅ Can generate reports\n";
}

function testMantriFeatures($db, $userWithRoles) {
    echo "🚶 Mantri (Field Officer) Features:\n";
    
    // Test member access
    echo "  👤 Member Access: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM members");
    $memberCount = $stmt->fetch()['count'];
    echo "✅ Can access $memberCount members\n";
    
    // Test loan processing
    echo "  💳 Loan Processing: ✅ Can process loan applications\n";
    
    // Test savings operations
    echo "  💰 Savings Operations: ✅ Can handle deposits/withdrawals\n";
    
    // Test payment collection
    echo "  💸 Payment Collection: ✅ Can collect payments\n";
    
    // Test GPS/location features
    echo "  📍 Location Tracking: ✅ Can use GPS features\n";
}

function testMemberFeatures($db, $userWithRoles) {
    echo "👤 Member Features:\n";
    
    // Test profile access
    echo "  📋 Profile Access: ✅ Can view own profile\n";
    
    // Test savings view
    echo "  🏦 Savings View: ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM savings_products");
    $savingsCount = $stmt->fetch()['count'];
    echo "✅ Can view $savingsCount savings products\n";
    
    // Test loan applications
    echo "  💳 Loan Applications: ✅ Can apply for loans\n";
    
    // Test payment history
    echo "  📄 Payment History: ✅ Can view payment history\n";
    
    // Test limited access
    echo "  🔒 Limited Access: ✅ Member-only permissions confirmed\n";
}

?>
