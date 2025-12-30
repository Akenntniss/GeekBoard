<?php
// Inclure la configuration de base de données
require_once '../config/database.php';

// Initialiser la session magasin
initializeShopSession();

// Obtenir la connexion à la base de données
$pdo = getShopDBConnection();

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les IDs de statut depuis les paramètres GET
$statusIds = isset($_GET['status_ids']) ? $_GET['status_ids'] : '';

// Debug : Log des paramètres reçus
error_log("get_repairs_by_status.php - Paramètres reçus: " . print_r($_GET, true));

if (empty($statusIds)) {
    error_log("get_repairs_by_status.php - Erreur: IDs de statut manquants");
    echo json_encode([
        'success' => false, 
        'error' => 'IDs de statut manquants',
        'received_params' => $_GET,
        'debug' => 'Le paramètre status_ids est vide ou manquant'
    ]);
    exit;
}

// Convertir la chaîne en tableau d'entiers
$statusIdsArray = array_map('intval', explode(',', $statusIds));
$statusIdsArray = array_filter($statusIdsArray, function($id) { return $id > 0; });

if (empty($statusIdsArray)) {
    echo json_encode(['success' => false, 'error' => 'IDs de statut invalides']);
    exit;
}

try {
    // Créer les placeholders pour la requête préparée
    $placeholders = str_repeat('?,', count($statusIdsArray) - 1) . '?';
    
    // Mapper les IDs de statut vers leurs codes texte correspondants
    $statusCodeMapping = [
        1 => 'nouveau_diagnostique',
        2 => 'nouvelle_intervention',
        3 => 'nouvelle_commande',
        4 => 'en_cours_diagnostique',
        5 => 'en_cours_intervention',
        6 => 'en_attente_accord_client',
        7 => 'en_attente_livraison',
        8 => 'en_attente_responsable',
        9 => 'reparation_effectue',
        10 => 'reparation_annule',
        19 => 'devis_accepte',
        20 => 'devis_refuse'
    ];
    
    // Récupérer les codes correspondant aux IDs demandés
    $statusCodes = [];
    foreach ($statusIdsArray as $id) {
        if (isset($statusCodeMapping[$id])) {
            $statusCodes[] = $statusCodeMapping[$id];
        }
    }
    
    // Créer les placeholders pour les codes
    $codePlaceholders = '';
    $allParams = $statusIdsArray;
    if (!empty($statusCodes)) {
        $codePlaceholders = str_repeat('?,', count($statusCodes) - 1) . '?';
        $allParams = array_merge($statusIdsArray, $statusCodes);
    }
    
    // Requête pour récupérer les réparations par statut avec jointure clients
    // On cherche SOIT par statut_id SOIT par statut (colonne texte)
    $sql = "
        SELECT 
            r.id,
            c.nom as client_nom,
            c.telephone as client_telephone,
            r.marque as appareil_marque,
            r.modele as appareil_modele,
            r.description_probleme as probleme_description,
            r.prix,
            r.prix_reparation,
            d.total_ttc as devis_montant,
            r.statut_id,
            s.nom as statut_nom,
            r.date_reception as date_creation,
            r.date_modification
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut_id = s.id
        LEFT JOIN devis d ON r.id = d.reparation_id AND d.statut = 'accepte'
        WHERE (r.statut_id IN ($placeholders)" . 
            (!empty($statusCodes) ? " OR r.statut IN ($codePlaceholders)" : "") . ")
          AND r.statut NOT IN ('restitue', 'restituee', 'gardiennage')
        ORDER BY r.date_reception DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allParams);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($repairs as &$repair) {
        // Debug temporaire
        // error_log("Repair ID: " . $repair['id'] . " - Prix: " . ($repair['prix'] ?? 'NULL') . " - PrixRep: " . ($repair['prix_reparation'] ?? 'NULL'));

        // Consolider le prix
        // Si prix est vide (null ou ''), on regarde prix_reparation
        if ((!isset($repair['prix']) || $repair['prix'] === '' || $repair['prix'] === null) && 
            isset($repair['prix_reparation']) && $repair['prix_reparation'] !== '' && $repair['prix_reparation'] !== null) {
            $repair['prix'] = $repair['prix_reparation'];
        }
        
        // Si toujours vide, on regarde le devis
        if ((!isset($repair['prix']) || $repair['prix'] === '' || $repair['prix'] === null) && 
            isset($repair['devis_montant']) && $repair['devis_montant'] !== '' && $repair['devis_montant'] !== null) {
            $repair['prix'] = $repair['devis_montant'];
        }

        // Formater le prix
        if (isset($repair['prix']) && $repair['prix'] !== '' && $repair['prix'] !== null) {
            $repair['prix'] = number_format((float)$repair['prix'], 2, ',', ' ');
        }
        
        // Formater les dates
        if (!empty($repair['date_creation'])) {
            $repair['date_creation_formatted'] = date('d/m/Y H:i', strtotime($repair['date_creation']));
        }
        
        if (!empty($repair['date_modification'])) {
            $repair['date_modification_formatted'] = date('d/m/Y H:i', strtotime($repair['date_modification']));
        }
        
        // Nettoyer les données
        $repair['client_nom'] = htmlspecialchars($repair['client_nom'] ?? '', ENT_QUOTES, 'UTF-8');
        $repair['client_telephone'] = htmlspecialchars($repair['client_telephone'] ?? '', ENT_QUOTES, 'UTF-8');
        $repair['appareil_marque'] = htmlspecialchars($repair['appareil_marque'] ?? '', ENT_QUOTES, 'UTF-8');
        $repair['appareil_modele'] = htmlspecialchars($repair['appareil_modele'] ?? '', ENT_QUOTES, 'UTF-8');
        $repair['probleme_description'] = htmlspecialchars($repair['probleme_description'] ?? '', ENT_QUOTES, 'UTF-8');
        $repair['statut_nom'] = htmlspecialchars($repair['statut_nom'] ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'repairs' => $repairs,
        'count' => count($repairs),
        'status_ids' => $statusIdsArray
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des réparations par statut: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur de base de données',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur générale lors de la récupération des réparations par statut: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
?>