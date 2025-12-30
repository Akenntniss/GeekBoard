<?php
/**
 * ================================================================================
 * ENDPOINT MODERNE DE CRÉATION DE DEVIS
 * ================================================================================
 * Description: API pour créer, sauvegarder et envoyer des devis modernes
 * Date: 2025-01-27
 * ================================================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration et includes
require_once __DIR__ . '/../config/subdomain_database_detector.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sms_functions.php';

// Gestion des CORS pour les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupération des données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit();
}

// Initialisation de la base de données
try {
    $detector = new SubdomainDatabaseDetector();
    $pdo = $detector->getConnection();
} catch (Exception $e) {
    error_log("Erreur de connexion DB: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Validation des données requises
$required_fields = ['reparation_id', 'pannes', 'solutions', 'action'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Champ manquant: $field"]);
        exit();
    }
}

$reparation_id = (int)$data['reparation_id'];
// Générer un titre automatique basé sur les pannes
$titre = "Devis pour réparation #" . $reparation_id;
if (!empty($data['pannes']) && is_array($data['pannes'])) {
    $premierePanne = $data['pannes'][0]['titre'] ?? '';
    if ($premierePanne) {
        $titre = "Devis pour " . $premierePanne . " - Réparation #" . $reparation_id;
    }
}
$description = trim($data['message_client'] ?? '');
$date_expiration = $data['date_expiration'] ?? date('Y-m-d', strtotime('+15 days'));
$pannes = $data['pannes'];
$solutions = $data['solutions'];
$action = $data['action']; // 'brouillon' ou 'envoyer'

// Validation des pannes et solutions
if (!is_array($pannes) || !is_array($solutions)) {
    echo json_encode(['success' => false, 'message' => 'Format des pannes ou solutions invalide']);
    exit();
}

if ($action === 'envoyer' && (empty($pannes) || empty($solutions))) {
    echo json_encode(['success' => false, 'message' => 'Au moins une panne et une solution sont requises pour envoyer le devis']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Récupération des informations de la réparation et du client
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom, c.prenom, c.telephone, c.email 
        FROM reparations r 
        LEFT JOIN clients c ON r.client_id = c.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception("Réparation non trouvée");
    }
    
    // Génération du numéro de devis
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(numero_devis, 8) AS UNSIGNED)), 0) + 1 as next_number
        FROM devis 
        WHERE numero_devis LIKE ?
    ");
    $year = date('Y');
    $stmt->execute(["DV-$year-%"]);
    $next_number = $stmt->fetchColumn();
    $numero_devis = sprintf("DV-%s-%04d", $year, $next_number);
    
    // Génération du lien sécurisé
    $lien_securise = md5($reparation_id . '-' . $reparation['client_id'] . '-' . time() . '-' . rand());
    
    // Calcul du total à partir des éléments
    $total_ht = 0;
    foreach ($solutions as $solution) {
        if (isset($solution['elements']) && is_array($solution['elements'])) {
            foreach ($solution['elements'] as $element) {
                $total_ht += floatval($element['prix'] ?? 0);
            }
        }
    }
    
    $taux_tva = 20.00;
    $total_ttc = $total_ht * (1 + $taux_tva / 100);
    
    // Insertion du devis principal
    $stmt = $pdo->prepare("
        INSERT INTO devis (
            reparation_id, client_id, employe_id, numero_devis, titre, description_generale,
            statut, date_expiration, lien_securise, total_ht, taux_tva, total_ttc
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $employe_id = $_SESSION['shop_id'] ?? 1; // Utiliser shop_id au lieu de user_id
    $statut = ($action === 'envoyer') ? 'envoye' : 'brouillon';
    
    $stmt->execute([
        $reparation_id,
        $reparation['client_id'],
        $employe_id,
        $numero_devis,
        $titre,
        $description,
        $statut,
        $date_expiration,
        $lien_securise,
        $total_ht,
        $taux_tva,
        $total_ttc
    ]);
    
    $devis_id = $pdo->lastInsertId();
    
    // Insertion des pannes
    if (!empty($pannes)) {
        $stmt = $pdo->prepare("
            INSERT INTO devis_pannes (devis_id, titre, description, gravite, ordre)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($pannes as $panne) {
            $stmt->execute([
                $devis_id,
                $panne['titre'] ?? $panne['nom'] ?? 'Panne non spécifiée',
                $panne['description'] ?? '',
                $panne['gravite'] ?? 'moyenne',
                $panne['ordre'] ?? 1
            ]);
        }
    }
    
    // Insertion des solutions et de leurs éléments
    if (!empty($solutions)) {
        $stmt_solution = $pdo->prepare("
            INSERT INTO devis_solutions (devis_id, nom, description, prix_total, duree_reparation, garantie, ordre)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_element = $pdo->prepare("
            INSERT INTO devis_solutions_items (solution_id, nom, description, quantite, prix_unitaire, prix_total, type, ordre)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($solutions as $solution) {
            // Calculer le total de cette solution
            $prix_total_solution = 0;
            if (isset($solution['elements']) && is_array($solution['elements'])) {
                foreach ($solution['elements'] as $element) {
                    $prix_total_solution += floatval($element['prix'] ?? 0);
                }
            }
            
            $stmt_solution->execute([
                $devis_id,
                $solution['titre'] ?? $solution['nom'] ?? 'Solution non spécifiée',
                $solution['description'] ?? '',
                $prix_total_solution,
                $solution['duree'] ?? null,
                $solution['garantie'] ?? null,
                $solution['ordre'] ?? 1
            ]);
            
            $solution_id = $pdo->lastInsertId();
            
            if (!empty($solution['elements'])) {
                foreach ($solution['elements'] as $element) {
                    $prix_unitaire = floatval($element['prix']);
                    $quantite = 1;
                    $prix_total = $prix_unitaire * $quantite;
                    
                    $stmt_element->execute([
                        $solution_id,
                        $element['nom'] ?? $element['titre'] ?? 'Élément non spécifié',
                        $element['description'] ?? '',
                        $quantite,
                        $prix_unitaire,
                        $prix_total,
                        $element['type'] ?? 'piece', // Type par défaut
                        $element['ordre'] ?? 1
                    ]);
                }
            }
        }
    }
    
    // Log de création
    $stmt = $pdo->prepare("
        INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, utilisateur_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $action_log = ($action === 'envoyer') ? 'CREATION_ENVOI' : 'CREATION_BROUILLON';
    $description_log = ($action === 'envoyer') ? 'Devis créé et envoyé par SMS' : 'Devis créé en brouillon';
    
    $stmt->execute([
        $devis_id,
        $action_log,
        $description_log,
        'employe',
        $employe_id,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Si envoi par SMS
    if ($action === 'envoyer') {
        // Mise à jour de la date d'envoi
        $stmt = $pdo->prepare("UPDATE devis SET date_envoi = NOW() WHERE id = ?");
        $stmt->execute([$devis_id]);
        
        // Récupération du template SMS
        $stmt = $pdo->prepare("
            SELECT contenu FROM devis_templates 
            WHERE nom = 'SMS Envoi Devis' AND type = 'sms' AND actif = 1 
            LIMIT 1
        ");
        $stmt->execute();
        $template = $stmt->fetchColumn();
        
        if ($template) {
            // Remplacement des variables dans le template
            $lien_complet = "https://" . $_SERVER['HTTP_HOST'] . "/pages/devis_client.php?lien=" . $lien_securise;
            
            $variables = [
                '{CLIENT_PRENOM}' => $reparation['prenom'],
                '{CLIENT_NOM}' => $reparation['nom'],
                '{APPAREIL_TYPE}' => $reparation['type_appareil'],
                '{APPAREIL_MODELE}' => $reparation['modele'],
                '{LIEN_DEVIS}' => $lien_complet,
                '{NOM_MAGASIN}' => 'GeekBoard',
                '{NUMERO_DEVIS}' => $numero_devis
            ];
            
            $message_sms = str_replace(array_keys($variables), array_values($variables), $template);
            
            // Insertion de la notification SMS
            $stmt = $pdo->prepare("
                INSERT INTO devis_notifications (devis_id, type, telephone, message, statut_envoi, date_programmee)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $devis_id,
                'envoi_devis',
                $reparation['telephone'],
                $message_sms,
                'en_attente'
            ]);
            
            // Tentative d'envoi immédiat du SMS (si fonction disponible)
            if (function_exists('send_sms')) {
                try {
                    // Inclure les fonctions SMS si elles ne sont pas disponibles
                    if (!function_exists('send_sms')) {
                        require_once '../includes/sms_functions.php';
                    }
                    
                    // Appel corrigé avec tous les paramètres pour l'enregistrement en base
                    $sms_result = send_sms(
                        $reparation['telephone'], 
                        $message_sms,
                        'envoi_devis',  // Type de référence pour l'enregistrement
                        $devis_id,      // ID de référence (devis_id)
                        $_SESSION['shop_id'] ?? null  // ID utilisateur (shop_id)
                    );
                    
                    // Mise à jour du statut selon le résultat
                    $nouveau_statut = ($sms_result['success'] ?? false) ? 'envoye' : 'echec';
                    $stmt = $pdo->prepare("
                        UPDATE devis_notifications 
                        SET statut_envoi = ?, date_envoi = NOW(), tentatives = tentatives + 1
                        WHERE devis_id = ? AND type = 'envoi_devis'
                    ");
                    $stmt->execute([$nouveau_statut, $devis_id]);
                    
                } catch (Exception $e) {
                    error_log("Erreur envoi SMS devis: " . $e->getMessage());
                    // SMS en échec mais on continue
                }
            }
        }
        
        // Mise à jour de la réparation avec changement de statut
        $stmt = $pdo->prepare("
            UPDATE reparations 
            SET devis_envoye = 1, 
                date_envoi_devis = NOW(), 
                statut = 'en_attente_accord_client',
                statut_id = 6
            WHERE id = ?
        ");
        $stmt->execute([$reparation_id]);
    }
    
    $pdo->commit();
    
    // Réponse de succès
    $response = [
        'success' => true,
        'message' => ($action === 'envoyer') ? 'Devis créé et envoyé avec succès' : 'Devis sauvegardé en brouillon',
        'devis_id' => $devis_id,
        'numero_devis' => $numero_devis
    ];
    
    if ($action === 'envoyer') {
        $response['lien_client'] = "https://" . $_SERVER['HTTP_HOST'] . "/pages/devis_client.php?lien=" . $lien_securise;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Erreur création devis: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du devis: ' . $e->getMessage()
    ]);
}
?> 