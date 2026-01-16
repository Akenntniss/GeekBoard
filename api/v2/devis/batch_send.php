<?php
require_once __DIR__ . '/../config.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// We can wrap the existing legacy script or reimplement.
// The legacy script `ajax/renvoyer_tous_devis.php` does the heavy lifting (SMS sending loop).
// Reimplementing gives us better control over auth.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    require_auth();
    $input = json_decode(file_get_contents('php://input'), true);
    $shop_id = $_GET['shop_id'] ?? $input['shop_id'] ?? null;
    $include_ids = $input['include_ids'] ?? []; // List of specific Devis IDs to send

    if (!$shop_id) {
        throw new Exception('Shop ID manquant');
    }

    initialize_api_shop_context($shop_id);
    $pdo = getShopDBConnection(); // Use $pdo for consistency
    $shop_pdo = $pdo; 
    
    // We need SMS functions
    require_once __DIR__ . '/../../../includes/sms_functions.php';

    // Build Query
    // Logic from legacy: SELECT ... WHERE statut='envoye' AND (expiration rules) AND telephone IS NOT NULL
    $sql = "
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
    ";

    $params = [];

    // Filter by specific IDs if provided
    if (!empty($include_ids) && is_array($include_ids)) {
        $in  = str_repeat('?,', count($include_ids) - 1) . '?';
        $sql .= " AND d.id IN ($in)";
        $params = array_values($include_ids);
    }
    
    $sql .= " ORDER BY d.date_expiration ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $devis_a_renvoyer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Checks for dry run / preview
    $action = $_GET['action'] ?? $input['action'] ?? 'send';
    
    if ($action === 'preview') {
         echo json_encode([
            'success' => true,
            'count' => count($devis_a_renvoyer),
            'devis' => $devis_a_renvoyer
        ]);
        exit();
    }

    if (empty($devis_a_renvoyer)) {
        echo json_encode([
            'success' => true,
            'message' => 'Aucun devis à renvoyer',
            'envoyes' => 0,
            'total_traites' => 0
        ]);
        exit();
    }
    
    // Get Templates
    $stmt = $pdo->prepare("
        SELECT * FROM sms_templates 
        WHERE nom IN ('Devis en attente - Rappel', 'Devis expiré - Gardiennage', 'Relance Devis') 
        AND est_actif = 1
    ");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $templates_by_name = [];
    foreach ($templates as $t) $templates_by_name[$t['nom']] = $t;

    // Get Company Params (Optional)
    $stmt_company = $pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
    $stmt_company->execute();
    $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
    $company_name = $company_params['company_name'] ?? 'Maison du Geek';
    $company_phone = $company_params['company_phone'] ?? '08 95 79 59 33';

    // =================================================================
    // OPTIMISATION ASYNCHRONE
    // On répond immédiatement au client que le traitement démarre
    // =================================================================
    
    $total_devis = count($devis_a_renvoyer);
    $response = [
        'success' => true,
        'message' => "Envoi de $total_devis devis démarré en arrière-plan.",
        'envoyes_scheduled' => $total_devis,
        'async' => true
    ];
    
    // Si la fonction n'existe pas (cas rare), on simule
    if (function_exists('flush_response_and_continue')) {
        flush_response_and_continue($response);
    } else {
        // Fallback manuel
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        echo json_encode($response);
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        flush();
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    }
    
    // À partir d'ici, le client a reçu la réponse et le script continue sur le serveur
    
    $envoyes = 0;
    $erreurs = [];

    foreach ($devis_a_renvoyer as $devis) {
        try {
            // Re-vérifier la connexion DB si nécessaire (certains environnements ferment la connexion après flush)
            // Mais PDO persiste généralement.
            
            $telephone = trim($devis['client_telephone']);
            if (empty($telephone) || strlen($telephone) < 8) continue;
            
            $now = new DateTime();
            $expiration = new DateTime($devis['date_expiration']);
            $diff = $expiration->diff($now);
            $est_expire = $expiration < $now;
            
            // Template selection logic
            if ($devis['statut_relance'] === 'expire_recent') {
                $template_name = 'Devis expiré - Gardiennage';
            } else {
                $template_name = 'Devis en attente - Rappel';
            }
            $template = $templates_by_name[$template_name] ?? $templates_by_name['Relance Devis'] ?? null;
            
            if (!$template) {
                $erreurs[] = "Template manquant pour devis #{$devis['numero_devis']}";
                continue;
            }

            // Urls
            $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
            $protocol = 'https://';
            $devis_url = $protocol . $current_host . '/pages/devis_client.php?lien=' . ($devis['lien_securise'] ?? '');
            $suivi_url = $protocol . $current_host . '/suivi.php?id=' . $devis['reparation_id'];

            // Variables
            $variables = [
                '[CLIENT_NOM]' => $devis['client_nom'],
                '[CLIENT_PRENOM]' => $devis['client_prenom'],
                '[MONTANT]' => number_format($devis['total_ttc'] ?? 0, 2, ',', ' ') . '€',
                '[URL_DEVIS]' => $devis_url,
                '[URL_SUIVI]' => $suivi_url,
                '[JOURS_RESTANTS]' => $est_expire ? 0 : $diff->days,
                '[JOURS_EXPIRES]' => $est_expire ? $diff->days : 0,
                '[PRIX_GARDIENNAGE]' => '5,00',
                '[DOMAINE]' => $current_host,
                '[COMPANY_NAME]' => $company_name,
                '[COMPANY_PHONE]' => $company_phone
            ];

            $message = $template['contenu'];
            foreach ($variables as $variable => $valeur) {
                $message = str_replace($variable, $valeur, $message);
            }

            // Send SMS
            $sms_result = send_sms(
                $telephone, 
                $message, 
                'relance_devis_auto', 
                $devis['id'], 
                $decoded_token['user_id'] ?? null
            );

            if ($sms_result && ($sms_result['success'] ?? false)) {
                $envoyes++;
                
                // Log and Update
                $stmt = $pdo->prepare("
                    INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, utilisateur_id, donnees_supplementaires, date_action)
                    VALUES (?, 'sms_renvoye', ?, 'employe', ?, ?, NOW())
                ");
                $stmt->execute([
                    $devis['id'],
                    "SMS renvoyé à $telephone",
                    $decoded_token['user_id'] ?? null,
                    json_encode(['template' => $template_name, 'message' => $message])
                ]);
                
                $pdo->prepare("UPDATE devis SET date_envoi = NOW() WHERE id = ?")->execute([$devis['id']]);
            }

        } catch (Exception $e) {
            error_log("Async Devis Error #{$devis['id']}: " . $e->getMessage());
        }
    }
    
    // Fin du script (pas d'echo ici car connexion fermée)

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
