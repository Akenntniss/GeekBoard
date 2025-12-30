<?php
/**
 * API pour générer une analyse IA
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin
initializeShopSession();
require_once __DIR__ . '/../includes/kpi_ai_analysis.php';

header('Content-Type: application/json');

// Vérifier authentification
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

try {
    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['profile_id'])) {
        throw new Exception('Données invalides');
    }
    
    $profileId = $input['profile_id'];
    $kpiData = $input['kpi_data'] ?? [];
    $selectedKpiData = $input['selected_kpi_data'] ?? null;
    $employeeId = $input['employee_id'] ?? null;
    $employeeNotes = $input['employee_notes'] ?? null;
    $customPrompt = $input['custom_prompt'] ?? null;  // Prompt personnalisé
    $dateStart = $input['date_start'] ?? null;
    $dateEnd = $input['date_end'] ?? null;
    
    // Initialiser l'analyseur IA
    $pdo = getShopDBConnection();
    $analyzer = new KPIAIAnalyzer($pdo);
    
    // Générer l'analyse avec les KPI sélectionnés
    $analysis = $analyzer->generateAnalysis($profileId, $kpiData, $employeeId, $dateStart, $dateEnd, $selectedKpiData, $employeeNotes, $customPrompt);
    
    if (isset($analysis['error'])) {
        throw new Exception($analysis['message']);
    }
    
    echo json_encode(['success' => true, 'data' => $analysis]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
