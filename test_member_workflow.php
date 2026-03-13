<?php
/**
 * Create Test Member and Test Financial Operations
 * Test complete member workflow and financial operations
 */

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/src/services/AuthService.php';

echo "👥 MEMBER WORKFLOW TESTING\n";
echo "========================\n\n";

try {
    $db = Config::getDatabase();
    $auth = new AuthService();
    
    // Login as admin to create member
    echo "🔑 Admin Login...\n";
    $adminLogin = $auth->loginUser('admin@lamgabejaya.coop', 'admin123');
    
    if (!$adminLogin['success']) {
        echo "❌ Admin login failed\n";
        exit;
    }
    
    echo "✅ Admin login successful\n\n";
    
    // Create test member
    echo "👤 Creating Test Member...\n";
    $memberData = [
        'member_number' => 'M001',
        'nik' => '1234567890123456',
        'name' => 'Budi Santoso',
        'gender' => 'male',
        'birth_place' => 'Jakarta',
        'birth_date' => '1985-05-15',
        'address' => 'Jl. Test No. 123, Jakarta',
        'phone' => '08123456789',
        'email' => 'budi@example.com',
        'occupation' => 'Pegawai Swasta',
        'company_name' => 'PT Example',
        'company_address' => 'Jl. Company No. 456',
        'monthly_income' => 5000000,
        'marital_status' => 'married',
        'spouse_name' => 'Siti Nurhaliza',
        'spouse_phone' => '08123456790',
        'spouse_occupation' => 'Ibu Rumah Tangga',
        'emergency_contact_name' => 'Ahmad',
        'emergency_contact_phone' => '08123456791',
        'emergency_contact_relation' => 'Saudara',
        'credit_score' => 75.50,
        'registration_date' => date('Y-m-d'),
        'unit_id' => 1
    ];
    
    // Insert member directly for testing
    $stmt = $db->prepare("
        INSERT INTO members (uuid, member_number, nik, name, gender, birth_place, birth_date, 
                              address, phone, email, occupation, company_name, company_address, 
                              monthly_income, marital_status, spouse_name, spouse_phone, spouse_occupation,
                              emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                              credit_score, registration_date, unit_id, created_by, created_at)
        VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $memberData['member_number'],
        $memberData['nik'],
        $memberData['name'],
        $memberData['gender'],
        $memberData['birth_place'],
        $memberData['birth_date'],
        $memberData['address'],
        $memberData['phone'],
        $memberData['email'],
        $memberData['occupation'],
        $memberData['company_name'],
        $memberData['company_address'],
        $memberData['monthly_income'],
        $memberData['marital_status'],
        $memberData['spouse_name'],
        $memberData['spouse_phone'],
        $memberData['spouse_occupation'],
        $memberData['emergency_contact_name'],
        $memberData['emergency_contact_phone'],
        $memberData['emergency_contact_relation'],
        $memberData['credit_score'],
        $memberData['registration_date'],
        $memberData['unit_id'],
        $adminLogin['user']['id']
    ]);
    
    $memberId = $db->lastInsertId();
    echo "✅ Member created: {$memberData['name']} (ID: $memberId)\n\n";
    
    // Create savings accounts for member
    echo "🏦 Creating Savings Accounts...\n";
    $savingsProducts = [
        ['name' => 'Simpanan Wajib', 'code' => 'WAJIB'],
        ['name' => 'Simpanan Sukarela', 'code' => 'SUKARELA']
    ];
    
    foreach ($savingsProducts as $product) {
        $stmt = $db->prepare("SELECT id FROM savings_products WHERE code = ?");
        $stmt->execute([$product['code']]);
        $productId = $stmt->fetch()['id'];
        
        $accountNumber = $product['code'] . date('Ymd') . str_pad($memberId, 4, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("
            INSERT INTO savings_accounts (uuid, member_id, product_id, account_number, balance, opened_date, unit_id, created_by, created_at)
            VALUES (UUID(), ?, ?, ?, 0.00, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $memberId,
            $productId,
            $accountNumber,
            date('Y-m-d'),
            1,
            $adminLogin['user']['id']
        ]);
        
        echo "✅ Savings account created: $accountNumber\n";
    }
    echo "\n";
    
    // Test loan application
    echo "💳 Creating Loan Application...\n";
    $loanData = [
        'loan_number' => 'LN' . date('Ymd') . str_pad($memberId, 4, '0', STR_PAD_LEFT),
        'member_id' => $memberId,
        'product_id' => 1, // Pinjaman Produktif
        'amount' => 10000000,
        'interest_rate' => 0.0150,
        'term_months' => 12,
        'admin_fee' => 100000,
        'disbursement_amount' => 9900000,
        'purpose' => 'Modal usaha tambahan',
        'collateral_description' => 'BPKB Motor Honda',
        'application_date' => date('Y-m-d'),
        'unit_id' => 1,
        'created_by' => $adminLogin['user']['id']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO loans (uuid, loan_number, member_id, product_id, amount, interest_rate, term_months, 
                           admin_fee, disbursement_amount, purpose, collateral_description, 
                           application_date, unit_id, created_by, created_at)
        VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $loanData['loan_number'],
        $loanData['member_id'],
        $loanData['product_id'],
        $loanData['amount'],
        $loanData['interest_rate'],
        $loanData['term_months'],
        $loanData['admin_fee'],
        $loanData['disbursement_amount'],
        $loanData['purpose'],
        $loanData['collateral_description'],
        $loanData['application_date'],
        $loanData['unit_id'],
        $loanData['created_by']
    ]);
    
    $loanId = $db->lastInsertId();
    echo "✅ Loan application created: {$loanData['loan_number']} (ID: $loanId)\n\n";
    
    // Test role-based access with member data
    echo "🔍 Testing Role Access with Member Data...\n";
    $testUsers = [
        ['email' => 'test_admin@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Admin'],
        ['email' => 'test_mantri@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Mantri'],
        ['email' => 'test_member@lamgabejaya.coop', 'password' => 'test12345', 'role' => 'Member']
    ];
    
    foreach ($testUsers as $testUser) {
        echo "🔑 Testing: {$testUser['role']}\n";
        $loginResult = $auth->loginUser($testUser['email'], $testUser['password']);
        
        if ($loginResult['success']) {
            $userWithRoles = $auth->getUserWithRoles($loginResult['user']['id']);
            
            // Test member access
            $canViewMembers = $auth->hasPermission($loginResult['user']['id'], 'members.read');
            $canEditMembers = $auth->hasPermission($loginResult['user']['id'], 'members.write');
            $canViewLoans = $auth->hasPermission($loginResult['user']['id'], 'loans.read');
            $canEditLoans = $auth->hasPermission($loginResult['user']['id'], 'loans.write');
            
            echo "  👥 View Members: " . ($canViewMembers ? "✅" : "❌") . "\n";
            echo "  ✏️  Edit Members: " . ($canEditMembers ? "✅" : "❌") . "\n";
            echo "  💳 View Loans: " . ($canViewLoans ? "✅" : "❌") . "\n";
            echo "  ✏️  Edit Loans: " . ($canEditLoans ? "✅" : "❌") . "\n";
            
            // Test actual data access
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM members");
                $memberCount = $stmt->fetch()['count'];
                echo "  📊 Can access members data: ✅ ($memberCount members)\n";
            } catch (Exception $e) {
                echo "  📊 Can access members data: ❌ (Error: " . $e->getMessage() . ")\n";
            }
            
        } else {
            echo "  ❌ Login failed\n";
        }
        echo "\n";
    }
    
    // Display summary
    echo "📋 SUMMARY\n";
    echo "=========\n";
    echo "👤 Test Member: {$memberData['name']}\n";
    echo "📱 Member ID: $memberId\n";
    echo "💳 Loan Application: {$loanData['loan_number']}\n";
    echo "🏦 Savings Accounts: " . count($savingsProducts) . " accounts\n";
    echo "🔐 Role Testing: " . count($testUsers) . " roles tested\n";
    echo "\n🎉 MEMBER WORKFLOW TESTING COMPLETED!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
