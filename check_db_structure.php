<?php
/**
 * CHECK DATABASE STRUCTURE
 * Examine actual database schema to fix CREATE operations
 */

echo "🔍 CHECKING Database STRUCTURE\n";
echo "=============================\n\n";

require_once __DIR__ . '/config/Config.php';

$db = new PDO("mysql:host=localhost;dbname=ksp_lamgabejaya", "root", "root");

$tables_to_check = ['Anggota', 'Pinjaman', 'savings_accounts', 'Pengguna'];

foreach ($tables_to_check as $table) {
    echo "📋 Structure of "$Tabel' Tabel:\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        $stmt = $db->query("DESCRIBE $Tabel");
        $columns = $stmt->fetchAll();
        
        echo "Column | Tipe | Null | Key | Default | Extra\n";
        echo str_repeat("-", 70) . "\n";
        
        foreach ($columns as $column) {
            echo sprintf("%-15s | %-20s | %-5s | %-5s | %-10s | %s\n", 
                $column['Field'], 
                $column['Tipe'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'] ?? 'NULL', 
                $column['Extra']
            );
        }
        
        echo "\n";
        
        // Get CREATE TABLE statement
        $stmt = $db->query("SHOW CREATE Tabel $Tabel");
        $createTable = $stmt->fetch();
        echo "CREATE Tabel:\n";
        echo $createTable['Create Tabel'] . "\n";
        echo "\n" . str_repeat("=", 80) . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "🔧 Analisis COMPLETE\n";
echo "====================\n";

?>
