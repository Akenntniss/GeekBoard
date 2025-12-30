<?php
/**
 * API de Génération de Planning par IA
 * Utilise GROQ API (llama-3.1-8b-instant) pour générer des plannings optimisés
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Configuration
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier authentification
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Configuration GROQ
$GROQ_API_KEY = "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";
$GROQ_ENDPOINT = "https://api.groq.com/openai/v1/chat/completions";
$GROQ_MODEL = "llama-3.3-70b-versatile";

$action = $_POST['action'] ?? '';

try {
    $shop_pdo = getShopDBConnection();
    
    switch ($action) {
        case 'generate':
            handleGenerate($shop_pdo);
            break;
        
        case 'modify':
            handleModify($shop_pdo);
            break;
            
        case 'save':
            handleSave($shop_pdo);
            break;
            
        case 'save_config':
            handleSaveConfig($shop_pdo);
            break;
            
        case 'get_config':
            handleGetConfig($shop_pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
} catch (Exception $e) {
    error_log("Planning AI Generator Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Génère un planning via IA (prévisualisation, pas d'insert)
 */
function handleGenerate($pdo) {
    global $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL;
    
    // Récupérer les paramètres
    $month = $_POST['month'] ?? date('Y-m');
    $store_config = json_decode($_POST['store_config'] ?? '{}', true);
    $employees_config = json_decode($_POST['employees_config'] ?? '[]', true);
    $store_rules = json_decode($_POST['store_rules'] ?? '{}', true);
    
    if (empty($employees_config)) {
        echo json_encode(['success' => false, 'error' => 'Aucun employé configuré']);
        return;
    }
    
    // Récupérer les absences existantes si demandé
    $absences = [];
    if (!empty($store_rules['check_absences'])) {
        $absences = getExistingAbsences($pdo, $month);
    }
    
    // Récupérer les noms des employés
    $employee_ids = array_column($employees_config, 'id');
    $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE id IN ($placeholders)");
    $stmt->execute($employee_ids);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $employee_names = [];
    foreach ($employees as $emp) {
        $employee_names[$emp['id']] = $emp['full_name'] ?? $emp['username'];
    }
    
    // Ajouter les noms aux configs
    foreach ($employees_config as &$emp) {
        $emp['name'] = $employee_names[$emp['id']] ?? 'Employé #' . $emp['id'];
    }
    
    // Construire le prompt
    $systemPrompt = buildSystemPrompt($store_rules);
    $userPrompt = buildUserPrompt($month, $store_config, $employees_config, $store_rules, $absences);
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];
    
    // Appeler GROQ
    $response = callGroqAPI($messages, $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL);
    
    if (!$response['success']) {
        echo json_encode(['success' => false, 'error' => $response['error']]);
        return;
    }
    
    // Parser la réponse JSON
    $aiContent = $response['content'];
    
    // Extraire le JSON de la réponse
    $jsonMatch = [];
    if (preg_match('/\{[\s\S]*"schedules"[\s\S]*\}/m', $aiContent, $jsonMatch)) {
        $planningData = json_decode($jsonMatch[0], true);
        if ($planningData && isset($planningData['schedules'])) {
            // Ajouter les noms d'employés pour l'affichage
            foreach ($planningData['schedules'] as &$schedule) {
                $schedule['employee_name'] = $employee_names[$schedule['user_id']] ?? 'Inconnu';
            }
            echo json_encode([
                'success' => true,
                'schedules' => $planningData['schedules'],
                'month' => $month,
                'ai_response' => $aiContent
            ]);
            return;
        }
    }
    
    // Si pas de JSON valide trouvé
    echo json_encode([
        'success' => false, 
        'error' => 'L\'IA n\'a pas retourné un planning valide. Réessayez.',
        'ai_response' => $aiContent
    ]);
}

/**
 * Récupère les absences (congés, maladies) pour le mois donné
 */
function getExistingAbsences($pdo, $month) {
    try {
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.username, es.schedule_date, es.schedule_type 
            FROM employee_schedules es
            JOIN users u ON es.user_id = u.id
            WHERE es.schedule_date BETWEEN ? AND ? 
            AND es.schedule_type != 'work'
            ORDER BY es.schedule_date ASC
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching absences: " . $e->getMessage());
        return [];
    }
}

/**
 * Modifie un planning existant via conversation IA
 */
function handleModify($pdo) {
    global $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL;
    
    $message = $_POST['message'] ?? '';
    $current_schedules = json_decode($_POST['current_schedules'] ?? '[]', true);
    $month = $_POST['month'] ?? '';
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message de modification requis']);
        return;
    }
    
    if (empty($current_schedules)) {
        echo json_encode(['success' => false, 'error' => 'Aucun planning à modifier']);
        return;
    }
    
    // Construire le contexte du planning actuel
    $currentPlanningJson = json_encode(['schedules' => $current_schedules], JSON_PRETTY_PRINT);
    
    $systemPrompt = "Tu es un expert en planification RH. Tu vas modifier un planning existant selon les demandes de l'utilisateur.

RÈGLES:
1. Garde le maximum du planning original sauf les modifications demandées
2. Respecte toujours les contraintes de base (1 personne minimum)
3. Ajuste les heures de manière cohérente

RÉPONSE OBLIGATOIRE EN JSON avec exactement le même format que le planning reçu:
{
  \"schedules\": [
    {
      \"user_id\": <int>,
      \"date\": \"YYYY-MM-DD\",
      \"start_time\": \"HH:MM\",
      \"end_time\": \"HH:MM\",
      \"break_start\": \"HH:MM\",
      \"break_end\": \"HH:MM\",
      \"employee_name\": \"<nom>\"
    }
  ]
}

NE GÉNÈRE QUE DU JSON, AUCUN TEXTE.";

    $userPrompt = "Voici le planning actuel ($month):\n\n$currentPlanningJson\n\nMODIFICATION DEMANDÉE: $message\n\nRetourne le planning modifié en JSON.";
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];
    
    $response = callGroqAPI($messages, $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL);
    
    if (!$response['success']) {
        echo json_encode(['success' => false, 'error' => $response['error']]);
        return;
    }
    
    $aiContent = $response['content'];
    
    // Extraire le JSON (supporte le texte avant/après grâce au regex loose)
    // On cherche le premier objet JSON valide contenant "schedules"
    $jsonStart = strpos($aiContent, '{');
    $jsonEnd = strrpos($aiContent, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonStr = substr($aiContent, $jsonStart, $jsonEnd - $jsonStart + 1);
        $planningData = json_decode($jsonStr, true);
        
        if ($planningData && isset($planningData['schedules'])) {
             // Ajouter les noms d'employés pour l'affichage (répété ici car context response)
             // Note: En mode modify, on n'a peut-être pas $employee_names dispo si pas rechargé
             // Mais ce bloc est pour handleGenerate principalement.
             // Pour handleModify, le flux est similaire.
             
             echo json_encode([
                'success' => true,
                'schedules' => $planningData['schedules'],
                'ai_response' => $aiContent
            ]);
            return;
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'L\'IA n\'a pas retourné un planning valide.',
        'ai_response' => $aiContent
    ]);
}

/**
 * Sauvegarde le planning validé en BDD
 */
function handleSave($pdo) {
    $schedules = json_decode($_POST['schedules'] ?? '[]', true);
    
    if (empty($schedules)) {
        echo json_encode(['success' => false, 'error' => 'Aucun créneau à enregistrer']);
        return;
    }
    
    $count = 0;
    foreach ($schedules as $schedule) {
        $user_id = intval($schedule['user_id'] ?? 0);
        $date = $schedule['date'] ?? '';
        $start_time = $schedule['start_time'] ?? '';
        $end_time = $schedule['end_time'] ?? '';
        $break_start = $schedule['break_start'] ?? null;
        $break_end = $schedule['break_end'] ?? null;
        
        if (!$user_id || !$date || !$start_time || !$end_time) continue;
        
        // Supprimer l'existant
        $stmt = $pdo->prepare("DELETE FROM employee_schedules WHERE user_id = ? AND schedule_date = ?");
        $stmt->execute([$user_id, $date]);
        
        // Insérer le nouveau
        $stmt = $pdo->prepare("INSERT INTO employee_schedules 
            (user_id, schedule_date, start_time, end_time, break_start, break_end, schedule_type, created_by)
            VALUES (?, ?, ?, ?, ?, ?, 'work', ?)");
        $stmt->execute([
            $user_id, $date, $start_time, $end_time, 
            $break_start ?: null, $break_end ?: null,
            $_SESSION['user_id'] ?? null
        ]);
        $count++;
    }
    
    echo json_encode(['success' => true, 'message' => "$count créneaux enregistrés"]);
}

/**
 * Construit le prompt système
 */
function buildSystemPrompt($rules = []) {
    $stagger = !empty($rules['stagger_breaks']) ? "OUI" : "NON";
    $equity = !empty($rules['equity']) ? "OUI" : "NON";
    $min_staff = $rules['min_staff'] ?? 1;
    $max_days = $rules['max_consecutive_days'] ?? 6;
    
    return "Tu es un expert RH et mathématicien. Tu dois générer le planning OPTIMAL pour un magasin de réparation.

PROCESSUS DE RAISONNEMENT (OBLIGATOIRE):
Avant de générer le planning, tu dois RÉFLÉCHIR étape par étape dans ta réponse (Chain of Thought):
0. Identifie le nombre de jours et de semaines dans le mois.
1. Analyse les contraintes de chaque employé (heures/sem, repos, école).
2. Vérifie les absences bloquantes.
3. Groupe les contraintes par jour (ex: 'Samedi: besoin de 2 personnes, X ne peut pas').
4. Valide que chaque jour respecte le minimum de $min_staff personnes À CHAQUE INSTANT (pause incluse si décalée).

VERIFICATION OBLIGATOIRE FINAL (Checklist avec COMPTEURS EXPLICITES):
Avant d'écrire le JSON, tu dois lister pour CHAQUE employé ET CHAQUE SEMAINE:

EXEMPLE de format attendu:
SEMAINE 1 (01/03-07/03):
  Adam: Mer(9h) + Jeu(9h) + Ven(9h) + Sam(9h) = 36h vs 35h contrat ✓ (±2h ok)
  Benjamin: Mer(7h,avec Adam) + Jeu(7h,avec Adam) = 14h vs 28h contrat ✗ ERREUR → RECALCUL

Puis:
- Jours de repos respectés ? (Adam: 2j/sem, Benjamin: 3j/sem)
- Contraintes École/Apprenti respectées ?

Si UNE SEULE règle n'est pas respectée, RECALCULE TOUT avant de donner le JSON.

RÈGLES CRITIQUES:
RÈGLES CRITIQUES:
1. RESPECT ABSOLU des heures/semaine (±2h) et jours de repos. IMPORTANT: Le compteur d'heures se RÉINITIALISE chaque Lundi. Si contrat 35h, c'est 35h CHAQUE semaine, pas 35h pour le mois.
2. CONTINUITÉ DE SERVICE: Toujours au moins $min_staff employé(s) présent(s) en magasin.
   - Si 'Pauses Décalées' = OUI : Interdiction de pause simultanée si ça laisse le magasin avec < $min_staff.
3. ÉQUITÉ ($equity): Répartir équitablement les samedis et les fermetures.
4. SANTÉ: Max $max_days jours de travail consécutifs.
5. APPRENTIS: Un apprenti ne doit JAMAIS être seul (toujours avec un confirmé).
6. SHIFTS: Durée respectant les bornes min/max définies.
7. REPOS CONSÉCUTIFS: Si demandé par l'employé, grouper ses jours de repos.

FORMAT DE RÉPONSE:
[Raisonnement détaillé ici...]

```json
{
  \"schedules\": [
    {
      \"user_id\": 1,
      \"date\": \"2026-01-05\",
      \"start_time\": \"10:00\",
      \"end_time\": \"19:00\",
      \"break_start\": \"13:00\",
      \"break_end\": \"14:00\"
    }
  ]
}
```

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📚 EXEMPLE DE PLANNING VALIDE (Few-Shot Learning)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mois: Février 2025 | Magasin: Mar-Sam 09:00-19:00 | Pause: 1h30 (11:30-15:00)

🧑 Adam (Contrat: 35h/sem, 2j repos, PAS apprenti):
  SEMAINE 1 (01/02-07/02):
    - Mar 04/02: 09:00-19:00 (pause 13:00-14:30) = 9.5h
    - Mer 05/02: 09:00-19:00 (pause 13:00-14:30) = 9.5h
    - Jeu 06/02: 09:00-19:00 (pause 13:00-14:30) = 9.5h
    - Ven 07/02: 09:00-19:00 (pause 13:00-14:30) = 9.5h
    TOTAL: 38h ✓ (35h ±2h)

👨‍🏫 Benjamin (Contrat: 28h/sem, 3j repos, APPRENTI, ÉCOLE Lun-Mar):
  SEMAINE 1 (01/02-07/02):
    - Mer 05/02: 09:00-16:00 (avec Adam ✓) = 7h
    - Jeu 06/02: 09:00-16:00 (avec Adam ✓) = 7h
    - Ven 07/02: 09:00-16:00 (avec Adam ✓) = 7h
    - Sam 08/02: 09:00-16:00 (avec Adam ✓) = 7h
    TOTAL: 28h ✓ | JAMAIS SEUL ✓ | ÉCOLE respectée ✓

JSON généré:
```json
{
  \"schedules\": [
    {\"user_id\": 1, \"date\": \"2025-02-04\", \"start_time\": \"09:00\", \"end_time\": \"19:00\", \"break_start\": \"13:00\", \"break_end\": \"14:30\"},
    {\"user_id\": 1, \"date\": \"2025-02-05\", \"start_time\": \"09:00\", \"end_time\": \"19:00\", \"break_start\": \"13:00\", \"break_end\": \"14:30\"},
    {\"user_id\": 1, \"date\": \"2025-02-06\", \"start_time\": \"09:00\", \"end_time\": \"19:00\", \"break_start\": \"13:00\", \"break_end\": \"14:30\"},
    {\"user_id\": 1, \"date\": \"2025-02-07\", \"start_time\": \"09:00\", \"end_time\": \"19:00\", \"break_start\": \"13:00\", \"break_end\": \"14:30\"},
    {\"user_id\": 2, \"date\": \"2025-02-05\", \"start_time\": \"09:00\", \"end_time\": \"16:00\", \"break_start\": null, \"break_end\": null},
    {\"user_id\": 2, \"date\": \"2025-02-06\", \"start_time\": \"09:00\", \"end_time\": \"16:00\", \"break_start\": null, \"break_end\": null},
    {\"user_id\": 2, \"date\": \"2025-02-07\", \"start_time\": \"09:00\", \"end_time\": \"16:00\", \"break_start\": null, \"break_end\": null},
    {\"user_id\": 2, \"date\": \"2025-02-08\", \"start_time\": \"09:00\", \"end_time\": \"16:00\", \"break_start\": null, \"break_end\": null}
  ]
}
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 Maintenant, génère le planning demandé avec la MÊME RIGUEUR et le MÊME FORMAT.";
}

/**
 * Construit le prompt utilisateur avec les contraintes
 */
function buildUserPrompt($month, $store, $employees, $rules = [], $absences = []) {
    $year_month = $month; 
    setlocale(LC_TIME, 'fr_FR.UTF-8');
    $first_day = new DateTime($month . '-01');
    $last_day = clone $first_day;
    $last_day->modify('last day of this month');
    
    $prompt = "Génère un planning COMPLET pour CHAQUE SEMAINE de : " . $first_day->format('F Y') . " ($month).\n";
    $prompt .= "IMPORTANT: Tu dois générer les créneaux pour L'INTÉGRALITÉ du mois, du 1er au dernier jour (" . $last_day->format('d') . "). Ne t'arrête pas à la première semaine.\n\n";
    
    // Génération du calendrier textuel pour aider l'IA
    $prompt .= "=== CALENDRIER DU MOIS (STRUCTURE À SUIVRE) ===\n";
    $current = clone $first_day;
    $week_num = 1;
    while ($current <= $last_day) {
        $week_start = clone $current;
        // Trouver la fin de la semaine (Dimanche ou fin de mois)
        $week_end = clone $current;
        $week_end->modify('next sunday'); 
        if ($week_end > $last_day) $week_end = clone $last_day; // Ne pas dépasser la fin du mois
        if ($current->format('N') == 7) $week_end = clone $current; // Si on commence un dimanche
        
        $prompt .= "SEMAINE $week_num (" . $current->format('d/m') . " au " . $week_end->format('d/m') . "):\n";
        
        // Lister les jours
        $w_day = clone $current;
        while ($w_day <= $week_end) {
             // Traduire le jour en français
             $day_fr = str_replace(
                ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
                $w_day->format('D')
             );
             $prompt .= "  - $day_fr " . $w_day->format('d/m') . "\n";
             $w_day->modify('+1 day');
        }
        $prompt .= "\n";
        
        $current = clone $week_end;
        $current->modify('+1 day');
        $week_num++;
    }
    $prompt .= "================================================\n\n";
    
    $prompt .= "=== RÈGLES GLOBALES ===\n";
    $prompt .= "- Effectif Min: " . ($rules['min_staff'] ?? 1) . " pers.\n";
    $prompt .= "- Pauses Décalées: " . (!empty($rules['stagger_breaks']) ? "OUI (Couverture continue requise)" : "NON (Fermeture possible)") . "\n";
    $prompt .= "- Équité: " . (!empty($rules['equity']) ? "OUI (Rotation samedis/fermetures)" : "NON") . "\n";
    $prompt .= "- Max Jours Consécutifs: " . ($rules['max_consecutive_days'] ?? 6) . "\n";
    $prompt .= "- Shift: Min " . ($rules['shift_min'] ?? 4) . "h - Max " . ($rules['shift_max'] ?? 10) . "h\n\n";
    
    $prompt .= "=== CONFIGURATION MAGASIN ===\n";
    $prompt .= "Horaires: " . ($store['open_time'] ?? '10:00') . " - " . ($store['close_time'] ?? '19:00') . "\n";
    $days_open = $store['days_open'] ?? [0,1,2,3,4,5];
    $days_names = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $open_days = array_map(function($d) use ($days_names) { return $days_names[$d]; }, $days_open);
    $prompt .= "Jours Open: " . implode(', ', $open_days) . "\n";
    $prompt .= "Pause: " . ($store['break_duration'] ?? '1h30') . " (" . ($store['break_window_start'] ?? '11:30') . "-" . ($store['break_window_end'] ?? '15:00') . ")\n";
    if (!empty($store['constraints'])) $prompt .= "Autres: " . $store['constraints'] . "\n";
    $prompt .= "\n";
    
    if (!empty($absences)) {
        $prompt .= "=== ABSENCES VALIDÉES (NE PAS PLANIFIER) ===\n";
        foreach ($absences as $abs) {
            $name = $abs['full_name'] ?? $abs['username'];
            $prompt .= "- $name : " . $abs['schedule_date'] . " (" . $abs['schedule_type'] . ")\n";
        }
        $prompt .= "\n";
    }
    
    $prompt .= "=== EMPLOYÉS ===\n";
    foreach ($employees as $emp) {
        $prompt .= "EMPLOYÉ: " . $emp['name'] . " (ID:" . $emp['id'] . ")\n";
        $prompt .= "  - Contrat: " . ($emp['hours_per_week'] ?? 35) . "h/sem\n";
        $prompt .= "  - Repos: " . ($emp['rest_days'] ?? 2) . "j/sem";
        if (!empty($emp['consecutive_rest'])) $prompt .= " (GROUPÉS si possible)";
        $prompt .= "\n";
        
        if (!empty($emp['preference'])) $prompt .= "  - PREFERENCE (A respecter si possible): " . $emp['preference'] . "\n";
        if (!empty($emp['apprentice_mode'])) $prompt .= "  - STATUT APPRENTI : OBLIGATOIRE > Ne jamais laisser seul en boutique. Doit être avec un autre employé confirmé.\n";
        if (!empty($emp['school_days'])) $prompt .= "  - ÉCOLE : INDISPONIBILITÉ TOTALE (INTERDICTION DE TRAVAILLER) durant : " . ($emp['school_days'] ? $emp['school_days'] . "j" : "Non") . "\n";
        if (!empty($emp['constraints'])) $prompt .= "  - AUTRES CONTRAINTES : " . $emp['constraints'] . "\n";
        $prompt .= "\n";
    }
    
    $prompt .= "Génère le planning JSON en respectant scrupuleusement ces règles.";
    return $prompt;
}

/**
 * Appelle l'API GROQ
 */
function callGroqAPI($messages, $apiKey, $endpoint, $model) {
    $maxRetries = 3;
    $retryDelays = [30, 60, 90]; // seconds
    
    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 12000, // Réduit pour économiser quota API
            'top_p' => 0.9
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return ['success' => false, 'error' => 'Erreur connexion: ' . $curl_error];
        }
        
        // HTTP 429 = Rate limit exceeded
        if ($http_code === 429) {
            if ($attempt < $maxRetries) {
                $waitTime = $retryDelays[$attempt];
                error_log("API Rate limit (429). Tentative " . ($attempt + 1) . "/$maxRetries. Attente de {$waitTime}s...");
                sleep($waitTime);
                continue; // Retry
            } else {
                return [
                    'success' => false, 
                    'error' => "Quota API dépassé après $maxRetries tentatives. Veuillez réessayer dans 5-10 minutes."
                ];
            }
        }
        
        if ($http_code !== 200) {
            return ['success' => false, 'error' => "Erreur API (HTTP $http_code)"];
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            return ['success' => false, 'error' => 'Réponse API invalide'];
        }
        
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }
    
    return ['success' => false, 'error' => 'Erreur inconnue'];
}

/**
 * Sauvegarde la configuration AI
 */
function handleSaveConfig($pdo) {
    $config = $_POST['config'] ?? '';
    if (empty($config)) {
        echo json_encode(['success' => false, 'error' => 'Configuration vide']);
        return;
    }
    
    try {
        // Vérifier existence
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parametres WHERE cle = 'ai_planning_config'");
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE parametres SET valeur = ? WHERE cle = 'ai_planning_config'");
            $stmt->execute([$config]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES ('ai_planning_config', ?, 'Configuration par défaut pour la génération de planning AI')");
            $stmt->execute([$config]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Récupère la configuration AI sauvegardée
 */
function handleGetConfig($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'ai_planning_config'");
        $stmt->execute();
        $config = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'config' => $config ? json_decode($config, true) : null]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
