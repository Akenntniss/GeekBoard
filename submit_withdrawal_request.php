<?php
session_start();

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser la session du magasin
initializeShopSession();

header('Content-Type: application/json');

try {
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Vous devez être connecté pour effectuer un retrait.');
    }
    
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $user_id = $_SESSION['user_id'];
    $montant = isset($input['montant']) ? (float)$input['montant'] : 0;
    $methode_paiement = isset($input['methode_paiement']) ? cleanInput($input['methode_paiement']) : '';
    $details_paiement = isset($input['details_paiement']) ? cleanInput($input['details_paiement']) : '';

    // Validation
    if ($montant <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }

    if (empty($methode_paiement) || !in_array($methode_paiement, ['virement', 'paypal', 'especes'])) {
        throw new Exception('Méthode de paiement invalide');
    }

    if (empty($details_paiement)) {
        throw new Exception('Les détails de paiement sont obligatoires');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    // Fallback si getShopDBConnection() échoue
    if (!$shop_pdo && isset($_SESSION['shop_id'])) {
        $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
    }
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin.');
    }
    
    // Vérifier quelle base de données est utilisée
    try {
        $db_check = $shop_pdo->query("SELECT DATABASE() AS db_name");
        $db_info = $db_check->fetch(PDO::FETCH_ASSOC);
        $current_db = $db_info['db_name'] ?? 'inconnue';
        error_log("Submit withdrawal - User ID: $user_id, Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini') . ", DB: $current_db");
    } catch (Exception $e) {
        error_log("Impossible de vérifier la base de données: " . $e->getMessage());
    }

    // Vérifier le solde disponible - Essayer d'abord user_cagnotte, puis users
    $solde_disponible = 0;
    
    // Vérifier dans user_cagnotte
    try {
        // Vérifier d'abord si la table existe
        $table_check = $shop_pdo->query("SHOW TABLES LIKE 'user_cagnotte'");
        if ($table_check->rowCount() > 0) {
            $check_solde = $shop_pdo->prepare("
                SELECT solde_euros, solde_points 
                FROM user_cagnotte 
                WHERE user_id = ?
            ");
            $check_solde->execute([$user_id]);
            $cagnotte = $check_solde->fetch(PDO::FETCH_ASSOC);
            
            if ($cagnotte) {
                $solde_disponible = (float)$cagnotte['solde_euros'];
                error_log("Solde trouvé dans user_cagnotte: $solde_disponible€ pour user_id=$user_id");
            } else {
                error_log("Aucune entrée dans user_cagnotte pour user_id=$user_id");
            }
        } else {
            error_log("Table user_cagnotte n'existe pas dans cette base");
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification user_cagnotte: " . $e->getMessage());
    }
    
    // Si pas de solde dans user_cagnotte, vérifier dans users (peut être dans geekboard_general)
    if ($solde_disponible == 0) {
        try {
            // Vérifier d'abord si la table users existe dans la base du magasin
            $table_check = $shop_pdo->query("SHOW TABLES LIKE 'users'");
            $users_table_exists = $table_check->rowCount() > 0;
            
            if ($users_table_exists) {
                $check_users = $shop_pdo->prepare("
                    SELECT cagnotte, points_experience 
                    FROM users 
                    WHERE id = ?
                ");
                $check_users->execute([$user_id]);
                $user_data = $check_users->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data) {
                    $solde_users = (float)$user_data['cagnotte'];
                    $points_users = (int)$user_data['points_experience'];
                    
                    error_log("Données users trouvées dans base magasin: cagnotte=$solde_users€, points=$points_users");
                    
                    if ($solde_users > 0 || $points_users > 0) {
                        // Créer ou mettre à jour l'entrée dans user_cagnotte
                        try {
                            // Vérifier d'abord si l'entrée existe
                            $check_existing = $shop_pdo->prepare("SELECT id FROM user_cagnotte WHERE user_id = ?");
                            $check_existing->execute([$user_id]);
                            $existing = $check_existing->fetch(PDO::FETCH_ASSOC);
                            
                            if ($existing) {
                                // Mettre à jour
                                $update_cagnotte = $shop_pdo->prepare("
                                    UPDATE user_cagnotte 
                                    SET solde_euros = ?, solde_points = ?, total_gagne_euros = ?, total_gagne_points = ?
                                    WHERE user_id = ?
                                ");
                                $update_cagnotte->execute([$solde_users, $points_users, $solde_users, $points_users, $user_id]);
                                error_log("Cagnotte mise à jour dans user_cagnotte: $solde_users€");
                            } else {
                                // Créer
                                $create_cagnotte = $shop_pdo->prepare("
                                    INSERT INTO user_cagnotte (user_id, solde_euros, solde_points, total_gagne_euros, total_gagne_points)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $create_cagnotte->execute([$user_id, $solde_users, $points_users, $solde_users, $points_users]);
                                error_log("Cagnotte créée dans user_cagnotte: $solde_users€");
                            }
                            $solde_disponible = $solde_users;
                        } catch (PDOException $e) {
                            error_log("Erreur lors de la création/mise à jour user_cagnotte: " . $e->getMessage());
                            // Utiliser directement la valeur de users
                            $solde_disponible = $solde_users;
                        }
                    } else {
                        throw new Exception('Aucune cagnotte trouvée. Vous devez compléter des missions pour gagner des récompenses.');
                    }
                } else {
                    error_log("Utilisateur ID $user_id non trouvé dans la table users de la base magasin");
                    // Ne pas lancer d'erreur ici, on va essayer de chercher dans user_cagnotte avec tous les user_id possibles
                }
            } else {
                error_log("Table users n'existe pas dans la base du magasin");
            }
            
            // Si toujours pas de solde, essayer de trouver n'importe quelle entrée dans user_cagnotte pour cet utilisateur
            // (au cas où le user_id serait différent)
            if ($solde_disponible == 0) {
                error_log("Tentative de recherche alternative dans user_cagnotte...");
                // Ne pas lancer d'erreur, on va utiliser une valeur par défaut ou demander à l'utilisateur
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification users: " . $e->getMessage());
            // Ne pas lancer d'erreur ici, on continue
        }
    }
    
    if ($solde_disponible <= 0) {
        // Message d'erreur plus détaillé
        $error_msg = "Aucune cagnotte trouvée pour votre compte (ID: $user_id). ";
        $error_msg .= "Vérifiez que vous avez complété des missions et que votre solde est disponible.";
        error_log("ERREUR RETRAIT - User ID: $user_id, Solde disponible: $solde_disponible");
        throw new Exception($error_msg);
    }

    if ($montant > $solde_disponible) {
        throw new Exception("Montant demandé ($montant€) supérieur au solde disponible ($solde_disponible€)");
    }

    // Vérifier s'il y a déjà une demande en attente
    $check_pending = $shop_pdo->prepare("
        SELECT COUNT(*) 
        FROM demandes_retrait 
        WHERE user_id = ? AND statut = 'en_attente'
    ");
    $check_pending->execute([$user_id]);
    $pending_count = $check_pending->fetchColumn();

    if ($pending_count > 0) {
        throw new Exception('Vous avez déjà une demande de retrait en attente de validation');
    }

    // Créer la demande de retrait
    $insert_sql = "
        INSERT INTO demandes_retrait (user_id, montant, methode_paiement, details_paiement, statut)
        VALUES (?, ?, ?, ?, 'en_attente')
    ";
    $insert_stmt = $shop_pdo->prepare($insert_sql);
    $insert_stmt->execute([$user_id, $montant, $methode_paiement, $details_paiement]);
    
    error_log("Demande de retrait créée pour user_id=$user_id");

    $demande_id = $shop_pdo->lastInsertId();

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Demande de retrait soumise avec succès. Elle sera traitée par un administrateur.',
        'demande_id' => $demande_id
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la soumission de demande de retrait: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la soumission de demande de retrait: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
