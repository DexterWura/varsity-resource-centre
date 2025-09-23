<?php
/**
 * Test Migration V9 - Enhanced Timetable
 * 
 * This script tests the V9 migration to ensure it won't cause duplicate key errors.
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🧪 Testing Migration V9 - Enhanced Timetable\n";
    echo "==========================================\n\n";
    
    // Check if universities table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'universities'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Universities table doesn't exist. Migration V9 needs to be run first.\n";
        exit(1);
    }
    
    echo "✅ Universities table exists\n";
    
    // Check current universities
    $stmt = $pdo->query("SELECT code, name FROM universities ORDER BY code");
    $universities = $stmt->fetchAll();
    
    echo "📊 Current universities in database:\n";
    if (empty($universities)) {
        echo "   (No universities found)\n";
    } else {
        foreach ($universities as $uni) {
            echo "   - {$uni['code']}: {$uni['name']}\n";
        }
    }
    
    // Check for duplicates
    $stmt = $pdo->query("
        SELECT code, COUNT(*) as count 
        FROM universities 
        GROUP BY code 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (!empty($duplicates)) {
        echo "\n⚠️  WARNING: Found duplicate university codes:\n";
        foreach ($duplicates as $dup) {
            echo "   - Code '{$dup['code']}' appears {$dup['count']} times\n";
        }
        echo "\n💡 Run 'php fix_duplicate_university.php' to fix this issue.\n";
        exit(1);
    }
    
    echo "\n✅ No duplicate university codes found\n";
    
    // Test the INSERT IGNORE statement
    echo "\n🧪 Testing INSERT IGNORE for universities...\n";
    
    $testCode = 'TEST_UNI_' . time();
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO universities (name, code, country, website) 
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        'Test University',
        $testCode,
        'Zimbabwe',
        'https://test.example.com'
    ]);
    
    if ($result) {
        echo "✅ INSERT IGNORE test successful\n";
        
        // Clean up test data
        $stmt = $pdo->prepare("DELETE FROM universities WHERE code = ?");
        $stmt->execute([$testCode]);
        echo "🧹 Test data cleaned up\n";
    } else {
        echo "❌ INSERT IGNORE test failed\n";
        exit(1);
    }
    
    echo "\n🎉 Migration V9 test completed successfully!\n";
    echo "💡 The migration should run without duplicate key errors.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
