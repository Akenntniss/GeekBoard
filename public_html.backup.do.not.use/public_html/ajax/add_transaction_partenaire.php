<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
        error_log("Shop ID détecté et défini: " . $detected_shop_id);
    } else {
        error_log("Impossible de détecter le shop_id depuis le sous-domaine");
    }
}

// Ajouter un logging de la session pour débogage
error_log("=== DEBUG SESSION ===");
error_log("Session data: " . json_encode($_SESSION));
error_log("Session ID: " . session_id());
error_log("Cookie info: " . json_encode($_COOKIE));
error_log("HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
error_log("HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'N/A'));
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A'));
error_log("=== END DEBUG ===");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si le shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Magasin non identifié']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
$type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
$description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
error_log("Données reçues - partenaire_id: " . var_export($partenaire_id, true));
error_log("Données reçues - type: " . var_export($type, true));
error_log("Données reçues - montant: " . var_export($montant, true));
error_log("Données reçues - description: " . var_export($description, true));

if (!$partenaire_id || !$type || !$montant) {
    error_log("Validation échouée - partenaire_id: " . ($partenaire_id ? "OK" : "MANQUANT") . 
              ", type: " . ($type ? "OK" : "MANQUANT") . 
              ", montant: " . ($montant ? "OK" : "MANQUANT"));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Valider le type de transaction et convertir si nécessaire
$types_valides = ['credit', 'debit', 'AVANCE', 'REMBOURSEMENT', 'SERVICE'];
if (!in_array($type, $types_valides)) {
    error_log("Type de transaction invalide: " . $type);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Type de transaction invalide']);
    exit;
}

// Convertir les types pour la base de données
if ($type === 'credit') {
    $type = 'AVANCE'; // Crédit = ce que nous devons = avance au partenaire
} elseif ($type === 'debit') {
    $type = 'REMBOURSEMENT'; // Débit = ce qu'on nous doit = remboursement du partenaire
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }

    error_log("Début de la transaction SQL");
    // Démarrer une transaction
    $shop_pdo->beginTransaction();

    // Vérifier la structure de la table
    $stmt = $shop_pdo->prepare("DESCRIBE transactions_partenaires");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Structure de la table transactions_partenaires: " . json_encode($columns));

    // Insérer la transaction
    $stmt = $shop_pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description, date_transaction, statut) 
        VALUES (?, ?, ?, ?, NOW(), 'VALIDÉ')
    ");
    error_log("Exécution de l'insertion - Paramètres: " . json_encode([
        $partenaire_id, $type, $montant, $description
    ]));
    $stmt->execute([$partenaire_id, $type, $montant, $description]);
    error_log("ID de la transaction insérée: " . $shop_pdo->lastInsertId());

    // Mettre à jour le solde du partenaire
    $montant_final = $type === 'REMBOURSEMENT' ? -$montant : $montant;
    error_log("Calcul du montant final: " . $montant_final);
    
    // Vérifier si un solde existe déjà pour ce partenaire
    $stmt = $shop_pdo->prepare("SELECT solde_actuel FROM soldes_partenaires WHERE partenaire_id = ?");
    $stmt->execute([$partenaire_id]);
    $solde = $stmt->fetch();
    error_log("Solde existant trouvé: " . var_export($solde, true));

    if ($solde) {
        // Mettre à jour le solde existant
        $stmt = $shop_pdo->prepare("
            UPDATE soldes_partenaires 
            SET solde_actuel = solde_actuel + ?, 
                derniere_mise_a_jour = CURRENT_TIMESTAMP 
            WHERE partenaire_id = ?
        ");
        error_log("Mise à jour du solde existant - Paramètres: " . json_encode([
            $montant_final, $partenaire_id
        ]));
        $stmt->execute([$montant_final, $partenaire_id]);
        error_log("Nombre de lignes affectées par la mise à jour: " . $stmt->rowCount());
    } else {
        // Créer un nouveau solde
        $stmt = $shop_pdo->prepare("
            INSERT INTO soldes_partenaires 
            (partenaire_id, solde_actuel) 
            VALUES (?, ?)
        ");
        error_log("Création d'un nouveau solde - Paramètres: " . json_encode([
            $partenaire_id, $montant_final
        ]));
        $stmt->execute([$partenaire_id, $montant_final]);
        error_log("ID du nouveau solde créé: " . $shop_pdo->lastInsertId());
    }

    // Récupérer les informations du partenaire pour le SMS
    $stmt = $shop_pdo->prepare("SELECT nom, telephone FROM partenaires WHERE id = ?");
    $stmt->execute([$partenaire_id]);
    $partenaire_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Valider la transaction
    $shop_pdo->commit();
    error_log("Transaction SQL validée avec succès");
    
    // Envoyer SMS au partenaire si un numéro de téléphone est disponible
    if ($partenaire_info && !empty($partenaire_info['telephone'])) {
        $type_text = ($type === 'AVANCE') ? 'Crédit' : 'Débit';
        $sms_message = "Ajout Transaction\nType : " . $type_text . "\nMontant : " . number_format($montant, 2) . " €\nDescription : " . $description;
        
        // Envoyer le SMS
        envoyerSMSPartenaire($partenaire_info['telephone'], $sms_message, $partenaire_info['nom']);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Transaction enregistrée avec succès']);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
        error_log("Transaction SQL annulée");
    }
    
    error_log("Erreur PDO détaillée: " . $e->getMessage());
    error_log("Code d'erreur PDO: " . $e->getCode());
    error_log("État SQL: " . implode(', ', $e->errorInfo));
    error_log("Trace de l'erreur: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'enregistrement de la transaction: ' . $e->getMessage(),
        'debug' => [
            'code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? 'N/A',
            'error_code' => $e->errorInfo[1] ?? 'N/A',
            'error_message' => $e->errorInfo[2] ?? 'N/A'
        ]
    ]);
} catch (Exception $e) {
    // Gérer les autres exceptions
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
        error_log("Transaction SQL annulée (Exception générale)");
    }
    
    error_log("Erreur générale: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'enregistrement de la transaction: ' . $e->getMessage()
    ]);
}

/**
 * Fonction pour envoyer un SMS au partenaire
 */
function envoyerSMSPartenaire($telephone, $message, $nom_partenaire) {
    try {
        error_log("Tentative d'envoi SMS au partenaire $nom_partenaire ($telephone): $message");
        
        // Configuration SMS (utilise la même API que le système existant)
        $sms_api_url = 'https://api.smspartner.fr/v1/send';
        $sms_api_key = 'b1b5c6c6d1f8a8a8a8a8a8a8a8a8a8a8'; // Remplacer par la vraie clé API
        
        // Nettoyer le numéro de téléphone
        $telephone_clean = preg_replace('/[^0-9+]/', '', $telephone);
        if (substr($telephone_clean, 0, 1) === '0') {
            $telephone_clean = '+33' . substr($telephone_clean, 1);
        }
        
        // Données pour l'API SMS
        $sms_data = [
            'apiKey' => $sms_api_key,
            'phoneNumbers' => [$telephone_clean],
            'message' => $message,
            'sender' => 'GeekBoard'
        ];
        
        // Envoi via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sms_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            error_log("SMS envoyé avec succès au partenaire $nom_partenaire ($telephone_clean)");
            return true;
        } else {
            error_log("Erreur envoi SMS au partenaire $nom_partenaire: HTTP $http_code - $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception lors de l'envoi SMS au partenaire $nom_partenaire: " . $e->getMessage());
        return false;
    }
}
?>