<?php
/**
 * Script to update existing deals that don't have deal_code
 * Run this once to fix existing data
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

try {
    // Get all deals without deal_code
    $stmt = $db->query("SELECT id FROM deals WHERE deal_code IS NULL OR deal_code = '' ORDER BY id");
    $deals = $stmt->fetchAll();
    
    echo "Found " . count($deals) . " deals without deal_code\n";
    
    // Get current max number
    $maxStmt = $db->query("SELECT MAX(CAST(SUBSTRING(deal_code FROM 5) AS INTEGER)) as max_num 
                           FROM deals 
                           WHERE deal_code IS NOT NULL AND deal_code LIKE 'DEAL%'");
    $maxResult = $maxStmt->fetch();
    $nextNum = ($maxResult['max_num'] ?? 0) + 1;
    
    // Update each deal
    foreach ($deals as $deal) {
        $dealCode = 'DEAL' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        
        $updateStmt = $db->prepare("UPDATE deals SET deal_code = :code WHERE id = :id");
        $updateStmt->execute([
            'code' => $dealCode,
            'id' => $deal['id']
        ]);
        
        echo "Updated deal ID {$deal['id']} with code: $dealCode\n";
        $nextNum++;
    }
    
    echo "\nDone! Updated " . count($deals) . " deals.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

