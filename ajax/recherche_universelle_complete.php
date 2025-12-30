<?php
/**
 * Recherche Universelle Complete V2 - Corrigée avec vraies colonnes
 * Recherche intelligente cross-référencée pour GeekBoard
 */

session_start();
header('Content-Type: application/json');

// Sécurisation et nettoyage des entrées
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Récupérer le terme de recherche
$terme = isset($_POST['terme']) ? cleanInput($_POST['terme']) : '';

if (empty($terme) || strlen($terme) < 2) {
    echo json_encode([
        'success' => false, 
        'error' => 'Terme de recherche trop court (minimum 2 caractères)',
        'clients' => [],
        'reparations' => [],
        'commandes' => []
    ]);
    exit;
}

// Configuration de base de données via getShopDBConnection
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

// Initialiser la session du shop si nécessaire
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

try {
    // Connexion à la base de données
    $pdo = getShopDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $shop_id = $_SESSION['shop_id'];
    $db_name = 'geekboard_' . ($_SESSION['shop_subdomain'] ?? 'unknown');
    $subdomain = $_SESSION['shop_subdomain'] ?? 'unknown';

    // Tableaux pour stocker les résultats
    $clients_results = [];
    $reparations_results = [];
    $commandes_results = [];
    
    // IDs trouvés pour éviter les doublons
    $found_client_ids = [];
    $found_reparation_ids = [];
    $found_commande_ids = [];
    
    // Log pour debug
    error_log("🔍 Recherche intelligente - Terme: {$terme}, Shop: {$subdomain}, DB: {$db_name}");
    
    // ==================== RECHERCHE CLIENTS ====================
    $sql_clients = "SELECT id, nom, prenom, email, telephone, date_creation 
                   FROM clients 
                   WHERE (nom LIKE :terme OR prenom LIKE :terme OR email LIKE :terme OR telephone LIKE :terme) 
                   ORDER BY nom, prenom 
                   LIMIT 20";
    
    $stmt = $pdo->prepare($sql_clients);
    $stmt->execute([':terme' => "%{$terme}%"]);
    
    while ($row = $stmt->fetch()) {
        $found_client_ids[] = $row['id'];
        
        $clients_results[] = [
            'id' => $row['id'],
            'nom' => trim($row['nom'] . ' ' . $row['prenom']),
            'email' => $row['email'] ?: 'Non renseigné',
            'telephone' => $row['telephone'] ?: 'Non renseigné',
            'date_creation' => $row['date_creation'] ? date('d/m/Y', strtotime($row['date_creation'])) : 'Inconnue'
        ];
    }
    
    // ==================== RECHERCHE RÉPARATIONS ====================
    $sql_reparations = "SELECT r.id, r.type_appareil, r.modele, r.description_probleme, r.statut, r.date_reception, r.client_id,
                              c.nom as client_nom, c.prenom as client_prenom
                       FROM reparations r
                       LEFT JOIN clients c ON r.client_id = c.id
                       WHERE (r.type_appareil LIKE :terme OR r.modele LIKE :terme OR r.description_probleme LIKE :terme
                              OR c.nom LIKE :terme OR c.prenom LIKE :terme)
                       ORDER BY r.date_reception DESC
                       LIMIT 20";
    
    $stmt = $pdo->prepare($sql_reparations);
    $stmt->execute([':terme' => "%{$terme}%"]);
    
    while ($row = $stmt->fetch()) {
        $found_reparation_ids[] = $row['id'];
        
        // Ajouter le client s'il n'a pas déjà été trouvé
        if ($row['client_id'] && !in_array($row['client_id'], $found_client_ids)) {
            $found_client_ids[] = $row['client_id'];
            
            $clients_results[] = [
                'id' => $row['client_id'],
                'nom' => trim($row['client_nom'] . ' ' . $row['client_prenom']),
                'email' => 'Via réparation',
                'telephone' => 'À vérifier',
                'date_creation' => 'Client lié'
            ];
        }
        
        $reparations_results[] = [
            'id' => $row['id'],
            'client' => $row['client_nom'] ? trim($row['client_nom'] . ' ' . $row['client_prenom']) : 'Client inconnu',
            'client_id' => $row['client_id'],
            'appareil' => trim(($row['type_appareil'] ?: 'N/A') . ' ' . ($row['modele'] ?: '')),
            'probleme' => substr($row['description_probleme'] ?: 'Non spécifié', 0, 100) . (strlen($row['description_probleme']) > 100 ? '...' : ''),
            'statut' => $row['statut'] ?: 'Indéterminé',
            'date' => $row['date_reception'] ? date('d/m/Y', strtotime($row['date_reception'])) : 'Non datée'
        ];
    }
    
    // ==================== RECHERCHE COMMANDES ====================
    try {
        $sql_commandes = "SELECT cp.id, cp.reparation_id, cp.nom_piece, cp.statut, cp.date_commande, cp.fournisseur,
                                r.type_appareil, r.modele, r.client_id,
                                c.nom as client_nom, c.prenom as client_prenom
                         FROM commandes_pieces cp
                         LEFT JOIN reparations r ON cp.reparation_id = r.id
                         LEFT JOIN clients c ON r.client_id = c.id
                         WHERE (cp.nom_piece LIKE :terme OR cp.fournisseur LIKE :terme 
                                OR r.type_appareil LIKE :terme OR r.modele LIKE :terme
                                OR c.nom LIKE :terme OR c.prenom LIKE :terme)
                         ORDER BY cp.date_commande DESC
                         LIMIT 20";
        
        $stmt = $pdo->prepare($sql_commandes);
        $stmt->execute([':terme' => "%{$terme}%"]);
        
        while ($row = $stmt->fetch()) {
            $found_commande_ids[] = $row['id'];
            
            // Ajouter le client s'il n'a pas déjà été trouvé
            if ($row['client_id'] && !in_array($row['client_id'], $found_client_ids)) {
                $found_client_ids[] = $row['client_id'];
                
                $clients_results[] = [
                    'id' => $row['client_id'],
                    'nom' => trim($row['client_nom'] . ' ' . $row['client_prenom']),
                    'email' => 'Via commande',
                    'telephone' => 'À vérifier',
                    'date_creation' => 'Client lié'
                ];
            }
            
            // Ajouter la réparation si elle n'a pas déjà été trouvée
            if ($row['reparation_id'] && !in_array($row['reparation_id'], $found_reparation_ids)) {
                $found_reparation_ids[] = $row['reparation_id'];
                
                $reparations_results[] = [
                    'id' => $row['reparation_id'],
                    'client' => $row['client_nom'] ? trim($row['client_nom'] . ' ' . $row['client_prenom']) : 'Client inconnu',
                    'client_id' => $row['client_id'],
                    'appareil' => trim(($row['type_appareil'] ?: 'N/A') . ' ' . ($row['modele'] ?: '')),
                    'probleme' => 'Réparation liée à la commande',
                    'statut' => 'Via commande',
                    'date' => 'Lié à commande'
                ];
            }
            
            $commandes_results[] = [
                'id' => $row['id'],
                'piece' => $row['nom_piece'] ?: 'Pièce non spécifiée',
                'appareil' => trim(($row['type_appareil'] ?: 'N/A') . ' ' . ($row['modele'] ?: '')),
                'client' => $row['client_nom'] ? trim($row['client_nom'] . ' ' . $row['client_prenom']) : 'Client inconnu',
                'client_id' => $row['client_id'],
                'reparation_id' => $row['reparation_id'],
                'fournisseur' => $row['fournisseur'] ?: 'Non spécifié',
                'statut' => $row['statut'] ?: 'Indéterminé',
                'date' => $row['date_commande'] ? date('d/m/Y', strtotime($row['date_commande'])) : 'Non datée'
            ];
        }
    } catch (PDOException $e) {
        // Table commandes_pieces pourrait ne pas exister
        error_log("Table commandes_pieces non accessible : " . $e->getMessage());
    }
    
    // Limiter les résultats pour éviter la surcharge
    $clients_results = array_slice($clients_results, 0, 20);
    $reparations_results = array_slice($reparations_results, 0, 20);
    $commandes_results = array_slice($commandes_results, 0, 20);
    
    // Statistiques pour le log
    $total_results = count($clients_results) + count($reparations_results) + count($commandes_results);
    error_log("✅ Recherche terminée - Clients: " . count($clients_results) . 
              ", Réparations: " . count($reparations_results) . 
              ", Commandes: " . count($commandes_results) . 
              ", Total: {$total_results}");
    
    // Retourner les résultats structurés
    echo json_encode([
        'success' => true,
        'clients' => $clients_results,
        'reparations' => $reparations_results,
        'commandes' => $commandes_results,
        'counts' => [
            'clients' => count($clients_results),
            'reparations' => count($reparations_results),
            'commandes' => count($commandes_results),
            'total' => $total_results
        ],
        'search_info' => [
            'terme' => $terme,
            'shop_id' => $shop_id,
            'subdomain' => $subdomain,
            'database' => $db_name
        ]
    ]);

} catch (PDOException $e) {
    error_log("❌ Erreur recherche intelligente : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'clients' => [],
        'reparations' => [],
        'commandes' => [],
        'debug_info' => [
            'terme' => $terme,
            'shop_id' => $shop_id,
            'subdomain' => $subdomain,
            'database' => $db_name,
            'query_error' => $e->getMessage()
        ]
    ]);
}
?> 