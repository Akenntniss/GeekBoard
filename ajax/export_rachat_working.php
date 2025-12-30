<?php
// Export rachat fonctionnel - Version simplifiée

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Connexion dynamique à la base du shop actuel
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Récupérer l'ID si fourni
    $rachat_id = $_GET['id'] ?? null;
    
    // Préparer la requête
    if ($rachat_id) {
        $stmt = $pdo->prepare('SELECT * FROM rachat_appareils WHERE id = ?');
        $stmt->execute([$rachat_id]);
        $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query('SELECT * FROM rachat_appareils ORDER BY id DESC');
        $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Headers pour téléchargement HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="rachats-'.date('Ymd-His').'.html"');
    
    // Générer le HTML
    $title = $rachat_id ? 'Rachat #' . $rachat_id : 'Liste des rachats';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>' . $title . '</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Type Appareil</th>
            <th>Modèle</th>
            <th>Prix</th>
            <th>Date</th>
            <th>Fonctionnel</th>
        </tr>';

    foreach ($rachats as $rachat) {
        echo '<tr>
            <td>' . htmlspecialchars($rachat['id'] ?? '') . '</td>
            <td>' . htmlspecialchars($rachat['type_appareil'] ?? '') . '</td>
            <td>' . htmlspecialchars($rachat['modele'] ?? '') . '</td>
            <td>' . htmlspecialchars($rachat['prix'] ?? '0') . ' €</td>
            <td>' . htmlspecialchars($rachat['date_rachat'] ?? '') . '</td>
            <td>' . (($rachat['fonctionnel'] ?? 0) ? 'Oui' : 'Non') . '</td>
        </tr>';
    }

    echo '</table>
    <p><strong>Total: ' . count($rachats) . ' rachat(s)</strong></p>
</body>
</html>';

} catch (Exception $e) {
    echo 'Erreur: ' . $e->getMessage();
    echo '<br>Trace: ' . $e->getTraceAsString();
}
?>
