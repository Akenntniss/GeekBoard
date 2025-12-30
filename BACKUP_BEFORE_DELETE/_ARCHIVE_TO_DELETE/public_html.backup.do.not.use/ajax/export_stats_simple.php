<?php
/**
 * API pour exporter les données statistiques en CSV
 */

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

try {
    // Obtenir la connexion à la base de données de la boutique
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion PDO existe
    if (!isset($shop_pdo) || $shop_pdo === null) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Récupérer les paramètres
    $type = $_GET['type'] ?? '';
    $period = $_GET['period'] ?? 'day';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Valider les paramètres
    $validTypes = ['nouvelles_reparations', 'reparations_effectuees', 'reparations_restituees', 'devis_envoyes'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Type de statistique invalide');
    }
    
    $validPeriods = ['day', 'week', 'month'];
    if (!in_array($period, $validPeriods)) {
        throw new Exception('Période invalide');
    }
    
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('Format de date invalide');
    }
    
    // Configuration des noms
    $typeNames = [
        'nouvelles_reparations' => 'Nouvelles réparations',
        'reparations_effectuees' => 'Réparations effectuées', 
        'reparations_restituees' => 'Réparations restituées',
        'devis_envoyes' => 'Devis envoyés'
    ];
    
    $periodNames = [
        'day' => 'journalier',
        'week' => 'hebdomadaire',
        'month' => 'mensuel'
    ];
    
    // Nom du fichier
    $filename = sprintf(
        'statistiques_%s_%s_%s.csv',
        str_replace(' ', '_', strtolower($typeNames[$type])),
        $periodNames[$period],
        $date
    );
    
    // Headers pour le téléchargement CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Créer le contenu CSV
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes du CSV
    fputcsv($output, [
        'Type de statistique',
        'Période',
        'Date de référence',
        'Date générée'
    ], ';');
    
    fputcsv($output, [
        $typeNames[$type],
        $periodNames[$period],
        $date,
        date('Y-m-d H:i:s')
    ], ';');
    
    // Ligne vide
    fputcsv($output, [], ';');
    
    // En-têtes des données
    fputcsv($output, ['Date', 'Nombre'], ';');
    
    // Générer des données d'exemple pour le moment
    for ($i = 7; $i >= 0; $i--) {
        $currentDate = new DateTime($date);
        $currentDate->modify("-{$i} days");
        
        fputcsv($output, [
            $currentDate->format('d/m/Y'),
            rand(0, 10)
        ], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Erreur dans export_stats.php: " . $e->getMessage());
    
    // Retourner une erreur HTTP
    http_response_code(400);
    echo "Erreur: " . $e->getMessage();
}
?>
