<?php
// Désactiver l'affichage des erreurs PHP pour la production
// mais les logger pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Ajouter un fichier de log personnalisé pour cet endpoint
ini_set('error_log', __DIR__ . '/../logs/errors/app_errors.log');
error_log("Démarrage de send_devis_sms.php");

// Démarrer la session pour récupérer l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

// Vérifier le type de requête HTTP
$method = $_SERVER['REQUEST_METHOD'];
error_log("Méthode HTTP reçue: " . $method);

require_once('../config/database.php');

// Utiliser la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    error_log("Erreur: Connexion à la base de données non établie dans send_devis_sms.php");
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier quelle base de données nous utilisons réellement
try {
    $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Base de données connectée dans send_devis_sms.php: " . ($db_info['current_db'] ?? 'Inconnue'));
} catch (Exception $e) {
    error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
}

// Journaliser les données reçues pour le débogage
error_log("Données POST reçues: " . print_r($_POST, true));

// Vérifier si les données nécessaires sont fournies
if (!isset($_POST['repair_id']) || !isset($_POST['sms_type'])) {
    error_log("Erreur: Données requises non fournies dans send_devis_sms.php");
    echo json_encode([
        'success' => false,
        'error' => 'Données requises non fournies'
    ]);
    exit;
}

$repair_id = (int)$_POST['repair_id'];
$sms_type = (int)$_POST['sms_type'];

// Récupérer les nouveaux paramètres
$prix_update = isset($_POST['prix']) ? (float)$_POST['prix'] : null;
$type_message = isset($_POST['type_message']) ? $_POST['type_message'] : 'simple';
$notes_techniques = isset($_POST['notes_techniques']) ? $_POST['notes_techniques'] : '';

error_log("Traitement de l'envoi de devis pour réparation ID: $repair_id, Type SMS: $sms_type, Type Message: $type_message");
if ($prix_update !== null) {
    error_log("Prix mis à jour: $prix_update €");
}

// Vérifier que le SMS type est bien 4 (devis)
if ($sms_type !== 4) {
    error_log("Erreur: Type de SMS invalide: {$sms_type}");
    echo json_encode([
        'success' => false,
        'error' => 'Type de SMS invalide'
    ]);
    exit;
}

try {
    // 1. Récupérer les informations de la réparation et du client
    error_log("Étape 1: Récupération des informations de la réparation et du client");
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.telephone, c.nom, c.prenom, c.id as client_id, c.email
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        error_log("Réparation non trouvée avec ID: $repair_id dans la base " . ($db_info['current_db'] ?? 'Inconnue'));
        echo json_encode([
            'success' => false,
            'error' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    error_log("Réparation trouvée: " . print_r([
        'id' => $repair['id'],
        'client_id' => $repair['client_id'],
        'telephone' => $repair['telephone'],
        'statut' => $repair['statut']
    ], true));
    
    // 2. Changer le statut de la réparation en "en attente d'accord client"
    error_log("Étape 2: Changement du statut en 'en attente d'accord client'");
    $statut_code = "en_attente_accord_client";
    
    // Récupérer l'ID du statut pour la journalisation
    $statusIdStmt = $shop_pdo->prepare("SELECT id FROM statuts WHERE code = ?");
    $statusIdStmt->execute([$statut_code]);
    $statusRow = $statusIdStmt->fetch(PDO::FETCH_ASSOC);
    $statut_id = $statusRow ? $statusRow['id'] : null;
    
    error_log("ID du statut trouvé: " . ($statut_id ? $statut_id : "null"));
    
    // Mettre à jour le statut
    $updateStmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET statut = ?, statut_id = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    
    $updateSuccess = $updateStmt->execute([$statut_code, $statut_id, $repair_id]);
    
    if (!$updateSuccess) {
        $error = $updateStmt->errorInfo();
        error_log("Erreur lors de la mise à jour du statut: " . json_encode($error));
        throw new PDOException("Erreur lors de la mise à jour du statut: " . $error[2]);
    }
    
    error_log("Statut de la réparation mis à jour avec succès");
    
    // 3. Enregistrer le changement dans l'historique
    error_log("Étape 3: Enregistrement du changement dans l'historique");
    $logStmt = $shop_pdo->prepare("
        INSERT INTO reparation_logs (reparation_id, employe_id, action_type, date_action, statut_avant, statut_apres, details) 
        VALUES (?, ?, 'changement_statut', NOW(), ?, ?, ?)
    ");
    
    $description = "Statut changé en 'En attente d'accord client' lors de l'envoi d'un devis";
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Valeur par défaut si non connecté
    $statut_avant = $repair['statut']; // Status actuel avant changement
    $statut_apres = $statut_code; // Nouveau statut (en_attente_accord_client)
    
    $logResult = $logStmt->execute([$repair_id, $user_id, $statut_avant, $statut_apres, $description]);
    if (!$logResult) {
        error_log("Avertissement: Impossible d'enregistrer l'action dans l'historique: " . json_encode($logStmt->errorInfo()));
    } else {
        error_log("Changement enregistré dans l'historique avec succès");
    }
    
    // 4. Envoyer le SMS de devis au client
    error_log("Étape 4: Préparation du SMS de devis");
    // Récupérer le modèle de SMS type 4 (Devis)
    $templateStmt = $shop_pdo->prepare("
        SELECT * FROM sms_templates WHERE id = ?
    ");
    
    $templateStmt->execute([4]); // SMS ID 4 pour le devis
    $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        error_log("Modèle de SMS ID 4 non trouvé");
        throw new Exception("Modèle de SMS pour devis non trouvé");
    }
    
    error_log("Modèle de SMS trouvé: " . $template['nom']);
    
    // Préparer le message SMS
    $message = $template['contenu'];
    
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
    
    // Remplacer les variables dans le message
    // Tableau des remplacements (format utilisé dans le modèle)
    $replacements = [
        '[CLIENT_NOM]' => $repair['nom'],
        '[CLIENT_PRENOM]' => $repair['prenom'],
        '[REPARATION_ID]' => $repair_id,
        '[APPAREIL_TYPE]' => $repair['type_appareil'],
        '[APPAREIL_MARQUE]' => $repair['marque'],
        '[APPAREIL_MODELE]' => $repair['modele'],
        '[REF]' => $repair_id,
        '[PRIX]' => $repair['prix_reparation'] . '€',
        '[LIEN]' => "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id,
        '[APPAREIL]' => $repair['type_appareil'] . ' ' . $repair['marque'] . ' ' . $repair['modele'],
        '[COMPANY_NAME]' => $company_name,
        '[COMPANY_PHONE]' => $company_phone
    ];
    
    // Remplacer toutes les variables du tableau dans le message
    foreach ($replacements as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }
    
    // Si le type de message est détaillé, ajouter les notes techniques
    if ($type_message === 'detaille' && !empty($notes_techniques)) {
        error_log("Ajout des notes techniques au message");
        $message .= "\n\nDétails techniques:\n" . $notes_techniques;
    }
    
    // Vérifier si des variables n'ont pas été remplacées
    $pattern = '/\[([A-Z_]+)\]/';
    if (preg_match_all($pattern, $message, $matches)) {
        error_log("ATTENTION: Variables non remplacées détectées: " . implode(', ', $matches[0]));
        
        // Remplacer les variables restantes par des valeurs par défaut
        $message = preg_replace('/\[CLIENT_NOM\]/', '[Nom client]', $message);
        $message = preg_replace('/\[CLIENT_PRENOM\]/', '[Prénom client]', $message);
        $message = preg_replace('/\[REPARATION_ID\]/', $repair_id, $message);
        $message = preg_replace('/\[APPAREIL_TYPE\]/', $repair['type_appareil'] ?: '[Type appareil]', $message);
        $message = preg_replace('/\[APPAREIL_MARQUE\]/', $repair['marque'] ?: '[Marque]', $message);
        $message = preg_replace('/\[APPAREIL_MODELE\]/', $repair['modele'] ?: '[Modèle]', $message);
        $message = preg_replace('/\[REF\]/', $repair_id, $message);
        $message = preg_replace('/\[PRIX\]/', $repair['prix_reparation'] . '€', $message);
        $message = preg_replace('/\[APPAREIL\]/', ($repair['type_appareil'] ?: '') . ' ' . 
                                              ($repair['marque'] ?: '') . ' ' . 
                                              ($repair['modele'] ?: ''), $message);
        
        // URL d'acceptation du devis
        $message = preg_replace('/\[LIEN\]/', "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id, $message);
    }
    
    // Créer un lien pour accepter le devis (si nécessaire)
    $lien_acceptation = "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id;
    
    error_log("Message SMS après remplacement des variables: " . $message);
    error_log("Longueur du message: " . strlen($message) . " caractères");
    error_log("Lien d'acceptation: " . $lien_acceptation);
    
    // Enregistrer l'envoi du SMS dans la base de données
    error_log("Étape 5: Enregistrement de l'envoi du SMS dans la base de données");
    $smsLogStmt = $shop_pdo->prepare("
        INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    
    $smsLogResult = $smsLogStmt->execute([
        $repair_id,
        4, // ID du modèle de SMS
        $repair['telephone'],
        $message,
        $statut_id // Utiliser l'ID du statut récupéré précédemment
    ]);
    
    if (!$smsLogResult) {
        error_log("Avertissement: Impossible d'enregistrer le SMS dans l'historique: " . json_encode($smsLogStmt->errorInfo()));
    }
    
    $sms_id = $shop_pdo->lastInsertId();
    error_log("SMS enregistré dans l'historique avec ID: " . $sms_id);
    
    // Envoi du SMS via la nouvelle API Gateway
    error_log("Étape 6: Envoi du SMS via API Gateway");
    
    // Inclure la fonction SMS unifiée
    if (!function_exists('send_sms')) {
        require_once __DIR__ . '/../includes/sms_functions.php';
    }
    
    $recipient = $repair['telephone'];
    error_log("Numéro de téléphone: $recipient");
    
    // Envoyer le SMS via la fonction unifiée
    $smsResult = send_sms($recipient, $message, 'devis_sms', $repair_id, $_SESSION['user_id'] ?? 1);
    
    error_log("Résultat envoi SMS: " . json_encode($smsResult));
    
    // 5. Envoyer également un email si l'adresse email du client est disponible
    if (!empty($repair['email'])) {
        error_log("Étape 7: Envoi d'un email au client à l'adresse " . $repair['email']);
        // Code pour envoyer un email (si nécessaire)
        // ...
    }
    
    // Mettre à jour le prix de la réparation si fourni
    if ($prix_update !== null) {
        try {
            $updatePrixStmt = $shop_pdo->prepare("
                UPDATE reparations 
                SET prix_reparation = ? 
                WHERE id = ?
            ");
            $updatePrixSuccess = $updatePrixStmt->execute([$prix_update, $repair_id]);
            
            if ($updatePrixSuccess) {
                error_log("Prix de la réparation mis à jour avec succès: $prix_update €");
                // Mettre à jour le prix dans notre variable $repair
                $repair['prix_reparation'] = $prix_update;
            } else {
                error_log("Erreur lors de la mise à jour du prix: " . json_encode($updatePrixStmt->errorInfo()));
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la mise à jour du prix: " . $e->getMessage());
        }
    }
    
    // Préparer la réponse
    error_log("Étape 8: Envoi de la réponse JSON de succès");
    echo json_encode([
        'success' => true,
        'message' => 'Devis envoyé avec succès et statut mis à jour',
        'sms_id' => $sms_id,
        'repair_id' => $repair_id
    ]);
    
    error_log("Devis envoyé avec succès pour la réparation ID: $repair_id");
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans send_devis_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'envoi du devis: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue dans send_devis_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue: ' . $e->getMessage()
    ]);
} 