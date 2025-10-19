<?php
header('Content-Type: application/json');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/subdomain_database_detector.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sms_functions.php';

try {
    // Récupérer le shop_id depuis l'URL ou utiliser le SubdomainDatabaseDetector
    $shop_id = $_GET['shop_id'] ?? null;
    
    if ($shop_id) {
        // Utiliser la méthode standard avec shop_id
        $shop_pdo = getShopDBConnectionById($shop_id);
    } else {
        // Utiliser le SubdomainDatabaseDetector comme fallback
        $detector = new SubdomainDatabaseDetector();
        $shopConfig = $detector->detectShopFromSubdomain();
        
        if (!$shopConfig) {
            throw new Exception('Shop non détecté');
        }
        
        $shop_pdo = $detector->getShopConnection();
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['devis_ids']) || !is_array($input['devis_ids'])) {
        throw new Exception('IDs des devis manquants ou invalides');
    }
    
    $devis_ids = $input['devis_ids'];
    
    if (empty($devis_ids)) {
        throw new Exception('Aucun devis spécifié');
    }
    
    // Préparer la requête pour récupérer les devis
    $placeholders = str_repeat('?,', count($devis_ids) - 1) . '?';
    
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            r.id as reparation_id,
            d.lien_securise as lien_acceptation
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE d.id IN ($placeholders)
        AND c.telephone IS NOT NULL 
        AND c.telephone != ''
    ");
    
    $stmt->execute($devis_ids);
    $devis_a_renvoyer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($devis_a_renvoyer)) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun devis trouvé ou client sans numéro de téléphone'
        ]);
        exit();
    }
    
    // Récupérer les templates SMS par nom
    $stmt = $shop_pdo->prepare("
        SELECT * FROM sms_templates 
        WHERE nom IN ('Devis en attente - Rappel', 'Devis expiré - Gardiennage', 'Relance Devis') 
        AND est_actif = 1
    ");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organiser les templates par nom
    $templates_by_name = [];
    foreach ($templates as $template) {
        $templates_by_name[$template['nom']] = $template;
    }
    
    $envoyes = 0;
    $erreurs = [];
    
    foreach ($devis_a_renvoyer as $devis) {
        try {
            $now = new DateTime();
            $expiration = new DateTime($devis['date_expiration']);
            $diff = $expiration->diff($now);
            
            $est_expire = $expiration < $now;
            $jours_restants = $est_expire ? -$diff->days : $diff->days;
            
            // Choisir le template approprié
            if ($est_expire) {
                $template_name = 'Devis expiré - Gardiennage'; // Pour les devis expirés
            } else {
                $template_name = 'Devis en attente - Rappel'; // Pour les devis en attente
            }
            
            $template = $templates_by_name[$template_name] ?? $templates_by_name['Relance Devis'] ?? null;
            
            if (!$template) {
                $erreurs[] = "Template SMS non trouvé pour le devis #{$devis['numero_devis']}";
                continue;
            }
            
            // Générer les URLs dynamiques
            $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'https://';
            $devis_url = $protocol . $current_host . '/pages/devis_client.php?lien=' . ($devis['lien_securise'] ?? $devis['lien_acceptation'] ?? '');
            $suivi_url = $protocol . $current_host . '/suivi.php?id=' . $devis['reparation_id'];
            
            // Récupérer les paramètres d'entreprise
            $company_name = 'Maison du Geek';  // Valeur par défaut
            $company_phone = '08 95 79 59 33';  // Valeur par défaut
            
            try {
                $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
                $stmt_company->execute();
                $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
                
                if (!empty($company_params['company_name'])) {
                    $company_name = $company_params['company_name'];
                }
                if (!empty($company_params['company_phone'])) {
                    $company_phone = $company_params['company_phone'];
                }
            } catch (Exception $e) {
                error_log("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
            }
            
            // Préparer les variables pour le template (nouveau système avec crochets)
            $variables = [
                '[CLIENT_NOM]' => $devis['client_nom'],
                '[CLIENT_PRENOM]' => $devis['client_prenom'],
                '[MONTANT]' => number_format($devis['total_ttc'] ?? 0, 2, ',', ' ') . '€',
                '[URL_DEVIS]' => $devis_url,
                '[URL_SUIVI]' => $suivi_url,
                '[JOURS_RESTANTS]' => abs($jours_restants),
                '[JOURS_EXPIRES]' => abs($jours_restants),
                '[PRIX_GARDIENNAGE]' => '5,00',
                '[DOMAINE]' => $current_host,
                '[COMPANY_NAME]' => $company_name,
                '[COMPANY_PHONE]' => $company_phone
            ];
            
            // Remplacer les variables dans le template
            $message = $template['contenu'];
            foreach ($variables as $variable => $valeur) {
                $message = str_replace($variable, $valeur, $message);
            }
            
            // Envoyer le SMS avec enregistrement en base de données
            $sms_result = send_sms(
                $devis['client_telephone'], 
                $message, 
                'relance_devis',  // Type de référence pour l'enregistrement
                $devis['id'],     // ID de référence (devis_id)
                $_SESSION['user_id'] ?? null  // ID utilisateur
            );
            
            if ($sms_result && ($sms_result['success'] ?? false)) {
                $envoyes++;
                
                // Logger l'envoi
                $stmt = $shop_pdo->prepare("
                    INSERT INTO devis_logs (devis_id, action, details, date_action)
                    VALUES (?, 'sms_renvoye', ?, NOW())
                ");
                $stmt->execute([
                    $devis['id'],
                    json_encode([
                        'template_utilise' => $template_name,
                        'message' => $message,
                        'telephone' => $devis['client_telephone']
                    ])
                ]);
                
                // Mettre à jour la date d'envoi
                $stmt = $shop_pdo->prepare("
                    UPDATE devis 
                    SET date_envoi = NOW(), tentatives = tentatives + 1
                    WHERE id = ?
                ");
                $stmt->execute([$devis['id']]);
                
            } else {
                $erreurs[] = "Échec d'envoi SMS pour le devis #{$devis['numero_devis']} (client: {$devis['client_nom']})";
            }
            
        } catch (Exception $e) {
            $erreurs[] = "Erreur pour le devis #{$devis['numero_devis']}: " . $e->getMessage();
        }
    }
    
    if ($envoyes > 0) {
        echo json_encode([
            'success' => true,
            'envoyes' => $envoyes,
            'total_devis' => count($devis_a_renvoyer),
            'erreurs' => $erreurs,
            'message' => count($devis_a_renvoyer) === 1 ? 
                'Devis renvoyé avec succès' : 
                "$envoyes devis renvoyés sur " . count($devis_a_renvoyer) . " devis sélectionnés"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun devis n\'a pu être renvoyé',
            'erreurs' => $erreurs
        ]);
    }

} catch (Exception $e) {
    error_log("Erreur renvoyer_devis.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du renvoi : ' . $e->getMessage()
    ]);
}
?> 