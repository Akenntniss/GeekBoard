<?php
/**
 * API de prévisualisation d'étiquette
 * Génère un aperçu d'un layout avec des données de test
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/label_manager.php';

header('Content-Type: application/json');

// Démarrer la session mais ne pas vérifier l'authentification
session_start();

try {
    // Récupérer le layout demandé
    $layoutId = isset($_GET['layout']) ? cleanInput($_GET['layout']) : '';
    
    if (empty($layoutId)) {
        throw new Exception("ID de layout manquant");
    }
    
    // Vérifier que le layout existe
    $layoutInfo = LabelManager::getLayoutInfo($layoutId);
    if (!$layoutInfo) {
        throw new Exception("Layout non trouvé");
    }
    
    // Données de test pour la prévisualisation
    $reparation = [
        'id' => 12345,
        'client_nom' => 'DUPONT',
        'client_prenom' => 'Jean',
        'client_telephone' => '06 12 34 56 78',
        'type_appareil' => 'iPhone',
        'modele' => '13 Pro Max',
        'description_probleme' => 'Écran cassé suite à une chute. L\'appareil fonctionne mais l\'écran tactile ne répond plus correctement sur la partie inférieure.',
        'mot_de_passe' => '1234',
        'prix_reparation' => 89.90,
        'statut' => 'En attente',
        'date_reception' => date('Y-m-d'),
        'notes_techniques' => 'Vérifier également le capteur de proximité qui semble défectueux.'
    ];
    
    // Charger le layout
    $html = LabelManager::loadLayout($layoutId, $reparation);
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'layout_info' => $layoutInfo
    ]);
    
} catch (Exception $e) {
    error_log("Erreur preview_label.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

