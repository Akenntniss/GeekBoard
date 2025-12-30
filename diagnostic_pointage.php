<?php
/**
 * Script de diagnostic et réparation du système de pointage
 * Résout l'erreur "Vous avez déjà un pointage en cours"
 */

require_once 'config/database.php';

// Initialiser la session shop
session_start();
initializeShopSession();

echo "🔍 DIAGNOSTIC DU SYSTÈME DE POINTAGE\n";
echo "=====================================\n\n";

try {
    $pdo = getShopDBConnection();
    
    // Informations sur le magasin actuel
    $shop_id = $_SESSION['shop_id'] ?? 'Non défini';
    echo "🏪 Magasin actuel: shop_id = $shop_id\n";
    
    // Récupérer le nom de la base de données actuelle
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $db_info = $stmt->fetch();
    echo "💾 Base de données: " . $db_info['db_name'] . "\n\n";
    
    // Vérifier si la table time_tracking existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "❌ Table 'time_tracking' n'existe pas dans cette base.\n";
        echo "✅ Pas de problème de pointage car pas de système configuré.\n";
        exit;
    }
    
    echo "✅ Table 'time_tracking' trouvée.\n\n";
    
    // 1. DIAGNOSTIC: Rechercher les pointages orphelins (sans clock_out)
    echo "🔍 ÉTAPE 1: Recherche des pointages orphelins\n";
    echo "----------------------------------------------\n";
    
    $stmt = $pdo->prepare("
        SELECT t.*, u.nom as user_name, u.prenom as user_firstname
        FROM time_tracking t 
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.clock_out IS NULL 
        ORDER BY t.clock_in DESC
    ");
    $stmt->execute();
    $orphaned_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphaned_entries)) {
        echo "✅ Aucun pointage orphelin trouvé.\n";
        echo "🤔 Le problème pourrait venir d'ailleurs...\n\n";
    } else {
        echo "⚠️  POINTAGES ORPHELINS TROUVÉS:\n";
        foreach ($orphaned_entries as $entry) {
            $user_name = $entry['user_name'] . ' ' . $entry['user_firstname'];
            echo "   - ID: {$entry['id']} | User: {$user_name} (ID: {$entry['user_id']}) | Entrée: {$entry['clock_in']} | Status: {$entry['status']}\n";
        }
        echo "\n";
    }
    
    // 2. DIAGNOSTIC: Vérifier les pointages d'aujourd'hui
    echo "🔍 ÉTAPE 2: Pointages d'aujourd'hui\n";
    echo "-----------------------------------\n";
    
    $stmt = $pdo->prepare("
        SELECT t.*, u.nom as user_name, u.prenom as user_firstname
        FROM time_tracking t 
        LEFT JOIN users u ON t.user_id = u.id
        WHERE DATE(t.clock_in) = CURDATE()
        ORDER BY t.clock_in DESC
    ");
    $stmt->execute();
    $today_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($today_entries)) {
        echo "ℹ️  Aucun pointage aujourd'hui.\n\n";
    } else {
        echo "📊 Pointages d'aujourd'hui:\n";
        foreach ($today_entries as $entry) {
            $user_name = $entry['user_name'] . ' ' . $entry['user_firstname'];
            $clock_out = $entry['clock_out'] ?? '(EN COURS)';
            $status_icon = $entry['clock_out'] ? '✅' : '⏳';
            echo "   $status_icon ID: {$entry['id']} | User: {$user_name} | {$entry['clock_in']} → $clock_out\n";
        }
        echo "\n";
    }
    
    // 3. DIAGNOSTIC: Si l'utilisateur courant a un problème
    if (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
        echo "🔍 ÉTAPE 3: Vérification utilisateur courant (ID: $current_user_id)\n";
        echo "----------------------------------------------------------------\n";
        
        $stmt = $pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND clock_out IS NULL 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$current_user_id]);
        $user_active_entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_active_entry) {
            echo "⚠️  PROBLÈME TROUVÉ: Utilisateur $current_user_id a un pointage actif orphelin!\n";
            echo "   - ID d'entrée: {$user_active_entry['id']}\n";
            echo "   - Heure d'entrée: {$user_active_entry['clock_in']}\n";
            echo "   - Status: {$user_active_entry['status']}\n";
            echo "   - IP: {$user_active_entry['ip_address']}\n\n";
        } else {
            echo "✅ Utilisateur courant n'a pas de pointage actif orphelin.\n\n";
        }
    }
    
    // 4. PROPOSER DES SOLUTIONS
    echo "🔧 SOLUTIONS DISPONIBLES\n";
    echo "========================\n";
    
    if (!empty($orphaned_entries)) {
        echo "1. Fermer automatiquement tous les pointages orphelins (recommandé)\n";
        echo "2. Supprimer les pointages orphelins (ATTENTION: perte de données)\n";
        echo "3. Analyse manuelle détaillée\n\n";
        
        echo "Voulez-vous appliquer la solution 1 (fermer automatiquement) ? [y/N]: ";
        $input = trim(fgets(STDIN));
        
        if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
            echo "\n🔧 APPLICATION DE LA SOLUTION 1\n";
            echo "--------------------------------\n";
            
            foreach ($orphaned_entries as $entry) {
                // Calculer une heure de sortie raisonnable (8h après l'entrée)
                $clock_in_time = new DateTime($entry['clock_in']);
                $suggested_clock_out = clone $clock_in_time;
                $suggested_clock_out->add(new DateInterval('PT8H'));
                
                // Si c'est aujourd'hui et qu'on n'est pas encore à cette heure, utiliser maintenant
                $now = new DateTime();
                if ($clock_in_time->format('Y-m-d') === $now->format('Y-m-d') && $suggested_clock_out > $now) {
                    $suggested_clock_out = $now;
                }
                
                $clock_out_str = $suggested_clock_out->format('Y-m-d H:i:s');
                
                echo "   Fermeture pointage ID {$entry['id']} → clock_out = $clock_out_str\n";
                
                $stmt = $pdo->prepare("
                    UPDATE time_tracking 
                    SET clock_out = ?, status = 'completed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$clock_out_str, $entry['id']]);
            }
            
            echo "\n✅ RÉPARATION TERMINÉE!\n";
            echo "Tous les pointages orphelins ont été fermés automatiquement.\n";
            echo "Vous pouvez maintenant essayer de pointer depuis la navbar.\n\n";
        }
    } else {
        echo "✅ Aucun pointage orphelin détecté.\n";
        echo "Le problème pourrait venir de:\n";
        echo "- Un cache JavaScript côté client\n";
        echo "- Une session incorrecte\n";
        echo "- Un autre magasin si vous avez changé de sous-domaine\n\n";
        
        echo "🔄 Suggestions:\n";
        echo "1. Actualisez la page (F5)\n";
        echo "2. Videz le cache du navigateur\n";
        echo "3. Vérifiez que vous êtes sur le bon sous-domaine\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 Diagnostic terminé.\n";
?>
