<?php
header('Content-Type: application/json');

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/subdomain_database_detector.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sms_functions.php';

try {
    // Récupérer le shop_id depuis l'URL
    $shop_id = $_GET['shop_id'] ?? null;
    
    if ($shop_id) {
        $shop_pdo = getShopDBConnectionById($shop_id);
    } else {
        $detector = new SubdomainDatabaseDetector();
        $shopConfig = $detector->detectShopFromSubdomain();
        
        if (!$shopConfig) {
            throw new Exception('Shop non détecté');
        }
        
        $shop_pdo = $detector->getShopConnection();
    }
    
    $debug_info = [];
    
    // 1. Vérifier les devis éligibles
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            r.id as reparation_id,
            d.lien_securise as lien_acceptation,
            CASE 
                WHEN d.date_expiration > NOW() THEN 'en_attente'
                WHEN d.date_expiration <= NOW() AND d.date_expiration >= DATE_SUB(NOW(), INTERVAL 15 DAY) THEN 'expire_recent'
                ELSE 'expire_ancien'
            END as statut_relance
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE d.statut = 'envoye' 
        AND (
            d.date_expiration > NOW() 
            OR (d.date_expiration <= NOW() AND d.date_expiration >= DATE_SUB(NOW(), INTERVAL 15 DAY))
        )
        AND c.telephone IS NOT NULL 
        AND c.telephone != ''
        ORDER BY d.date_expiration ASC
    ");
    
    $stmt->execute();
    $devis_a_renvoyer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info['devis_eligibles'] = [
        'count' => count($devis_a_renvoyer),
        'devis' => array_map(function($d) {
            return [
                'id' => $d['id'],
                'numero_devis' => $d['numero_devis'],
                'client' => $d['client_nom'] . ' ' . $d['client_prenom'],
                'telephone' => $d['client_telephone'],
                'statut_relance' => $d['statut_relance'],
                'date_expiration' => $d['date_expiration']
            ];
        }, $devis_a_renvoyer)
    ];
    
    // 2. Vérifier les templates SMS
    $stmt = $shop_pdo->prepare("
        SELECT * FROM sms_templates 
        WHERE nom IN ('Devis en attente - Rappel', 'Devis expiré - Gardiennage', 'Relance Devis') 
        AND est_actif = 1
    ");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info['templates_sms'] = [
        'count' => count($templates),
        'templates' => $templates
    ];
    
    // 3. Vérifier tous les templates disponibles
    $stmt = $shop_pdo->prepare("SELECT * FROM sms_templates ORDER BY nom");
    $stmt->execute();
    $all_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info['tous_templates'] = [
        'count' => count($all_templates),
        'templates' => $all_templates
    ];
    
    // 4. Test de l'API SMS avec un numéro fictif
    if (!empty($devis_a_renvoyer)) {
        $premier_devis = $devis_a_renvoyer[0];
        $test_message = "Test SMS - Devis #{$premier_devis['numero_devis']}";
        
        // Test sans envoi réel
        $debug_info['test_sms_preparation'] = [
            'telephone' => $premier_devis['client_telephone'],
            'message' => $test_message,
            'message_length' => strlen($test_message)
        ];
        
        // Vérifier si la fonction send_sms existe
        $debug_info['sms_function_exists'] = function_exists('send_sms');
        
        // Vérifier les classes SMS
        $debug_info['sms_classes'] = [
            'NewSmsService' => class_exists('NewSmsService'),
            'SmsDeduplication' => class_exists('SmsDeduplication')
        ];
    }
    
    // 5. Vérifier la session
    $debug_info['session'] = [
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'user_id' => $_SESSION['user_id'] ?? 'non défini',
        'shop_id' => $_SESSION['shop_id'] ?? 'non défini'
    ];
    
    // 6. Vérifier la configuration de l'entreprise
    try {
        $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
        $stmt_company->execute();
        $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $debug_info['company_params'] = $company_params;
    } catch (Exception $e) {
        $debug_info['company_params_error'] = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'debug_info' => $debug_info
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
