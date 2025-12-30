<?php
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// Force usage of samitest DB or try to detect
// We will just try to use getShopDBConnection(). 
// If it fails because of CLI, we might need to simulate environment or just use raw PDO with hardcoded credentials if we knew them for sure.
// But wait, getShopDBConnection relies on subdomain. In CLI, we might not have it.

// Let's rely on the strategy used in migrate_direct.php: connect to main DB, get shop, connect to shop.
// We'll assume the user is 'samitest' or we'll just check ALL shops for any signed message.

define('MAIN_DB_HOST', 'localhost');
define('MAIN_DB_USER', 'root');
define('MAIN_DB_PASS', 'Mamanmaman01#');
define('MAIN_DB_NAME', 'geekboard_general');

try {
    $mainPdo = new PDO("mysql:host=".MAIN_DB_HOST.";dbname=".MAIN_DB_NAME, MAIN_DB_USER, MAIN_DB_PASS);
    
    // Get ALL active shops
    $stmt = $mainPdo->query("SELECT * FROM shops WHERE active=1");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($shops) . " shops.\n";
    
    foreach ($shops as $shop) {
        echo "------------------------------------------------\n";
        echo "Checking Shop: " . $shop['name'] . " (DB: " . $shop['db_name'] . ")\n";
        
        try {
            $shopPdo = new PDO(
                "mysql:host=" . $shop['db_host'] . ";dbname=" . $shop['db_name'],
                $shop['db_user'],
                $shop['db_pass']
            );
            
            // Check last 3 messages
            $stmt = $shopPdo->query("SELECT * FROM messages ORDER BY id DESC LIMIT 5");
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($messages)) {
                    echo "  No messages found.\n";
                } else {
                    foreach ($messages as $msg) {
                        $content = substr($msg['contenu'], 0, 20);
                        
                        // Count signatures for this message
                        $sigCountStmt = $shopPdo->prepare("SELECT COUNT(*) FROM message_signatures WHERE message_id = ?");
                        $sigCountStmt->execute([$msg['id']]);
                        $sigCount = $sigCountStmt->fetchColumn();
                        
                        $signersStmt = $shopPdo->prepare("SELECT user_id, signed_at FROM message_signatures WHERE message_id = ?");
                        $signersStmt->execute([$msg['id']]);
                        $signersData = $signersStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $signersStr = "";
                        foreach ($signersData as $sd) {
                            $uId = $sd['user_id'];
                            // Get name
                            $uStmt = $shopPdo->prepare("SELECT username, full_name, role FROM users WHERE id = ?");
                            $uStmt->execute([$uId]);
                            $uData = $uStmt->fetch(PDO::FETCH_ASSOC);
                            $uName = $uData['full_name'] ?: $uData['username'];
                            $signersStr .= "[ID:$uId $uName ({$uData['role']}) @ {$sd['signed_at']}] ";
                        }

                        echo "  [MSG #{$msg['id']}] {$msg['date_envoi']} | ReqSig: {$msg['requires_signature']} | ActualSigs: {$sigCount} | Signers: {$signersStr} | Content: {$content}\n";
                    }
                }
                
                $count = $shopPdo->query("SELECT COUNT(*) FROM message_signatures")->fetchColumn();
                echo "  Total signatures in table: $count\n";


        } catch (Exception $e) {
            echo "  Connection Failed: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
