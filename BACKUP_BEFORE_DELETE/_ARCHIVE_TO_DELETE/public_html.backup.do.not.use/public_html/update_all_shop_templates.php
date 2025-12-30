<?php
/**
 * Script pour mettre à jour les templates SMS dans toutes les bases de données de magasins
 * Remplace les URLs hardcodées par la variable [URL_SUIVI]
 */

// Configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Mise à jour des templates SMS - Toutes les bases magasins</h1>\n";
echo "<pre>\n";

try {
    // Connexion à la base principale pour récupérer la liste des magasins
    $main_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=geekboard_general;charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Récupérer tous les magasins
    $stmt = $main_pdo->query("SELECT id, subdomain, database_name FROM shops WHERE status = 'active'");
    $shops = $stmt->fetchAll();
    
    echo "Magasins trouvés: " . count($shops) . "\n\n";
    
    $total_updated = 0;
    
    foreach ($shops as $shop) {
        echo "=== Magasin: {$shop['subdomain']} (Base: {$shop['database_name']}) ===\n";
        
        try {
            // Connexion à la base du magasin
            $shop_pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname={$shop['database_name']};charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Vérifier si la table existe
            $check = $shop_pdo->query("SHOW TABLES LIKE 'sms_templates'");
            if ($check->rowCount() === 0) {
                echo "  ⚠️ Table sms_templates non trouvée - ignoré\n\n";
                continue;
            }
            
            // Mettre à jour le template "Nouvelle Intervention" spécifiquement
            $stmt = $shop_pdo->prepare("
                UPDATE sms_templates 
                SET contenu = '👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 [URL_SUIVI]\r\n💶 [PRIX]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️',
                    updated_at = NOW()
                WHERE nom = 'Nouvelle Intervention'
            ");
            $stmt->execute();
            $updated_nouvelle = $stmt->rowCount();
            
            // Mise à jour générale de toutes les URLs hardcodées
            $patterns = [
                'http://Mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'http://mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'http://Mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'http://mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'https://Mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'https://mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
                'mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]'
            ];
            
            $updated_others = 0;
            foreach ($patterns as $old_url => $new_variable) {
                $stmt = $shop_pdo->prepare("
                    UPDATE sms_templates 
                    SET contenu = REPLACE(contenu, ?, ?),
                        updated_at = NOW()
                    WHERE contenu LIKE ?
                ");
                $stmt->execute([$old_url, $new_variable, "%$old_url%"]);
                $updated_others += $stmt->rowCount();
            }
            
            // Compter les templates avec [URL_SUIVI]
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM sms_templates WHERE contenu LIKE '%[URL_SUIVI]%'");
            $count_with_url_suivi = $stmt->fetchColumn();
            
            echo "  ✅ Template 'Nouvelle Intervention': $updated_nouvelle mis à jour\n";
            echo "  ✅ Autres templates: $updated_others mis à jour\n";
            echo "  📊 Total templates avec [URL_SUIVI]: $count_with_url_suivi\n\n";
            
            $total_updated += $updated_nouvelle + $updated_others;
            
        } catch (PDOException $e) {
            echo "  ❌ Erreur pour {$shop['subdomain']}: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "=== RÉSUMÉ GLOBAL ===\n";
    echo "Magasins traités: " . count($shops) . "\n";
    echo "Templates mis à jour au total: $total_updated\n";
    echo "\n✅ Mise à jour terminée!\n";
    echo "Tous les templates utilisent maintenant [URL_SUIVI] qui sera remplacé dynamiquement.\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR GLOBALE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='javascript:history.back()'>← Retour</a></p>\n";
?>
