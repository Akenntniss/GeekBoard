<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$_SESSION['shop_id'] = 1;
initializeShopSession();
$shop_pdo = getShopDBConnection();

echo "=== TEMPLATES SMS ===\n";

$stmt = $shop_pdo->query("SELECT nom, est_actif FROM sms_templates ORDER BY nom");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($templates as $template) {
    $status = $template['est_actif'] ? 'ACTIF' : 'INACTIF';
    echo "- {$template['nom']} ({$status})\n";
}

echo "\n=== TEMPLATES RECHERCHÉS ===\n";
$recherches = ['Devis en attente - Rappel', 'Devis expiré - Gardiennage', 'Relance Devis'];
foreach ($recherches as $nom) {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM sms_templates WHERE nom = ? AND est_actif = 1");
    $stmt->execute([$nom]);
    $result = $stmt->fetch();
    $found = $result['count'] > 0 ? 'TROUVÉ' : 'MANQUANT';
    echo "- $nom: $found\n";
}
?>
