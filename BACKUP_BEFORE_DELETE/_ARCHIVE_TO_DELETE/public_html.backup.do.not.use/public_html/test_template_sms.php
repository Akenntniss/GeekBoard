<?php
// Test pour vérifier que le template SMS "Nouvelle Intervention" est bien récupéré

require_once __DIR__ . '/config/database.php';

// Initialiser la session pour le système multi-magasin
session_start();
initializeShopSession();

echo "<h1>Test Template SMS 'Nouvelle Intervention'</h1>\n";
echo "<pre>\n";

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
    
    // Récupérer la base de données actuelle
    $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Base de données active: $db_name\n";
    echo "Shop ID en session: " . ($_SESSION['shop_id'] ?? 'Non défini') . "\n";
    echo "Host actuel: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n\n";
    
    // Test de récupération du template
    $template_query = $shop_pdo->prepare("
        SELECT id, nom, contenu, est_actif FROM sms_templates 
        WHERE nom = 'Nouvelle Intervention' AND est_actif = 1 
        LIMIT 1
    ");
    $template_query->execute();
    $template = $template_query->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        echo "✅ Template 'Nouvelle Intervention' trouvé!\n";
        echo "ID: " . $template['id'] . "\n";
        echo "Nom: " . $template['nom'] . "\n";
        echo "Actif: " . ($template['est_actif'] ? 'OUI' : 'NON') . "\n\n";
        echo "Contenu du template:\n";
        echo "---\n";
        echo $template['contenu'] . "\n";
        echo "---\n\n";
        
        // Test de remplacement des variables
        echo "Test de remplacement des variables:\n";
        $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
        $protocol = 'https://';
        $suivi_url = $protocol . $current_host . '/suivi.php?id=999';
        
        $variables = [
            '[CLIENT_PRENOM]' => 'Jean',
            '[CLIENT_NOM]' => 'Dupont',
            '[APPAREIL_MODELE]' => 'Galaxy S21',
            '[APPAREIL_TYPE]' => 'Smartphone',
            '[REPARATION_ID]' => '999',
            '[PRIX]' => '89,90 €',
            '[DATE]' => date('d/m/Y'),
            '[URL_SUIVI]' => $suivi_url,
            '[DOMAINE]' => $current_host
        ];
        
        $message = $template['contenu'];
        foreach ($variables as $variable => $valeur) {
            $message = str_replace($variable, $valeur, $message);
        }
        
        echo "Message après remplacement:\n";
        echo "---\n";
        echo $message . "\n";
        echo "---\n\n";
        
        if (strpos($message, '[URL_SUIVI]') !== false) {
            echo "❌ PROBLÈME: La variable [URL_SUIVI] n'a pas été remplacée!\n";
        } else {
            echo "✅ Toutes les variables ont été remplacées correctement!\n";
        }
        
    } else {
        echo "❌ Template 'Nouvelle Intervention' NON TROUVÉ!\n";
        
        // Lister tous les templates disponibles
        echo "\nTemplates disponibles:\n";
        $all_templates = $shop_pdo->query("SELECT id, nom, est_actif FROM sms_templates ORDER BY nom");
        while ($tmpl = $all_templates->fetch(PDO::FETCH_ASSOC)) {
            echo "- ID {$tmpl['id']}: {$tmpl['nom']} (Actif: " . ($tmpl['est_actif'] ? 'OUI' : 'NON') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='javascript:history.back()'>← Retour</a></p>\n";
?>
