<?php
// Export simple sans TCPDF - Alternative de secours

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__.'/../config/database.php';
    
    // Initialiser la connexion à la base de données
    if (!function_exists('getShopDBConnection')) {
        require_once __DIR__.'/../functions.php';
    }

    // Démarrer la session pour la détection du magasin
    session_start();

    $shop_pdo = getShopDBConnection();

    // Vérifier s'il y a un ID spécifique à exporter
    $rachat_id = $_GET['id'] ?? null;

    // Vérifier quelle table existe
    $tables_to_check = ['rachats', 'rachat_appareils'];
    $table_name = null;
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                $table_name = $table;
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    if (!$table_name) {
        throw new Exception('Aucune table de rachats trouvée (rachats ou rachat_appareils)');
    }

    if ($rachat_id) {
        // Exporter un rachat spécifique
        $stmt = $shop_pdo->prepare("SELECT * FROM {$table_name} WHERE id = ?");
        $stmt->execute([$rachat_id]);
        $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Exporter tous les rachats
        $stmt = $shop_pdo->query("SELECT * FROM {$table_name}");
        $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Headers pour forcer le téléchargement HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="rachats-'.date('Ymd-His').'.html"');

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
        echo sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s €</td><td>%s</td><td>%s</td></tr>',
            htmlspecialchars($rachat['id'] ?? ''),
            htmlspecialchars($rachat['type_appareil'] ?? $rachat['marque'] ?? ''),
            htmlspecialchars($rachat['modele'] ?? ''),
            htmlspecialchars($rachat['prix'] ?? '0'),
            htmlspecialchars($rachat['date_rachat'] ?? $rachat['date_achat'] ?? ''),
            ($rachat['fonctionnel'] ?? 0) ? 'Oui' : 'Non'
        );
    }

    echo '</table>
    </body>
    </html>';

} catch (Exception $e) {
    // Afficher l'erreur pour debug
    echo "Erreur: " . $e->getMessage();
    echo "<br>Trace: " . $e->getTraceAsString();
}
?>
