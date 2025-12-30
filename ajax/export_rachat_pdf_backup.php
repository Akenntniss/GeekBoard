<?php
// Authentification retirée pour permettre l'export PDF

// Démarrer la session AVANT tout autre output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Vérifier si TCPDF existe
    if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
        throw new Exception('Autoload Composer non trouvé');
    }
    
    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../config/database.php';

    // Initialiser la connexion à la base de données
    if (!function_exists('getShopDBConnection')) {
        require_once __DIR__.'/../functions.php';
    }

    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion fonctionne
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }

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

    // Vérifier si TCPDF est disponible
    if (!class_exists('TCPDF')) {
        throw new Exception('TCPDF non disponible');
    }

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('GeekBoard');
    $pdf->SetTitle($rachat_id ? 'Rachat #' . $rachat_id : 'Liste des rachats');
    $pdf->AddPage();

    $title = $rachat_id ? 'Rachat #' . $rachat_id : 'Liste des rachats';
    $html = '<h1>' . $title . '</h1>
    <table border="1">
    <tr><th>ID</th><th>Type Appareil</th><th>Modèle</th><th>Prix</th><th>Date</th><th>Fonctionnel</th></tr>';

    foreach ($rachats as $rachat) {
        $html .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s €</td><td>%s</td><td>%s</td></tr>',
            htmlspecialchars($rachat['id'] ?? ''),
            htmlspecialchars($rachat['type_appareil'] ?? $rachat['marque'] ?? ''),
            htmlspecialchars($rachat['modele'] ?? ''),
            htmlspecialchars($rachat['prix'] ?? '0'),
            htmlspecialchars($rachat['date_rachat'] ?? $rachat['date_achat'] ?? ''),
            ($rachat['fonctionnel'] ?? 0) ? 'Oui' : 'Non'
        );
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('rachats-'.date('Ymd-His').'.pdf', 'D');

} catch (Exception $e) {
    // Afficher l'erreur pour debug
    echo "Erreur: " . $e->getMessage();
    echo "<br>Trace: " . $e->getTraceAsString();
}