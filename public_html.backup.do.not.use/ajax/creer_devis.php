<?php
/**
 * ================================================================================
 * CRÉATION DE DEVIS - ENDPOINT AJAX
 * ================================================================================
 * Description: Traite la création de devis avec pannes, solutions et envoi SMS
 * Date: 2025-01-27
 * ================================================================================
 */

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session
session_start();

// Vérifier l'authentification avec shop_id (système GeekBoard)
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentification requise - Session shop_id manquante'
    ]);
    exit;
}

try {
    // Inclure les dépendances
    require_once '../config/config.php';
    require_once '../includes/functions.php';
    require_once '../includes/database.php';

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée');
    }

    // Récupérer et décoder les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Logger les données reçues pour debug
    error_log("=== CRÉATION DEVIS ===");
    error_log("Données reçues: " . print_r($data, true));

    // Validation des données obligatoires
    $required_fields = ['reparation_id', 'titre', 'pannes', 'solutions', 'action'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Le champ '$field' est obligatoire");
        }
    }

    // Validation des pannes
    if (empty($data['pannes']) || !is_array($data['pannes'])) {
        throw new Exception('Au moins une panne doit être identifiée');
    }

    // Validation des solutions
    if (empty($data['solutions']) || !is_array($data['solutions'])) {
        throw new Exception('Au moins une solution doit être proposée');
    }

    // Récupérer la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Commencer la transaction
    $shop_pdo->beginTransaction();

    try {
        // 1. Vérifier que la réparation existe et récupérer les infos client
        $stmt = $shop_pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, 
                   c.telephone as client_telephone, c.email as client_email
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$data['reparation_id']]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reparation) {
            throw new Exception('Réparation non trouvée');
        }

        // 2. Créer le devis principal
        $stmt = $shop_pdo->prepare("
            INSERT INTO devis (
                reparation_id, client_id, employe_id, titre, description_generale,
                date_expiration, taux_tva, statut
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $date_expiration = !empty($data['date_expiration']) 
            ? $data['date_expiration'] 
            : date('Y-m-d', strtotime('+15 days'));
        
        $taux_tva = isset($data['taux_tva']) ? floatval($data['taux_tva']) : 20.00;
        $statut = ($data['action'] === 'envoyer') ? 'envoye' : 'brouillon';

        $stmt->execute([
            $data['reparation_id'],
            $reparation['client_id'],
            $_SESSION['shop_id'] ?? 1, // Utiliser shop_id comme user_id
            $data['titre'],
            $data['description'] ?? '',
            $date_expiration,
            $taux_tva,
            $statut
        ]);

        $devis_id = $shop_pdo->lastInsertId();

        // Récupérer le numéro de devis généré automatiquement
        $stmt = $shop_pdo->prepare("SELECT numero_devis, lien_securise FROM devis WHERE id = ?");
        $stmt->execute([$devis_id]);
        $devis_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$devis_info) {
            throw new Exception('Erreur lors de la récupération du numéro de devis');
        }

        // 3. Insérer les pannes identifiées
        $stmt = $shop_pdo->prepare("
            INSERT INTO devis_pannes (devis_id, titre, description, gravite, ordre)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data['pannes'] as $index => $panne) {
            if (empty($panne['titre'])) continue;
            
            $stmt->execute([
                $devis_id,
                $panne['titre'],
                $panne['description'] ?? '',
                $panne['gravite'] ?? 'moyenne',
                $panne['ordre'] ?? ($index + 1)
            ]);
        }

        // 4. Insérer les solutions proposées
        $stmt_solution = $shop_pdo->prepare("
            INSERT INTO devis_solutions (
                devis_id, nom, description, prix_total, duree_reparation, 
                garantie, recommandee, ordre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt_element = $shop_pdo->prepare("
            INSERT INTO devis_solutions_items (
                solution_id, nom, description, quantite, prix_unitaire, 
                prix_total, type, ordre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $total_devis_ht = 0;

        foreach ($data['solutions'] as $index => $solution) {
            if (empty($solution['nom']) || $solution['prix'] <= 0) continue;

            // Insérer la solution
            $stmt_solution->execute([
                $devis_id,
                $solution['nom'],
                $solution['description'] ?? '',
                floatval($solution['prix']),
                $solution['duree'] ?? '',
                $solution['garantie'] ?? '',
                isset($solution['recommandee']) ? 1 : 0,
                $solution['ordre'] ?? ($index + 1)
            ]);

            $solution_id = $shop_pdo->lastInsertId();
            $total_devis_ht += floatval($solution['prix']);

            // Insérer les éléments de la solution si ils existent
            if (!empty($solution['elements']) && is_array($solution['elements'])) {
                foreach ($solution['elements'] as $elem_index => $element) {
                    if (empty($element['nom']) || $element['prix_unitaire'] <= 0) continue;

                    $stmt_element->execute([
                        $solution_id,
                        $element['nom'],
                        '', // description vide pour les éléments
                        intval($element['quantite']),
                        floatval($element['prix_unitaire']),
                        floatval($element['prix_total']),
                        $element['type'] ?? 'piece',
                        $elem_index + 1
                    ]);
                }
            }
        }

        // 5. Mettre à jour les totaux du devis
        $total_tva = $total_devis_ht * ($taux_tva / 100);
        $total_ttc = $total_devis_ht + $total_tva;

        $stmt = $shop_pdo->prepare("
            UPDATE devis 
            SET total_ht = ?, total_ttc = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_devis_ht, $total_ttc, $devis_id]);

        // 6. Logger la création
        $stmt = $shop_pdo->prepare("
            INSERT INTO devis_logs (
                devis_id, action, description, utilisateur_type, utilisateur_id,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $devis_id,
            'CREATION',
            "Devis créé par " . ($_SESSION['username'] ?? 'Shop ' . ($_SESSION['shop_id'] ?? 'inconnu')),
            'employe',
            $_SESSION['shop_id'] ?? 1, // Utiliser shop_id
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        // 7. Si on doit envoyer le devis, préparer l'envoi SMS
        $sms_sent = false;
        $sms_message = '';

        if ($data['action'] === 'envoyer' && !empty($reparation['client_telephone'])) {
            
            // Mettre à jour la date d'envoi
            $stmt = $shop_pdo->prepare("UPDATE devis SET date_envoi = NOW() WHERE id = ?");
            $stmt->execute([$devis_id]);

            // Préparer le lien sécurisé
            $lien_devis = "https://{$_SERVER['HTTP_HOST']}/devis/{$devis_info['lien_securise']}";

            // Récupérer le template SMS
            $stmt = $shop_pdo->prepare("
                SELECT * FROM devis_templates 
                WHERE nom = 'SMS Envoi Devis' AND type = 'sms' AND actif = 1 
                LIMIT 1
            ");
            $stmt->execute();
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($template) {
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
                
                // Remplacer les variables dans le template
                $message = $template['contenu'];
                $variables = [
                    '{CLIENT_PRENOM}' => $reparation['client_prenom'],
                    '{CLIENT_NOM}' => $reparation['client_nom'],
                    '{APPAREIL_TYPE}' => $reparation['type_appareil'],
                    '{APPAREIL_MODELE}' => $reparation['modele'],
                    '{LIEN_DEVIS}' => $lien_devis,
                    '{NOM_MAGASIN}' => $company_name, // Utilise le nom configuré
                    '{NUMERO_DEVIS}' => $devis_info['numero_devis'],
                    // Support du nouveau format avec crochets
                    '[CLIENT_PRENOM]' => $reparation['client_prenom'],
                    '[CLIENT_NOM]' => $reparation['client_nom'],
                    '[APPAREIL_TYPE]' => $reparation['type_appareil'],
                    '[APPAREIL_MODELE]' => $reparation['modele'],
                    '[URL_DEVIS]' => $lien_devis,
                    '[COMPANY_NAME]' => $company_name,
                    '[COMPANY_PHONE]' => $company_phone,
                    '[NUMERO_DEVIS]' => $devis_info['numero_devis']
                ];

                foreach ($variables as $var => $value) {
                    $message = str_replace($var, $value, $message);
                }

                // Insérer la notification dans la queue
                $stmt = $shop_pdo->prepare("
                    INSERT INTO devis_notifications (
                        devis_id, type, telephone, message, date_programmee
                    ) VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $devis_id,
                    'envoi_devis',
                    $reparation['client_telephone'],
                    $message
                ]);

                // Inclure les fonctions SMS si elles ne sont pas disponibles
                if (!function_exists('send_sms')) {
                    require_once '../includes/sms_functions.php';
                }

                // Essayer d'envoyer immédiatement le SMS avec enregistrement en base
                if (function_exists('send_sms')) {
                    $sms_result = send_sms(
                        $reparation['client_telephone'], 
                        $message, 
                        'envoi_devis',  // Type de référence pour l'enregistrement
                        $devis_id,      // ID de référence (devis_id)
                        $_SESSION['shop_id'] ?? null  // ID utilisateur (shop_id)
                    );
                    
                    if ($sms_result['success'] ?? false) {
                        $sms_sent = true;
                        $sms_message = 'SMS envoyé avec succès et enregistré en base de données';
                        
                        // Mettre à jour le statut de la notification
                        $stmt = $shop_pdo->prepare("
                            UPDATE devis_notifications 
                            SET statut_envoi = 'envoye', date_envoi = NOW()
                            WHERE devis_id = ? AND type = 'envoi_devis'
                        ");
                        $stmt->execute([$devis_id]);
                        
                    } else {
                        $sms_message = 'Erreur lors de l\'envoi du SMS: ' . ($sms_result['message'] ?? 'Erreur inconnue');
                        
                        // Mettre à jour le statut d'erreur
                        $stmt = $shop_pdo->prepare("
                            UPDATE devis_notifications 
                            SET statut_envoi = 'echec', erreur = ?
                            WHERE devis_id = ? AND type = 'envoi_devis'
                        ");
                        $stmt->execute([$sms_result['message'] ?? 'Erreur inconnue', $devis_id]);
                    }
                } else {
                    $sms_message = 'Fonction SMS non disponible, notification mise en queue';
                }

                // Logger l'envoi
                $stmt = $shop_pdo->prepare("
                    INSERT INTO devis_logs (
                        devis_id, action, description, utilisateur_type, utilisateur_id
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $devis_id,
                    'ENVOI_SMS',
                    $sms_sent ? 'SMS envoyé avec succès' : $sms_message,
                    'employe',
                    $_SESSION['shop_id'] ?? 1 // Utiliser shop_id
                ]);
            }

            // 8. Mettre à jour le statut de la réparation
            $stmt = $shop_pdo->prepare("
                UPDATE reparations 
                SET devis_envoye = 'OUI', 
                    date_envoi_devis = NOW(),
                    statut = 'en_attente_accord_client',
                    statut_id = 6
                WHERE id = ?
            ");
            $stmt->execute([$data['reparation_id']]);
        }

        // Valider la transaction
        $shop_pdo->commit();

        // Réponse de succès
        $response = [
            'success' => true,
            'message' => $data['action'] === 'envoyer' 
                ? 'Devis créé et envoyé avec succès' 
                : 'Devis sauvegardé en brouillon',
            'devis_id' => $devis_id,
            'numero_devis' => $devis_info['numero_devis'],
            'lien_securise' => $devis_info['lien_securise'],
            'total_ht' => $total_devis_ht,
            'total_ttc' => $total_ttc,
            'sms_sent' => $sms_sent,
            'sms_message' => $sms_message
        ];

        error_log("Devis créé avec succès: " . $devis_info['numero_devis']);
        echo json_encode($response);

    } catch (Exception $e) {
        // Annuler la transaction
        $shop_pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log de l'erreur
    error_log("ERREUR CRÉATION DEVIS: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?> 