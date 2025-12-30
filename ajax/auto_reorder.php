<?php
/**
 * API de Réapprovisionnement Automatique
 * Génère automatiquement des bons de commande pour les produits en stock faible
 * 
 * LOGIQUE ANTI-DOUBLONS:
 * - Cherche d'abord les commandes EN_ATTENTE existantes
 * - Si trouvée → UPDATE la quantité
 * - Sinon → INSERT nouvelle commande
 */

// Configuration de session
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Header JSON seulement si pas en CLI (cron)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

// Inclure les dépendances
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Logs
error_log("=== Début auto_reorder.php ===");

// Vérifier session utilisateur
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée - veuillez vous reconnecter'
    ]);
    exit;
}

// Vérifier shop_id
if (!isset($_SESSION['shop_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session invalide'
    ]);
    exit;
}

try {
    // Connexion DB
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données");
    }
    
    // === ÉTAPE 0: Vérifier si la colonne suivre_stock existe ===
    $checkColumn = $shop_pdo->query("SHOW COLUMNS FROM produits LIKE 'suivre_stock'");
    $hasFollowStock = $checkColumn->rowCount() > 0;
    
    // === ÉTAPE 1: Récupérer produits nécessitant réapprovisionnement ===
    if ($hasFollowStock) {
        // Avec suivi stock
        $stmt = $shop_pdo->prepare("
            SELECT 
                p.id,
                p.nom,
                p.reference,
                p.quantite,
                p.seuil_alerte,
                p.fournisseur_id,
                f.nom as fournisseur_nom
            FROM produits p
            LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
            WHERE p.suivre_stock = 1
              AND p.quantite < p.seuil_alerte
            ORDER BY p.quantite ASC, p.nom ASC
        ");
    } else {
        // Sans colonne suivre_stock - prendre tous les produits en alerte
        $stmt = $shop_pdo->prepare("
            SELECT 
                p.id,
                p.nom,
                p.reference,
                p.quantite,
                p.seuil_alerte,
                p.fournisseur_id,
                f.nom as fournisseur_nom
            FROM produits p
            LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
            WHERE p.quantite < p.seuil_alerte
            ORDER BY p.quantite ASC, p.nom ASC
        ");
    }
    
    $stmt->execute();
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Produits trouvés nécessitant réapprovisionnement: " . count($produits));
    
    // Compteurs pour rapport
    $created_count = 0;
    $updated_count = 0;
    $skipped_count = 0;
    $skipped_products = [];
    $processed_products = [];
    
    // === ÉTAPE 2: Obtenir/Créer client temporaire pour réappro auto ===
    $stmt = $shop_pdo->prepare("
        SELECT id FROM clients 
        WHERE nom = 'Réapprovisionnement Automatique' 
        LIMIT 1
    ");
    $stmt->execute();
    $auto_client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auto_client) {
        // Créer le client auto
        $stmt = $shop_pdo->prepare("
            INSERT INTO clients (nom, prenom, telephone, email, date_creation)
            VALUES ('Réapprovisionnement Automatique', 'Système', '', 'auto@system.local', NOW())
        ");
        $stmt->execute();
        $client_id = $shop_pdo->lastInsertId();
        error_log("Client automatique créé: ID " . $client_id);
    } else {
        $client_id = $auto_client['id'];
        error_log("Client automatique existant: ID " . $client_id);
    }
    
    // === ÉTAPE 2b: Obtenir/Créer fournisseur AUTRE pour produits sans fournisseur ===
    $stmt = $shop_pdo->prepare("
        SELECT id FROM fournisseurs 
        WHERE nom = 'AUTRE' 
        LIMIT 1
    ");
    $stmt->execute();
    $autre_fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$autre_fournisseur) {
        // Créer le fournisseur AUTRE
        $stmt = $shop_pdo->prepare("
            INSERT INTO fournisseurs (nom, email, created_at)
            VALUES ('AUTRE', 'aucun@fournisseur.com', NOW())
        ");
        $stmt->execute();
        $autre_fournisseur_id = $shop_pdo->lastInsertId();
        error_log("Fournisseur AUTRE créé: ID " . $autre_fournisseur_id);
    } else {
        $autre_fournisseur_id = $autre_fournisseur['id'];
        error_log("Fournisseur AUTRE existant: ID " . $autre_fournisseur_id);
    }
    
    // === ÉTAPE 3: Traiter chaque produit ===
    foreach ($produits as $produit) {
        // Si le produit n'a pas de fournisseur, utiliser AUTRE
        $fournisseur_id = !empty($produit['fournisseur_id']) 
            ? $produit['fournisseur_id'] 
            : $autre_fournisseur_id;
        
        $fournisseur_nom = !empty($produit['fournisseur_nom']) 
            ? $produit['fournisseur_nom'] 
            : 'AUTRE';
        
        // Calculer quantité nécessaire
        $quantite_necessaire = $produit['seuil_alerte'] - $produit['quantite'];
        
        if ($quantite_necessaire <= 0) {
            // Ne devrait pas arriver mais sécurité
            continue;
        }
        
        // === LOGIQUE ANTI-DOUBLONS (CRITIQUE) ===
        // Chercher commande EN_ATTENTE existante pour ce produit
        $stmt = $shop_pdo->prepare("
            SELECT id, quantite, reference
            FROM commandes_pieces
            WHERE nom_piece = :nom_piece
              AND fournisseur_id = :fournisseur_id
              AND statut = 'en_attente'
            ORDER BY date_creation DESC
            LIMIT 1
        ");
        
        $stmt->execute([
            'nom_piece' => $produit['nom'],
            'fournisseur_id' => $fournisseur_id
        ]);
        
        $commande_existante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($commande_existante) {
            // === CAS 1: UPDATE commande existante ===
            error_log("Commande existante trouvée (ID: {$commande_existante['id']}) - UPDATE quantité");
            
            $stmt = $shop_pdo->prepare("
                UPDATE commandes_pieces
                SET quantite = :quantite,
                    date_modification = NOW(),
                    notes = CONCAT(COALESCE(notes, ''), '\nQuantité ajustée automatiquement le ', NOW())
                WHERE id = :commande_id
            ");
            
            $stmt->execute([
                'quantite' => $quantite_necessaire,
                'commande_id' => $commande_existante['id']
            ]);
            
            $updated_count++;
            $processed_products[] = [
                'action' => 'updated',
                'nom' => $produit['nom'],
                'reference' => $commande_existante['reference'],
                'ancienne_quantite' => $commande_existante['quantite'],
                'nouvelle_quantite' => $quantite_necessaire,
                'fournisseur' => $fournisseur_nom
            ];
            
            error_log("Commande {$commande_existante['reference']} mise à jour: {$commande_existante['quantite']} → {$quantite_necessaire} unités");
            
        } else {
            // === CAS 2: INSERT nouvelle commande ===
            error_log("Aucune commande existante - INSERT nouvelle commande");
            
            // Générer référence unique
            $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO commandes_pieces (
                    reference,
                    client_id,
                    fournisseur_id,
                    nom_piece,
                    code_barre,
                    quantite,
                    statut,
                    notes,
                    date_creation
                ) VALUES (
                    :reference,
                    :client_id,
                    :fournisseur_id,
                    :nom_piece,
                    :code_barre,
                    :quantite,
                    'en_attente',
                    :notes,
                    NOW()
                )
            ");
            
            $notes = "Commande générée automatiquement par le système de réapprovisionnement.\n";
            $notes .= "Stock actuel: {$produit['quantite']}, Seuil alerte: {$produit['seuil_alerte']}";
            
            $stmt->execute([
                'reference' => $reference,
                'client_id' => $client_id,
                'fournisseur_id' => $fournisseur_id,
                'nom_piece' => $produit['nom'],
                'code_barre' => $produit['reference'],
                'quantite' => $quantite_necessaire,
                'notes' => $notes
            ]);
            
            $commande_id = $shop_pdo->lastInsertId();
            $created_count++;
            
            $processed_products[] = [
                'action' => 'created',
                'nom' => $produit['nom'],
                'reference' => $reference,
                'quantite' => $quantite_necessaire,
                'fournisseur' => $fournisseur_nom
            ];
            
            error_log("Nouvelle commande créée: {$reference} pour {$quantite_necessaire} unités");
        }
    }
    
    // === ÉTAPE 4: Retourner rapport ===
    $message = "Réapprovisionnement terminé : ";
    $message .= "{$created_count} commande(s) créée(s), ";
    $message .= "{$updated_count} commande(s) mise(s) à jour";
    
    if ($skipped_count > 0) {
        $message .= ", {$skipped_count} produit(s) ignoré(s)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'created' => $created_count,
        'updated' => $updated_count,
        'skipped' => $skipped_count,
        'total_products' => count($produits),
        'processed_products' => $processed_products,
        'skipped_products' => $skipped_products
    ]);
    
    error_log("=== Fin auto_reorder.php - Succès ===");
    
} catch (PDOException $e) {
    error_log("Erreur PDO auto_reorder: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("Erreur auto_reorder: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
