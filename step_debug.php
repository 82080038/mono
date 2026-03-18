<?php
/**
 * Step-by-step API debugging
 */

echo "=== STEP BY STEP DEBUG ===\n\n";

// Step 1: Test Config class
echo "Step 1: Testing Config class...\n";
try {
    require_once __DIR__ . '/config/Config.php';
    echo "✅ Config class loaded\n";
    
    $db = Config::getDatabase();
    echo "✅ Database connection established\n";
    
    // Test simple query
    $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "✅ Database query successful - Users: " . $result['count'] . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Config/DB failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Step 2: Test SecurityHelper
echo "Step 2: Testing SecurityHelper...\n";
try {
    require_once __DIR__ . '/security_fixes.php';
    echo "✅ SecurityHelper loaded\n\n";
} catch (Exception $e) {
    echo "❌ SecurityHelper failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Step 3: Test DatabaseHelper
echo "Step 3: Testing DatabaseHelper...\n";
try {
    require_once __DIR__ . '/api/DatabaseHelper.php';
    $helper = new DatabaseHelper($db);
    echo "✅ DatabaseHelper created\n\n";
} catch (Exception $e) {
    echo "❌ DatabaseHelper failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Step 4: Test CompleteAPIHandlers
echo "Step 4: Testing CompleteAPIHandlers...\n";
try {
    require_once __DIR__ . '/api/complete_handlers.php';
    $handlers = new CompleteAPIHandlers();
    echo "✅ CompleteAPIHandlers created\n\n";
} catch (Exception $e) {
    echo "❌ CompleteAPIHandlers failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Step 5: Test individual handlers
echo "Step 5: Testing individual handlers...\n";

// Test User Management
echo "Testing User Management...\n";
try {
    ob_start();
    $handlers->handleUserManagement();
    $output = ob_get_clean();
    $data = json_decode($output, true);
    
    if ($data && $data['success']) {
        echo "✅ User Management: SUCCESS\n";
    } else {
        echo "❌ User Management: FAILED - " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ User Management: EXCEPTION - " . $e->getMessage() . "\n";
}

// Test System Settings
echo "Testing System Settings...\n";
try {
    ob_start();
    $handlers->handleSystemSettings();
    $output = ob_get_clean();
    $data = json_decode($output, true);
    
    if ($data && $data['success']) {
        echo "✅ System Settings: SUCCESS\n";
    } else {
        echo "❌ System Settings: FAILED - " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ System Settings: EXCEPTION - " . $e->getMessage() . "\n";
}

// Test System Health
echo "Testing System Health...\n";
try {
    ob_start();
    $handlers->handleSystemHealth();
    $output = ob_get_clean();
    $data = json_decode($output, true);
    
    if ($data && $data['success']) {
        echo "✅ System Health: SUCCESS\n";
    } else {
        echo "❌ System Health: FAILED - " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ System Health: EXCEPTION - " . $e->getMessage() . "\n";
}

echo "\n=== STEP BY STEP DEBUG COMPLETE ===\n";

?>
