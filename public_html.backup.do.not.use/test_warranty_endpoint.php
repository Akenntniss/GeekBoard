<?php
// Script de test pour l'endpoint get_repair_details.php
session_start();

// Simuler une session valide
$_SESSION['shop_id'] = 63;
$_SESSION['user_id'] = 1;
$_GET['id'] = 1;

// Capturer la sortie
ob_start();

// Inclure l'endpoint
require_once 'config/database.php';

// Simuler l'appel à l'endpoint
$shop_pdo = getShopDBConnectionById(63);

$sql = "
    SELECT 
        r.*, 
        c.nom as client_nom, 
        c.prenom as client_prenom, 
        c.telephone as client_telephone, 
        c.email as client_email,
        s.nom as statut_nom,
        sc.couleur as statut_couleur,
        u.active_repair_id,
        g.id as garantie_id,
        g.date_debut as garantie_debut,
        g.date_fin as garantie_fin,
        g.statut as garantie_statut,
        g.duree_jours as garantie_duree,
        g.description_garantie as garantie_description,
        CASE 
            WHEN g.id IS NULL THEN 'aucune'
            WHEN g.statut = 'annulee' THEN 'annulee'
            WHEN g.date_fin < NOW() THEN 'expiree'
            WHEN DATEDIFF(g.date_fin, NOW()) <= 7 THEN 'expire_bientot'
            ELSE 'active'
        END as garantie_etat
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN statuts s ON r.statut = s.code
    LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
    LEFT JOIN users u ON u.id = ?
    LEFT JOIN garanties g ON r.id = g.reparation_id
    WHERE r.id = ?
";

$stmt = $shop_pdo->prepare($sql);
$stmt->execute([1, 1]);
$repair = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== TEST ENDPOINT GARANTIE ===\n";
echo "Réparation ID: " . $repair['id'] . "\n";
echo "Modèle: " . $repair['modele'] . "\n";
echo "Statut: " . $repair['statut'] . "\n";
echo "Garantie ID: " . ($repair['garantie_id'] ?? 'NULL') . "\n";
echo "Garantie État: " . ($repair['garantie_etat'] ?? 'NULL') . "\n";
echo "Garantie Début: " . ($repair['garantie_debut'] ?? 'NULL') . "\n";
echo "Garantie Fin: " . ($repair['garantie_fin'] ?? 'NULL') . "\n";
echo "Garantie Statut: " . ($repair['garantie_statut'] ?? 'NULL') . "\n";

echo "\n=== JSON RESPONSE ===\n";
echo json_encode([
    'success' => true,
    'repair' => $repair
], JSON_PRETTY_PRINT);
?>
