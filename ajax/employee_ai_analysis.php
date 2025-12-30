<?php
/**
 * API d'Analyse IA pour les employés
 * Génère une analyse complète des performances et données d'un employé
 * Utilise l'API Groq avec le modèle llama-3.1-8b-instant
 */

// Configuration - ACTIVER DEBUG TEMPORAIREMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// DEBUG: Log le début
error_log("=== EMPLOYEE AI ANALYSIS DEBUG ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Charger la configuration de session AVANT session_start
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';

// DEBUG: Vérifier si la session est déjà active
error_log("Session status avant: " . session_status());
error_log("Session ID avant: " . session_id());

// La session devrait déjà être démarrée par session_config.php
// Mais vérifions quand même
if (session_status() === PHP_SESSION_NONE) {
    error_log("Session non démarrée, démarrage manuel...");
    session_start();
}

error_log("Session status après: " . session_status());
error_log("Session ID après: " . session_id());
error_log("SESSION data: " . print_r($_SESSION, true));

// Charger les dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier l'authentification
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
error_log("User ID trouvé: " . ($user_id ? $user_id : "AUCUN"));

if (!$user_id) {
    error_log("ERREUR: Utilisateur non authentifié!");
    echo json_encode([
        'success' => false, 
        'error' => 'Non authentifié',
        'debug' => [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_keys' => array_keys($_SESSION ?? [])
        ]
    ]);
    exit;
}

// Configuration Groq
$GROQ_API_KEY = "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";
$GROQ_ENDPOINT = "https://api.groq.com/openai/v1/chat/completions";
$GROQ_MODEL = "llama-3.1-8b-instant";

// Récupérer les paramètres
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$user_prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';
$conversation_history = isset($_POST['conversation_history']) ? json_decode($_POST['conversation_history'], true) : [];

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID employé invalide']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    // Récupérer les données de l'employé
    $employeeData = getEmployeeFullData($pdo, $employee_id);
    
    if (!$employeeData) {
        echo json_encode(['success' => false, 'error' => 'Employé introuvable']);
        exit;
    }
    
    // Construire le contexte
    $context = buildEmployeeContext($employeeData);
    
    // Construire le prompt système
    $systemPrompt = buildSystemPrompt();
    
    // Construire les messages pour l'API
    $messages = [];
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    
    // Si c'est une nouvelle analyse (pas de conversation précédente)
    if (empty($conversation_history)) {
        $messages[] = [
            'role' => 'user', 
            'content' => "Voici les données complètes d'un employé à analyser :\n\n" . $context . "\n\n" .
                        ($user_prompt ?: "Génère une analyse complète et détaillée de cet employé avec des recommandations.")
        ];
    } else {
        // Reprendre la conversation existante
        foreach ($conversation_history as $msg) {
            $messages[] = $msg;
        }
        // Ajouter la nouvelle question
        $messages[] = ['role' => 'user', 'content' => $user_prompt];
    }
    
    // Appeler l'API Groq
    $response = callGroqAPI($messages);
    
    if ($response['success']) {
        // Mettre à jour l'historique
        $newHistory = $conversation_history;
        if (empty($newHistory)) {
            $newHistory[] = [
                'role' => 'user',
                'content' => "Données employé:\n" . $context . "\n\nQuestion: " . ($user_prompt ?: "Analyse complète")
            ];
        } else {
            $newHistory[] = ['role' => 'user', 'content' => $user_prompt];
        }
        $newHistory[] = ['role' => 'assistant', 'content' => $response['content']];
        
        echo json_encode([
            'success' => true,
            'analysis' => $response['content'],
            'employee_name' => $employeeData['user']['full_name'] ?? 'Employé',
            'conversation_history' => $newHistory,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $response['error']]);
    }
    
} catch (Exception $e) {
    error_log("Employee AI Analysis Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'analyse: ' . $e->getMessage()]);
}

/**
 * Récupère toutes les données d'un employé
 */
function getEmployeeFullData($pdo, $employee_id) {
    $data = [];
    
    // Informations utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$employee_id]);
    $data['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data['user']) {
        return null;
    }
    
    // Statistiques de réparations (derniers 30 jours et total)
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN action_type = 'changement_statut' 
                    AND (statut_apres LIKE '%effectue%' OR statut_apres LIKE '%termine%') 
                    THEN reparation_id END) as reparations_terminees,
                COUNT(DISTINCT CASE WHEN action_type = 'changement_statut' 
                    AND (statut_apres LIKE '%effectue%' OR statut_apres LIKE '%termine%')
                    AND date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    THEN reparation_id END) as reparations_30j,
                COUNT(DISTINCT CASE WHEN action_type = 'demarrage' THEN reparation_id END) as reparations_demarrees,
                MIN(date_action) as premiere_activite,
                MAX(date_action) as derniere_activite
            FROM reparation_logs 
            WHERE employe_id = ?
        ");
        $stmt->execute([$employee_id]);
        $data['repair_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['repair_stats'] = [];
    }
    
    // Dernières réparations avec détails
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.appareil, r.probleme, r.statut, r.date_creation, r.date_modification,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            INNER JOIN reparation_logs rl ON r.id = rl.reparation_id
            WHERE rl.employe_id = ?
            GROUP BY r.id
            ORDER BY r.date_modification DESC
            LIMIT 10
        ");
        $stmt->execute([$employee_id]);
        $data['recent_repairs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['recent_repairs'] = [];
    }
    
    // Heures travaillées (time tracking)
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status = 'completed' THEN work_duration ELSE 0 END) as heures_totales,
                SUM(CASE WHEN status = 'completed' AND DATE_FORMAT(clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') 
                    THEN work_duration ELSE 0 END) as heures_mois_courant,
                COUNT(DISTINCT DATE(clock_in)) as jours_travailles,
                COUNT(*) as total_pointages,
                AVG(CASE WHEN status = 'completed' THEN work_duration END) as moyenne_heures_jour
            FROM time_tracking 
            WHERE user_id = ?
        ");
        $stmt->execute([$employee_id]);
        $data['time_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['time_stats'] = [];
    }
    
    // Notes managériales
    try {
        $stmt = $pdo->prepare("
            SELECT note_type, title, description, severity, date_incident
            FROM employee_notes 
            WHERE employee_id = ?
            ORDER BY date_incident DESC
            LIMIT 5
        ");
        $stmt->execute([$employee_id]);
        $data['notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['notes'] = [];
    }
    
    // Tâches
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_taches,
                COUNT(CASE WHEN statut = 'termine' THEN 1 END) as taches_terminees,
                COUNT(CASE WHEN statut = 'en_cours' THEN 1 END) as taches_en_cours,
                COUNT(CASE WHEN statut = 'a_faire' THEN 1 END) as taches_a_faire
            FROM taches 
            WHERE employe_id = ?
        ");
        $stmt->execute([$employee_id]);
        $data['task_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['task_stats'] = [];
    }
    
    return $data;
}

/**
 * Construit le contexte textuel pour l'IA
 */
function buildEmployeeContext($data) {
    $context = "";
    
    // Infos employé
    $user = $data['user'];
    $context .= "=== INFORMATIONS EMPLOYÉ ===\n";
    $context .= "Nom complet: " . ($user['full_name'] ?? $user['nom'] . ' ' . $user['prenom']) . "\n";
    $context .= "Rôle: " . ($user['role'] ?? 'Non défini') . "\n";
    $context .= "Email: " . ($user['email'] ?? 'Non renseigné') . "\n";
    $context .= "Téléphone: " . ($user['telephone'] ?? 'Non renseigné') . "\n";
    $context .= "Date de création du compte: " . ($user['created_at'] ?? 'Inconnue') . "\n\n";
    
    // Statistiques réparations
    if (!empty($data['repair_stats'])) {
        $stats = $data['repair_stats'];
        $context .= "=== STATISTIQUES RÉPARATIONS ===\n";
        $context .= "Réparations terminées (total): " . ($stats['reparations_terminees'] ?? 0) . "\n";
        $context .= "Réparations terminées (30 derniers jours): " . ($stats['reparations_30j'] ?? 0) . "\n";
        $context .= "Réparations démarrées: " . ($stats['reparations_demarrees'] ?? 0) . "\n";
        $context .= "Première activité: " . ($stats['premiere_activite'] ?? 'N/A') . "\n";
        $context .= "Dernière activité: " . ($stats['derniere_activite'] ?? 'N/A') . "\n\n";
    }
    
    // Dernières réparations
    if (!empty($data['recent_repairs'])) {
        $context .= "=== 10 DERNIÈRES RÉPARATIONS ===\n";
        foreach ($data['recent_repairs'] as $repair) {
            $client = trim(($repair['client_prenom'] ?? '') . ' ' . ($repair['client_nom'] ?? ''));
            $context .= "- #" . $repair['id'] . " | " . ($repair['appareil'] ?? 'N/A') . " | " . 
                       ($repair['statut'] ?? 'N/A') . " | Client: " . ($client ?: 'N/A') . "\n";
        }
        $context .= "\n";
    }
    
    // Heures travaillées
    if (!empty($data['time_stats'])) {
        $stats = $data['time_stats'];
        $context .= "=== TEMPS DE TRAVAIL ===\n";
        $context .= "Heures totales travaillées: " . round($stats['heures_totales'] ?? 0, 1) . "h\n";
        $context .= "Heures ce mois-ci: " . round($stats['heures_mois_courant'] ?? 0, 1) . "h\n";
        $context .= "Jours travaillés: " . ($stats['jours_travailles'] ?? 0) . "\n";
        $context .= "Moyenne heures/jour: " . round($stats['moyenne_heures_jour'] ?? 0, 1) . "h\n";
        $context .= "Total pointages: " . ($stats['total_pointages'] ?? 0) . "\n\n";
    }
    
    // Tâches
    if (!empty($data['task_stats'])) {
        $stats = $data['task_stats'];
        $context .= "=== STATISTIQUES TÂCHES ===\n";
        $context .= "Total tâches: " . ($stats['total_taches'] ?? 0) . "\n";
        $context .= "Tâches terminées: " . ($stats['taches_terminees'] ?? 0) . "\n";
        $context .= "Tâches en cours: " . ($stats['taches_en_cours'] ?? 0) . "\n";
        $context .= "Tâches à faire: " . ($stats['taches_a_faire'] ?? 0) . "\n\n";
    }
    
    // Notes managériales
    if (!empty($data['notes'])) {
        $context .= "=== NOTES MANAGÉRIALES ===\n";
        foreach ($data['notes'] as $note) {
            $context .= "[" . strtoupper($note['note_type'] ?? 'NOTE') . " - " . strtoupper($note['severity'] ?? 'info') . "]\n";
            $context .= "Titre: " . ($note['title'] ?? 'Sans titre') . "\n";
            if (!empty($note['description'])) {
                $context .= "Description: " . $note['description'] . "\n";
            }
            $context .= "Date: " . ($note['date_incident'] ?? 'N/A') . "\n\n";
        }
    }
    
    return $context;
}

/**
 * Construit le prompt système pour l'IA
 */
function buildSystemPrompt() {
    return "Tu es un assistant IA expert en gestion RH et analyse de performance pour un magasin de réparation électronique (téléphones, tablettes, ordinateurs).

Tu analyses les données des employés (techniciens et administrateurs) pour fournir des insights pertinents.

Tes analyses doivent être :
- 🎯 Précises et basées sur les données fournies
- 📊 Structurées avec des sections claires
- 💡 Avec des recommandations concrètes
- 🌟 Positives mais honnêtes sur les axes d'amélioration
- 📝 Avec des emojis pour rendre l'analyse plus visuelle

Utilise le français pour toutes tes réponses.

Quand tu analyses un employé, structure ta réponse ainsi :
1. 📋 Résumé du profil
2. 📈 Points forts identifiés
3. ⚠️ Points d'attention
4. 💡 Recommandations
5. 🎯 Objectifs suggérés

Tu peux aussi répondre à des questions spécifiques sur l'employé en te basant sur les données fournies.";
}

/**
 * Appelle l'API Groq
 */
function callGroqAPI($messages) {
    global $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL;
    
    $data = [
        'model' => $GROQ_MODEL,
        'messages' => $messages,
        'temperature' => 0.4,
        'max_tokens' => 2500,
        'top_p' => 0.95
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GROQ_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $GROQ_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['success' => false, 'error' => 'Erreur de connexion: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        return ['success' => false, 'error' => "Erreur API (HTTP $http_code)"];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Réponse invalide de l\'API'];
    }
    
    if (!isset($decoded['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Format de réponse inattendu'];
    }
    
    return ['success' => true, 'content' => $decoded['choices'][0]['message']['content']];
}
