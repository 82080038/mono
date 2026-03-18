<?php
/**
 * Fix Logout Functions - KSP Lam Gabe Jaya
 * Add logout functions to pages that are missing them
 */

echo "🔧 FIXING Keluar FUNCTIONS\n";
echo "==========================\n\n";

$pages_needing_logout = [
    'users_crud' => '/var/www/html/mono/users_crud.html',
    'Anggota' => '/var/www/html/mono/Anggota.html',
    'members_crud' => '/var/www/html/mono/members_crud.html',
    'loans_crud' => '/var/www/html/mono/loans_crud.html',
    'savings_crud' => '/var/www/html/mono/savings_crud.html',
    'notifications' => '/var/www/html/mono/notifications.html',
    'audit_logs' => '/var/www/html/mono/audit_logs.html',
    'risk_assessment' => '/var/www/html/mono/risk_assessment.html'
];

$logout_function = "
        function Keluar() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('Pengguna');
            window.Lokasi.href = '/mono/Masuk.html';
        }";

foreach ($pages_needing_logout as $page_name => $file_path) {
    echo "🔧 Memproses: $page_name\n";
    
    if (!file_exists($file_path)) {
        echo "  ❌ File Tidak Ditemukan: $file_path\n\n";
        continue;
    }
    
    $content = file_get_contents($file_path);
    
    // Check if logout function already exists
    if (strpos($content, 'function Keluar()') !== false) {
        echo "  ✅ Keluar function Sudah Ada\n\n";
        continue;
    }
    
    // Find the position before the closing </script> tag
    $script_end_pos = strrpos($content, '</script>');
    
    if ($script_end_pos === false) {
        echo "  ❌ Tidak script tag found\n\n";
        continue;
    }
    
    // Insert logout function before </script>
    $new_content = substr($content, 0, $script_end_pos) . 
                   $logout_function . "\n    </script>" . 
                   substr($content, $script_end_pos + 9);
    
    // Write back to file
    if (file_put_contents($file_path, $new_content)) {
        echo "  ✅ Keluar function added\n\n";
    } else {
        echo "  ❌ Failed to write File\n\n";
    }
}

echo "🎯 Keluar FUNCTIONS FIX COMPLETE\n";
echo "===============================\n\n";

echo "📊 Verifikasi:\n";
echo "==============\n";

$base_url = 'http://localhost/mono';

foreach ($pages_needing_logout as $page_name => $file_path) {
    $url = str_replace('/var/www/html/mono', '', $file_path);
    $response = @file_get_contents($base_url . $url);
    
    if ($response && strpos($response, 'function Keluar()') !== false) {
        echo "✅ $page_name: Keluar function present\n";
    } else {
        echo "❌ $page_name: Keluar function missing\n";
    }
}

echo "\n🚀 Semua Keluar functions should now be present!\n";
?>
