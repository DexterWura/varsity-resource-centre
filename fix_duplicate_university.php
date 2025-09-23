<?php
/**
 * Fix Duplicate University Code Issue
 * 
 * This script helps resolve the "Duplicate entry 'UZ' for key 'code'" error
 * by cleaning up any duplicate university entries.
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔍 Checking for duplicate university codes...\n";
    
    // Check for duplicates
    $stmt = $pdo->query("
        SELECT code, COUNT(*) as count 
        FROM universities 
        GROUP BY code 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ No duplicate university codes found. The database is clean!\n";
        exit(0);
    }
    
    echo "⚠️  Found duplicate university codes:\n";
    foreach ($duplicates as $dup) {
        echo "   - Code '{$dup['code']}' appears {$dup['count']} times\n";
    }
    
    echo "\n🔧 Fixing duplicates by keeping the first entry and removing others...\n";
    
    // Fix duplicates by keeping the first entry (lowest ID) and removing others
    foreach ($duplicates as $dup) {
        $code = $dup['code'];
        
        // Get all entries with this code, ordered by ID
        $stmt = $pdo->prepare("SELECT id FROM universities WHERE code = ? ORDER BY id ASC");
        $stmt->execute([$code]);
        $entries = $stmt->fetchAll();
        
        if (count($entries) > 1) {
            // Keep the first entry (lowest ID), remove the rest
            $keepId = $entries[0]['id'];
            $removeIds = array_slice(array_column($entries, 'id'), 1);
            
            echo "   - Keeping university ID {$keepId} for code '{$code}'\n";
            echo "   - Removing university IDs: " . implode(', ', $removeIds) . "\n";
            
            // Remove the duplicate entries
            $placeholders = str_repeat('?,', count($removeIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM universities WHERE id IN ($placeholders)");
            $stmt->execute($removeIds);
        }
    }
    
    echo "\n✅ Duplicate university codes have been fixed!\n";
    echo "🔄 You can now try running the installation again.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Try running: php reset_database.php\n";
    exit(1);
}
