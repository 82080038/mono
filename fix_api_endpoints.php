<?php
/**
 * Fix API Endpoints - KSP Lam Gabe Jaya
 * Fix HTTP 500 errors in API endpoints
 */

echo "🔧 FIXING API ENDPOINTS - KSP Lam Gabe Jaya\n";
echo "=====================================\n\n";

// Step 1: Check database connection
echo "📊 Step 1: Database Connection Check\n";
echo "-----------------------------------\n";

try {
    require_once __DIR__ . '/config/Config.php';
    $db = Config::getDatabase();
    
    if ($db) {
        echo "✅ Database connection successful\n";
        
        // Test basic query
        $stmt = $db->query("Pilih 1");
        if ($stmt) {
            echo "✅ Database query test passed\n";
        } else {
            echo "❌ Database query test failed\n";
        }
    } else {
        echo "❌ Database Koneksi gagal\n";
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 2: Check required tables
echo "📋 Step 2: Database Tables Check\n";
echo "--------------------------------\n";

$required_tables = [
    'Pengguna',
    'Anggota', 
    'Pinjaman',
    'savings_accounts',
    'audit_logs',
    'notifications',
    'risk_assessments'
];

try {
    $db = Config::getDatabase();
    $existing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$Tabel'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabel "$Tabel' exists\n";
            $existing_tables[] = $table;
        } else {
            echo "❌ Tabel "$Tabel' missing\n";
        }
    }
    
    echo "\n📊 Tables Status: " . count($existing_tables) . "/" . count($required_tables) . " exist\n";
    
} catch (Exception $e) {
    echo "❌ Tabel check Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 3: Test API endpoints individually
echo "🔌 Step 3: API Endpoint Testing\n";
echo "--------------------------------\n";

$api_endpoints = [
    'Pengguna' => 'Pengguna Manajemen',
    'Anggota' => 'Anggota Manajemen',
    'Pinjaman' => 'Pinjaman Manajemen',
    'Simpanan' => 'Simpanan Manajemen',
    'Laporan/Statistik' => 'Laporan Statistik',
    'audit_logs' => 'Audit Logs',
    'notifications' => 'Notifications',
    'risk_assessment' => 'Risiko Assessment'
];

foreach ($api_endpoints as $endpoint => $description) {
    echo "🔗 Testing: $endpoint ($Deskripsi)\n";
    
    $url = "http://localhost/mono/API/crud.php?path=" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Tipe: Aplikasi/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ❌ Connection Error: $curl_error\n";
    } else {
        echo "   📡 HTTP Status: $http_code\n";
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data && $data['Berhasil']) {
                echo "   ✅ API working\n";
                echo "   📊 Data: " . (isset($data['Data']) ? count($data['Data']) . " records" : "Tidak Data") . "\n";
            } else {
                echo "   ⚠️ API returned Error: " . ($data['Pesan'] ?? 'Unknown') . "\n";
            }
        } else {
            echo "   ❌ API failed: HTTP $http_code\n";
            if ($http_code === 500) {
                echo "   🐛 Error Server - checking logs...\n";
                // Try to get error details
                echo "   📝 Response preview: " . substr($response, 0, 200) . "...\n";
            }
        }
    }
    
    echo "\n";
}

// Step 4: Fix common issues
echo "🔧 Step 4: Fix Common Issues\n";
echo "----------------------------\n";

// Check if crud.php has syntax errors
echo "📝 Checking crud.php syntax...\n";
$output = [];
$return_var = 0;
exec('php -l /var/www/html/mono/API/crud.php 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "✅ crud.php syntax is valid\n";
} else {
    echo "❌ crud.php syntax Error:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

echo "\n";

// Check if auth.php has syntax errors
echo "📝 Checking auth.php syntax...\n";
$output = [];
$return_var = 0;
exec('php -l /var/www/html/mono/API/auth.php 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "✅ auth.php syntax is valid\n";
} else {
    echo "❌ auth.php syntax Error:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

echo "\n";

// Step 5: Create missing tables if needed
echo "🗄️ Step 5: Create Missing Tables\n";
echo "--------------------------------\n";

try {
    $db = Config::getDatabase();
    
    // Create audit_logs table if missing
    $stmt = $db->query("SHOW TABLES LIKE 'audit_logs'");
    if ($stmt->rowCount() === 0) {
        echo "🔨 Creating audit_logs Tabel...\n";
        
        $sql = "
        CREATE Tabel audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            module VARCHAR(100),
            Detail TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            session_id VARCHAR(100),
            Status VARCHAR(20) DEFAULT 'Berhasil',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON Perbarui CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $db->exec($sql);
        echo "✅ audit_logs Tabel Dibuat\n";
    }
    
    // Create notifications table if missing
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() === 0) {
        echo "🔨 Creating notifications Tabel...\n";
        
        $sql = "
        CREATE Tabel notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            Judul VARCHAR(255),
            Pesan TEXT,
            Tipe VARCHAR(50) DEFAULT 'Info',
            Status VARCHAR(20) DEFAULT 'unread',
            sender_id INT,
            target_user_id INT,
            Prioritas INT DEFAULT 1,
            expires_at TIMESTAMP NULL,
            action_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON Perbarui CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $db->exec($sql);
        echo "✅ notifications Tabel Dibuat\n";
    }
    
    // Create risk_assessments table if missing
    $stmt = $db->query("SHOW TABLES LIKE 'risk_assessments'");
    if ($stmt->rowCount() === 0) {
        echo "🔨 Creating risk_assessments Tabel...\n";
        
        $sql = "
        CREATE Tabel risk_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT,
            risk_score INT,
            risk_level VARCHAR(20),
            risk_factors TEXT,
            recommendations TEXT,
            assessed_by INT,
            assessment_method VARCHAR(50) DEFAULT 'automated',
            confidence_score INT DEFAULT 70,
            next_assessment_date Tanggal,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON Perbarui CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $db->exec($sql);
        echo "✅ risk_assessments Tabel Dibuat\n";
    }
    
} catch (Exception $e) {
    echo "❌ Tabel creation Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 6: Add sample data for testing
echo "📊 Step 6: Tambah Sample Data\n";
echo "-------------------------\n";

try {
    $db = Config::getDatabase();
    
    // Add sample audit logs
    $stmt = $db->query("Pilih Jumlah(*) FROM audit_logs");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "🔨 Adding sample Audit logs...\n";
        
        $sql = "
        INSERT INTO audit_logs (user_id, action, module, Detail, ip_address, user_agent, session_id, Status) VALUES
        (1, 'Masuk', 'Authentication', 'Pengguna Masuk successful', '192.168.1.100', 'Mozilla/5.0', 'sess_abc123', 'Berhasil'),
        (1, 'create', 'Anggota', 'Dibuat new Anggota', '192.168.1.100', 'Mozilla/5.0', 'sess_abc123', 'Berhasil'),
        (1, 'Perbarui', 'Pinjaman', 'Diperbarui Pinjaman Status', '192.168.1.100', 'Mozilla/5.0', 'sess_abc123', 'Berhasil');
        ";
        
        $db->exec($sql);
        echo "✅ Sample Audit logs added\n";
    }
    
    // Add sample notifications
    $stmt = $db->query("Pilih Jumlah(*) FROM notifications");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "🔨 Adding sample notifications...\n";
        
        $sql = "
        INSERT INTO notifications (Judul, Pesan, Tipe, Status, sender_id, Prioritas) VALUES
        ('Pinjaman Baru', 'Ada pinjaman baru yang perlu persetujuan', 'Info', 'unread', 1, 1),
        ('Pengingat Jatuh Tempo', '3 pinjaman akan jatuh tempo minggu ini', 'Peringatan', 'unread', 1, 2),
        ('Backup Selesai', 'Backup Database harian telah selesai', 'Berhasil', 'read', 1, 3);
        ";
        
        $db->exec($sql);
        echo "✅ Sample notifications added\n";
    }
    
    // Add sample risk assessments
    $stmt = $db->query("Pilih Jumlah(*) FROM risk_assessments");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "🔨 Adding sample Risiko assessments...\n";
        
        $sql = "
        INSERT INTO risk_assessments (member_id, risk_score, risk_level, risk_factors, recommendations, assessed_by) VALUES
        (1, 25, 'low', 'Good Pembayaran Riwayat', 'Maintain current Status', 1),
        (2, 75, 'medium', 'Late Pembayaran, High debt Rasio', 'Monitor closely', 1),
        (3, 85, 'high', 'Multiple late Pembayaran, High debt', 'Immediate action Wajib', 1);
        ";
        
        $db->exec($sql);
        echo "✅ Sample Risiko assessments added\n";
    }
    
} catch (Exception $e) {
    echo "❌ Sample Data Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 7: Final API test
echo "🧪 Step 7: Final API Test\n";
echo "------------------------\n";

echo "🔄 Testing API endpoints after fixes...\n\n";

foreach ($api_endpoints as $endpoint => $description) {
    echo "🔗 Testing: $endpoint ($Deskripsi)\n";
    
    $url = "http://localhost/mono/API/crud.php?path=" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Tipe: Aplikasi/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   📡 HTTP Status: $http_code\n";
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if ($data && $data['Berhasil']) {
            echo "   ✅ API working\n";
            echo "   📊 Data: " . (isset($data['Data']) ? count($data['Data']) . " records" : "Tidak Data") . "\n";
        } else {
            echo "   ⚠️ API returned Error: " . ($data['Pesan'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "   ❌ API still failing: HTTP $http_code\n";
    }
    
    echo "\n";
}

echo "🎉 API FIX Proses COMPLETE!\n";
echo "=====================================\n";

?>
