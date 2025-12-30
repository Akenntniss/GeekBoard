<?php
/**
 * Script pour mettre à jour les templates SMS en remplaçant les URLs hardcodées 
 * par une variable dynamique [URL_SUIVI]
 */

// Inclure la configuration
require_once __DIR__ . '/config/database.php';

// Initialiser la session pour le système multi-magasin
session_start();
initializeShopSession();

echo "<h1>Mise à jour des templates SMS - URLs dynamiques</h1>\n";
echo "<pre>\n";

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
    
    // Récupérer la base de données actuelle
    $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Base de données active: $db_name\n\n";
    
    // Définir les patterns d'URLs à remplacer
    $url_patterns = [
        'http://Mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'http://mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'http://Mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'http://mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'https://Mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'https://mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'mdgeek.top/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]',
        'mdgeek.fr/suivi.php?id=[REPARATION_ID]' => '[URL_SUIVI]'
    ];
    
    // Récupérer tous les templates SMS
    $stmt = $shop_pdo->query("SELECT id, nom, contenu FROM sms_templates WHERE est_actif = 1");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Nombre de templates trouvés: " . count($templates) . "\n\n";
    
    $updated_count = 0;
    
    foreach ($templates as $template) {
        $original_content = $template['contenu'];
        $updated_content = $original_content;
        $has_changes = false;
        
        // Appliquer tous les remplacements d'URLs
        foreach ($url_patterns as $old_url => $new_variable) {
            if (strpos($updated_content, $old_url) !== false) {
                $updated_content = str_replace($old_url, $new_variable, $updated_content);
                $has_changes = true;
                echo "Template '{$template['nom']}' (ID: {$template['id']}): Remplacé '$old_url' par '$new_variable'\n";
            }
        }
        
        // Mettre à jour en base si des changements ont été faits
        if ($has_changes) {
            $update_stmt = $shop_pdo->prepare("UPDATE sms_templates SET contenu = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$updated_content, $template['id']]);
            
            $updated_count++;
            echo "✓ Template '{$template['nom']}' mis à jour avec succès\n";
            echo "  Ancien contenu: " . substr($original_content, 0, 100) . "...\n";
            echo "  Nouveau contenu: " . substr($updated_content, 0, 100) . "...\n\n";
        } else {
            echo "- Template '{$template['nom']}' (ID: {$template['id']}): Aucune URL hardcodée trouvée\n";
        }
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Templates analysés: " . count($templates) . "\n";
    echo "Templates mis à jour: $updated_count\n";
    
    if ($updated_count > 0) {
        echo "\n✅ Mise à jour terminée avec succès!\n";
        echo "Les templates SMS utilisent maintenant la variable [URL_SUIVI] qui sera remplacée dynamiquement par l'URL correcte selon le domaine/sous-domaine.\n";
    } else {
        echo "\nℹ️ Aucun template n'avait besoin de mise à jour.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='javascript:history.back()'>← Retour</a></p>\n";
?>
