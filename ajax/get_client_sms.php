<?php
// ajax/get_client_sms.php
require_once '../config/database.php';
require_once '../config/session_config.php';

// Initialiser la session
initializeShopSession();

// Définir le type de contenu JSON
header('Content-Type: application/json');

try {
    // Récupérer les paramètres - accepter client_id OU phone
    $client_id = $_GET['client_id'] ?? null;
    $phone = $_GET['phone'] ?? '';
    
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    $client = null;
    
    // Si on a un client_id, récupérer les données du client
    if (!empty($client_id)) {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, telephone FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            echo json_encode([
                'success' => false,
                'error' => 'Client non trouvé'
            ]);
            exit;
        }
        
        // Utiliser le numéro de téléphone du client
        $phone = $client['telephone'];
    }
    // Sinon, si on a un numéro de téléphone, chercher le client
    elseif (!empty($phone)) {
        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Rechercher le client par numéro de téléphone
        $stmt = $pdo->prepare("SELECT id, nom, prenom, telephone FROM clients WHERE telephone = ? OR telephone = ? OR telephone = ? LIMIT 1");
        
        // Essayer différents formats de numéro
        $phoneVariants = [
            $phone,
            '+33' . substr($phone, 2), // Ajouter +33 si commence par 33
            '0' . substr($phone, 3)    // Remplacer +33 par 0
        ];
        
        $stmt->execute($phoneVariants);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Vérifier qu'on a bien un client et un téléphone
    if (!$client || empty($client['telephone'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Numéro de téléphone manquant'
        ]);
        exit;
    }
    
    // Récupérer l'historique SMS pour ce client depuis les deux tables
    $sms_history = [];
    
    // D'abord depuis sms_logs
    $sms_stmt = $pdo->prepare("
        SELECT 
            id,
            recipient as telephone,
            message,
            date_envoi,
            status,
            reparation_id,
            response,
            'sms_logs' as source_table
        FROM sms_logs 
        WHERE recipient = ? 
        ORDER BY date_envoi DESC 
        LIMIT 50
    ");
    
    $sms_stmt->execute([$client['telephone']]);
    $sms_from_logs = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensuite depuis sms_historique (si la table existe)
    try {
        $sms_stmt2 = $pdo->prepare("
            SELECT 
                id,
                telephone,
                message,
                date_envoi,
                statut as status,
                reparation_id,
                NULL as response,
                'sms_historique' as source_table
            FROM sms_historique 
            WHERE telephone = ? 
            ORDER BY date_envoi DESC 
            LIMIT 50
        ");
        
        $sms_stmt2->execute([$client['telephone']]);
        $sms_from_historique = $sms_stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Fusionner les résultats
        $sms_history = array_merge($sms_from_logs, $sms_from_historique);
    } catch (Exception $e) {
        // Si la table n'existe pas, utiliser seulement sms_logs
        $sms_history = $sms_from_logs;
    }
    
    // Trier par date décroissante
    usort($sms_history, function($a, $b) {
        return strtotime($b['date_envoi']) - strtotime($a['date_envoi']);
    });
    
    // Limiter à 50 résultats
    $sms_history = array_slice($sms_history, 0, 50);
    
    // Formater les SMS pour l'affichage
    $formatted_sms = [];
    foreach ($sms_history as $sms) {
        // Récupérer les infos de réparation si présentes
        $reparation_info = null;
        if (!empty($sms['reparation_id'])) {
            $rep_stmt = $pdo->prepare("SELECT id, appareil, status FROM reparations WHERE id = ?");
            $rep_stmt->execute([$sms['reparation_id']]);
            $reparation_info = $rep_stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Déterminer le statut
        $status_value = $sms['status'];
        $status_class = 'info';
        $status_text = 'En attente';
        
        if ($status_value == 1 || $status_value == 'envoyé' || $status_value == 'success') {
            $status_class = 'success';
            $status_text = 'Envoyé';
        } elseif ($status_value == 0 || $status_value == 'échec' || $status_value == 'error') {
            $status_class = 'danger';
            $status_text = 'Échec';
        }
        
        $formatted_sms[] = [
            'id' => $sms['id'],
            'message' => $sms['message'],
            'date_envoi' => $sms['date_envoi'],
            'date_formatted' => date('d/m/Y à H:i', strtotime($sms['date_envoi'])),
            'status_class' => $status_class,
            'status_text' => $status_text,
            'reparation_id' => $sms['reparation_id'],
            'reparation_info' => $reparation_info,
            'source_table' => $sms['source_table']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'client' => $client,
        'sms' => $formatted_sms,
        'total' => count($formatted_sms)
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_client_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>