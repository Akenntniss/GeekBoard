<?php
/**
 * API de recherche universelle
 * Recherche dans les clients, réparations et commandes
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Priorité à l'URL si shop_id est fourni en paramètre
if (isset($_GET['shop_id']) && (int)$_GET['shop_id'] > 0) {
    $_SESSION['shop_id'] = (int)$_GET['shop_id'];
}

// Initialiser shop_id si non défini
if (!isset($_SESSION['shop_id'])) {
    // Essayer de récupérer depuis l'URL
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    } else {
        // Essayer de détecter automatiquement depuis la base principale
        try {
            $main_pdo = getMainDBConnection();
            $stmt = $main_pdo->query("SELECT id FROM shops WHERE active = 1 ORDER BY id ASC LIMIT 1");
            $first_shop = $stmt->fetch();
            $_SESSION['shop_id'] = $first_shop['id'] ?? 1;
        } catch (Exception $e) {
            $_SESSION['shop_id'] = 1; // Fallback standard
        }
    }
}

// Récupérer le terme de recherche
$terme = isset($_POST['terme']) ? trim($_POST['terme']) : '';

if (empty($terme) || strlen($terme) < 2) {
    echo json_encode([
        'clients' => [],
        'reparations' => [],
        'commandes' => []
    ]);
    exit;
}

try {
    // Vérifier la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données du magasin impossible');
    }
    
    // Recherche des clients
    $clients = searchClients($terme, $shop_pdo);
    
    // Recherche des réparations
    $reparations = searchReparations($terme, $shop_pdo);
    
    // Recherche des commandes
    $commandes = searchCommandes($terme, $shop_pdo);
    
    
    // Retourner les résultats
    echo json_encode([
        'clients' => $clients,
        'reparations' => $reparations,
        'commandes' => $commandes
    ]);
    
} catch (Exception $e) {
    error_log("Erreur recherche universelle - Shop ID: " . ($_SESSION['shop_id'] ?? 'unknown') . " - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche: ' . $e->getMessage()]);
}

/**
 * Recherche des clients
 */
function searchClients($terme, $shop_pdo) {
    try {
        $sql = "SELECT id, nom, prenom, telephone, email 
                FROM clients 
                WHERE nom LIKE ? 
                OR prenom LIKE ? 
                OR telephone LIKE ? 
                OR email LIKE ? 
                ORDER BY nom, prenom 
                LIMIT 10";
                
        $stmt = $shop_pdo->prepare($sql);
        $terme_wildcard = "%{$terme}%";
        $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $results;
    } catch (Exception $e) {
        error_log("Erreur searchClients: " . $e->getMessage());
        return [];
    }
}

/**
 * Recherche des réparations
 */
function searchReparations($terme, $shop_pdo) {
    try {
        // Utiliser les vraies colonnes de la table reparations
        $sql = "SELECT r.id, 
                       CONCAT(r.type_appareil, ' ', r.modele) as appareil, 
                       r.description_probleme as probleme, 
                       r.statut, r.date_reception as date_creation, r.notes_techniques as note_interne,
                       CONCAT(c.nom, ' ', c.prenom) as client_nom,
                       c.telephone as client_telephone
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.type_appareil LIKE ? 
                OR r.modele LIKE ? 
                OR r.description_probleme LIKE ? 
                OR r.notes_techniques LIKE ?
                OR c.nom LIKE ? 
                OR c.prenom LIKE ?
                OR c.telephone LIKE ?
                ORDER BY r.date_reception DESC 
                LIMIT 10";
                
        $stmt = $shop_pdo->prepare($sql);
        $terme_wildcard = "%{$terme}%";
        $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $results;
    } catch (Exception $e) {
        error_log("Erreur searchReparations: " . $e->getMessage());
        return [];
    }
}

/**
 * Recherche des commandes
 */
function searchCommandes($terme, $shop_pdo) {
    try {
        // Vérifier si la table commandes existe
        $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'commandes'");
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Essayer avec commandes_pieces si commandes n'existe pas
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'commandes_pieces'");
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Utiliser commandes_pieces
                $sql = "SELECT cp.id, cp.reference, cp.date_creation as date_commande, 
                               0 as montant, cp.statut,
                               CONCAT(cl.nom, ' ', cl.prenom) as client_nom
                        FROM commandes_pieces cp
                        JOIN clients cl ON cp.client_id = cl.id
                        WHERE cp.reference LIKE ? 
                        OR cp.nom_piece LIKE ?
                        OR cl.nom LIKE ? 
                        OR cl.prenom LIKE ?
                        ORDER BY cp.date_creation DESC 
                        LIMIT 10";
            } else {
                // Aucune table de commandes trouvée
                return [];
            }
        } else {
            // Utiliser la table commandes standard
            $sql = "SELECT c.id, c.reference, c.date_commande, c.montant, c.statut,
                           CONCAT(cl.nom, ' ', cl.prenom) as client_nom
                    FROM commandes c
                    JOIN clients cl ON c.client_id = cl.id
                    WHERE c.reference LIKE ? 
                    OR cl.nom LIKE ? 
                    OR cl.prenom LIKE ?
                    ORDER BY c.date_commande DESC 
                    LIMIT 10";
        }
                
        $stmt = $shop_pdo->prepare($sql);
        $terme_wildcard = "%{$terme}%";
        // Déterminer le nombre de paramètres selon la requête
        if (strpos($sql, 'commandes_pieces') !== false) {
            $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard, $terme_wildcard]);
        } else {
            $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur searchCommandes: " . $e->getMessage());
        return [];
    }
}
?> 