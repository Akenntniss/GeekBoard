<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-modern.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil-modern');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    exit;
}

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord (avec cache APCu léger)
$cache_key = 'dashboard_quick_' . ($_SESSION['shop_id'] ?? 'default');
$use_cache = function_exists('apcu_exists') && function_exists('apcu_fetch') && function_exists('apcu_store');

// Essayer le cache d'abord (1 minute seulement)
if ($use_cache && apcu_exists($cache_key)) {
    $cached_data = apcu_fetch($cache_key);
    if ($cached_data && is_array($cached_data)) {
        extract($cached_data);
    } else {
        $use_cache = false; // Cache corrompu, désactiver
    }
}

// Si pas de cache ou cache expiré, récupérer normalement
if (!$use_cache || !isset($reparations_stats_categorie)) {
    $reparations_stats_categorie = get_reparations_count_by_status_categorie();
    $reparations_en_attente = $reparations_stats_categorie['en_attente'];
    $reparations_en_cours = $reparations_stats_categorie['en_cours'];
    $reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
    $reparations_actives = count_active_reparations();

    $total_clients = get_total_clients();
    $taches_recentes_count = get_taches_recentes_count();
    $reparations_recentes = get_recent_reparations(5);
    $reparations_recentes_count = count_recent_reparations();
    $taches = get_taches_en_cours(5);
    
    // Mettre en cache pour 1 minute seulement
    if ($use_cache) {
        try {
            apcu_store($cache_key, compact(
                'reparations_stats_categorie', 'reparations_en_attente', 'reparations_en_cours', 
                'reparations_nouvelles', 'reparations_actives', 'total_clients', 'taches_recentes_count',
                'reparations_recentes', 'reparations_recentes_count', 'taches'
            ), 60);
        } catch (Exception $e) {
            // Ignorer les erreurs de cache
        }
    }
}

// Récupérer les commandes récentes et leur compteur
$commandes_recentes = [];
$commandes_en_attente_count = 0;
try {
    $shop_pdo = getShopDBConnection();
    
    // Compter les commandes en attente
    $stmt_count = $shop_pdo->query("
        SELECT COUNT(*) as count 
        FROM commandes_pieces 
        WHERE statut IN ('en_attente', 'urgent')
    ");
    $commandes_en_attente_count = $stmt_count->fetch()['count'];
    
    // Récupérer les commandes récentes
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom 
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
}

// Récupérer les statistiques journalières
function get_daily_stats($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $shop_pdo = getShopDBConnection();
        
        // Nouvelles réparations du jour (toutes les réparations créées aujourd'hui, peu importe leur statut actuel)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ?
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();
        
        // Réparations effectuées du jour (réparations qui ont changé vers le statut "effectué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_effectuees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET terminées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        ");
        $stmt->execute([$date]);
        $reparations_effectuees_nouvelles = $stmt->fetchColumn();
        
        $reparations_effectuees = $reparations_effectuees_modifiees + $reparations_effectuees_nouvelles;
        
        // Réparations restituées du jour (réparations qui ont changé vers le statut "restitué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND statut = 'restitue'
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_restituees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET restituées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND statut = 'restitue'
        ");
        $stmt->execute([$date]);
        $reparations_restituees_nouvelles = $stmt->fetchColumn();
        
        $reparations_restituees = $reparations_restituees_modifiees + $reparations_restituees_nouvelles;
        
        // Devis envoyés du jour
        $devis_envoyes = 0;
        try {
            $stmt = $shop_pdo->prepare("
                SELECT COUNT(*) as count 
                FROM devis 
                WHERE DATE(date_envoi) = ? AND statut = 'envoye'
            ");
            $stmt->execute([$date]);
            $devis_envoyes = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Table devis n'existe peut-être pas encore
            $devis_envoyes = 0;
        }
        
        return [
            'nouvelles_reparations' => $nouvelles_reparations ?: 0,
            'reparations_effectuees' => $reparations_effectuees ?: 0,
            'reparations_restituees' => $reparations_restituees ?: 0,
            'devis_envoyes' => $devis_envoyes ?: 0,
            'date' => $date
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques journalières: " . $e->getMessage());
        return [
            'nouvelles_reparations' => 0,
            'reparations_effectuees' => 0,
            'reparations_restituees' => 0,
            'devis_envoyes' => 0,
            'date' => $date
        ];
    }
}

$stats_journalieres = get_daily_stats();

// Récupérer le statut des employés/techniciens
function get_employee_status() {
    try {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer tous les utilisateurs EN LIGNE avec leur techbusy status et réparations actives
        $stmt = $shop_pdo->query("
            SELECT 
                u.id as user_id,
                u.full_name as user_name,
                u.role,
                u.is_online,
                u.techbusy,
                u.active_repair_id,
                u.isActiveTask,
                u.activetaskid,
                r.id as reparation_id,
                r.modele as model,
                r.description_probleme as probleme,
                r.date_reception,
                r.statut,
                c.nom as client_nom,
                c.prenom as client_prenom,
                (SELECT rl.date_action 
                 FROM reparation_logs rl 
                 WHERE rl.reparation_id = r.id 
                   AND rl.action_type IN ('demarrage', 'changement_statut')
                 ORDER BY rl.date_action DESC 
                 LIMIT 1) as dernier_changement_statut,
                (SELECT tl.created_at
                 FROM Task_logs tl
                 WHERE tl.task_id = u.activetaskid
                   AND tl.user_id = u.id
                   AND tl.action_type = 'start'
                 ORDER BY tl.created_at DESC
                 LIMIT 1) as task_start_time
            FROM users u
            LEFT JOIN reparations r ON (
                (u.techbusy = 1 AND u.active_repair_id = r.id) OR
                (u.techbusy = 0 AND u.id = r.employe_id AND r.statut IN ('en_cours', 'diagnostic', 'attente_piece', 'reparation_en_cours'))
            )
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE u.is_online = 1
            ORDER BY u.full_name, r.date_reception DESC
        ");
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les données par utilisateur
        $employee_status = [];
        foreach ($users as $row) {
            $user_id = $row['user_id'];
            
            if (!isset($employee_status[$user_id])) {
                // Déterminer le statut basé sur techbusy et tâches actives
                $statut = 'disponible';
                $task_elapsed_time = '';
                $task_time_color = '';
                
                if ($row['isActiveTask'] == 1 && $row['activetaskid']) {
                    $statut = 'tache_active';
                    
                    // Calculer le temps écoulé depuis le démarrage de la tâche
                    if ($row['task_start_time']) {
                        $date_start = new DateTime($row['task_start_time']);
                        $now = new DateTime();
                        $interval = $date_start->diff($now);
                        
                        // Calculer le nombre total de minutes
                        $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                        
                        // Déterminer la couleur en fonction du temps
                        if ($total_minutes <= 30) {
                            $task_time_color = 'time-green';
                        } elseif ($total_minutes <= 45) {
                            $task_time_color = 'time-orange';
                        } else {
                            $task_time_color = 'time-red';
                        }
                        
                        if ($interval->days > 0) {
                            $task_elapsed_time = $interval->days . 'j ';
                        }
                        $task_elapsed_time .= $interval->h . 'h ' . $interval->i . 'm';
                    }
                } elseif ($row['techbusy'] == 1 && $row['active_repair_id']) {
                    $statut = 'en_reparation';
                } elseif ($row['reparation_id']) {
                    $statut = 'en cours d\'intervention';
                }
                
                $employee_status[$user_id] = [
                    'nom' => $row['user_name'],
                    'poste' => ucfirst($row['role']),
                    'statut' => $statut,
                    'techbusy' => $row['techbusy'],
                    'active_repair_id' => $row['active_repair_id'],
                    'isActiveTask' => $row['isActiveTask'],
                    'activetaskid' => $row['activetaskid'],
                    'task_elapsed_time' => $task_elapsed_time,
                    'task_time_color' => $task_time_color,
                    'reparations' => []
                ];
            }
            
            if ($row['reparation_id']) {
                // Calculer le temps écoulé depuis le dernier changement de statut
                $temps_passe = '';
                if ($row['dernier_changement_statut']) {
                    $date_changement = new DateTime($row['dernier_changement_statut']);
                    $now = new DateTime();
                    $interval = $date_changement->diff($now);
                    
                    if ($interval->days > 0) {
                        $temps_passe = $interval->days . 'j ';
                    }
                    $temps_passe .= $interval->h . 'h ' . $interval->i . 'm';
                } else {
                    // Fallback sur date_reception si pas de log
                    $date_reception = new DateTime($row['date_reception']);
                    $now = new DateTime();
                    $interval = $date_reception->diff($now);
                    
                    if ($interval->days > 0) {
                        $temps_passe = $interval->days . 'j ';
                    }
                    $temps_passe .= $interval->h . 'h ' . $interval->i . 'm';
                }
                
                $employee_status[$user_id]['reparations'][] = [
                    'id' => $row['reparation_id'],
                    'model' => $row['model'] ?: 'N/A',
                    'probleme' => $row['probleme'] ?: 'N/A',
                    'temps_passe' => $temps_passe,
                    'client' => $row['client_nom'] . ' ' . $row['client_prenom']
                ];
            }
        }
        
        return $employee_status;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du statut des employés: " . $e->getMessage());
        return [];
    }
}

$employee_status = get_employee_status();
?>

<style>
/* ========================================
   CORRECTION DU DÉCALAGE EN HAUT DE PAGE
======================================== */
body {
    padding-top: 0 !important;
    margin-top: 0 !important;
}

/* ========================================
   PROTECTION DES STYLES BOUTONS D'ACTION
======================================== */
/* Priorité maximale pour éviter l'écrasement par les autres CSS */
html body .action-buttons-container .action-btn,
html body .action-btn,
body .action-buttons-container .action-btn,
body .action-btn,
.action-buttons-container .action-btn,
.action-btn {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.9) 100%) !important;
    border: 2px solid rgba(148, 163, 184, 0.2) !important;
    border-radius: 20px !important;
    padding: 2rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 1.5rem !important;
    text-decoration: none !important;
    color: var(--day-text) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    backdrop-filter: blur(20px) !important;
    box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.1),
        0 4px 16px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
    position: relative !important;
    overflow: hidden !important;
    animation: slideInUp 0.6s ease-out !important;
}

html body .action-buttons-container .action-btn:hover,
html body .action-btn:hover,
body .action-buttons-container .action-btn:hover,
body .action-btn:hover,
.action-buttons-container .action-btn:hover,
.action-btn:hover {
    transform: translateY(-8px) scale(1.02) !important;
    background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
    box-shadow: 
        0 25px 80px rgba(59, 130, 246, 0.25),
        0 12px 32px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 1) !important;
    border: 2px solid rgba(59, 130, 246, 0.4) !important;
}

html body .action-buttons-container .action-btn .icon,
html body .action-btn .icon,
body .action-buttons-container .action-btn .icon,
body .action-btn .icon,
.action-buttons-container .action-btn .icon,
.action-btn .icon {
    width: 60px !important;
    height: 60px !important;
    border-radius: 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.75rem !important;
    flex-shrink: 0 !important;
    transition: all 0.3s ease !important;
    color: white !important;
}

html body .action-buttons-container .action-btn:hover .icon,
html body .action-btn:hover .icon,
body .action-buttons-container .action-btn:hover .icon,
body .action-btn:hover .icon,
.action-buttons-container .action-btn:hover .icon,
.action-btn:hover .icon {
    transform: scale(1.1) rotate(5deg) !important;
}

/* Couleurs spécifiques avec priorité maximale */
html body .action-buttons-container .action-btn:nth-child(1) .icon,
html body .action-btn:nth-child(1) .icon,
body .action-buttons-container .action-btn:nth-child(1) .icon,
body .action-btn:nth-child(1) .icon,
.action-buttons-container .action-btn:nth-child(1) .icon,
.action-btn:nth-child(1) .icon {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3) !important;
}

html body .action-buttons-container .action-btn:nth-child(2) .icon,
html body .action-btn:nth-child(2) .icon,
body .action-buttons-container .action-btn:nth-child(2) .icon,
body .action-btn:nth-child(2) .icon,
.action-buttons-container .action-btn:nth-child(2) .icon,
.action-btn:nth-child(2) .icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3) !important;
}

html body .action-buttons-container .action-btn:nth-child(3) .icon,
html body .action-btn:nth-child(3) .icon,
body .action-buttons-container .action-btn:nth-child(3) .icon,
body .action-btn:nth-child(3) .icon,
.action-buttons-container .action-btn:nth-child(3) .icon,
.action-btn:nth-child(3) .icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3) !important;
}

html body .action-buttons-container .action-btn:nth-child(4) .icon,
html body .action-btn:nth-child(4) .icon,
body .action-buttons-container .action-btn:nth-child(4) .icon,
body .action-btn:nth-child(4) .icon,
.action-buttons-container .action-btn:nth-child(4) .icon,
.action-btn:nth-child(4) .icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3) !important;
}

/* Effets de survol avec priorité maximale */
html body .action-buttons-container .action-btn:nth-child(1):hover .icon,
html body .action-btn:nth-child(1):hover .icon,
body .action-buttons-container .action-btn:nth-child(1):hover .icon,
body .action-btn:nth-child(1):hover .icon,
.action-buttons-container .action-btn:nth-child(1):hover .icon,
.action-btn:nth-child(1):hover .icon {
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5) !important;
}

html body .action-buttons-container .action-btn:nth-child(2):hover .icon,
html body .action-btn:nth-child(2):hover .icon,
body .action-buttons-container .action-btn:nth-child(2):hover .icon,
body .action-btn:nth-child(2):hover .icon,
.action-buttons-container .action-btn:nth-child(2):hover .icon,
.action-btn:nth-child(2):hover .icon {
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5) !important;
}

html body .action-buttons-container .action-btn:nth-child(3):hover .icon,
html body .action-btn:nth-child(3):hover .icon,
body .action-buttons-container .action-btn:nth-child(3):hover .icon,
body .action-btn:nth-child(3):hover .icon,
.action-buttons-container .action-btn:nth-child(3):hover .icon,
.action-btn:nth-child(3):hover .icon {
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5) !important;
}

html body .action-buttons-container .action-btn:nth-child(4):hover .icon,
html body .action-btn:nth-child(4):hover .icon,
body .action-buttons-container .action-btn:nth-child(4):hover .icon,
body .action-btn:nth-child(4):hover .icon,
.action-buttons-container .action-btn:nth-child(4):hover .icon,
.action-btn:nth-child(4):hover .icon {
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5) !important;
}

html body .action-buttons-container .action-btn .content h3,
html body .action-btn .content h3,
body .action-buttons-container .action-btn .content h3,
body .action-btn .content h3,
.action-buttons-container .action-btn .content h3,
.action-btn .content h3 {
    margin: 0 0 0.5rem 0 !important;
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: var(--day-text) !important;
    letter-spacing: -0.025em !important;
}

html body .action-buttons-container .action-btn .content p,
html body .action-btn .content p,
body .action-buttons-container .action-btn .content p,
body .action-btn .content p,
.action-buttons-container .action-btn .content p,
.action-btn .content p {
    margin: 0 !important;
    font-size: 0.875rem !important;
    color: var(--day-text-light) !important;
}

/* ========================================
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes cardFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        opacity: 1;
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Particules flottantes pour le mode nuit - DÉSACTIVÉES */
.particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
    display: none !important; /* Masquer les particules */
    visibility: hidden !important;
    opacity: 0 !important;
}

.particle {
    position: absolute;
    width: 2px;
    height: 2px;
    background: var(--night-primary);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
    opacity: 0.7;
    box-shadow: var(--night-glow);
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
    50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
}

/* ========================================
   BOUTONS D'ACTIONS EN HAUT
======================================== */
.action-buttons-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.action-btn {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.9) 100%);
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(20px);
    box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.1),
        0 4px 16px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    position: relative;
    overflow: hidden;
    animation: slideInUp 0.6s ease-out;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s ease;
}

.action-btn::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 22px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.action-btn:hover::after {
    opacity: 1;
}

.action-btn:hover::before {
    left: 100%;
}

.action-btn:hover {
    transform: translateY(-8px) scale(1.02);
    background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 0.95) 100%);
    box-shadow: 
        0 25px 80px rgba(59, 130, 246, 0.25),
        0 12px 32px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 1);
    border: 2px solid rgba(59, 130, 246, 0.4);
}

.action-btn .icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

.action-btn:hover .icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
}

.action-btn .content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--day-text);
    letter-spacing: -0.025em;
}

.action-btn .content p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--day-text-light);
}

/* Couleurs spécifiques pour chaque bouton d'action */
.action-btn:nth-child(1) .icon {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

.action-btn:nth-child(2) .icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

.action-btn:nth-child(3) .icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3);
}

.action-btn:nth-child(4) .icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);
}

/* Effets de survol spécifiques */
.action-btn:nth-child(1):hover .icon {
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5);
}

.action-btn:nth-child(2):hover .icon {
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5);
}

.action-btn:nth-child(3):hover .icon {
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5);
}

.action-btn:nth-child(4):hover .icon {
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5);
}

/* ========================================
   STATISTIQUES
======================================== */
.statistics-container {
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--day-text);
    text-align: center;
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--day-shadow);
    animation: cardFloat 2s ease-in-out infinite;
}

/* Effet shimmer pour les cartes */
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.stat-card:hover::before {
    left: 100%;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.stat-card .stat-icon {
    background: var(--day-primary);
    animation: bounceIn 0.8s ease-out;
}

.stat-card:hover .stat-icon {
    animation: iconPulse 1.5s ease-in-out infinite;
    transform: scale(1.1);
}
.stat-card.progress-card .stat-icon {
    background: #10b981;
}
.stat-card.waiting-card .stat-icon {
    background: #f59e0b;
}
.stat-card.clients-card .stat-icon {
    background: #ef4444;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--day-text);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--day-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-link {
    font-size: 1.25rem;
    color: var(--day-text-light);
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-link {
    transform: translateX(4px);
}

/* ========================================
   TABLEAUX
======================================== */
.tables-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.table-section {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--day-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.table-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
    flex: 1;
}

.table-header .badge {
    background: var(--day-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.table-content {
    max-height: 400px;
    overflow-y: auto;
}

.table-row {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--day-border);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.table-row:hover {
    background: rgba(102, 126, 234, 0.05);
}

.table-row:last-child {
    border-bottom: none;
}

.row-indicator {
    width: 4px;
    height: 32px;
    border-radius: 2px;
    flex-shrink: 0;
}

.row-indicator.taches { background: #10b981; }
.row-indicator.reparations { background: var(--day-primary); }
.row-indicator.commandes { background: #f59e0b; }

.row-content {
    flex: 1;
    min-width: 0;
}

.row-title {
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-subtitle {
    font-size: 0.875rem;
    color: var(--day-text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-problem {
    font-size: 0.8rem;
    color: var(--day-text-light);
    margin-top: 0.5rem;
    font-style: italic;
    opacity: 0.8;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-meta {
    text-align: right;
    flex-shrink: 0;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-badge.haute { background: #fee2e2; color: #991b1b; }
.priority-badge.moyenne { background: #fef3c7; color: #92400e; }
.priority-badge.basse { background: #dbeafe; color: #1e40af; }

.date-badge {
    font-size: 0.75rem;
    color: var(--day-text-light);
    background: rgba(102, 126, 234, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
}

.table-empty {
    padding: 3rem;
    text-align: center;
    color: var(--day-text-light);
}

.table-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.table-empty .title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* ========================================
   STATISTIQUES DU JOUR - BOUTONS MODERNES  
======================================== */
.daily-stats-card {
    transition: all 0.3s ease;
}

.daily-stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--day-shadow);
}

.night-mode .daily-stats-card:hover {
    box-shadow: var(--night-glow);
}

/* ========================================
   MODE NUIT
======================================== */
body.night-mode {
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
    --day-primary: var(--night-primary);
}

.night-mode .action-btn,
.night-mode .stat-card,
.night-mode .table-section,
.night-mode .daily-stat-btn,
.night-mode .modern-action-card,
.night-mode .quick-stat-card,
.night-mode .status-metric-card,
.night-mode .card,
.night-mode .modal-content {
    background: rgba(13, 17, 23, 0.85) !important; /* Fond plus opaque pour contraste */
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(56, 139, 253, 0.3) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), 0 0 10px rgba(56, 139, 253, 0.1) !important;
    color: #ffffff !important;
}

/* Badges de notification (les chiffres 2, 0, 1) */
.night-mode .badge,
.night-mode .status-metric-badge {
    background: rgba(56, 139, 253, 0.2) !important;
    color: #58a6ff !important;
    border: 1px solid rgba(56, 139, 253, 0.4) !important;
    text-shadow: 0 0 5px rgba(56, 139, 253, 0.5) !important;
}

/* Icons styling specifically for night mode to ensure visibility */
.night-mode .action-btn i,
.night-mode .modern-action-card i,
.night-mode .stat-card i,
.night-mode .quick-stat-card i,
.night-mode .status-metric-icon i {
    color: #58a6ff !important;
    text-shadow: 0 0 10px rgba(88, 166, 255, 0.6) !important;
}

/* S'assurer que le texte est lisible */
.night-mode .text-muted,
.night-mode .text-secondary {
    color: rgba(255, 255, 255, 0.7) !important;
}

.night-mode h1, .night-mode h2, .night-mode h3, .night-mode h4, .night-mode h5, .night-mode h6 {
    color: #ffffff !important;
}

.night-mode .section-title {
    background: linear-gradient(45deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.night-mode .row-meta .priority-badge,
.night-mode .row-meta .modern-badge {
    color: #000 !important;
}

/* ========================================
   RESPONSIVE - IPAD
======================================== */
@media (max-width: 1024px) and (min-width: 768px) {
    .modern-dashboard {
        padding: 1.5rem;
    }

    .action-buttons-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .action-btn {
        padding: 1.75rem;
    }

    .action-btn .icon {
        width: 52px;
        height: 52px;
        font-size: 1.6rem;
    }

    .statistics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .tables-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .table-row {
        padding: 1.25rem 1.5rem;
    }

    /* Touch-friendly hovers for iPad */
    .action-btn:active,
    .stat-card:active,
    .table-row:active {
        transform: scale(0.98);
    }
}

/* ========================================
   RESPONSIVE - MOBILE
======================================== */
@media (max-width: 767px) {
    .modern-dashboard {
        padding: 1rem 0.75rem;
    }

    .action-buttons-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .action-btn {
        padding: 1.25rem;
        border-radius: 12px;
    }

    .action-btn .icon {
        width: 44px;
        height: 44px;
        font-size: 1.3rem;
    }

    .section-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .stat-card {
        padding: 1.25rem;
        border-radius: 12px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .tables-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .table-section {
        border-radius: 12px;
    }

    .table-header {
        padding: 1.25rem;
    }

    .table-row {
        padding: 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .row-content,
    .row-meta {
        width: 100%;
    }

    .row-meta {
        text-align: left;
    }

    /* Statistiques du jour responsive */
    .statistics-container .statistics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    /* Touch-friendly interactions */
    .action-btn,
    .stat-card,
    .table-row {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
}

/* RÈGLE GLOBALE POUR L'ANIMATION SERVO - HORS MEDIA QUERY */
#desktop-navbar .servo-logo-container {
    position: absolute !important;
    left: 50% !important;
    top: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 10001 !important;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

#desktop-navbar .servo-logo-container .loader {
    display: flex !important;
    align-items: center !important;
    gap: 3px !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: 45px !important;
}

#desktop-navbar .servo-logo-container svg {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: auto !important;
    height: auto !important;
}

/* S'assurer que tous les paths sont visibles et plus épais */
#desktop-navbar .servo-logo-container svg path {
    visibility: visible !important;
    opacity: 1 !important;
}

/* Épaissir les lettres - stroke-width plus important */
#desktop-navbar .servo-logo-container path[id="S"] {
    stroke-width: 13 !important;
}

#desktop-navbar .servo-logo-container path[id="E"],
#desktop-navbar .servo-logo-container path[id="R"],
#desktop-navbar .servo-logo-container path[id="V"] {
    stroke-width: 10 !important;
}

#desktop-navbar .servo-logo-container path[id="O"] {
    stroke-width: 13 !important;
    animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
    transform-origin: center !important;
}

/* S'assurer que les animations fonctionnent */
#desktop-navbar .servo-logo-container .dash,
#desktop-navbar .servo-logo-container .spin {
    visibility: visible !important;
    opacity: 1 !important;
}

/* Forcer l'animation spin sur le O */
#desktop-navbar .servo-logo-container .spin {
    animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
    transform-origin: center !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* S'assurer que le SVG contenant le O est visible */
#desktop-navbar .servo-logo-container svg:has(path#O) {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: 36px !important;
    height: 36px !important;
}

/* Alternative si :has() n'est pas supporté */
#desktop-navbar .servo-logo-container svg path#O {
    visibility: visible !important;
    opacity: 1 !important;
    display: block !important;
}

/* Keyframes pour les animations SERVO - au cas où le CSS externe n'est pas chargé */
@keyframes dashArray {
    0% { stroke-dasharray: 0 1 359 0; }
    50% { stroke-dasharray: 0 359 1 0; }
    100% { stroke-dasharray: 359 1 0 0; }
}

@keyframes dashOffset {
    0% { stroke-dashoffset: 385; }
    100% { stroke-dashoffset: 5; }
}

@keyframes spinDashArray {
    0% { stroke-dasharray: 270 90; }
    50% { stroke-dasharray: 0 360; }
    100% { stroke-dasharray: 250 90; }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    12.5%, 25% { transform: rotate(270deg); }
    37.5%, 50% { transform: rotate(540deg); }
    62.5%, 75% { transform: rotate(810deg); }
    87.5%, 100% { transform: rotate(1080deg); }
}

/* Forcer la visibilité même pendant les animations dash */
#desktop-navbar .servo-logo-container .dash {
    animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
}

/* S'assurer que les gradients SVG sont visibles */
#desktop-navbar .servo-logo-container svg defs linearGradient {
    visibility: visible !important;
    opacity: 1 !important;
}

/* Forcer la visibilité des lettres S, E, R, V - les IDs sont sur les paths */
#desktop-navbar .servo-logo-container path[id="S"],
#desktop-navbar .servo-logo-container path[id="E"],
#desktop-navbar .servo-logo-container path[id="R"],
#desktop-navbar .servo-logo-container path[id="V"] {
    visibility: visible !important;
    opacity: 1 !important;
    display: block !important;
    stroke-width: inherit !important;
}

/* S'assurer que tous les SVG contenant les lettres sont visibles */
#desktop-navbar .servo-logo-container svg.inline-block {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    vertical-align: middle !important;
}

/* Corriger la taille du S qui est plus grand */
#desktop-navbar .servo-logo-container svg[width="40"] {
    width: 40px !important;
    height: 40px !important;
    display: inline-block !important;
}

/* Taille standard pour E, R, V, O */
#desktop-navbar .servo-logo-container svg[width="32"] {
    width: 36px !important;
    height: 36px !important;
    display: inline-block !important;
}

#desktop-navbar .container-fluid {
    position: relative !important;
}

/* Masquer complètement le dock et la zone de rappel sur desktop (≥992px) */
@media (min-width: 992px) {
    #mobile-dock,
    #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer l'affichage correct de la navbar desktop et réserver l'espace */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger spécifiquement navbar-servo-fix.css */
    body #desktop-navbar,
    html body #desktop-navbar,
    body nav#desktop-navbar,
    html body nav#desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Forcer tous les éléments de la navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.3rem 1rem !important;
    }
    /* Ajuster la taille et position des éléments navbar - ULTRA SPÉCIFIQUE */
    body #desktop-navbar .navbar-brand,
    html body #desktop-navbar .navbar-brand,
    body nav#desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.2rem 0 !important;
        margin: 0 !important;
        position: relative !important;
        top: auto !important;
        left: auto !important;
        transform: none !important;
    }
    body #desktop-navbar .navbar-brand img,
    html body #desktop-navbar .navbar-brand img,
    body nav#desktop-navbar .navbar-brand img {
        height: 30px !important;
        max-height: 30px !important;
        min-height: 30px !important;
    }
    body #desktop-navbar .btn,
    body #desktop-navbar button,
    html body #desktop-navbar .btn,
    html body #desktop-navbar button {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.85rem !important;
        height: auto !important;
        line-height: 1.1 !important;
        margin: 0.1rem 0 !important;
    }
    /* Centrer l'animation SERVO - ULTRA SPÉCIFIQUE */
    body .servo-logo-container,
    html body .servo-logo-container,
    body #desktop-navbar .servo-logo-container,
    #desktop-navbar .servo-logo-container,
    nav#desktop-navbar .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 45px !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 10001 !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        pointer-events: auto !important;
    }
    
    /* S'assurer que le container-fluid a position relative pour le positionnement absolu */
    #desktop-navbar .container-fluid {
        position: relative !important;
    }
    
    /* S'assurer que l'animation SERVO est visible */
    body .servo-logo-container .loader,
    html body .servo-logo-container .loader {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    body .servo-logo-container svg,
    html body .servo-logo-container svg,
    #desktop-navbar .servo-logo-container svg {
        visibility: visible !important;
        opacity: 1 !important;
        display: inline-block !important;
        width: auto !important;
        height: auto !important;
        max-width: none !important;
        max-height: none !important;
    }
    
    /* Tailles spécifiques pour chaque lettre - plus grandes */
    #desktop-navbar .servo-logo-container svg[width="40"] {
        width: 40px !important;
        height: 40px !important;
    }
    
    #desktop-navbar .servo-logo-container svg[width="32"] {
        width: 36px !important;
        height: 36px !important;
    }
    
    /* Épaissir les lettres dans la media query aussi */
    #desktop-navbar .servo-logo-container path[id="S"] {
        stroke-width: 13 !important;
    }
    
    #desktop-navbar .servo-logo-container path[id="E"],
    #desktop-navbar .servo-logo-container path[id="R"],
    #desktop-navbar .servo-logo-container path[id="V"] {
        stroke-width: 10 !important;
    }
    
    #desktop-navbar .servo-logo-container path[id="O"] {
        stroke-width: 13 !important;
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center !important;
    }
    
    body .servo-logo-container path,
    html body .servo-logo-container path {
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Forcer la visibilité de tous les éléments de l'animation SERVO */
    body .servo-logo-container *,
    html body .servo-logo-container *,
    #desktop-navbar .servo-logo-container * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Règle de secours pour forcer l'affichage même si le CSS externe n'est pas chargé */
    #desktop-navbar .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    #desktop-navbar .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        gap: 2px !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    #desktop-navbar .servo-logo-container svg {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    body {
        /* Réserver l'espace pour la navbar (60px seulement) */
        padding-top: 60px !important;
        margin-top: 0 !important;
    }
    .modern-dashboard {
        padding-top: 0px !important; /* Pas d'espace supplémentaire */
        margin-top: 0px !important; /* Pas de marge supplémentaire */
    }
    
    /* Éliminer tout espace noir entre navbar et contenu */
    .bg-animated {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
}

/* ========================================
   ONGLETS MODERNES
======================================== */
.modern-tabs {
    display: flex;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 0.5rem;
}

.modern-tab-button {
    background: transparent;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--day-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
    text-align: center;
}

.modern-tab-button:hover {
    background: rgba(102, 126, 234, 0.1);
    color: var(--day-text);
}

.modern-tab-button.active {
    background: var(--day-primary);
    color: white;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.night-mode .modern-tab-button.active {
    background: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* ========================================
   UTILITAIRES
======================================== */
.fade-in {
    animation: slideInUp 0.6s ease-out;
}

.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }

/* Animation en cascade pour les boutons d'action */
.action-buttons-container .action-btn:nth-child(1) { animation-delay: 0.1s; }
.action-buttons-container .action-btn:nth-child(2) { animation-delay: 0.2s; }
.action-buttons-container .action-btn:nth-child(3) { animation-delay: 0.3s; }
.action-buttons-container .action-btn:nth-child(4) { animation-delay: 0.4s; }

.statistics-grid .stat-card:nth-child(1) { animation-delay: 0.1s; }
.statistics-grid .stat-card:nth-child(2) { animation-delay: 0.2s; }
.statistics-grid .stat-card:nth-child(3) { animation-delay: 0.3s; }
.statistics-grid .stat-card:nth-child(4) { animation-delay: 0.4s; }

/* ========================================
   NOUVEAUX BOUTONS D'ACTION MODERNES
======================================== */
.modern-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 20px !important;
    margin-bottom: 2rem;
}

.modern-action-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px var(--day-shadow);
}

.modern-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px var(--day-shadow);
    border-color: var(--day-primary);
}

.modern-action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.modern-action-card:hover::before {
    left: 100%;
}

.modern-action-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.modern-action-content {
    flex: 1;
}

.modern-action-title {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--day-text);
}

.modern-action-desc {
    margin: 0;
    font-size: 0.875rem;
    color: var(--day-text-light);
    opacity: 0.8;
}

.modern-action-arrow {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--day-primary);
    color: white;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.modern-action-card:hover .modern-action-arrow {
    transform: translateX(4px);
    opacity: 1;
}

/* Couleurs spécifiques pour chaque carte */
.search-card .modern-action-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

.task-card .modern-action-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

.repair-card .modern-action-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3);
}

.order-card .modern-action-icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);
}

/* Effets de survol pour les icônes */
.modern-action-card:hover .modern-action-icon {
    transform: scale(1.1) rotate(5deg);
}

.search-card:hover .modern-action-icon {
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5);
}

.task-card:hover .modern-action-icon {
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5);
}

.repair-card:hover .modern-action-icon {
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5);
}

.order-card:hover .modern-action-icon {
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5);
}

/* Mode nuit pour les nouveaux boutons */
body.night-mode .modern-action-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

body.night-mode .modern-action-card:hover {
    background: rgba(40, 40, 45, 0.98);
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 12px 40px rgba(0, 255, 255, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

body.night-mode .modern-action-title {
    color: #ffffff;
}

body.night-mode .modern-action-desc {
    color: #b0b0b0;
}

body.night-mode .modern-action-arrow {
    background: rgba(0, 255, 255, 0.8);
    color: #000000;
}

/* Styles pour le problème en mode nuit */
body.night-mode .row-problem {
    color: #9ca3af;
}

/* Animations en cascade pour les nouveaux boutons */
.modern-action-grid .modern-action-card:nth-child(1) { animation-delay: 0.1s; }
.modern-action-grid .modern-action-card:nth-child(2) { animation-delay: 0.2s; }
.modern-action-grid .modern-action-card:nth-child(3) { animation-delay: 0.3s; }
.modern-action-grid .modern-action-card:nth-child(4) { animation-delay: 0.4s; }

/* Responsive pour les nouveaux boutons */
@media (max-width: 1024px) and (min-width: 768px) {
    .modern-action-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (max-width: 767px) {
    /* Masquer la navbar desktop sur mobile */
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar.navbar-light {
        display: none !important;
    }
    
    /* Retirer le padding-top du body sur mobile */
    body {
        padding-top: 0 !important;
    }
    
    /* ANNULER l'effet du mobile_dock_bar.css qui masque le dock */
    #mobile-dock,
    #mobile-dock-clean,
    .mobile-dock-container:not(.dock-bar-container) {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        position: fixed !important;
        bottom: 0 !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        pointer-events: auto !important;
        z-index: 99999 !important;
        width: 100vw !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
    }
    
    /* Assurer que le dock mobile est visible - FORÇAGE ULTRA AGRESSIF */
    #mobile-dock {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        bottom: 0 !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 99999 !important;
        width: 100vw !important;
        height: auto !important;
        min-height: 80px !important;
        max-height: 120px !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Forcer l'affichage du container du dock - MODE JOUR SEULEMENT */
    #mobile-dock .mobile-dock-container {
        display: flex !important;
        justify-content: space-evenly !important;
        align-items: center !important;
        padding: 12px 20px 20px !important;
        width: 100% !important;
        height: auto !important;
        min-height: 80px !important;
        border-radius: 0 !important;
    }
    
    /* Styles du container en mode jour uniquement */
    body:not(.night-mode) #mobile-dock .mobile-dock-container {
        background: rgba(255, 255, 255, 0.95) !important;
        border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15) !important;
        backdrop-filter: blur(20px) !important;
    }
    
    /* Forcer l'affichage des éléments du dock */
    #mobile-dock .dock-item {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: none !important;
        color: #64748b !important;
        padding: 8px 12px !important;
        border-radius: 16px !important;
        min-width: 60px !important;
        flex: 1 !important;
        max-width: 80px !important;
        transition: all 0.3s ease !important;
        border: none !important;
    }
    /* Supprimer tout encadrement/bordure sur mobile */
    #mobile-dock .dock-item::before,
    #mobile-dock .dock-item::after {
        display: none !important;
        border: none !important;
        box-shadow: none !important;
    }
    body.night-mode #mobile-dock .dock-item { border: none !important; }
    body.night-mode #mobile-dock .dock-item::before { display: none !important; }
    
    /* Icônes du dock */
    #mobile-dock .dock-icon-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        background: rgba(100, 116, 139, 0.1) !important;
        border: none !important;
        margin-bottom: 4px !important;
    }
    
    #mobile-dock .dock-icon-wrapper i {
        font-size: 18px !important;
        color: inherit !important;
    }
    
    /* Labels du dock */
    #mobile-dock .dock-item span {
        font-size: 11px !important;
        font-weight: 500 !important;
        color: inherit !important;
        text-align: center !important;
        white-space: nowrap !important;
    }
    
    /* État actif et hover */
    #mobile-dock .dock-item.active,
    #mobile-dock .dock-item:hover {
        color: #3b82f6 !important;
        transform: translateY(-2px) !important;
    }
    
    #mobile-dock .dock-item.active .dock-icon-wrapper,
    #mobile-dock .dock-item:hover .dock-icon-wrapper {
        background: rgba(59, 130, 246, 0.2) !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    }
    
    /* Bouton + spécial */
    #mobile-dock .dock-item-center {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 !important;
        max-width: 80px !important;
    }
    
    #mobile-dock .btn-nouvelle-action {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 50px !important;
        height: 50px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4) !important;
        transform: scale(1.1) !important;
        transition: all 0.3s ease !important;
    }
    
    #mobile-dock .btn-nouvelle-action:hover {
        transform: scale(1.15) translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5) !important;
    }
    
    #mobile-dock .btn-nouvelle-action i {
        font-size: 20px !important;
    }
    
    /* Ajouter du padding en bas pour le dock mobile */
    .modern-dashboard {
        padding-bottom: 140px !important;
    }
}

/* ========================================
   DOCK MOBILE MODE NUIT - DESIGN FUTURISTE
======================================== */

/* ÉLIMINER TOUTE BANDE OPAQUE DERRIÈRE LE DOCK */
/* ÉLIMINER TOUTE BANDE OPAQUE DERRIÈRE LE DOCK */
body.night-mode {
    /* S'assurer qu'aucun élément ne crée de bande opaque en bas */
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
    /* FOND TRANSPARENT POUR VOIR LES COUCHES INFÉRIEURES */
    background: transparent !important;
    position: relative;
    overflow-x: hidden;
    min-height: 100vh;
}

html.night-mode {
    background: #0a0a0a !important; /* Fallback */
}

/* ========================================
   ANIMATIONS FUTURISTES ARRIÈRE-PLAN
======================================== */





/* Conteneur pour les animations (injecté via JS si nécessaire) */
/* Conteneur pour les animations (injecté via JS si nécessaire) */
.night-mode-bg-effects {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: -4; /* Au niveau de la grille */
    overflow: hidden;
}

/* Particules flottantes */
.night-particle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: rgba(0, 212, 255, 1);
    border-radius: 50%;
    animation: floatParticle 10s linear infinite;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.9), 0 0 30px rgba(0, 212, 255, 0.5);
    will-change: transform, opacity;
}

@keyframes floatParticle {
    0% {
        transform: translateY(100vh) translateX(0);
        opacity: 0;
    }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% {
        transform: translateY(-10vh) translateX(50px);
        opacity: 0;
    }
}

/* Effet de lueur pulsante sur les coins */
/* Effet de lueur pulsante sur les coins */
.night-corner-glow {
    position: fixed;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    z-index: -4;
    animation: cornerPulse 10s ease-in-out infinite;
}

.night-corner-glow.top-left {
    top: -200px;
    left: -200px;
    background: radial-gradient(circle, rgba(0, 212, 255, 0.3) 0%, transparent 70%);
}

.night-corner-glow.bottom-right {
    bottom: -200px;
    right: -200px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.25) 0%, transparent 70%);
    animation-delay: -5s;
}

@keyframes cornerPulse {
    0%, 100% { 
        transform: scale(1);
        opacity: 0.5;
    }
    50% { 
        transform: scale(1.3);
        opacity: 0.8;
    }
}

/* Lignes de données animées */
.night-data-line {
    position: fixed;
    height: 1px;
    background: linear-gradient(90deg, 
        transparent 0%,
        rgba(0, 212, 255, 0.3) 50%,
        transparent 100%);
    animation: dataFlow 4s linear infinite;
    pointer-events: none;
    z-index: -4;
}

@keyframes dataFlow {
    0% { 
        transform: translateX(-100%);
        opacity: 0;
    }
    20% { opacity: 1; }
    80% { opacity: 1; }
    100% { 
        transform: translateX(100vw);
        opacity: 0;
    }
}

/* Désactiver les pseudo-éléments qui pourraient interférer */

/* Annuler tout fond qui pourrait être derrière le dock */
/* Annuler tout fond qui pourrait être derrière le dock */
body.night-mode .modern-dashboard,
body.night-mode .container-fluid,
body.night-mode .main-content {
    background: transparent !important;
    position: relative;
    z-index: 1; /* Par dessus les animations */
    padding-bottom: 120px !important; /* Espace pour le dock */
}

/* Fond de base animé (injecté via JS) */
.night-mode-base-bg {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -10;
    background: var(--night-bg-animated) !important;
    background-size: 400% 400% !important;
    animation: gradientShift 15s ease infinite !important;
    pointer-events: none;
}

/* CIBLER SPÉCIFIQUEMENT LES ÉLÉMENTS QUI PEUVENT CRÉER UNE BANDE OPAQUE */
body.night-mode #mobile-dock,
body.night-mode #mobile-dock *:not(.dock-item):not(.dock-icon-wrapper) {
    background-color: transparent !important;
}

/* S'assurer qu'aucun élément parent du dock n'a de fond opaque */
body.night-mode #mobile-dock-clean,
body.night-mode .mobile-dock-bar,
body.night-mode .dock-bar-container {
    background: transparent !important;
    background-color: transparent !important;
}

/* Dock principal en mode nuit - Glassmorphism ultra-transparent et plus sombre */
body.night-mode #mobile-dock {
    /* Verre multi-couches plus profond et plus propre */
    background: 
        radial-gradient(1200px 200px at 50% 120%, rgba(0, 212, 255, 0.10) 0%, transparent 60%) !important,
        linear-gradient(135deg, 
            rgba(6, 12, 22, 0.10) 0%, 
            rgba(12, 20, 36, 0.07) 18%, 
            rgba(6, 12, 22, 0.12) 36%, 
            rgba(12, 20, 36, 0.06) 54%, 
            rgba(6, 12, 22, 0.11) 72%, 
            rgba(12, 20, 36, 0.08) 86%, 
            rgba(6, 12, 22, 0.10) 100%) !important;
    border-top: 2px solid rgba(0, 255, 255, 0.75) !important;
    border-left: 1px solid rgba(0, 255, 255, 0.45) !important;
    border-right: 1px solid rgba(0, 255, 255, 0.45) !important;
    box-shadow: 
        0 -14px 56px rgba(0, 255, 255, 0.50) !important,
        0 -10px 40px rgba(0, 212, 255, 0.38) !important,
        0 -6px 22px rgba(0, 255, 255, 0.28) !important,
        0 -2px 10px rgba(0, 255, 255, 0.20) !important,
        inset 0 2px 6px rgba(255, 255, 255, 0.10) !important,
        inset 0 -2px 6px rgba(0, 255, 255, 0.35) !important,
        inset 0 0 26px rgba(0, 255, 255, 0.14) !important;
    backdrop-filter: blur(70px) saturate(380%) brightness(0.85) contrast(1.35) !important;
    -webkit-backdrop-filter: blur(70px) saturate(380%) brightness(0.85) contrast(1.35) !important;
    position: fixed !important;
    bottom: 0 !important;
    top: auto !important;
    left: 0 !important;
    right: 0 !important;
    width: 100vw !important;
    height: auto !important;
    min-height: 80px !important;
    max-height: 120px !important;
    z-index: 99999 !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Overlay glassmorphism ultra-sombre et transparent */
body.night-mode #mobile-dock::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    /* Texture/verre: dégradé + trame subtile (plus transparente) */
    background: 
        linear-gradient(135deg, 
            rgba(255, 255, 255, 0.035) 0%, 
            rgba(0, 255, 255, 0.045) 20%, 
            rgba(255, 255, 255, 0.016) 45%, 
            rgba(0, 255, 255, 0.04) 65%, 
            rgba(255, 255, 255, 0.024) 82%, 
            rgba(0, 255, 255, 0.035) 100%) !important,
        repeating-linear-gradient( 135deg, rgba(255,255,255,0.015) 0px, rgba(255,255,255,0.015) 2px, transparent 2px, transparent 6px ) !important;
    backdrop-filter: blur(36px) saturate(320%) brightness(0.72) !important;
    -webkit-backdrop-filter: blur(36px) saturate(320%) brightness(0.72) !important;
    border-radius: 0 !important;
    pointer-events: none !important;
    z-index: 1 !important;
    opacity: 0.55 !important;
}

/* Effet de reflet glassmorphism ultra-premium */
body.night-mode #mobile-dock::after {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 52% !important;
    background: linear-gradient(180deg, 
        rgba(255, 255, 255, 0.22) 0%, 
        rgba(0, 255, 255, 0.14) 28%, 
        rgba(255, 255, 255, 0.10) 56%, 
        rgba(0, 255, 255, 0.06) 82%, 
        transparent 100%) !important;
    backdrop-filter: blur(18px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(18px) saturate(200%) !important;
    border-radius: 0 !important;
    pointer-events: none !important;
    z-index: 2 !important;
    opacity: 0.75 !important;
    animation: futuristicReflection 5.5s ease-in-out infinite alternate !important;
}

/* Container futuriste ultra-glassmorphism - Plus sombre et transparent */
body.night-mode #mobile-dock .mobile-dock-container {
    /* ANNULER COMPLÈTEMENT LE FOND BLANC DU MODE JOUR */
    background: 
        linear-gradient(135deg, 
            rgba(8, 15, 26, 0.04) 0%, 
            rgba(15, 23, 42, 0.035) 25%, 
            rgba(8, 15, 26, 0.07) 53%, 
            rgba(15, 23, 42, 0.035) 78%, 
            rgba(8, 15, 26, 0.04) 100%) !important,
        radial-gradient(600px 140px at 50% -40px, rgba(0, 212, 255, 0.12) 0%, transparent 70%) !important;
    border-radius: 0 !important;
    border-top: 2px solid rgba(0, 255, 255, 0.8) !important;
    border-left: 1px solid rgba(0, 255, 255, 0.4) !important;
    border-right: 1px solid rgba(0, 255, 255, 0.4) !important;
    border-bottom: none !important;
    position: relative !important;
    z-index: 10 !important;
    backdrop-filter: blur(55px) saturate(330%) brightness(0.82) contrast(1.42) !important;
    -webkit-backdrop-filter: blur(55px) saturate(330%) brightness(0.82) contrast(1.42) !important;
    box-shadow: 
        inset 0 2px 4px rgba(255, 255, 255, 0.05) !important,
        inset 0 -2px 5px rgba(0, 255, 255, 0.30) !important,
        inset 0 0 24px rgba(0, 255, 255, 0.16) !important,
        0 0 38px rgba(0, 255, 255, 0.22) !important;
    
    /* FORCER L'ANNULATION DE TOUT HÉRITAGE */
    background-color: transparent !important;
}

/* Effet de brillance futuriste ultra-premium sur le container */
body.night-mode #mobile-dock .mobile-dock-container::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 4px !important;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.4) 20%, 
        rgba(0, 255, 255, 0.8) 40%, 
        #00d4ff 50%, 
        rgba(0, 255, 255, 0.8) 60%, 
        rgba(255, 255, 255, 0.4) 80%, 
        transparent 100%) !important;
    backdrop-filter: blur(8px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(8px) saturate(200%) !important;
    animation: futuristicScan 4s ease-in-out infinite !important;
    opacity: 0.9 !important;
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.6) !important;
}

/* Éléments du dock en mode nuit - Glassmorphism ultra-transparent et sombre */
body.night-mode #mobile-dock .dock-item {
    color: #e2e8f0 !important;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.06) 0%, 
        rgba(8, 15, 26, 0.08) 25%, 
        rgba(255, 255, 255, 0.03) 50%, 
        rgba(8, 15, 26, 0.10) 75%, 
        rgba(255, 255, 255, 0.05) 100%) !important;
    border: 1px solid rgba(0, 255, 255, 0.5) !important;
    backdrop-filter: blur(30px) saturate(250%) brightness(0.9) !important;
    -webkit-backdrop-filter: blur(30px) saturate(250%) brightness(0.9) !important;
    box-shadow: 
        0 4px 16px rgba(0, 255, 255, 0.25) !important,
        inset 0 1px 0 rgba(255, 255, 255, 0.08) !important,
        inset 0 -1px 0 rgba(0, 255, 255, 0.35) !important;
    border-radius: 16px !important;
    position: relative !important;
    z-index: 15 !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

body.night-mode #mobile-dock .dock-item::before {
    content: '' !important;
    position: absolute !important;
    top: -2px !important;
    left: -2px !important;
    right: -2px !important;
    bottom: -2px !important;
    background: linear-gradient(45deg, 
        transparent, 
        rgba(0, 255, 255, 0.1), 
        transparent, 
        rgba(0, 212, 255, 0.1)) !important;
    border-radius: 18px !important;
    opacity: 0 !important;
    transition: opacity 0.3s ease !important;
    z-index: -1 !important;
}

body.night-mode #mobile-dock .dock-item:hover::before {
    opacity: 1 !important;
}

/* Icônes futuristes */
body.night-mode #mobile-dock .dock-icon-wrapper {
    background: rgba(0, 255, 255, 0.1) !important;
    border: 1px solid rgba(0, 255, 255, 0.2) !important;
    box-shadow: 
        0 0 10px rgba(0, 255, 255, 0.2) !important,
        inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    position: relative !important;
}

body.night-mode #mobile-dock .dock-icon-wrapper::after {
    content: '' !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    width: 60% !important;
    height: 60% !important;
    background: radial-gradient(circle, rgba(0, 255, 255, 0.1) 0%, transparent 70%) !important;
    border-radius: 50% !important;
    transform: translate(-50%, -50%) !important;
    animation: futuristicPulse 2s ease-in-out infinite !important;
}

body.night-mode #mobile-dock .dock-icon-wrapper i {
    color: #00d4ff !important;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5) !important;
    position: relative !important;
    z-index: 2 !important;
}

/* Labels futuristes */
body.night-mode #mobile-dock .dock-item span {
    color: #e2e8f0 !important;
    text-shadow: 0 0 8px rgba(0, 212, 255, 0.3) !important;
    font-weight: 600 !important;
}

/* États actifs et hover futuristes - Glassmorphism ultra-premium */
body.night-mode #mobile-dock .dock-item.active,
body.night-mode #mobile-dock .dock-item:hover {
    color: #00d4ff !important;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.25) 0%, 
        rgba(0, 212, 255, 0.3) 25%, 
        rgba(255, 255, 255, 0.15) 50%, 
        rgba(0, 212, 255, 0.35) 75%, 
        rgba(255, 255, 255, 0.2) 100%) !important;
    border: 2px solid rgba(0, 255, 255, 0.8) !important;
    backdrop-filter: blur(35px) saturate(300%) brightness(1.4) !important;
    -webkit-backdrop-filter: blur(35px) saturate(300%) brightness(1.4) !important;
    transform: translateY(-5px) scale(1.08) !important;
    box-shadow: 
        0 12px 35px rgba(0, 255, 255, 0.5) !important,
        0 6px 20px rgba(0, 212, 255, 0.3) !important,
        inset 0 2px 4px rgba(255, 255, 255, 0.3) !important,
        inset 0 -2px 4px rgba(0, 255, 255, 0.4) !important,
        0 0 25px rgba(0, 255, 255, 0.6) !important;
}

body.night-mode #mobile-dock .dock-item.active .dock-icon-wrapper,
body.night-mode #mobile-dock .dock-item:hover .dock-icon-wrapper {
    background: rgba(0, 255, 255, 0.2) !important;
    border-color: rgba(0, 255, 255, 0.5) !important;
    box-shadow: 
        0 0 20px rgba(0, 255, 255, 0.4) !important,
        0 4px 16px rgba(0, 212, 255, 0.3) !important,
        inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
    transform: scale(1.1) !important;
}

body.night-mode #mobile-dock .dock-item.active span,
body.night-mode #mobile-dock .dock-item:hover span {
    color: #ffffff !important;
    text-shadow: 0 0 15px rgba(0, 212, 255, 0.8) !important;
}

/* Bouton + ultra-futuriste */
body.night-mode #mobile-dock .btn-nouvelle-action {
    background: linear-gradient(135deg, 
        #00d4ff 0%, 
        #0099cc 25%, 
        #ff00aa 50%, 
        #cc0088 75%, 
        #00d4ff 100%) !important;
    background-size: 200% 200% !important;
    animation: futuristicGradient 3s ease infinite !important;
    border: 2px solid rgba(0, 255, 255, 0.5) !important;
    box-shadow: 
        0 0 30px rgba(0, 255, 255, 0.6) !important,
        0 0 60px rgba(255, 0, 170, 0.3) !important,
        0 4px 20px rgba(0, 212, 255, 0.4) !important,
        inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
    position: relative !important;
    overflow: hidden !important;
}

body.night-mode #mobile-dock .btn-nouvelle-action::before {
    content: '' !important;
    position: absolute !important;
    top: -50% !important;
    left: -50% !important;
    width: 200% !important;
    height: 200% !important;
    background: linear-gradient(45deg, 
        transparent, 
        rgba(255, 255, 255, 0.1), 
        transparent) !important;
    animation: futuristicRotate 2s linear infinite !important;
}

body.night-mode #mobile-dock .btn-nouvelle-action:hover {
    transform: scale(1.2) translateY(-4px) !important;
    box-shadow: 
        0 0 40px rgba(0, 255, 255, 0.8) !important,
        0 0 80px rgba(255, 0, 170, 0.5) !important,
        0 8px 30px rgba(0, 212, 255, 0.6) !important,
        inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
}

body.night-mode #mobile-dock .btn-nouvelle-action i {
    color: #000000 !important;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.8) !important;
    font-weight: 900 !important;
    position: relative !important;
    z-index: 2 !important;
}

/* Animations futuristes */
@keyframes futuristicScan {
    0%, 100% {
        transform: translateX(-100%);
        opacity: 0;
    }
    50% {
        transform: translateX(100%);
        opacity: 1;
    }
}

@keyframes futuristicPulse {
    0%, 100% {
        opacity: 0.3;
        transform: translate(-50%, -50%) scale(0.8);
    }
    50% {
        opacity: 0.8;
        transform: translate(-50%, -50%) scale(1.2);
    }
}

@keyframes futuristicGradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes futuristicRotate {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Masquer les boutons d'action sur mobile (remplacés par le dock) */
@media (max-width: 767px) {
    .modern-action-grid {
        display: none !important;
    }
}

/* CORRECTION POUR L'AUTO-HIDE DU NOUVEAU DOCK MOBILE */
/* Permettre l'auto-hide du nouveau dock mobile (#mobile_dock_bar) */
#mobile_dock_bar {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease !important;
}

/* États pour l'auto-hide du nouveau dock */
#mobile_dock_bar.dock-bar-visible {
    transform: translateY(0) !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

#mobile_dock_bar.dock-bar-hidden {
    transform: translateY(calc(100% - 12px)) !important;
    opacity: 0.2 !important;
    pointer-events: auto !important;
}

/* S'assurer que les styles de forçage ne s'appliquent PAS au nouveau dock */
#mobile_dock_bar:not(.dock-bar-hidden) {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: fixed !important;
    bottom: 0 !important;
    z-index: 99999 !important;
}

/* Pour les écrans moyens (tablettes portrait) */
@media (min-width: 768px) and (max-width: 1023px) {
    /* Masquer la navbar desktop sur tablette portrait */
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar.navbar-light {
        display: none !important;
    }
    
    /* Retirer le padding-top du body sur tablette */
    body {
        padding-top: 0 !important;
    }
    
    /* Assurer que le dock mobile est visible */
    #mobile-dock {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 9999 !important;
    }
    
    /* Ajouter du padding en bas pour le dock mobile */
    .modern-dashboard {
        padding-bottom: 140px !important;
    }
}

/* ========================================
   NOUVEAU DESIGN - ÉTAT DES RÉPARATIONS
======================================== */
.status-overview-section {
    margin-bottom: 2rem;
}

.status-section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1.5rem;
    text-align: center;
    position: relative;
}

.status-section-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    border-radius: 2px;
}

.status-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.status-metric-card {
    background: var(--day-card-bg) !important;
    border: 1px solid var(--day-border) !important;
    border-radius: 18px !important;
    padding: 1.75rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 1.25rem !important;
    text-decoration: none !important;
    color: var(--day-text) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: 0 6px 20px var(--day-shadow) !important;
}

.status-metric-card:hover {
    transform: translateY(-6px) scale(1.02) !important;
    box-shadow: 0 15px 50px var(--day-shadow) !important;
    border-color: var(--day-primary) !important;
}

.status-metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s ease;
}

.status-metric-card:hover::before {
    left: 100%;
}

.status-metric-badge {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.4s ease;
}

.status-metric-info {
    flex: 1;
}

.status-metric-number {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--day-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.status-metric-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--day-text-light);
    opacity: 0.9;
}

.status-metric-indicator {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--day-primary);
    color: white;
    font-size: 0.875rem;
    transition: all 0.4s ease;
    opacity: 0.8;
}

.status-metric-card:hover .status-metric-indicator {
    transform: translateX(6px) scale(1.1);
    opacity: 1;
}

.status-metric-card:hover .status-metric-badge {
    transform: scale(1.15) rotate(10deg);
}

/* Couleurs spécifiques pour chaque métrique */
.repairs-card .status-metric-badge {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.tasks-card .status-metric-badge {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.orders-card .status-metric-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.urgent-card .status-metric-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Effets de survol pour les badges */
.repairs-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.6);
}

.tasks-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.6);
}

.orders-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.6);
}

.urgent-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.6);
}

/* Mode nuit pour les métriques de statut */
body.night-mode .status-metric-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

body.night-mode .status-metric-card:hover {
    background: rgba(40, 40, 45, 0.98);
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 15px 50px rgba(0, 255, 255, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

body.night-mode .status-section-title {
    color: #ffffff;
}

body.night-mode .status-section-title::after {
    background: linear-gradient(90deg, #00d4ff, #ff00aa);
}

body.night-mode .status-metric-number {
    color: #ffffff;
}

body.night-mode .status-metric-label {
    color: #b0b0b0;
}

body.night-mode .status-metric-indicator {
    background: rgba(0, 255, 255, 0.8);
    color: #000000;
}

/* ========================================
   NOUVEAU DESIGN - STATISTIQUES DU JOUR
======================================== */
.daily-analytics-section {
    margin-bottom: 2rem;
}

.daily-analytics-title-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
}

.daily-stats-nav-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: var(--day-card-bg);
    color: var(--day-text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px var(--day-shadow);
    border: 1px solid var(--day-border);
}

.daily-stats-nav-btn:hover {
    background: var(--day-primary);
    color: #fff;
    transform: scale(1.1);
}

.daily-stats-nav-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.daily-stats-nav-btn i {
    font-size: 0.9rem;
}

.daily-analytics-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    text-align: center;
    position: relative;
    margin: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 220px;
}

.daily-analytics-title .stats-date-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--day-text-light);
    margin-top: 4px;
}

.daily-analytics-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--day-secondary), var(--day-accent));
    border-radius: 2px;
}

.daily-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.daily-analytics-card {
    background: var(--day-card-bg) !important;
    border: 1px solid var(--day-border) !important;
    border-radius: 20px !important;
    padding: 2rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 1.5rem !important;
    cursor: pointer !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: 0 8px 25px var(--day-shadow) !important;
}

.daily-analytics-card:hover {
    transform: translateY(-8px) scale(1.03) !important;
    box-shadow: 0 20px 60px var(--day-shadow) !important;
    border-color: var(--day-primary) !important;
}

.daily-analytics-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.daily-analytics-card:hover::after {
    transform: scaleX(1);
}

.daily-analytics-icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.4s ease;
    position: relative;
}

.daily-analytics-icon::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    padding: 2px;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.daily-analytics-card:hover .daily-analytics-icon::before {
    opacity: 1;
}

.daily-analytics-content {
    flex: 1;
}

.daily-analytics-value {
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--day-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.daily-analytics-text {
    font-size: 1rem;
    font-weight: 600;
    color: var(--day-text-light);
    opacity: 0.9;
}

.daily-analytics-action {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--day-primary);
    color: white;
    font-size: 1rem;
    transition: all 0.4s ease;
    opacity: 0.8;
}

.daily-analytics-card:hover .daily-analytics-action {
    transform: translateX(8px) rotate(15deg) scale(1.15);
    opacity: 1;
}

.daily-analytics-card:hover .daily-analytics-icon {
    transform: scale(1.2) rotate(-10deg);
}

/* Couleurs spécifiques pour chaque carte analytique */
.new-repairs-card .daily-analytics-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.completed-repairs-card .daily-analytics-icon {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
}

.returned-repairs-card .daily-analytics-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.quotes-sent-card .daily-analytics-icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
}

/* Effets de survol pour les icônes analytiques */
.new-repairs-card:hover .daily-analytics-icon {
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.6);
}

.completed-repairs-card:hover .daily-analytics-icon {
    box-shadow: 0 15px 40px rgba(6, 182, 212, 0.6);
}

.returned-repairs-card:hover .daily-analytics-icon {
    box-shadow: 0 15px 40px rgba(16, 185, 129, 0.6);
}

.quotes-sent-card:hover .daily-analytics-icon {
    box-shadow: 0 15px 40px rgba(139, 92, 246, 0.6);
}

/* Mode nuit pour les analytics */
body.night-mode .daily-analytics-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

body.night-mode .daily-analytics-card:hover {
    background: rgba(40, 40, 45, 0.98);
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 20px 60px rgba(0, 255, 255, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

body.night-mode .daily-analytics-card::after {
    background: linear-gradient(90deg, #00d4ff, #ff00aa);
}

body.night-mode .daily-analytics-title {
    color: #ffffff;
}

body.night-mode .daily-analytics-title .stats-date-label {
    color: #b0b0b0;
}

body.night-mode .daily-stats-nav-btn {
    background: rgba(30, 30, 35, 0.95);
    color: #ffffff;
    border-color: rgba(0, 255, 255, 0.3);
    box-shadow: 0 4px 12px rgba(0, 255, 255, 0.15);
}

body.night-mode .daily-stats-nav-btn:hover {
    background: linear-gradient(135deg, #00d4ff, #00a0cc);
    border-color: #00d4ff;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
}

body.night-mode .daily-stats-nav-btn:disabled {
    opacity: 0.3;
    background: rgba(30, 30, 35, 0.5);
}

body.night-mode .daily-analytics-title::after {
    background: linear-gradient(90deg, #00d4ff, #ff00aa);
}

body.night-mode .daily-analytics-value {
    color: #ffffff;
}

body.night-mode .daily-analytics-text {
    color: #b0b0b0;
}

body.night-mode .daily-analytics-action {
    background: rgba(0, 255, 255, 0.8);
    color: #000000;
}

/* Responsive pour les nouvelles sections */
@media (max-width: 1024px) and (min-width: 768px) {
    .status-metrics-grid,
    .daily-analytics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (max-width: 767px) {
    .status-metrics-grid,
    .daily-analytics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .status-metric-card,
    .daily-analytics-card {
        padding: 1.5rem;
        border-radius: 16px;
    }
    
    .status-metric-badge,
    .daily-analytics-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }
    
    .status-metric-number,
    .daily-analytics-value {
        font-size: 2rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Styles du toggle supprimés - Mode automatique uniquement */

/* ========================================
   FORÇAGE ULTRA-SPÉCIFIQUE DES NOUVEAUX DESIGNS
======================================== */
/* Priorité maximale pour les nouvelles sections */
html body div.status-overview-section div.status-metrics-grid a.status-metric-card,
body div.status-overview-section div.status-metrics-grid a.status-metric-card,
div.status-overview-section div.status-metrics-grid a.status-metric-card,
.status-overview-section .status-metrics-grid .status-metric-card {
    background: var(--day-card-bg) !important;
    border: 1px solid var(--day-border) !important;
    border-radius: 18px !important;
    padding: 1.75rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 1.25rem !important;
    text-decoration: none !important;
    color: var(--day-text) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: 0 6px 20px var(--day-shadow) !important;
}

html body div.daily-analytics-section div.daily-analytics-grid div.daily-analytics-card,
body div.daily-analytics-section div.daily-analytics-grid div.daily-analytics-card,
div.daily-analytics-section div.daily-analytics-grid div.daily-analytics-card,
.daily-analytics-section .daily-analytics-grid .daily-analytics-card {
    background: var(--day-card-bg) !important;
    border: 1px solid var(--day-border) !important;
    border-radius: 20px !important;
    padding: 2rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 1.5rem !important;
    cursor: pointer !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: 0 8px 25px var(--day-shadow) !important;
}

/* Mode nuit avec priorité maximale */
body.night-mode html body div.status-overview-section div.status-metrics-grid a.status-metric-card,
body.night-mode div.status-overview-section div.status-metrics-grid a.status-metric-card,
.night-mode .status-overview-section .status-metrics-grid .status-metric-card {
    background: rgba(30, 30, 35, 0.95) !important;
    border: 1px solid rgba(0, 255, 255, 0.2) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}

body.night-mode html body div.daily-analytics-section div.daily-analytics-grid div.daily-analytics-card,
body.night-mode div.daily-analytics-section div.daily-analytics-grid div.daily-analytics-card,
.night-mode .daily-analytics-section .daily-analytics-grid .daily-analytics-card {
    background: rgba(30, 30, 35, 0.95) !important;
    border: 1px solid rgba(0, 255, 255, 0.2) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}

/* ========================================
   STYLES BACKDROP POUR MODALS
======================================== */
/* Amélioration des backdrops Bootstrap */
.modal-backdrop {
    backdrop-filter: blur(8px) !important;
    background: rgba(0, 0, 0, 0.4) !important;
    transition: all 0.3s ease !important;
}

body.night-mode .modal-backdrop {
    backdrop-filter: blur(12px) !important;
    background: rgba(0, 0, 0, 0.6) !important;
}

/* Styles pour les modals avec backdrop */
.modal {
    backdrop-filter: blur(10px) !important;
}

body.night-mode .modal {
    backdrop-filter: blur(15px) !important;
}

/* Amélioration des modal-dialog */
.modal-dialog {
    backdrop-filter: blur(15px) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

body.night-mode .modal-dialog {
    backdrop-filter: blur(20px) !important;
}

/* Styles spécifiques pour nos modals */
#ajouterTacheModal .modal-content,
#ajouterCommandeModal .modal-content {
    backdrop-filter: blur(20px) !important;
    border: none !important;
    border-radius: 20px !important;
    overflow: hidden !important;
}

body.night-mode #ajouterTacheModal .modal-content,
body.night-mode #ajouterCommandeModal .modal-content {
    backdrop-filter: blur(25px) !important;
    border: 1px solid rgba(0, 255, 255, 0.3) !important;
    box-shadow: 0 25px 50px rgba(0, 255, 255, 0.4), 0 0 0 1px rgba(0, 255, 255, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}

/* Pas d'animation d'ouverture des modals - Affichage instantané */
.modal.fade .modal-dialog {
    transform: translateY(0) scale(1) !important;
    opacity: 1 !important;
    transition: none !important;
}

.modal.show .modal-dialog {
    transform: translateY(0) scale(1) !important;
    opacity: 1 !important;
}
/* ========================================
   🚀 NOUVEAU DESIGN - QUICK STATS BAR
   Mode Jour: Moderne & Professionnel
   Mode Nuit: Futuriste & Néon
======================================== */

/* Container de la barre */
.quick-stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    padding: 0.5rem;
    margin-bottom: 1.5rem;
}

/* Bouton individuel - MODE JOUR - PC : texte à droite */
.quick-stat-btn {
    display: flex;
    flex-direction: row; /* PC : texte à droite de l'icône */
    align-items: center;
    justify-content: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: #ffffff;
    border-radius: 16px;
    text-decoration: none;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    min-height: 70px;
}

/* Mobile : texte en bas de l'icône */
@media (max-width: 768px) {
    .quick-stat-btn {
        flex-direction: column; /* Mobile : texte en bas */
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 0.5rem;
        min-height: 90px;
    }
}

.quick-stat-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.quick-stat-btn:active {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

/* Icône - MODE JOUR */
.quick-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #ffffff;
    margin-bottom: 0; /* PC : pas de marge en bas */
    flex-shrink: 0;
    transition: all 0.3s ease;
}

/* Mobile : marge en bas de l'icône */
@media (max-width: 768px) {
    .quick-stat-icon {
        margin-bottom: 0.5rem;
    }
}

/* Couleurs des icônes par type */
.quick-stat-btn[data-color="blue"] .quick-stat-icon {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
}
.quick-stat-btn[data-color="purple"] .quick-stat-icon {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
}
.quick-stat-btn[data-color="green"] .quick-stat-icon {
    background: linear-gradient(135deg, #10B981, #059669);
}
.quick-stat-btn[data-color="orange"] .quick-stat-icon {
    background: linear-gradient(135deg, #F59E0B, #D97706);
}

/* Compteur - PC : taille augmentée */
.quick-stat-count {
    font-size: 2.1rem; /* PC : +20% */
    font-weight: 700;
    color: #1F2937;
    line-height: 1;
    margin-bottom: 0.15rem;
}

/* Label - PC : taille augmentée */
.quick-stat-label {
    font-size: 1.2rem; /* PC : +20% */
    font-weight: 500;
    color: #6B7280;
    text-align: left;
    white-space: nowrap;
    letter-spacing: -0.2px;
}

/* Mobile : tailles réduites comme avant */
@media (max-width: 768px) {
    .quick-stat-count {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    .quick-stat-label {
        font-size: 0.65rem;
        text-align: center;
    }
}

/* Effet de brillance au hover */
.quick-stat-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.quick-stat-btn:hover::before {
    left: 100%;
}

/* ========================================
   MODE NUIT - FUTURISTE & NÉON
======================================== */

body.night-mode .quick-stats-bar {
    gap: 0.5rem;
}

body.night-mode .quick-stat-btn {
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
    border: 1px solid rgba(0, 255, 255, 0.15);
    box-shadow: 
        0 4px 20px rgba(0, 0, 0, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

body.night-mode .quick-stat-btn:hover {
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 
        0 8px 32px rgba(0, 255, 255, 0.15),
        0 0 20px rgba(0, 255, 255, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transform: translateY(-4px) scale(1.02);
}

/* Icônes néon */
body.night-mode .quick-stat-btn[data-color="blue"] .quick-stat-icon {
    background: linear-gradient(135deg, #0EA5E9, #00D4FF);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
}
body.night-mode .quick-stat-btn[data-color="purple"] .quick-stat-icon {
    background: linear-gradient(135deg, #A855F7, #C084FC);
    box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
}
body.night-mode .quick-stat-btn[data-color="green"] .quick-stat-icon {
    background: linear-gradient(135deg, #10B981, #00FF88);
    box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
}
body.night-mode .quick-stat-btn[data-color="orange"] .quick-stat-icon {
    background: linear-gradient(135deg, #F59E0B, #FFB800);
    box-shadow: 0 0 20px rgba(255, 184, 0, 0.4);
}

/* Texte néon */
body.night-mode .quick-stat-count {
    color: #F1F5F9;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
}

body.night-mode .quick-stat-label {
    color: #94A3B8;
}

/* Animation pulse subtile sur les icônes en mode nuit */
@keyframes neonPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

body.night-mode .quick-stat-icon {
    animation: neonPulse 3s ease-in-out infinite;
}

/* Ligne lumineuse au bas du bouton au hover */
body.night-mode .quick-stat-btn::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #00D4FF, transparent);
    transition: width 0.3s ease;
}

body.night-mode .quick-stat-btn:hover::after {
    width: 80%;
}

/* Couleurs spécifiques de la ligne lumineuse */
body.night-mode .quick-stat-btn[data-color="blue"]:hover::after {
    background: linear-gradient(90deg, transparent, #00D4FF, transparent);
}

/* Ajustement bouton Rechercher (pas de compteur) - PC uniquement */
@media (min-width: 769px) {
    .quick-stat-btn[data-color="blue"] .quick-stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }
}

body.night-mode .quick-stat-btn[data-color="purple"]:hover::after {
    background: linear-gradient(90deg, transparent, #A855F7, transparent);
}
body.night-mode .quick-stat-btn[data-color="green"]:hover::after {
    background: linear-gradient(90deg, transparent, #00FF88, transparent);
}
body.night-mode .quick-stat-btn[data-color="orange"]:hover::after {
    background: linear-gradient(90deg, transparent, #FFB800, transparent);
}

/* Utilitaires de visibilité responsive pour les cartes - HAUTE SPÉCIFICITÉ */
@media (max-width: 768px) {
    body .card-desktop-only,
    body a.card-desktop-only,
    a.status-metric-card.card-desktop-only { 
        display: none !important; 
    }
    .card-mobile-only { display: flex !important; }

    /* FORÇAGE GRID 1x4 MOBILE */
    .status-metrics-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.25rem !important; /* Réduction de l'écart pour gagner de la place */
    }

    /* Redéfinition complète des cartes pour le format icône + texte dessous */
    body .status-metrics-grid .status-metric-card,
    body .status-metrics-grid .modern-action-card.search-card {
        flex-direction: column !important;
        text-align: center !important;
        padding: 0.75rem 0 !important; /* Aucune marge latérale interne */
        gap: 0.25rem !important;
        align-items: center !important;
        justify-content: flex-start !important;
        height: 100% !important;
        min-height: 90px !important;
        /* Uniformisation du style */
        background: var(--day-card-bg) !important;
        border: 1px solid var(--day-border) !important;
        border-radius: 12px !important; /* Un peu moins arrondi pour gagner de la place visuelle */
        box-shadow: 0 4px 10px var(--day-shadow) !important;
        overflow: visible !important; /* Laisser le texte dépasser si besoin */
    }

    /* Icones plus petites */
    body .status-metrics-grid .status-metric-badge,
    body .status-metrics-grid .modern-action-icon {
        width: 40px !important; /* Légère réduction */
        height: 40px !important;
        font-size: 1.1rem !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
        margin-bottom: 4px !important;
        border-radius: 10px !important;
    }
    
    /* Correction spécifique pour l'icône recherche qui semble différente */
    body .status-metrics-grid .modern-action-icon {
        background: #2563eb !important; /* Bleu standard ou variable */
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Conteneur info - FLEXBOX POUR CENTRAGE PARFAIT */
    body .status-metrics-grid .status-metric-info,
    body .status-metrics-grid .modern-action-content {
        width: 100% !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important; /* Centre horizontalement les enfants */
        justify-content: flex-start !important;
    }

    /* Masquer les flèches inutiles */
    body .status-metrics-grid .status-metric-indicator,
    body .status-metrics-grid .modern-action-arrow {
        display: none !important;
    }

    /* Typographie adaptée - OPTIMISÉE POUR LONGUEUR */
    body .status-metrics-grid .status-metric-number {
        font-size: 1.1rem !important;
        margin-bottom: 2px !important;
        line-height: 1 !important;
    }

    body .status-metrics-grid .status-metric-label {
        font-size: 0.6rem !important;
        white-space: nowrap !important; /* Force une seule ligne */
        overflow: visible !important; /* Laisse le texte déborder un peu si besoin */
        text-overflow: clip !important;
        line-height: 1.1 !important;
        width: auto !important; /* Laisse le contenu définir la largeur */
        margin: 0 !important; /* Pas de marge parasite */
        letter-spacing: -0.4px !important; /* Compresse légèrement le texte */
    }
    
    /* Pseudo-élément pour simuler le chiffre manquant et forcer l'alignement structurel */
    body .status-metrics-grid .modern-action-content::before {
        content: "0";
        display: block !important;
        font-size: 1.1rem !important; /* Identique à status-metric-number */
        line-height: 1 !important;
        margin-bottom: 2px !important;
        color: transparent !important;
        visibility: hidden !important;
        height: 1.1rem !important; /* Force la hauteur */
    }

    /* Spécifique Recherche - ALIGNEMENT AVEC LES AUTRES LABELS */
    body .status-metrics-grid .modern-action-title {
        font-size: 0.6rem !important; /* Identique à status-metric-label */
        margin: 0 !important;
        margin-top: 0 !important; /* Suppression de la marge manuelle */
        white-space: nowrap !important;
        overflow: visible !important;
        font-weight: 600 !important; /* On garde le gras pour la lisibilité */
        letter-spacing: -0.3px !important;
        width: auto !important; /* Flexbox gérera le centrage */
        margin-left: 0 !important;
    }
    body .status-metrics-grid .modern-action-desc {
        display: none !important;
    }
}
@media (min-width: 769px) {
    .card-desktop-only { display: flex !important; }
    body .card-mobile-only,
    body a.card-mobile-only { 
        display: none !important; 
    }
}

/* Badge de notification flottant mobile */
.mobile-floating-notif {
    display: none !important;
}

@media (max-width: 768px) {
    .mobile-floating-notif {
        display: flex !important;
        position: fixed !important;
        top: 15px !important;
        left: 20px !important;
        z-index: 10005 !important;
        width: 48px !important;
        height: 48px !important;
        background: rgba(255, 255, 255, 0.4) !important;
        backdrop-filter: blur(15px) !important;
        -webkit-backdrop-filter: blur(15px) !important;
        border-radius: 16px !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        align-items: center !important;
        justify-content: center !important;
        color: #1e293b !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
    }
    
    body.night-mode .mobile-floating-notif {
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid rgba(0, 212, 255, 0.3) !important;
        color: #00d4ff !important;
        box-shadow: 0 8px 32px rgba(0, 212, 255, 0.2) !important;
    }

    .mobile-floating-notif:active {
        transform: scale(0.9) !important;
    }

    .mobile-floating-notif .notif-icon {
        font-size: 1.25rem !important;
    }

    .mobile-floating-notif .unread-badge {
        position: absolute !important;
        top: -6px !important;
        right: -6px !important;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        min-width: 18px !important;
        height: 18px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 4px !important;
        border-radius: 10px !important;
        border: 2px solid white !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
        animation: pulse-red 2s infinite !important;
    }
    
    body.night-mode .mobile-floating-notif .unread-badge {
        border-color: #0f172a !important;
    }

    @keyframes pulse-red {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
}
</style>

<!-- Basculeur de thème -->
<!-- Toggle retiré - Mode automatique selon système -->

<!-- Container de particules (mode nuit) -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <?php 
    // Badge de notification flottant pour mobile
    $unread_count = count_unread_notifications($_SESSION['user_id']);
    ?>
    <a href="index.php?page=notifications" class="mobile-floating-notif">
        <div class="notif-icon">
            <i class="fas fa-bell"></i>
        </div>
        <?php if ($unread_count > 0): ?>
            <span class="unread-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
    
    <!-- 🚀 BOUTONS D'ACTIONS EN HAUT -->
    <!-- 🚀 NOUVEAUX BOUTONS D'ACTION MODERNES -->
    <div class="modern-action-grid fade-in">
        <a href="#" class="modern-action-card task-card" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal" onclick="event.preventDefault();">
            <div class="modern-action-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Tâche</h3>
                <p class="modern-action-desc">Créer une nouvelle tâche</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="index.php?page=ajouter_reparation" class="modern-action-card repair-card">
            <div class="modern-action-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Réparation</h3>
                <p class="modern-action-desc">Enregistrer une nouvelle réparation</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
        
        <a href="#" class="modern-action-card order-card" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
            <div class="modern-action-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Commande</h3>
                <p class="modern-action-desc">Commander une nouvelle pièce</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    </div>

    <!-- 📊 STATISTIQUES -->
    <!-- 📊 NOUVEAU DESIGN - ÉTAT DES RÉPARATIONS -->
    <div class="status-overview-section fade-in">
        <h3 class="status-section-title">État des Réparations</h3>
        <div class="quick-stats-bar">
            <!-- Rechercher -->
            <a href="#" class="quick-stat-btn" data-color="blue" onclick="ouvrirRechercheModerne(); return false;">
                <div class="quick-stat-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="quick-stat-label">Rechercher</div>
            </a>
            
            <!-- Réparations -->
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="quick-stat-btn" data-color="purple">
                <div class="quick-stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="quick-stat-count"><?php echo $reparations_actives; ?></div>
                <div class="quick-stat-label">Réparations</div>
            </a>
            
            <!-- Tâches -->
            <a href="index.php?page=taches" class="quick-stat-btn" data-color="green">
                <div class="quick-stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="quick-stat-count"><?php echo $taches_recentes_count; ?></div>
                <div class="quick-stat-label">Tâches</div>
            </a>
            
            <!-- Commandes -->
            <a href="index.php?page=commandes_pieces" class="quick-stat-btn" data-color="orange">
                <div class="quick-stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="quick-stat-count"><?php echo $commandes_en_attente_count; ?></div>
                <div class="quick-stat-label">Commandes</div>
            </a>
        </div>
    </div>

    <!-- 📋 TABLEAUX -->
    <div class="tables-container fade-in">
        <!-- Tableau 1: Tâches en cours -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-tasks"></i>
                <h4><a href="index.php?page=taches" style="text-decoration: none; color: inherit;">Tâches en cours</a></h4>
                <span class="badge"><?php echo $taches_recentes_count; ?></span>
            </div>
            
            <!-- Onglets pour les tâches -->
            <div class="modern-tabs" style="padding: 1rem; border-bottom: 1px solid var(--day-border);">
                <button class="modern-tab-button active" data-tab="toutes-taches" onclick="switchTab('toutes-taches')">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-taches" onclick="switchTab('mes-taches')">Mes tâches</button>
            </div>
            
            <div class="table-content">
                <!-- Contenu onglet "Toutes les tâches" -->
                <div class="tab-content active" id="toutes-taches">
                    <?php 
                    $toutes_taches = get_toutes_taches_en_cours(10);
                    if (!empty($toutes_taches)): ?>
                        <?php foreach ($toutes_taches as $tache): 
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche en cours</div>
                            <div>Toutes les tâches ont été complétées</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contenu onglet "Mes tâches" -->
                <div class="tab-content" id="mes-taches">
                    <?php if (!empty($taches)): ?>
                        <?php foreach ($taches as $tache): 
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche</div>
                            <div>Toutes les tâches sont terminées</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau 2: Réparations récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-wrench"></i>
                <h4><a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">Réparations récentes</a></h4>
                <span class="badge"><?php echo $reparations_recentes_count; ?></span>
            </div>
            <!-- Onglets pour les réparations (même logique que Tâches) -->
            <div class="modern-tabs" style="padding: 1rem; border-bottom: 1px solid var(--day-border);">
                <button class="modern-tab-button active" data-tab="toutes-reparations" onclick="switchTab('toutes-reparations')">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-reparations" onclick="switchTab('mes-reparations')">Mes réparations</button>
            </div>
            <div class="table-content">
                <!-- Contenu onglet "Toutes les réparations" -->
                <div class="tab-content active" id="toutes-reparations">
                    <?php 
                    $toutes_repairs = !empty($reparations_recentes) ? $reparations_recentes : [];
                    if (!empty($toutes_repairs)): ?>
                        <?php foreach ($toutes_repairs as $reparation): ?>
                            <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                <div class="row-indicator reparations"></div>
                                <div class="row-content">
                                    <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                                    <div class="row-problem">
                                        <?php 
                                        $probleme = $reparation['description_probleme'] ?? '';
                                        echo htmlspecialchars(strlen($probleme) > 60 ? substr($probleme, 0, 60) . '...' : $probleme); 
                                        ?>
                                    </div>
                                </div>
                                <div class="row-meta">
                                    <div class="date-badge"><?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-wrench"></i>
                            <div class="title">Aucune réparation</div>
                            <div>Pas de réparations en cours</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contenu onglet "Mes réparations" (filtré par utilisateur connecté) -->
                <div class="tab-content" id="mes-reparations">
                    <?php 
                    $current_user_id = $_SESSION['user_id'] ?? null;
                    $mes_repairs = [];
                    if ($current_user_id && !empty($reparations_recentes)) {
                        foreach ($reparations_recentes as $rep) {
                            if ((int)($rep['employe_id'] ?? 0) === (int)$current_user_id) { $mes_repairs[] = $rep; }
                        }
                    }
                    if (!empty($mes_repairs)): ?>
                        <?php foreach ($mes_repairs as $reparation): ?>
                            <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                <div class="row-indicator reparations"></div>
                                <div class="row-content">
                                    <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                                    <div class="row-problem">
                                        <?php 
                                        $probleme = $reparation['description_probleme'] ?? '';
                                        echo htmlspecialchars(strlen($probleme) > 60 ? substr($probleme, 0, 60) . '...' : $probleme); 
                                        ?>
                                    </div>
                                </div>
                                <div class="row-meta">
                                    <div class="date-badge"><?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-user"></i>
                            <div class="title">Aucune de mes réparations</div>
                            <div>Vous n'avez pas de réparations récentes assignées</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau 3: Commandes récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-shopping-cart"></i>
                <h4><a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">Commandes récentes</a></h4>
                <span class="badge"><?php echo count($commandes_recentes); ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($commandes_recentes)): ?>
                    <?php foreach ($commandes_recentes as $commande): ?>
                        <div class="table-row" data-commande-id="<?php echo $commande['id']; ?>" onclick="ouvrirModalStatut(event, <?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>', '<?php echo htmlspecialchars($commande['reference'] ?? 'REF-' . $commande['id']); ?>', '<?php echo htmlspecialchars($commande['nom_piece']); ?>')">
                            <div class="row-indicator commandes"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($commande['nom_piece'] ?? 'Produit N/A'); ?></div>
                                <div class="row-subtitle"><?php echo htmlspecialchars($commande['fournisseur_nom'] ?? 'Fournisseur N/A'); ?></div>
                            </div>
                            <div class="row-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($commande['date_creation'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <div class="title">Aucune commande</div>
                        <div>Pas de commandes en attente</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 📈 NOUVEAU DESIGN - STATISTIQUES DU JOUR (ADMIN UNIQUEMENT) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="daily-analytics-section mt-4 fade-in" id="dailyStatsSection" data-current-date="<?php echo date('Y-m-d'); ?>">
        <div class="daily-analytics-title-container">
            <button class="daily-stats-nav-btn" id="statsPrevDay" onclick="navigateDailyStats(-1)" title="Jour précédent">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h3 class="daily-analytics-title">
                <span id="statsTitleText">Statistiques du jour</span>
                <span class="stats-date-label" id="statsDateLabel"></span>
            </h3>
            <button class="daily-stats-nav-btn" id="statsNextDay" onclick="navigateDailyStats(1)" title="Jour suivant" disabled>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="daily-analytics-grid" id="dailyStatsGrid">
            <div class="daily-analytics-card new-repairs-card" onclick="openStatsModal('nouvelles_reparations')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['nouvelles_reparations']; ?></div>
                    <div class="daily-analytics-text">Nouvelles réparations</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
            
            <div class="daily-analytics-card completed-repairs-card" onclick="openStatsModal('reparations_effectuees')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['reparations_effectuees']; ?></div>
                    <div class="daily-analytics-text">Réparations effectuées</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            
            <div class="daily-analytics-card returned-repairs-card" onclick="openStatsModal('reparations_restituees')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['reparations_restituees']; ?></div>
                    <div class="daily-analytics-text">Réparations restituées</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>
            
            <div class="daily-analytics-card quotes-sent-card" onclick="openStatsModal('devis_envoyes')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['devis_envoyes']; ?></div>
                    <div class="daily-analytics-text">Devis envoyés</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 👥 STATUT DES EMPLOYÉS (ADMIN UNIQUEMENT) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="employee-status-section mt-5 fade-in">
        <h3 class="employee-status-title">Statut des employés</h3>
        
        <div class="employee-status-table-container">
            <table class="employee-status-table">
                <thead>
                    <tr>
                        <th>Technicien</th>
                        <th>Statut</th>
                        <th>Temps</th>
                        <th>ID Réparation</th>
                        <th>Modèle</th>
                        <th>Problème</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employee_status)): ?>
                        <?php foreach ($employee_status as $userId => $employee): ?>
                            <?php if (empty($employee['reparations'])): ?>
                                <!-- Employé disponible ou sur tâche -->
                                <tr class="employee-row <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>">
                                    <td class="employee-name employee-name-clickable" onclick="openEmployeeActivityModal('<?php echo $userId; ?>', '<?php echo addslashes(htmlspecialchars($employee['nom'])); ?>')" style="cursor: pointer; color: #007bff; text-decoration: underline;">
                                        <?php echo htmlspecialchars($employee['nom']); ?>
                                    </td>
                                    <td class="employee-status <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>">
                                        <span class="status-indicator <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>"></span>
                                        <?php 
                                        if ($employee['isActiveTask'] == 1 && $employee['activetaskid']) {
                                            echo '<span class="clickable-status" onclick="afficherDetailsTache(event, ' . htmlspecialchars($employee['activetaskid']) . ')" style="cursor: pointer; color: #007bff; text-decoration: underline;">📋 Tâche en cours : #' . htmlspecialchars($employee['activetaskid']) . '</span>';
                                        } else {
                                            echo 'Aucune activité pour le moment';
                                        }
                                        ?>
                                    </td>
                                    <td class="repair-time <?php echo ($employee['isActiveTask'] == 1 && !empty($employee['task_time_color'])) ? htmlspecialchars($employee['task_time_color']) : ''; ?>">
                                        <?php echo ($employee['isActiveTask'] == 1 && !empty($employee['task_elapsed_time'])) ? htmlspecialchars($employee['task_elapsed_time']) : '-'; ?>
                                    </td>
                                    <td class="repair-id">-</td>
                                    <td class="repair-model">-</td>
                                    <td class="repair-problem">-</td>
                                </tr>
                            <?php else: ?>
                                <!-- Employé avec réparations en cours -->
                                <?php foreach ($employee['reparations'] as $index => $reparation): ?>
                                    <tr class="employee-row busy">
                                        <?php if ($index === 0): ?>
                                            <td class="employee-name employee-name-clickable" rowspan="<?php echo count($employee['reparations']); ?>" onclick="openEmployeeActivityModal('<?php echo $userId; ?>', '<?php echo addslashes(htmlspecialchars($employee['nom'])); ?>')" style="cursor: pointer; color: #007bff; text-decoration: underline;">
                                                <?php echo htmlspecialchars($employee['nom']); ?>
                                            </td>
                                            <td class="employee-status <?php echo ($employee['statut'] == 'en_reparation') ? 'repairing' : 'busy'; ?>" rowspan="<?php echo count($employee['reparations']); ?>">
                                                <span class="status-indicator <?php echo ($employee['statut'] == 'en_reparation') ? 'repairing' : 'busy'; ?>"></span>
                                                <?php 
                                                $firstRepairId = !empty($employee['reparations']) ? $employee['reparations'][0]['id'] : null;
                                                if ($employee['statut'] == 'en_reparation') {
                                                    echo '<span class="clickable-status" onclick="event.stopPropagation(); openRepairQuickInfo(' . htmlspecialchars($firstRepairId) . ');" style="cursor: pointer; color: #007bff; text-decoration: underline;">🔧 En réparation</span>';
                                                } else {
                                                    echo '<span class="clickable-status" onclick="event.stopPropagation(); openRepairQuickInfo(' . htmlspecialchars($firstRepairId) . ');" style="cursor: pointer; color: #007bff; text-decoration: underline;">Actif sur une réparation</span>';
                                                }
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="repair-time"><?php echo htmlspecialchars($reparation['temps_passe']); ?></td>
                                        <td class="repair-id">#<?php echo htmlspecialchars($reparation['id']); ?></td>
                                        <td class="repair-model"><?php echo htmlspecialchars($reparation['model']); ?></td>
                                        <td class="repair-problem"><?php echo htmlspecialchars(substr($reparation['probleme'], 0, 50)) . (strlen($reparation['probleme']) > 50 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-data">
                            <td colspan="6">Aucun technicien trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Note: Le modal de statistiques est géré par le système existant via openStatsModal() -->

<script>
// ========================================
// NAVIGATION DES STATISTIQUES DU JOUR
// ========================================
let dailyStatsCurrentDate = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD

function navigateDailyStats(direction) {
    const section = document.getElementById('dailyStatsSection');
    if (!section) return;
    
    // Calculer la nouvelle date
    const currentDate = new Date(dailyStatsCurrentDate);
    currentDate.setDate(currentDate.getDate() + direction);
    const newDate = currentDate.toISOString().split('T')[0];
    
    // Vérifier qu'on ne dépasse pas aujourd'hui
    const today = new Date().toISOString().split('T')[0];
    if (newDate > today) return;
    
    // Mettre à jour la date courante
    dailyStatsCurrentDate = newDate;
    
    // Afficher un état de chargement
    const grid = document.getElementById('dailyStatsGrid');
    if (grid) {
        grid.style.opacity = '0.5';
        grid.style.pointerEvents = 'none';
    }
    
    // Faire la requête AJAX
    fetch(`ajax/get_daily_stats.php?date=${newDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDailyStatsUI(data, newDate, today);
            } else {
                console.error('Erreur lors de la récupération des statistiques:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
        })
        .finally(() => {
            if (grid) {
                grid.style.opacity = '1';
                grid.style.pointerEvents = 'auto';
            }
        });
}

function updateDailyStatsUI(data, date, today) {
    // Mettre à jour les valeurs
    const cards = document.querySelectorAll('.daily-analytics-card');
    const values = [
        data.nouvelles_reparations,
        data.reparations_effectuees,
        data.reparations_restituees,
        data.devis_envoyes
    ];
    
    cards.forEach((card, index) => {
        const valueEl = card.querySelector('.daily-analytics-value');
        if (valueEl && values[index] !== undefined) {
            valueEl.textContent = values[index];
        }
    });
    
    // Mettre à jour le titre et le label de date
    const titleText = document.getElementById('statsTitleText');
    const dateLabel = document.getElementById('statsDateLabel');
    
    if (date === today) {
        if (titleText) titleText.textContent = "Statistiques du jour";
        if (dateLabel) dateLabel.textContent = '';
    } else {
        // Formater la date en français
        const dateObj = new Date(date);
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('fr-FR', options);
        
        if (titleText) titleText.textContent = "Statistiques";
        if (dateLabel) dateLabel.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
    }
    
    // Mettre à jour l'état des boutons
    const prevBtn = document.getElementById('statsPrevDay');
    const nextBtn = document.getElementById('statsNextDay');
    
    if (nextBtn) {
        nextBtn.disabled = (date >= today);
    }
    // Le bouton précédent est toujours actif (on peut toujours remonter dans le passé)
    if (prevBtn) {
        prevBtn.disabled = false;
    }
}

// Initialiser la date courante au chargement
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('dailyStatsSection');
    if (section) {
        dailyStatsCurrentDate = section.dataset.currentDate || new Date().toISOString().split('T')[0];
    }
});
</script>

<script>
// ========================================
// GESTION DU THÈME
// ========================================
let currentTheme = 'day'; // Sera automatiquement détecté par initTheme()
let particlesCreated = false;

function setupModalListeners() {
    console.log('🎭 Configuration des écouteurs de modals');
    
    const modals = ['ajouterTacheModal', 'ajouterCommandeModal', 'taskDetailsModal'];
    
    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            // Écouter l'ouverture du modal
            modalElement.addEventListener('shown.bs.modal', function() {
                console.log('🎭 Modal ouvert:', modalId);
                
                // Appliquer les styles selon le thème actuel
                setTimeout(() => {
                    if (currentTheme === 'night') {
                        forceModalsNightMode();
                    } else {
                        forceModalsDayMode();
                    }
                }, 50);
            });
            
            // Écouter quand le modal est sur le point de s'ouvrir
            modalElement.addEventListener('show.bs.modal', function() {
                console.log('🎭 Modal en cours d\'ouverture:', modalId);
                
                // Pré-appliquer les styles
                if (currentTheme === 'night') {
                    forceModalsNightMode();
                } else {
                    forceModalsDayMode();
                }
            });
        }
    });
    
    console.log('✅ Écouteurs de modals configurés');
}

// Fonction pour forcer les styles du mode jour sur les NOUVELLES cartes de statistiques
function forceStatCardsDayMode() {
    console.log('🌞 Forçage du mode jour pour les NOUVELLES cartes de statistiques');
    
    // Forcer les variables CSS du mode jour
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', 'rgba(255, 255, 255, 0.95)');
    root.style.setProperty('--day-text', '#1e293b');
    root.style.setProperty('--day-text-light', '#64748b');
    root.style.setProperty('--day-shadow', 'rgba(0, 0, 0, 0.1)');
    root.style.setProperty('--day-border', 'rgba(148, 163, 184, 0.2)');
    root.style.setProperty('--day-primary', '#3b82f6');
    
    // Forcer les styles sur les NOUVELLES cartes de statistiques (status-metric-card)
    const statusCards = document.querySelectorAll('.status-metric-card');
    statusCards.forEach(card => {
        card.style.setProperty('background', 'var(--day-card-bg)', 'important');
        card.style.setProperty('border', '1px solid var(--day-border)', 'important');
        card.style.setProperty('color', 'var(--day-text)', 'important');
        card.style.setProperty('box-shadow', '0 6px 20px var(--day-shadow)', 'important');
        card.style.setProperty('border-radius', '18px', 'important');
        card.style.setProperty('padding', '1.75rem', 'important');
        
        // Forcer les styles sur le contenu
        const number = card.querySelector('.status-metric-number');
        const label = card.querySelector('.status-metric-label');
        if (number) {
            number.style.setProperty('color', 'var(--day-text)', 'important');
        }
        if (label) {
            label.style.setProperty('color', 'var(--day-text-light)', 'important');
        }
    });
    
    // Forcer les styles sur les NOUVELLES cartes analytiques (daily-analytics-card)
    const analyticsCards = document.querySelectorAll('.daily-analytics-card');
    analyticsCards.forEach(card => {
        card.style.setProperty('background', 'var(--day-card-bg)', 'important');
        card.style.setProperty('border', '1px solid var(--day-border)', 'important');
        card.style.setProperty('color', 'var(--day-text)', 'important');
        card.style.setProperty('box-shadow', '0 8px 25px var(--day-shadow)', 'important');
        card.style.setProperty('border-radius', '20px', 'important');
        card.style.setProperty('padding', '2rem', 'important');
        
        // Forcer les styles sur le contenu
        const value = card.querySelector('.daily-analytics-value');
        const text = card.querySelector('.daily-analytics-text');
        if (value) {
            value.style.setProperty('color', 'var(--day-text)', 'important');
        }
        if (text) {
            text.style.setProperty('color', 'var(--day-text-light)', 'important');
        }
    });
    
    // Forcer les modals en mode jour
    forceModalsDayMode();
    
    console.log('✅ Styles du mode jour forcés sur', statusCards.length, 'cartes de statut et', analyticsCards.length, 'cartes analytiques');
}

// Fonction pour forcer les styles du mode nuit sur les NOUVELLES cartes de statistiques
function forceStatCardsNightMode() {
    console.log('🌙 Forçage du mode nuit pour les NOUVELLES cartes de statistiques');
    
    // Forcer les variables CSS du mode nuit
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', 'rgba(30, 30, 35, 0.95)');
    root.style.setProperty('--day-text', '#ffffff');
    root.style.setProperty('--day-text-light', '#b0b0b0');
    root.style.setProperty('--day-shadow', 'rgba(0, 255, 255, 0.15)');
    root.style.setProperty('--day-border', 'rgba(0, 255, 255, 0.2)');
    root.style.setProperty('--day-primary', '#00d4ff');
    
    // Forcer les styles sur les NOUVELLES cartes de statistiques (status-metric-card)
    const statusCards = document.querySelectorAll('.status-metric-card');
    statusCards.forEach(card => {
        card.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        card.style.setProperty('color', '#ffffff', 'important');
        card.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        card.style.setProperty('border-radius', '18px', 'important');
        card.style.setProperty('padding', '1.75rem', 'important');
        
        // Forcer les styles sur le contenu
        const number = card.querySelector('.status-metric-number');
        const label = card.querySelector('.status-metric-label');
        if (number) {
            number.style.setProperty('color', '#ffffff', 'important');
        }
        if (label) {
            label.style.setProperty('color', '#b0b0b0', 'important');
        }
    });
    
    // Forcer les styles sur les NOUVELLES cartes analytiques (daily-analytics-card)
    const analyticsCards = document.querySelectorAll('.daily-analytics-card');
    analyticsCards.forEach(card => {
        card.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        card.style.setProperty('color', '#ffffff', 'important');
        card.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        card.style.setProperty('border-radius', '20px', 'important');
        card.style.setProperty('padding', '2rem', 'important');
        
        // Forcer les styles sur le contenu
        const value = card.querySelector('.daily-analytics-value');
        const text = card.querySelector('.daily-analytics-text');
        if (value) {
            value.style.setProperty('color', '#ffffff', 'important');
        }
        if (text) {
            text.style.setProperty('color', '#b0b0b0', 'important');
        }
    });
    
    // Forcer les modals en mode nuit
    forceModalsNightMode();
    
    console.log('✅ Styles du mode nuit forcés sur', statusCards.length, 'cartes de statut et', analyticsCards.length, 'cartes analytiques');
}

// Fonction pour forcer les modals en mode jour
function forceModalsDayMode() {
    console.log('🌞 Forçage des modals en mode jour - Design spécialisé');
    
    // Design premium pour ajouterCommandeModal
    const commandeModal = document.querySelector('#ajouterCommandeModal');
    if (commandeModal) {
        forceCommandeModalPremiumDayMode(commandeModal);
    }
    
    // Design standard pour ajouterTacheModal
    const tacheModal = document.querySelector('#ajouterTacheModal');
    if (tacheModal) {
        forceStandardModalDayMode(tacheModal);
    }
    
    // Forcer le backdrop global
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.style.setProperty('backdrop-filter', 'blur(12px)', 'important');
        backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.3)', 'important');
    });
    
    console.log('✅ Modals forcés en mode jour avec designs spécialisés');
}

// Design premium ultra-moderne pour ajouterCommandeModal
function forceCommandeModalPremiumDayMode(modal) {
    console.log('🛒 Application du design premium pour ajouterCommandeModal');
    
    const modalDialog = modal.querySelector('.modal-dialog');
    const modalContent = modal.querySelector('.modal-content');
    const modalHeader = modal.querySelector('.modal-header');
    const modalBody = modal.querySelector('.modal-body');
    const modalFooter = modal.querySelector('.modal-footer');
    
    // Modal principal avec effet glassmorphism avancé
    modal.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
    modal.style.setProperty('background', 'rgba(0, 0, 0, 0.2)', 'important');
    
    // Dialog avec taille optimisée
    if (modalDialog) {
        modalDialog.style.setProperty('backdrop-filter', 'blur(25px)', 'important');
        modalDialog.style.setProperty('transform', 'none', 'important');
        modalDialog.style.setProperty('transition', 'none', 'important');
        modalDialog.style.setProperty('max-width', '1000px', 'important');
        modalDialog.style.setProperty('margin', '2rem auto', 'important');
    }
    
    // Contenu avec design glassmorphism premium
    if (modalContent) {
        modalContent.style.setProperty('background', 'linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 50%, rgba(241, 245, 249, 0.92) 100%)', 'important');
        modalContent.style.setProperty('color', '#0f172a', 'important');
        modalContent.style.setProperty('border', '2px solid rgba(255, 255, 255, 0.4)', 'important');
        modalContent.style.setProperty('border-radius', '28px', 'important');
        modalContent.style.setProperty('box-shadow', '0 40px 80px rgba(0, 0, 0, 0.15), 0 20px 40px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.3), inset 0 2px 0 rgba(255, 255, 255, 0.9)', 'important');
        modalContent.style.setProperty('backdrop-filter', 'blur(30px)', 'important');
        modalContent.style.setProperty('overflow', 'hidden', 'important');
        modalContent.style.setProperty('position', 'relative', 'important');
        
        // Pas d'animation - affichage instantané
        modalContent.style.setProperty('background-image', 'none', 'important');
        modalContent.style.setProperty('animation', 'none', 'important');
    }
    
    // Header avec design ultra-moderne
    if (modalHeader) {
        modalHeader.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 25%, #6366f1 50%, #8b5cf6 75%, #a855f7 100%)', 'important');
        modalHeader.style.setProperty('color', '#ffffff', 'important');
        modalHeader.style.setProperty('border', 'none', 'important');
        modalHeader.style.setProperty('border-radius', '28px 28px 0 0', 'important');
        modalHeader.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalHeader.style.setProperty('padding', '2rem 2.5rem', 'important');
        modalHeader.style.setProperty('position', 'relative', 'important');
        modalHeader.style.setProperty('box-shadow', 'inset 0 1px 0 rgba(255, 255, 255, 0.2)', 'important');
        
        // Pas d'effet de brillance - affichage statique
        modalHeader.style.setProperty('background-image', 'none', 'important');
        
        // Styliser le titre avec icône
        const title = modalHeader.querySelector('.modal-title');
        if (title) {
            title.style.setProperty('font-size', '1.75rem', 'important');
            title.style.setProperty('font-weight', '800', 'important');
            title.style.setProperty('text-shadow', '0 2px 8px rgba(0,0,0,0.2)', 'important');
            title.style.setProperty('display', 'flex', 'important');
            title.style.setProperty('align-items', 'center', 'important');
            title.style.setProperty('gap', '1rem', 'important');
            title.style.setProperty('letter-spacing', '-0.025em', 'important');
            
            // Ajouter une icône si elle n'existe pas
            if (!title.querySelector('.fas')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-shopping-cart';
                icon.style.setProperty('font-size', '1.5rem', 'important');
                icon.style.setProperty('padding', '0.5rem', 'important');
                icon.style.setProperty('background', 'rgba(255, 255, 255, 0.2)', 'important');
                icon.style.setProperty('border-radius', '12px', 'important');
                icon.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
                title.insertBefore(icon, title.firstChild);
            }
        }
        
        // Styliser le bouton de fermeture
        const closeBtn = modalHeader.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.style.setProperty('background', 'rgba(255, 255, 255, 0.25)', 'important');
            closeBtn.style.setProperty('border-radius', '16px', 'important');
            closeBtn.style.setProperty('padding', '0.75rem', 'important');
            closeBtn.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            closeBtn.style.setProperty('transition', 'none', 'important');
            closeBtn.style.setProperty('border', '1px solid rgba(255, 255, 255, 0.3)', 'important');
            closeBtn.style.setProperty('box-shadow', '0 4px 12px rgba(0, 0, 0, 0.1)', 'important');
        }
    }
    
    // Body avec design premium
    if (modalBody) {
        modalBody.style.setProperty('background', 'rgba(255, 255, 255, 0.6)', 'important');
        modalBody.style.setProperty('color', '#0f172a', 'important');
        modalBody.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalBody.style.setProperty('padding', '2.5rem', 'important');
        modalBody.style.setProperty('position', 'relative', 'important');
    }
    
    // Footer avec design cohérent
    if (modalFooter) {
        modalFooter.style.setProperty('background', 'linear-gradient(145deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.9) 100%)', 'important');
        modalFooter.style.setProperty('color', '#0f172a', 'important');
        modalFooter.style.setProperty('border', 'none', 'important');
        modalFooter.style.setProperty('border-radius', '0 0 28px 28px', 'important');
        modalFooter.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalFooter.style.setProperty('padding', '2rem 2.5rem', 'important');
        modalFooter.style.setProperty('border-top', '1px solid rgba(226, 232, 240, 0.6)', 'important');
        modalFooter.style.setProperty('box-shadow', 'inset 0 1px 0 rgba(255, 255, 255, 0.8)', 'important');
    }
    
    // Champs de formulaire avec design ultra-moderne
    const formControls = modal.querySelectorAll('.form-control, .form-select, input, select, textarea');
    formControls.forEach(control => {
        control.style.setProperty('background', 'rgba(255, 255, 255, 0.85)', 'important');
        control.style.setProperty('border', '2px solid rgba(59, 130, 246, 0.25)', 'important');
        control.style.setProperty('border-radius', '16px', 'important');
        control.style.setProperty('color', '#0f172a', 'important');
        control.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        control.style.setProperty('padding', '1rem 1.25rem', 'important');
        control.style.setProperty('font-size', '1rem', 'important');
        control.style.setProperty('font-weight', '500', 'important');
        control.style.setProperty('transition', 'none', 'important');
        control.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9)', 'important');
        
        // États focus et hover sans animation
        control.addEventListener('focus', function() {
            this.style.setProperty('border-color', '#3b82f6', 'important');
            this.style.setProperty('box-shadow', '0 0 0 4px rgba(59, 130, 246, 0.15), 0 8px 20px rgba(0, 0, 0, 0.12)', 'important');
            this.style.setProperty('background', 'rgba(255, 255, 255, 0.95)', 'important');
        });
        
        control.addEventListener('blur', function() {
            this.style.setProperty('border-color', 'rgba(59, 130, 246, 0.25)', 'important');
            this.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9)', 'important');
            this.style.setProperty('background', 'rgba(255, 255, 255, 0.85)', 'important');
        });
    });
    
    // Labels avec style premium
    const labels = modal.querySelectorAll('label, .form-label');
    labels.forEach(label => {
        label.style.setProperty('color', '#1e293b', 'important');
        label.style.setProperty('font-weight', '700', 'important');
        label.style.setProperty('font-size', '0.95rem', 'important');
        label.style.setProperty('margin-bottom', '0.75rem', 'important');
        label.style.setProperty('text-transform', 'uppercase', 'important');
        label.style.setProperty('letter-spacing', '0.05em', 'important');
        label.style.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 0.8)', 'important');
    });
    
    // Boutons avec design premium
    const buttons = modal.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.style.setProperty('border-radius', '16px', 'important');
        button.style.setProperty('padding', '1rem 2rem', 'important');
        button.style.setProperty('font-weight', '700', 'important');
        button.style.setProperty('font-size', '1rem', 'important');
        button.style.setProperty('transition', 'none', 'important');
        button.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        button.style.setProperty('text-transform', 'uppercase', 'important');
        button.style.setProperty('letter-spacing', '0.025em', 'important');
        
        if (button.classList.contains('btn-primary')) {
            button.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #6366f1 100%)', 'important');
            button.style.setProperty('border', 'none', 'important');
            button.style.setProperty('color', '#ffffff', 'important');
            button.style.setProperty('box-shadow', '0 10px 25px rgba(59, 130, 246, 0.4), 0 4px 12px rgba(59, 130, 246, 0.3)', 'important');
            button.style.setProperty('text-shadow', '0 1px 2px rgba(0, 0, 0, 0.2)', 'important');
            
            // Pas d'animation hover pour le bouton principal
            
        } else if (button.classList.contains('btn-secondary')) {
            button.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
            button.style.setProperty('border', '2px solid rgba(156, 163, 175, 0.4)', 'important');
            button.style.setProperty('color', '#374151', 'important');
            button.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.12)', 'important');
            
            // Pas d'animation hover pour le bouton secondaire
        }
    });
    
    // Textes muted avec style premium
    const mutedTexts = modal.querySelectorAll('.text-muted, .small');
    mutedTexts.forEach(text => {
        text.style.setProperty('color', '#64748b', 'important');
        text.style.setProperty('font-size', '0.9rem', 'important');
        text.style.setProperty('font-weight', '500', 'important');
    });
}

// Design standard pour ajouterTacheModal
function forceStandardModalDayMode(modal) {
    const modalDialog = modal.querySelector('.modal-dialog');
    const modalContent = modal.querySelector('.modal-content');
    const modalHeader = modal.querySelector('.modal-header');
    const modalBody = modal.querySelector('.modal-body');
    const modalFooter = modal.querySelector('.modal-footer');
    
    // Modal standard
    modal.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
    
    if (modalDialog) {
        modalDialog.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        modalDialog.style.setProperty('transform', 'none', 'important');
        modalDialog.style.setProperty('transition', 'all 0.3s ease', 'important');
    }
    
    if (modalContent) {
        modalContent.style.setProperty('background', 'rgba(255, 255, 255, 0.95)', 'important');
        modalContent.style.setProperty('color', '#1f2937', 'important');
        modalContent.style.setProperty('border', 'none', 'important');
        modalContent.style.setProperty('border-radius', '20px', 'important');
        modalContent.style.setProperty('box-shadow', '0 25px 50px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1)', 'important');
        modalContent.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
    }
    
    if (modalHeader) {
        modalHeader.style.setProperty('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'important');
        modalHeader.style.setProperty('color', '#ffffff', 'important');
        modalHeader.style.setProperty('border', 'none', 'important');
        modalHeader.style.setProperty('border-radius', '20px 20px 0 0', 'important');
        modalHeader.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }
    
    if (modalBody) {
        modalBody.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
        modalBody.style.setProperty('color', '#1f2937', 'important');
        modalBody.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }
    
    if (modalFooter) {
        modalFooter.style.setProperty('background', 'rgba(248, 249, 250, 0.9)', 'important');
        modalFooter.style.setProperty('color', '#1f2937', 'important');
        modalFooter.style.setProperty('border', 'none', 'important');
        modalFooter.style.setProperty('border-radius', '0 0 20px 20px', 'important');
        modalFooter.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }
    
    // Champs de formulaire standard
    const formControls = modal.querySelectorAll('.form-control, .form-select');
    formControls.forEach(control => {
        control.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
        control.style.setProperty('border', '1px solid rgba(209, 213, 219, 0.8)', 'important');
        control.style.setProperty('color', '#1f2937', 'important');
        control.style.setProperty('backdrop-filter', 'blur(5px)', 'important');
    });
    
    // Boutons standard
    const buttons = modal.querySelectorAll('.btn');
    buttons.forEach(button => {
        if (button.classList.contains('btn-primary')) {
            button.style.setProperty('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'important');
            button.style.setProperty('border', 'none', 'important');
            button.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
        }
    });
}

// Fonction pour forcer les modals en mode nuit
function forceModalsNightMode() {
    console.log('🌙 Forçage des modals en mode nuit avec backdrop');
    
    // Cibler les modals spécifiques
    const modals = ['#ajouterTacheModal', '#ajouterCommandeModal', '#taskDetailsModal'];
    
    modals.forEach(modalId => {
        const modal = document.querySelector(modalId);
        if (modal) {
            const modalDialog = modal.querySelector('.modal-dialog');
            const modalContent = modal.querySelector('.modal-content');
            const modalHeader = modal.querySelector('.modal-header');
            const modalBody = modal.querySelector('.modal-body');
            const modalFooter = modal.querySelector('.modal-footer');
            
            // Forcer le modal lui-même
            if (modal) {
                modal.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                modal.style.setProperty('background', 'rgba(0, 0, 0, 0.7)', 'important');
            }
            
            // Forcer le dialog
            if (modalDialog) {
                modalDialog.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
                modalDialog.style.setProperty('transform', 'none', 'important');
                modalDialog.style.setProperty('transition', 'all 0.3s ease', 'important');
            }
            
            if (modalContent) {
                modalContent.style.setProperty('background', 'rgba(30, 30, 35, 0.9)', 'important');
                modalContent.style.setProperty('color', '#ffffff', 'important');
                modalContent.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.3)', 'important');
                modalContent.style.setProperty('border-radius', '20px', 'important');
                modalContent.style.setProperty('box-shadow', '0 25px 50px rgba(0, 255, 255, 0.4), 0 0 0 1px rgba(0, 255, 255, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
                modalContent.style.setProperty('backdrop-filter', 'blur(25px)', 'important');
            }
            
            if (modalHeader) {
                modalHeader.style.setProperty('background', 'linear-gradient(135deg, #00d4ff 0%, #ff00aa 100%)', 'important');
                modalHeader.style.setProperty('color', '#000000', 'important');
                modalHeader.style.setProperty('border', 'none', 'important');
                modalHeader.style.setProperty('border-radius', '20px 20px 0 0', 'important');
                modalHeader.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                modalHeader.style.setProperty('font-weight', '700', 'important');
            }
            
            if (modalBody) {
                modalBody.style.setProperty('background', 'rgba(30, 30, 35, 0.8)', 'important');
                modalBody.style.setProperty('color', '#ffffff', 'important');
                modalBody.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            }
            
            if (modalFooter) {
                modalFooter.style.setProperty('background', 'rgba(40, 40, 45, 0.8)', 'important');
                modalFooter.style.setProperty('color', '#ffffff', 'important');
                modalFooter.style.setProperty('border', 'none', 'important');
                modalFooter.style.setProperty('border-radius', '0 0 20px 20px', 'important');
                modalFooter.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            }
            
            // Forcer les champs de formulaire
            const formControls = modal.querySelectorAll('.form-control, .form-select');
            formControls.forEach(control => {
                control.style.setProperty('background', 'rgba(40, 40, 45, 0.8)', 'important');
                control.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.4)', 'important');
                control.style.setProperty('color', '#ffffff', 'important');
                control.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
                control.style.setProperty('box-shadow', '0 0 10px rgba(0, 255, 255, 0.2)', 'important');
            });
            
            // Forcer les textes muted
            const mutedTexts = modal.querySelectorAll('.text-muted');
            mutedTexts.forEach(text => {
                text.style.setProperty('color', '#b0b0b0', 'important');
            });
            
            // Forcer les boutons
            const buttons = modal.querySelectorAll('.btn');
            buttons.forEach(button => {
                if (button.classList.contains('btn-primary')) {
                    button.style.setProperty('background', 'linear-gradient(135deg, #00d4ff 0%, #ff00aa 100%)', 'important');
                    button.style.setProperty('border', 'none', 'important');
                    button.style.setProperty('color', '#000000', 'important');
                    button.style.setProperty('font-weight', '700', 'important');
                    button.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                    button.style.setProperty('box-shadow', '0 0 20px rgba(0, 255, 255, 0.5)', 'important');
                }
            });
        }
    });
    
    // Forcer le backdrop global
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.style.setProperty('backdrop-filter', 'blur(12px)', 'important');
        backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.6)', 'important');
    });
    
    console.log('✅ Modals forcés en mode nuit avec backdrop');
}

// Fonction pour forcer les boutons d'action en mode nuit avec le même fond que les statistiques
function forceActionButtonsNightMode() {
    console.log('🌙 Forçage AGRESSIF des boutons d\'action en mode nuit');
    
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach((btn, index) => {
        // Supprimer toutes les classes qui pourraient interférer
        btn.classList.remove('geek-action-btn', 'futuristic-action-btn', 'action-card');
        
        // Styles JS désactivés pour laisser le CSS gérer le Glassmorphism
        // btn.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        // btn.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        // btn.style.setProperty('color', '#ffffff', 'important');
        // btn.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        // btn.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        // btn.style.setProperty('border-radius', '20px', 'important');
        // btn.style.setProperty('padding', '2rem', 'important');
        // btn.style.setProperty('display', 'flex', 'important');
        // btn.style.setProperty('align-items', 'center', 'important');
        // btn.style.setProperty('gap', '1.5rem', 'important');
        // btn.style.setProperty('text-decoration', 'none', 'important');
        // btn.style.setProperty('transition', 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', 'important');
        
        // Ajouter un attribut pour identifier les boutons forcés
        btn.setAttribute('data-night-forced', 'true');
    });
    
    console.log('✅ Boutons d\'action ULTRA-FORCÉS en mode nuit:', actionButtons.length, 'boutons');
}

// ========================================
// EFFETS VISUELS FUTURISTES - MODE NUIT
// ========================================

function injectNightModeEffects() {
    // Éviter les doublons
    if (document.querySelector('.night-mode-bg-effects')) return;
    
    console.log('✨ Injection des effets visuels futuristes mode nuit');
    
    // Créer le conteneur principal
    const container = document.createElement('div');
    container.className = 'night-mode-bg-effects';
    
    // Ajouter les lueurs de coins
    const glowTopLeft = document.createElement('div');
    glowTopLeft.className = 'night-corner-glow top-left';
    container.appendChild(glowTopLeft);
    
    const glowBottomRight = document.createElement('div');
    glowBottomRight.className = 'night-corner-glow bottom-right';
    container.appendChild(glowBottomRight);
    
    // Ajouter des particules flottantes
    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.className = 'night-particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (10 + Math.random() * 10) + 's';
        particle.style.width = (2 + Math.random() * 4) + 'px';
        particle.style.height = particle.style.width;
        particle.style.opacity = 0.3 + Math.random() * 0.5;
        container.appendChild(particle);
    }
    
    // Ajouter quelques lignes de données
    for (let i = 0; i < 3; i++) {
        const dataLine = document.createElement('div');
        dataLine.className = 'night-data-line';
        dataLine.style.top = (20 + i * 30) + '%';
        dataLine.style.width = (100 + Math.random() * 200) + 'px';
        dataLine.style.animationDelay = (i * 2) + 's';
        container.appendChild(dataLine);
    }
    
    // Insérer au début du body
    document.body.insertBefore(container, document.body.firstChild);
    
    console.log('✅ Effets visuels futuristes injectés');
}

function removeNightModeEffects() {
    const container = document.querySelector('.night-mode-bg-effects');
    if (container) {
        container.remove();
        console.log('🧹 Effets visuels futuristes supprimés');
    }
}

// Surveillance continue des boutons d'action en mode nuit
let nightModeWatcher = null;

function startNightModeWatcher() {
    if (nightModeWatcher) {
        clearInterval(nightModeWatcher);
    }
    
    console.log('🔄 Démarrage de la surveillance continue du mode nuit');
    
    nightModeWatcher = setInterval(() => {
        if (currentTheme === 'night' && document.body.classList.contains('night-mode')) {
            const actionButtons = document.querySelectorAll('.action-btn');
            let needsForcing = false;
            
            actionButtons.forEach(btn => {
                const currentBg = window.getComputedStyle(btn).backgroundColor;
                // Vérifier si le fond n'est pas celui attendu
                if (!currentBg.includes('30, 30, 35') && !currentBg.includes('rgba(30, 30, 35')) {
                    needsForcing = true;
                }
            });
            
            if (needsForcing) {
                console.log('⚠️ Styles écrasés détectés - Re-forçage immédiat');
                forceActionButtonsNightMode();
            }
        }
    }, 500); // Vérification toutes les 500ms
}

function stopNightModeWatcher() {
    if (nightModeWatcher) {
        clearInterval(nightModeWatcher);
        nightModeWatcher = null;
        console.log('⏹️ Arrêt de la surveillance du mode nuit');
    }
}

// MutationObserver pour détecter les changements de style en temps réel
let styleObserver = null;

function startStyleObserver() {
    if (styleObserver) {
        styleObserver.disconnect();
    }
    
    console.log('👁️ Démarrage de l\'observateur de styles');
    
    styleObserver = new MutationObserver((mutations) => {
        if (currentTheme === 'night' && document.body.classList.contains('night-mode')) {
            let needsForcing = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    const target = mutation.target;
                    if (target.classList.contains('action-btn')) {
                        needsForcing = true;
                    }
                }
            });
            
            if (needsForcing) {
                console.log('🔄 Changement de style détecté - Re-forçage');
                setTimeout(() => forceActionButtonsNightMode(), 10);
            }
        }
    });
    
    // Observer tous les boutons d'action
    document.querySelectorAll('.action-btn').forEach(btn => {
        styleObserver.observe(btn, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
}

function stopStyleObserver() {
    if (styleObserver) {
        styleObserver.disconnect();
        styleObserver = null;
        console.log('⏹️ Arrêt de l\'observateur de styles');
    }
}

// ========================================
// PARTICULES FLOTTANTES (MODE NUIT)
// ========================================
function createParticles() {
    const container = document.getElementById('particles');
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        container.appendChild(particle);
    }
    particlesCreated = true;
}

function removeParticles() {
    const container = document.getElementById('particles');
    container.innerHTML = '';
    particlesCreated = false;
}

// ========================================
// MODALS DE STATISTIQUES
// ========================================
</script>
<!-- Système de statistiques avancé -->
<script src="assets/js/advanced-stats-system.js?v=<?php echo time(); ?>"></script>
<script>

// ========================================
// GESTION DES ONGLETS
// ========================================
function switchTab(tabId) {
    console.log('Basculement vers onglet:', tabId);
    
    // Masquer tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.modern-tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer le contenu de l'onglet sélectionné
    const selectedContent = document.getElementById(tabId);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }
    
    // Activer le bouton de l'onglet sélectionné
    const selectedButton = document.querySelector(`[data-tab="${tabId}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

// ========================================
// INITIALISATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le thème automatique
    initTheme();
    
    // Configurer l'écoute des changements de préférences système
    setupThemeListener();
    
    // Configurer les écouteurs pour les modals
    setupModalListeners();
    
    // Forcer les bons styles au chargement selon le thème
    setTimeout(() => {
        if (currentTheme === 'night') {
            forceStatCardsNightMode();
            forceActionButtonsNightMode();
            injectNightModeEffects(); // Injecter les animations futuristes
        } else {
            forceStatCardsDayMode();
            removeNightModeEffects(); // Nettoyer les animations
        }
    }, 100);
    
    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                // Vérifier et corriger les styles après l'animation
                setTimeout(() => {
                    if (currentTheme === 'night') {
                        forceStatCardsNightMode();
                        forceActionButtonsNightMode();
                    } else {
                        forceStatCardsDayMode();
                    }
                }, 50);
            }
        });
    }, observerOptions);
    
    // Observer tous les éléments avec fade-in
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    console.log('✅ Page accueil-modern initialisée');
});

// Charger les scripts et styles du système de statistiques avancé
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Chargement du système de statistiques avancé...');
    
    // Charger les styles du système de statistiques en premier
    const statsCSS = document.createElement('link');
    statsCSS.rel = 'stylesheet';
    statsCSS.href = 'assets/css/advanced-stats-system.css';
    document.head.appendChild(statsCSS);
    
    // Fonction pour charger Chart.js puis le système de stats
    function loadStatsSystem() {
        if (typeof Chart === 'undefined') {
            console.log('📊 Chargement de Chart.js...');
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            chartScript.onload = function() {
                console.log('✅ Chart.js chargé, chargement du système de stats...');
                loadAdvancedStatsScript();
            };
            chartScript.onerror = function() {
                console.error('❌ Erreur lors du chargement de Chart.js');
                loadAdvancedStatsScript(); // Charger quand même le système
            };
            document.head.appendChild(chartScript);
        } else {
            console.log('📊 Chart.js déjà disponible');
            loadAdvancedStatsScript();
        }
    }
    
    // Fonction pour charger le script du système de statistiques
    function loadAdvancedStatsScript() {
        const statsScript = document.createElement('script');
        statsScript.src = 'assets/js/advanced-stats-system.js';
        statsScript.onload = function() {
            console.log('✅ Système de statistiques avancé chargé avec succès');
        };
        statsScript.onerror = function() {
            console.error('❌ Erreur lors du chargement du système de statistiques');
        };
        document.head.appendChild(statsScript);
    }
    
    // Démarrer le chargement
    loadStatsSystem();
});

// ========================================
// DÉTECTION TACTILE
// ========================================
function isTouchDevice() {
    return (('ontouchstart' in window) ||
           (navigator.maxTouchPoints > 0) ||
           (navigator.msMaxTouchPoints > 0));
}

// Ajuster les interactions pour les appareils tactiles
if (isTouchDevice()) {
    document.body.classList.add('touch-device');
    
    // Gestion des touches pour les NOUVELLES cartes
    document.querySelectorAll('.modern-action-card, .status-metric-card, .daily-analytics-card, .table-row').forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // 🛡️ PROTECTION ULTRA-AGRESSIVE DES BOUTONS D'ACTION
    function forceActionButtonStyles() {
        const actionButtons = document.querySelectorAll('.action-btn');
        const isNightMode = document.body.classList.contains('night-mode');
        
        actionButtons.forEach((btn, index) => {
            // Supprimer toutes les classes qui pourraient interférer
            btn.classList.remove('geek-action-btn', 'futuristic-action-btn', 'action-card');
            
            // Forcer les styles avec setProperty pour bypasser !important
            const style = btn.style;
            
            if (isNightMode) {
                // Styles mode nuit - EXACTEMENT le même fond que les boutons de statistiques
                style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
                style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
                style.setProperty('color', '#ffffff', 'important');
                style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
            } else {
                // Styles mode jour
                style.setProperty('background', 'linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%)', 'important');
                style.setProperty('border', '3px solid rgba(59, 130, 246, 0.3)', 'important');
                style.setProperty('color', '#1e293b', 'important');
                style.setProperty('box-shadow', '0 15px 50px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1), inset 0 2px 0 rgba(255, 255, 255, 0.9), 0 0 0 1px rgba(255, 255, 255, 0.5)', 'important');
            }
            
            style.setProperty('border-radius', '20px', 'important');
            style.setProperty('padding', '2rem', 'important');
            style.setProperty('display', 'flex', 'important');
            style.setProperty('align-items', 'center', 'important');
            style.setProperty('gap', '1.5rem', 'important');
            style.setProperty('text-decoration', 'none', 'important');
            style.setProperty('transition', 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', 'important');
            style.setProperty('backdrop-filter', 'blur(20px)', 'important');
            style.setProperty('position', 'relative', 'important');
            style.setProperty('overflow', 'hidden', 'important');
            style.setProperty('animation', 'slideInUp 0.6s ease-out', 'important');
            style.setProperty('width', 'auto', 'important');
            style.setProperty('height', 'auto', 'important');
            style.setProperty('min-width', 'auto', 'important');
            style.setProperty('min-height', 'auto', 'important');
            style.setProperty('max-width', 'none', 'important');
            style.setProperty('max-height', 'none', 'important');
            style.setProperty('flex', 'none', 'important');

            // Forcer les styles des icônes
            const icon = btn.querySelector('.icon');
            if (icon) {
                const iconStyle = icon.style;
                iconStyle.setProperty('width', '60px', 'important');
                iconStyle.setProperty('height', '60px', 'important');
                iconStyle.setProperty('border-radius', '16px', 'important');
                iconStyle.setProperty('display', 'flex', 'important');
                iconStyle.setProperty('align-items', 'center', 'important');
                iconStyle.setProperty('justify-content', 'center', 'important');
                iconStyle.setProperty('font-size', '1.75rem', 'important');
                iconStyle.setProperty('flex-shrink', '0', 'important');
                iconStyle.setProperty('transition', 'all 0.3s ease', 'important');

                // Couleurs spécifiques par bouton selon le mode
                let colors, shadows;
                
                if (isNightMode) {
                    // Mode nuit - Couleurs néon
                    iconStyle.setProperty('color', '#000000', 'important');
                    colors = [
                        'linear-gradient(135deg, #00d4ff 0%, #0099cc 100%)', // Cyan
                        'linear-gradient(135deg, #00ff41 0%, #00cc33 100%)', // Vert néon
                        'linear-gradient(135deg, #ff8c00 0%, #ff6600 100%)', // Orange néon
                        'linear-gradient(135deg, #ff00aa 0%, #cc0088 100%)'  // Rose néon
                    ];
                    
                    shadows = [
                        '0 4px 16px rgba(0, 212, 255, 0.5), 0 0 20px rgba(0, 212, 255, 0.3)',
                        '0 4px 16px rgba(0, 255, 65, 0.5), 0 0 20px rgba(0, 255, 65, 0.3)',
                        '0 4px 16px rgba(255, 140, 0, 0.5), 0 0 20px rgba(255, 140, 0, 0.3)',
                        '0 4px 16px rgba(255, 0, 170, 0.5), 0 0 20px rgba(255, 0, 170, 0.3)'
                    ];
                } else {
                    // Mode jour - Couleurs classiques
                    iconStyle.setProperty('color', 'white', 'important');
                    colors = [
                        'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)', // Bleu
                        'linear-gradient(135deg, #10b981 0%, #059669 100%)', // Vert
                        'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', // Orange
                        'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)'  // Violet
                    ];
                    
                    shadows = [
                        '0 4px 16px rgba(59, 130, 246, 0.3)',
                        '0 4px 16px rgba(16, 185, 129, 0.3)',
                        '0 4px 16px rgba(245, 158, 11, 0.3)',
                        '0 4px 16px rgba(139, 92, 246, 0.3)'
                    ];
                }

                if (colors[index]) {
                    iconStyle.setProperty('background', colors[index], 'important');
                    iconStyle.setProperty('box-shadow', shadows[index], 'important');
                }
            }

            // Forcer les styles du contenu
            const content = btn.querySelector('.content');
            if (content) {
                const h3 = content.querySelector('h3');
                const p = content.querySelector('p');
                
                if (h3) {
                    const h3Style = h3.style;
                    h3Style.setProperty('margin', '0 0 0.5rem 0', 'important');
                    h3Style.setProperty('font-size', '1.4rem', 'important');
                    h3Style.setProperty('font-weight', '900', 'important');
                    h3Style.setProperty('letter-spacing', '-0.025em', 'important');
                    
                    if (isNightMode) {
                        h3Style.setProperty('color', '#f8fafc', 'important');
                        h3Style.setProperty('text-shadow', '0 0 20px rgba(0, 212, 255, 1), 0 3px 6px rgba(0, 0, 0, 0.8), 0 0 40px rgba(0, 212, 255, 0.6), 0 1px 0 rgba(0, 212, 255, 0.8)', 'important');
                    } else {
                        h3Style.setProperty('color', '#020617', 'important');
                        h3Style.setProperty('text-shadow', '0 2px 4px rgba(255, 255, 255, 1), 0 1px 0 rgba(255, 255, 255, 0.8), 0 0 10px rgba(255, 255, 255, 0.5)', 'important');
                    }
                }
                
                if (p) {
                    const pStyle = p.style;
                    pStyle.setProperty('margin', '0', 'important');
                    pStyle.setProperty('font-size', '0.95rem', 'important');
                    pStyle.setProperty('font-weight', '600', 'important');
                    
                    if (isNightMode) {
                        pStyle.setProperty('color', '#e2e8f0', 'important');
                        pStyle.setProperty('text-shadow', '0 0 15px rgba(0, 212, 255, 0.8), 0 2px 4px rgba(0, 0, 0, 0.5), 0 0 25px rgba(0, 212, 255, 0.4)', 'important');
                    } else {
                        pStyle.setProperty('color', '#334155', 'important');
                        pStyle.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 1), 0 0 5px rgba(255, 255, 255, 0.7)', 'important');
                    }
                }
            }
        });
    }

    // Appliquer immédiatement
    forceActionButtonStyles();

    // Réappliquer toutes les 100ms pendant les 5 premières secondes
    let protectionInterval = setInterval(forceActionButtonStyles, 100);
    setTimeout(() => {
        clearInterval(protectionInterval);
        // Puis toutes les secondes pendant 10 secondes
        protectionInterval = setInterval(forceActionButtonStyles, 1000);
        setTimeout(() => {
            clearInterval(protectionInterval);
            console.log('🛡️ Protection des boutons d\'action terminée');
        }, 10000);
    }, 5000);

    // Observer les changements de style
    const styleObserver = new MutationObserver(function(mutations) {
        let needsForcing = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && 
                (mutation.attributeName === 'style' || mutation.attributeName === 'class') &&
                mutation.target.classList.contains('action-btn')) {
                needsForcing = true;
            }
        });
        if (needsForcing) {
            setTimeout(forceActionButtonStyles, 10);
        }
    });

    // Observer tous les boutons d'action
    document.querySelectorAll('.action-btn').forEach(btn => {
        styleObserver.observe(btn, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });

    console.log('🛡️ Protection ultra-agressive des boutons d\'action activée');
}
</script>

<!-- 🛡️ CSS DE PROTECTION ABSOLUE - CHARGÉ EN DERNIER -->
<link rel="stylesheet" href="assets/css/action-buttons-force-override.css?v=<?php echo time(); ?>" type="text/css">

<script>
// 🛡️ PROTECTION FINALE - Injecter du CSS inline en dernier recours
function injectFinalCSS() {
    const style = document.createElement('style');
    style.innerHTML = `
        /* PROTECTION FINALE AVEC PRIORITÉ ABSOLUE */
        .action-btn {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
            border: 3px solid rgba(59, 130, 246, 0.3) !important;
            border-radius: 20px !important;
            padding: 2rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 1.5rem !important;
            text-decoration: none !important;
            color: #1e293b !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            backdrop-filter: blur(25px) !important;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1), inset 0 2px 0 rgba(255, 255, 255, 0.9), 0 0 0 1px rgba(255, 255, 255, 0.5) !important;
            position: relative !important;
            overflow: hidden !important;
            animation: slideInUp 0.6s ease-out !important;
            width: auto !important;
            height: auto !important;
            min-width: auto !important;
            min-height: auto !important;
            max-width: none !important;
            max-height: none !important;
            flex: none !important;
        }
        .action-btn:hover {
            transform: translateY(-8px) scale(1.02) !important;
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
            box-shadow: 0 25px 80px rgba(59, 130, 246, 0.25), 0 12px 32px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 1) !important;
            border: 2px solid rgba(59, 130, 246, 0.4) !important;
        }
        .action-btn .icon {
            width: 60px !important;
            height: 60px !important;
            border-radius: 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.75rem !important;
            flex-shrink: 0 !important;
            transition: all 0.3s ease !important;
            color: white !important;
        }
        .action-btn:nth-child(1) .icon { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important; box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3) !important; }
        .action-btn:nth-child(2) .icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3) !important; }
        .action-btn:nth-child(3) .icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3) !important; }
        .action-btn:nth-child(4) .icon { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important; box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3) !important; }
        .action-btn:hover .icon { transform: scale(1.1) rotate(5deg) !important; }
        .action-btn:nth-child(1):hover .icon { box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5) !important; }
        .action-btn:nth-child(2):hover .icon { box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5) !important; }
        .action-btn:nth-child(3):hover .icon { box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5) !important; }
        .action-btn:nth-child(4):hover .icon { box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5) !important; }
        .action-btn .content h3 { margin: 0 0 0.5rem 0 !important; font-size: 1.4rem !important; font-weight: 900 !important; color: #020617 !important; letter-spacing: -0.025em !important; text-shadow: 0 2px 4px rgba(255, 255, 255, 1), 0 1px 0 rgba(255, 255, 255, 0.8), 0 0 10px rgba(255, 255, 255, 0.5) !important; }
        .action-btn .content p { margin: 0 !important; font-size: 0.95rem !important; font-weight: 600 !important; color: #334155 !important; text-shadow: 0 1px 2px rgba(255, 255, 255, 1), 0 0 5px rgba(255, 255, 255, 0.7) !important; }
        
        /* MODE NUIT */
/*
        body.night-mode .action-btn, .night-mode .action-btn {
            /* Styles gérés par CSS global désormais */
        }
        body.night-mode .action-btn:hover, .night-mode .action-btn:hover {
             /* Styles gérés par CSS global désormais */
        }
*/  
        
        /* Styles spécifiques pour les icônes en mode nuit pour garantir la visibilité */
        body.night-mode .action-btn .icon, .night-mode .action-btn .icon {
            color: #ffffff !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 0 15px rgba(0, 212, 255, 0.3) inset !important;
        }
        body.night-mode .action-btn:nth-child(1) .icon, .night-mode .action-btn:nth-child(1) .icon { background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%) !important; box-shadow: 0 4px 16px rgba(0, 212, 255, 0.5), 0 0 20px rgba(0, 212, 255, 0.3) !important; }
        body.night-mode .action-btn:nth-child(2) .icon, .night-mode .action-btn:nth-child(2) .icon { background: linear-gradient(135deg, #00ff41 0%, #00cc33 100%) !important; box-shadow: 0 4px 16px rgba(0, 255, 65, 0.5), 0 0 20px rgba(0, 255, 65, 0.3) !important; }
        body.night-mode .action-btn:nth-child(3) .icon, .night-mode .action-btn:nth-child(3) .icon { background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%) !important; box-shadow: 0 4px 16px rgba(255, 140, 0, 0.5), 0 0 20px rgba(255, 140, 0, 0.3) !important; }
        body.night-mode .action-btn:nth-child(4) .icon, .night-mode .action-btn:nth-child(4) .icon { background: linear-gradient(135deg, #ff00aa 0%, #cc0088 100%) !important; box-shadow: 0 4px 16px rgba(255, 0, 170, 0.5), 0 0 20px rgba(255, 0, 170, 0.3) !important; }
        body.night-mode .action-btn:nth-child(1):hover .icon, .night-mode .action-btn:nth-child(1):hover .icon { box-shadow: 0 8px 24px rgba(0, 212, 255, 0.7), 0 0 30px rgba(0, 212, 255, 0.5) !important; }
        body.night-mode .action-btn:nth-child(2):hover .icon, .night-mode .action-btn:nth-child(2):hover .icon { box-shadow: 0 8px 24px rgba(0, 255, 65, 0.7), 0 0 30px rgba(0, 255, 65, 0.5) !important; }
        body.night-mode .action-btn:nth-child(3):hover .icon, .night-mode .action-btn:nth-child(3):hover .icon { box-shadow: 0 8px 24px rgba(255, 140, 0, 0.7), 0 0 30px rgba(255, 140, 0, 0.5) !important; }
        body.night-mode .action-btn:nth-child(4):hover .icon, .night-mode .action-btn:nth-child(4):hover .icon { box-shadow: 0 8px 24px rgba(255, 0, 170, 0.7), 0 0 30px rgba(255, 0, 170, 0.5) !important; }
        body.night-mode .action-btn .content h3, .night-mode .action-btn .content h3 { color: #f8fafc !important; font-size: 1.4rem !important; font-weight: 900 !important; text-shadow: 0 0 20px rgba(0, 212, 255, 1), 0 3px 6px rgba(0, 0, 0, 0.8), 0 0 40px rgba(0, 212, 255, 0.6), 0 1px 0 rgba(0, 212, 255, 0.8) !important; }
        body.night-mode .action-btn .content p, .night-mode .action-btn .content p { color: #e2e8f0 !important; font-size: 0.95rem !important; font-weight: 600 !important; text-shadow: 0 0 15px rgba(0, 212, 255, 0.8), 0 2px 4px rgba(0, 0, 0, 0.5), 0 0 25px rgba(0, 212, 255, 0.4) !important; }
    `;
    document.head.appendChild(style);
    console.log('🛡️ CSS de protection finale injecté');
}

// Injecter le CSS après un délai
setTimeout(injectFinalCSS, 2000);
setTimeout(injectFinalCSS, 5000);
</script>

<!-- Modal pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- En-tête du modal -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="taskDetailsModalLabel" style="margin:0;">Détails de la tâche</h5>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="modern-priority-badge" id="task-priority"></span>
                            <span class="modern-status-badge" id="task-status">En attente</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row g-3">
                        <!-- Colonne gauche: titre + description -->
                        <div class="col-12 col-lg-8">
                            <div class="task-details-card" style="margin-bottom:1rem;">
                                <h3 class="section-title" style="margin-bottom:1rem;">
                                    <i class="fas fa-heading me-2"></i>Titre
                                </h3>
                                <h4 id="task-title" class="modern-task-title" style="margin:0;"></h4>
                            </div>

                            <div class="task-details-card" style="margin-bottom:1rem;">
                                <h3 class="section-title" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-file-alt"></i>
                                    Description
                                </h3>
                                <div class="description-content">
                                    <div id="task-description-loader" class="description-loader" style="display:none;">
                                        <div class="loader-spinner"></div>
                                        <span>Chargement de la description...</span>
                                    </div>
                                    <p id="task-description" class="modern-description" style="margin:0;"></p>
                                </div>
                            </div>

                            <!-- Pièces jointes -->
                            <div id="task-attachments" class="task-details-card" style="display:none;">
                                <h3 class="section-title" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-paperclip"></i>
                                    Pièces jointes
                                </h3>
                                <div id="task-attachments-list"></div>
                            </div>
                        </div>

                        <!-- Colonne droite: informations complémentaires -->
                        <div class="col-12 col-lg-4">
                            <div class="task-details-card">
                                <h3 class="section-title" style="margin-bottom:1rem;">
                                    <i class="fas fa-info-circle me-2"></i>Informations
                                </h3>
                                <div class="task-info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Créée le</span>
                                        <span id="task-created-date" class="info-value">-</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Assignée à</span>
                                        <span id="task-assignee" class="info-value">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteneur d'erreur -->
                <div id="task-error-container" class="error-container" style="display:none;">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="error-message">Une erreur est survenue</div>
                </div>
            </div>

            <!-- Pied du modal avec boutons d'action -->
            <div class="modal-footer">
                <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                    <i class="fas fa-play me-2"></i> Démarrer
                </button>
                <button id="complete-task-btn" class="btn btn-success" data-task-id="" data-status="termine">
                    <i class="fas fa-check me-2"></i> Terminer
                </button>
                <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt me-2"></i> Voir toutes les tâches
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal des tâches -->
<style>
.task-details-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px var(--day-shadow);
}

body.night-mode .task-details-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.modern-task-title {
    color: var(--day-text);
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 15px;
    line-height: 1.3;
}

body.night-mode .modern-task-title {
    color: #f9fafb;
}

.section-title {
    color: var(--day-text);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

body.night-mode .section-title {
    color: #f9fafb;
}

.modern-description {
    color: var(--day-text-light);
    font-size: 1rem;
    line-height: 1.6;
}

body.night-mode .modern-description {
    color: #e5e7eb;
}

.modern-priority-badge {
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.modern-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.task-info-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--day-border);
}

body.night-mode .info-item {
    border-bottom: 1px solid rgba(0, 255, 255, 0.2);
}

.info-label {
    font-weight: 600;
    color: var(--day-text-light);
}

body.night-mode .info-label {
    color: #b0b0b0;
}

.info-value {
    font-weight: 500;
    color: var(--day-text);
}

body.night-mode .info-value {
    color: #ffffff;
}

.description-loader {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    justify-content: center;
}

.loader-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--day-border);
    border-top: 3px solid var(--day-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

body.night-mode .loader-spinner {
    border: 3px solid rgba(0, 255, 255, 0.2);
    border-top: 3px solid #00d4ff;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 12px;
    color: #dc2626;
    margin-top: 1rem;
}

.error-icon {
    font-size: 1.5rem;
}

.attachment-item {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.5rem;
}

body.night-mode .attachment-item {
    background: rgba(40, 40, 45, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
}
</style>

<!-- Modal pour changer le statut des commandes -->

<!-- Modal moderne pour changer le statut d'une commande -->
<div class="modal fade" id="commandeStatutModal" tabindex="-1" aria-labelledby="commandeStatutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- En-tête du modal -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="commandeStatutModalLabel" style="margin:0;">Changer le statut</h5>
                        <p class="modal-subtitle">Mettre à jour le statut de la commande</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body">
                <div class="statut-update-container">
                    <!-- Section titre et statut actuel -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="statut-commande-reference" class="modern-task-title"></h4>
                            <p id="statut-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut actuel</span>
                                    <span id="statut-actuel" class="modern-priority-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options de statut -->
                    <div class="status-options-grid">
                        <div class="status-option" data-status="en_attente">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #ffa502, #ff6348);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">En attente</div>
                                    <div class="status-description">Commande en attente de traitement</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="commande">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #3742fa, #2f3542);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Commandé</div>
                                    <div class="status-description">Commande passée chez le fournisseur</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="recue">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #2ed573, #1e90ff);">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Reçu</div>
                                    <div class="status-description">Pièce reçue en magasin</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="utilise">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #70a1ff, #5352ed);">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Utilisé</div>
                                    <div class="status-description">Pièce utilisée pour la réparation</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="annulee">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #ff4757, #c44569);">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Annulé</div>
                                    <div class="status-description">Commande annulée</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="a_retourner">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #57606f, #3d4454);">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">À retourner</div>
                                    <div class="status-description">Pièce à retourner au fournisseur</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loader et erreur -->
                    <div id="statut-update-loader" class="description-loader" style="display:none;">
                        <div class="loader-spinner"></div>
                        <span>Mise à jour en cours...</span>
                    </div>

                    <div id="statut-error-container" class="error-container" style="display:none;">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="error-message">Une erreur est survenue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour les modals des commandes -->
<style>
.task-subtitle {
    color: var(--day-text-light);
    font-size: 1rem;
    margin: 0;
}

body.night-mode .task-subtitle {
    color: #b0b0b0;
}

.task-header-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--day-border);
}

body.night-mode .task-header-section {
    border-bottom: 1px solid rgba(0, 255, 255, 0.2);
}

.task-meta {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.priority-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.priority-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--day-text-light);
}

body.night-mode .priority-label {
    color: #b0b0b0;
}

.status-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 2rem;
}

/* Responsive pour mobile - 2 colonnes sur écrans plus petits */
@media (max-width: 768px) {
    .status-options-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(3, 1fr);
    }
}

.status-option {
    cursor: pointer;
}

.status-option-card {
    background: var(--day-card-bg);
    border: 2px solid var(--day-border);
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    min-height: 80px;
}

body.night-mode .status-option-card {
    background: rgba(30, 30, 35, 0.95);
    border: 2px solid rgba(0, 255, 255, 0.2);
}

.status-option-card:hover {
    border-color: var(--day-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--day-shadow);
}

body.night-mode .status-option-card:hover {
    border-color: #00d4ff;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.25);
}

.status-option-card.selected {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.1);
}

body.night-mode .status-option-card.selected {
    border-color: #00d4ff;
    background: rgba(0, 212, 255, 0.1);
}

.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.status-info {
    flex: 1;
}

.status-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--day-text);
    margin-bottom: 0.2rem;
    line-height: 1.2;
}

body.night-mode .status-title {
    color: #ffffff;
}

.status-description {
    font-size: 0.8rem;
    color: var(--day-text-light);
    line-height: 1.3;
}

body.night-mode .status-description {
    color: #b0b0b0;
}

.modal-subtitle {
    font-size: 0.875rem;
    color: var(--day-text-light);
    margin: 0;
}

body.night-mode .modal-subtitle {
    color: #b0b0b0;
}

/* ========================================
   CORRECTION NAVBAR MODE NUIT - ACCUEIL MODERN
======================================== */

/* Forcer les styles du mode nuit sur la navbar même avec les classes Bootstrap */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar,
body.night-mode .navbar-light,
body.night-mode .navbar.bg-white {
    background: linear-gradient(135deg, #0f1419 0%, #1a1f2e 50%, #0f1419 100%) !important;
    border-bottom: 2px solid transparent !important;
    border-image: linear-gradient(90deg, #00d4ff 0%, #0099cc 50%, #00d4ff 100%) 1 !important;
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
}

/* Textes et éléments de la navbar en mode nuit */
body.night-mode #desktop-navbar .navbar-brand,
body.night-mode #desktop-navbar .fw-medium,
body.night-mode #desktop-navbar .text-primary,
body.night-mode #desktop-navbar a,
body.night-mode #desktop-navbar span {
    color: #e2e8f0 !important;
    text-shadow: 0 0 5px rgba(0, 212, 255, 0.5) !important;
}

/* Logo avec effet néon en mode nuit */
body.night-mode #desktop-navbar .navbar-brand img {
    filter: brightness(1.2) contrast(1.1) drop-shadow(0 0 8px rgba(0, 212, 255, 0.6)) !important;
    transition: all 0.3s ease !important;
}

/* Boutons de la navbar en mode nuit */
body.night-mode #desktop-navbar .btn {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #e2e8f0 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    transition: all 0.3s ease !important;
}

body.night-mode #desktop-navbar .btn:hover {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    border-color: rgba(0, 212, 255, 0.6) !important;
    color: #00d4ff !important;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-1px) !important;
}

/* Bouton primaire spécial en mode nuit */
body.night-mode #desktop-navbar .btn-primary {
    background: linear-gradient(135deg, #0369a1 0%, #0284c7 50%, #0369a1 100%) !important;
    border: 1px solid rgba(0, 212, 255, 0.5) !important;
    box-shadow: 0 0 15px rgba(3, 105, 161, 0.4) !important;
}

body.night-mode #desktop-navbar .btn-primary:hover {
    background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 50%, #0284c7 100%) !important;
    box-shadow: 0 0 20px rgba(14, 165, 233, 0.6) !important;
}

/* Badge en mode nuit */
body.night-mode #desktop-navbar .badge {
    background: #0369a1 !important;
    color: white !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
}

/* Animation SERVO en mode nuit */
body.night-mode .servo-logo-container svg path {
    filter: drop-shadow(0 0 8px rgba(0, 212, 255, 0.8)) !important;
}

/* Icônes des boutons avec effet néon */
body.night-mode #desktop-navbar .btn i {
    text-shadow: 0 0 5px rgba(0, 212, 255, 0.5) !important;
    transition: all 0.3s ease !important;
}

body.night-mode #desktop-navbar .btn:hover i {
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.8) !important;
    transform: scale(1.1) !important;
}

/* ========================================
   MODAL STATISTIQUES MODE NUIT
======================================== */

/* Modal de statistiques en mode nuit */
.stats-modal-dark {
    background: linear-gradient(135deg, #0f1419 0%, #1a1f2e 100%) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    box-shadow: 0 20px 60px rgba(0, 212, 255, 0.2), 
                0 0 0 1px rgba(0, 212, 255, 0.1) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
}

.stats-modal-dark .modal-header {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1) 0%, rgba(138, 43, 226, 0.1) 100%) !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #e2e8f0 !important;
}

.stats-modal-dark .modal-title {
    color: #e2e8f0 !important;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5) !important;
}

.stats-modal-dark .modal-body {
    background: transparent !important;
    color: #e2e8f0 !important;
}

.stats-modal-dark .display-1 {
    color: #00d4ff !important;
    text-shadow: 0 0 20px rgba(0, 212, 255, 0.6) !important;
    font-weight: 700 !important;
}

.stats-modal-dark .lead {
    color: #e2e8f0 !important;
    text-shadow: 0 0 5px rgba(0, 212, 255, 0.3) !important;
}

.stats-modal-dark .text-muted {
    color: #94a3b8 !important;
}

.stats-modal-dark .modal-footer {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.05) 0%, rgba(138, 43, 226, 0.05) 100%) !important;
    border-top: 1px solid rgba(0, 212, 255, 0.3) !important;
}

.stats-modal-dark .btn-secondary {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
    border: 1px solid rgba(0, 212, 255, 0.4) !important;
    color: #e2e8f0 !important;
    box-shadow: 0 4px 15px rgba(0, 212, 255, 0.2) !important;
    transition: all 0.3s ease !important;
}

.stats-modal-dark .btn-secondary:hover,
.stats-modal-btn:hover {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    border-color: rgba(0, 212, 255, 0.6) !important;
    color: #00d4ff !important;
    box-shadow: 0 6px 20px rgba(0, 212, 255, 0.4) !important;
    transform: translateY(-2px) !important;
}

/* Styles forcés pour le bouton du modal */
.stats-modal-btn {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
    border: 1px solid rgba(0, 212, 255, 0.4) !important;
    color: #e2e8f0 !important;
    box-shadow: 0 4px 15px rgba(0, 212, 255, 0.2) !important;
    transition: all 0.3s ease !important;
}

/* Animation d'apparition du modal en mode nuit */
.stats-modal-dark.show {
    animation: statsModalGlow 0.3s ease-out;
}

@keyframes statsModalGlow {
    0% {
        box-shadow: 0 0 0 rgba(0, 212, 255, 0);
        transform: scale(0.9);
        opacity: 0;
    }
    100% {
        box-shadow: 0 20px 60px rgba(0, 212, 255, 0.2), 
                    0 0 0 1px rgba(0, 212, 255, 0.1);
        transform: scale(1);
        opacity: 1;
    }
}

/* ========================================
   STATUT DES EMPLOYÉS - TABLEAU PERSONNALISÉ
======================================== */

.employee-status-section {
    margin-top: 2rem;
    padding: 0 1rem;
}

.employee-status-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1.5rem;
    text-align: center;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.employee-status-table-container {
    width: 100%;
    overflow-x: auto;
    background: var(--day-card-bg);
    border-radius: 16px;
    box-shadow: 0 8px 32px var(--day-shadow);
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.employee-status-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 0.9rem;
}

.employee-status-table thead {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
}

.employee-status-table th {
    padding: 1rem 0.75rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: none;
    position: relative;
}

.employee-status-table th:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 25%;
    height: 50%;
    width: 1px;
    background: rgba(255, 255, 255, 0.2);
}

.employee-status-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid var(--day-border);
}

.employee-status-table tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.employee-status-table td {
    padding: 1rem 0.75rem;
    color: var(--day-text);
    border: none;
    vertical-align: middle;
}

.employee-name {
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
}

.employee-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    animation: statusPulse 2s ease-in-out infinite;
}

.status-indicator.available {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}

.status-indicator.busy {
    background: #f59e0b;
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.5);
}

@keyframes statusPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.1);
    }
}

.employee-status.available {
    color: #10b981;
}

.employee-status.busy {
    color: #f59e0b;
}

.repair-id {
    font-family: 'Monaco', 'Menlo', monospace;
    font-weight: 600;
    color: var(--day-primary);
    background: rgba(59, 130, 246, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.8rem;
}

.repair-model {
    font-weight: 500;
    color: var(--day-text);
}

.repair-problem {
    color: var(--day-text-light);
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.repair-time {
    font-family: 'Monaco', 'Menlo', monospace;
    font-weight: 600;
    color: var(--day-accent);
    background: rgba(6, 182, 212, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.8rem;
}

/* Classes de couleur pour le temps des tâches */
.time-green {
    color: #10b981 !important;
    background: rgba(16, 185, 129, 0.1) !important;
    font-weight: 700 !important;
}

.time-orange {
    color: #f59e0b !important;
    background: rgba(245, 158, 11, 0.1) !important;
    font-weight: 700 !important;
}

.time-red {
    color: #ef4444 !important;
    background: rgba(239, 68, 68, 0.1) !important;
    font-weight: 700 !important;
}

.no-data td {
    text-align: center;
    color: var(--day-text-light);
    font-style: italic;
    padding: 2rem;
}

/* Mode nuit pour le tableau des employés */
body.night-mode .employee-status-title {
    color: var(--night-text);
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .employee-status-table-container {
    background: var(--night-card-bg);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

body.night-mode .employee-status-table thead {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .employee-status-table tbody tr:hover {
    background: rgba(0, 212, 255, 0.1);
    box-shadow: 0 4px 12px rgba(0, 212, 255, 0.2);
}

body.night-mode .employee-status-table td {
    color: var(--night-text);
}

body.night-mode .employee-name {
    color: var(--night-text);
}

body.night-mode .repair-id {
    color: var(--night-primary);
    background: rgba(0, 212, 255, 0.2);
}

body.night-mode .repair-model {
    color: var(--night-text);
}

body.night-mode .repair-problem {
    color: var(--night-text-light);
}

body.night-mode .repair-time {
    color: var(--night-accent);
    background: rgba(255, 0, 170, 0.2);
}

body.night-mode .no-data td {
    color: var(--night-text-light);
}

/* Responsive pour le tableau des employés */
@media (max-width: 768px) {
    .employee-status-table-container {
        border-radius: 12px;
    }
    
    .employee-status-table th,
    .employee-status-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .repair-problem {
        max-width: 150px;
    }
}

@media (max-width: 480px) {
    .employee-status-table th,
    .employee-status-table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
    }
    
    .repair-problem {
        max-width: 100px;
    }
}
</style>

<!-- Modal pour ajouter un nouveau client (identique à ajouter_reparation) -->
<div class="modal fade" id="nouveauClientModal_commande" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Ajouter un nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNouveauClient_commande">
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <input type="hidden" id="nouveau_shop_id_commande" name="shop_id" value="<?php echo $_SESSION['shop_id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="nouveau_nom_commande" class="form-label">Nom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_nom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_commande" class="form-label">Prénom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_prenom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_commande" class="form-label">Téléphone * <small class="text-muted">Format international : 331234567890</small></label>
                        <input type="tel" inputmode="tel" class="form-control form-control-lg" id="nouveau_telephone_commande" placeholder="331234567890" pattern="[0-9]{11}" maxlength="11" required>
                        <div class="form-text">Format : 11 chiffres (ex: 331234567890)</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-secondary flex-grow-1 me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary flex-grow-1" id="btn_sauvegarder_client_commande">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le changement de statut des commandes -->
<script src="assets/js/commande-statut.js"></script>

<!-- Inclusion du script des tâches -->
<script src="assets/js/taches.js"></script>

<!-- Script pour le modal de création de client -->
<script>
// Fonction pour ouvrir le modal de création de client (identique à ajouter_reparation)
window.createNewClientModal = function() {
    console.log('👤 Ouverture du modal nouveau client (style ajouter_reparation)');
    
    // Fermer d'abord le modal de commande s'il est ouvert
    const modalCommande = document.getElementById('ajouterCommandeModal');
    if (modalCommande) {
        const modalCommandeInstance = bootstrap.Modal.getInstance(modalCommande);
        if (modalCommandeInstance) {
            console.log('🔄 Fermeture du modal de commande...');
            modalCommandeInstance.hide();
        }
    }
    
    // Attendre un peu que le modal de commande se ferme avant d'ouvrir le nouveau
    setTimeout(() => {
        // Nettoyer les champs du formulaire
        document.getElementById('nouveau_nom_commande').value = '';
        document.getElementById('nouveau_prenom_commande').value = '';
        document.getElementById('nouveau_telephone_commande').value = '';
        
        // Ouvrir le modal nouveau client
        const modal = new bootstrap.Modal(document.getElementById('nouveauClientModal_commande'));
        modal.show();
        
        console.log('✅ Modal nouveau client ouvert');
    }, 300); // Délai pour laisser le temps au modal de commande de se fermer
};

// Gestionnaire pour sauvegarder le nouveau client
document.addEventListener('DOMContentLoaded', function() {
    const btnSauvegarder = document.getElementById('btn_sauvegarder_client_commande');
    if (btnSauvegarder) {
        btnSauvegarder.addEventListener('click', function() {
            const nom = document.getElementById('nouveau_nom_commande').value.trim();
            const prenom = document.getElementById('nouveau_prenom_commande').value.trim();
            const telephone = document.getElementById('nouveau_telephone_commande').value.trim();
            
            // Validation
            if (!nom || !prenom || !telephone) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            // Validation du téléphone (11 chiffres)
            if (!/^[0-9]{11}$/.test(telephone)) {
                alert('Le numéro de téléphone doit contenir exactement 11 chiffres (format international).');
                return;
            }
            
            // Préparer les données
            const formData = new FormData();
            formData.append('action', 'ajouter_client');
            formData.append('nom', nom);
            formData.append('prenom', prenom);
            formData.append('telephone', telephone);
            if (document.getElementById('nouveau_shop_id_commande')) {
                formData.append('shop_id', document.getElementById('nouveau_shop_id_commande').value);
            }
            
            // Désactiver le bouton pendant l'envoi
            btnSauvegarder.disabled = true;
            btnSauvegarder.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';
            
            // Envoyer la requête AJAX vers la version nettoyée
            fetch('ajax/ajouter_client_clean.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - mettre à jour l'interface
                    const clientSearchInput = document.getElementById('nom_client_selectionne');
                    const clientIdInput = document.getElementById('client_id');
                    const clientSelectionne = document.getElementById('client_selectionne');
                    
                    if (clientSearchInput && clientIdInput && clientSelectionne) {
                        clientIdInput.value = data.client_id;
                        clientSearchInput.value = nom + ' ' + prenom;
                        clientSelectionne.classList.remove('d-none');
                        
                        // Mettre à jour le texte affiché
                        const clientNomSpan = clientSelectionne.querySelector('.client-nom');
                        if (clientNomSpan) {
                            clientNomSpan.textContent = nom + ' ' + prenom;
                        }
                    }
                    
                    // Fermer le modal nouveau client
                    const modal = bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal_commande'));
                    modal.hide();
                    
                    // Rouvrir le modal de commande après un court délai
                    setTimeout(() => {
                        const modalCommande = document.getElementById('ajouterCommandeModal');
                        if (modalCommande) {
                            const modalCommandeInstance = new bootstrap.Modal(modalCommande);
                            modalCommandeInstance.show();
                            console.log('🔄 Modal de commande rouvert après création du client');
                        }
                    }, 300);
                    
                    // Message de succès
                    console.log('✅ Client créé avec succès:', data);
                } else {
                    alert('Erreur lors de la création du client: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la communication avec le serveur.');
            })
            .finally(() => {
                // Réactiver le bouton
                btnSauvegarder.disabled = false;
                btnSauvegarder.innerHTML = 'Sauvegarder';
            });
        });
    }
});

// ========================================
// CORRECTION FORCÉE DU MODE NUIT
// ========================================
(function() {
    'use strict';
    
    function forceApplyDarkMode() {
        // Vérifier si le système préfère le mode sombre
        const prefersDarkScheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (prefersDarkScheme) {
            console.log('🌙 Mode sombre détecté par le système - Application forcée');
            
            // Appliquer les classes de mode nuit
            document.documentElement.classList.add('night-mode');
            document.body.classList.add('night-mode');
            document.body.classList.add('dark-mode'); // Fallback
            
            // Sauvegarder la préférence
            try {
                localStorage.setItem('geekboard_theme', 'dark');
            } catch (e) {
                console.warn('Impossible de sauvegarder la préférence de thème');
            }
            
            console.log('✅ Mode nuit appliqué avec succès');
        } else {
            console.log('☀️ Mode jour détecté par le système');
        }
    }
    
    // Appliquer immédiatement
    forceApplyDarkMode();
    
    // Écouter les changements de préférence système
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            console.log('🔄 Changement de préférence système détecté:', e.matches ? 'sombre' : 'clair');
            forceApplyDarkMode();
        });
    }
})();
</script>

<script>
// Définir la fonction globalement dès le chargement pour éviter les erreurs
window.openEmployeeActivityModal = window.openEmployeeActivityModal || function(userId, employeeName) {
    console.log('openEmployeeActivityModal called with:', userId, employeeName);
};
</script>

<?php
// Modal d'activité employé - Accessible à tous les utilisateurs connectés
?>

<!-- Modal d'activité employé -->
<div class="modal fade" id="employeeActivityModal" tabindex="-1" aria-labelledby="employeeActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-primary bg-gradient text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 52px; height: 52px; font-size: 1.3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark" id="employeeActivityModalLabel">
                            <span id="employeeName">...</span>
                        </h5>
                        <small class="text-muted">Suivi d'activité journalier</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0 bg-light">
                <!-- Contrôles de date -->
                <div class="date-navigation bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top shadow-sm" style="z-index: 1020;">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" onclick="changeActivityDate(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border">
                        <i class="far fa-calendar-alt text-primary me-2"></i>
                        <input type="date" id="activityDateInput" class="form-control form-control-sm border-0 bg-transparent fw-bold text-center p-0 text-dark" style="width: 130px; outline: none; box-shadow: none;" onchange="loadActivityForDate(this.value)">
                    </div>
                    
                    <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" onclick="changeActivityDate(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div id="activityLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted fw-medium">Chargement de l'activité...</p>
                </div>
                
                <div id="activityContent" style="display: none; height: 65vh; overflow-y: auto;" class="px-4 py-4 custom-scrollbar">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-0 ls-1">Timeline des Activités</h6>
                        <span class="badge bg-white text-primary border shadow-sm rounded-pill px-3 py-2">
                            <i class="fas fa-history me-1"></i>
                            <span id="activityCount">0</span> réparations
                        </span>
                    </div>
                    
                    <div id="activityTimeline" class="modern-timeline">
                        <!-- Les logs seront insérés ici -->
                    </div>
                    
                    <div id="noActivityMessage" class="text-center py-5" style="display: none;">
                        <div class="mb-3 text-muted opacity-25">
                            <i class="fas fa-calendar-day fa-4x"></i>
                        </div>
                        <h5 class="text-muted fw-bold">Aucune activité</h5>
                        <p class="text-muted">Aucun log n'a été trouvé pour cette date.</p>
                    </div>
                </div>
                
                <div id="activityError" class="alert alert-danger m-4 shadow-sm border-0" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">Erreur de chargement</h6>
                            <span id="activityErrorMessage"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Infos Rapides Réparation -->
<div class="modal fade" id="repairQuickInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg night-mode" style="border-radius: 16px;">
            <div class="modal-header night-mode border-bottom">
                <h5 class="modal-title fw-bold night-mode"><i class="fas fa-tools me-2"></i>Informations Réparation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 night-mode">
                <div id="repairQuickInfoLoading" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Chargement...</p></div>
                <div id="repairQuickInfoContent" style="display: none;">
                    <div class="mb-3"><h6 class="text-muted small"><i class="far fa-user me-1"></i> CLIENT</h6><p class="h5 night-mode mb-0" id="repairClientName">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-mobile-alt me-1"></i> APPAREIL</h6><p class="h6 night-mode mb-0" id="repairModel">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-exclamation-circle me-1"></i> PROBLÈME</h6><p class="night-mode mb-0" id="repairProblem">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-info-circle me-1"></i> STATUT</h6><span id="repairStatus" class="badge bg-primary">-</span></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-sticky-note me-1"></i> NOTE INTERNE</h6><div class="p-3 rounded night-mode" style="background: rgba(0,0,0,0.05);"><p class="mb-0 small night-mode" id="repairNote">Aucune note</p></div></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-camera me-1"></i> PHOTO</h6><div id="repairPhotoContainer" class="text-center"><img id="repairPhoto" class="img-fluid rounded shadow-sm" style="max-height: 300px; display: none;"><p id="repairNoPhoto" class="text-muted mb-0">Aucune photo disponible</p></div></div>
                </div>
                <div id="repairQuickInfoError" class="alert alert-danger" style="display: none;"><i class="fas fa-exclamation-triangle me-2"></i><span id="repairQuickInfoErrorMessage">Erreur</span></div>
            </div>
            <div class="modal-footer border-0 night-mode">
                <button type="button" class="btn btn-secondary night-mode" data-bs-dismiss="modal">Fermer</button>
                <a id="repairDetailsLink" href="#" target="_blank" class="btn btn-primary night-mode"><i class="fas fa-external-link-alt me-1"></i> Voir détails</a>
            </div>
        </div>
    </div>
</div>


<!-- Modal Infos Rapides Tâche -->
<div class="modal fade" id="taskQuickInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg night-mode" style="border-radius: 16px;">
            <div class="modal-header night-mode border-bottom">
                <h5 class="modal-title fw-bold night-mode"><i class="fas fa-tasks me-2"></i>Informations Tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 night-mode">
                <div id="taskQuickInfoLoading" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Chargement...</p></div>
                <div id="taskQuickInfoContent" style="display: none;">
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-heading me-1"></i> TITRE</h6><p class="h5 night-mode mb-0" id="taskTitle">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-align-left me-1"></i> DESCRIPTION</h6><p class="night-mode mb-0" id="taskDescription">-</p></div>
                    <div class="mb-3 d-flex gap-3"><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-info-circle me-1"></i> STATUT</h6><span id="taskStatus" class="badge bg-primary">-</span></div><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-flag me-1"></i> PRIORITÉ</h6><span id="taskPriority" class="badge bg-secondary">-</span></div></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-user me-1"></i> ASSIGNÉ À</h6><p class="night-mode mb-0" id="taskAssignedTo">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-user-plus me-1"></i> CRÉÉ PAR</h6><p class="night-mode mb-0" id="taskCreatedBy">-</p></div>
                    <div class="mb-3 d-flex gap-3"><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> ÉCHÉANCE</h6><p class="night-mode mb-0" id="taskDueDate">-</p></div><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-clock me-1"></i> CRÉÉE LE</h6><p class="night-mode mb-0" id="taskCreatedAt">-</p></div></div>
                </div>
                <div id="taskQuickInfoError" class="alert alert-danger" style="display: none;"><i class="fas fa-exclamation-triangle me-2"></i><span id="taskQuickInfoErrorMessage">Erreur</span></div>
            </div>
            <div class="modal-footer border-0 night-mode">
                <button type="button" class="btn btn-secondary night-mode" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.ls-1 { letter-spacing: 1px; }

.modern-timeline {
    position: relative;
    padding-left: 24px;
}

.modern-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #e9ecef;
    border-radius: 3px;
}

.timeline-item {
    position: relative;
    padding-left: 36px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -11px;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #fff;
    border: 4px solid #0d6efd;
    z-index: 1;
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 1);
}

.timeline-content {
    background: #fff;
    border: 0;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    box-shadow: 0 4px 6px rgba(0,0,0,0.02), 0 1px 3px rgba(0,0,0,0.05);
}

.timeline-content:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08), 0 4px 8px rgba(0,0,0,0.04);
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8f9fa;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #ced4da;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .modern-timeline::before { background: #343a40; }
    .timeline-marker { background: #212529; border-color: #0d6efd; box-shadow: 0 0 0 4px #212529; }
    .timeline-content { background: #212529; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
<script src="assets/js/task-quick-info.js?v=<?php echo time(); ?>"></script>
    .timeline-content:hover { background: #2c3034; box-shadow: 0 12px 24px rgba(0,0,0,0.3); }
    .date-navigation { background: #212529 !important; border-color: #343a40 !important; }
    .modal-header { background: #212529 !important; border-color: #343a40 !important; }
    .modal-body { background: #1a1d21 !important; }
    .modal-content { background: #212529; }
    .modal-title { color: #fff !important; }
    .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    #activityDateInput { color: #fff; }
    .bg-light { background-color: #2c3034 !important; }
    .text-dark { color: #f8f9fa !important; }
    .border { border-color: #343a40 !important; }
    .border-bottom { border-color: #343a40 !important; }
    .shadow-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.25) !important; }
    
    /* Repair Quick Info Modal Dark Mode */
    #repairQuickInfoModal .modal-content.night-mode {
        background: #1e2124 !important;
    }
    #repairQuickInfoModal .modal-header.night-mode {
        background: #2c2f33 !important;
        border-color: #40444b !important;
    }
    #repairQuickInfoModal .modal-body.night-mode {
        background: #1e2124 !important;
        color: #dcddde !important;
    }
    #repairQuickInfoModal .night-mode {
        color: #dcddde !important;
    }
    #repairQuickInfoModal .modal-footer.night-mode {
        background: #2c2f33 !important;
        border-color: #40444b !important;
    }
    #repairQuickInfoModal .night-mode h5,
    #repairQuickInfoModal .night-mode h6,
    #repairQuickInfoModal .night-mode p {
        color: #dcddde !important;
    }
    #repairQuickInfoModal .text-muted {
        color: #8e9297 !important;
    }
    #repairQuickInfoModal .modal-body.night-mode > div[style*="background"] {
        background: rgba(255, 255, 255, 0.05) !important;
    }
    
    /* Task Quick Info Modal Dark Mode */
    #taskQuickInfoModal .modal-content.night-mode {
        background: #1e2124 !important;
    }
    #taskQuickInfoModal .modal-header.night-mode {
        background: #2c2f33 !important;
        border-color: #40444b !important;
    }
    #taskQuickInfoModal .modal-body.night-mode {
        background: #1e2124 !important;
        color: #dcddde !important;
    }
    #taskQuickInfoModal .night-mode {
        color: #dcddde !important;
    }
    #taskQuickInfoModal .modal-footer.night-mode {
        background: #2c2f33 !important;
        border-color: #40444b !important;
    }
    #taskQuickInfoModal .night-mode h5,
    #taskQuickInfoModal .night-mode h6,
    #taskQuickInfoModal .night-mode p {
        color: #dcddde !important;
    }
    #taskQuickInfoModal .text-muted {
        color: #8e9297 !important;
    }
}
</style>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script>


<!-- Employee Activity Modal Script -->
<script src="assets/js/employee-activity-modal.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/repair-quick-info.js?v=<?php echo time(); ?>"></script>

<!-- Script autonome pour les animations futuristes mode nuit -->
<script>
(function() {
    'use strict';
    
    function injectNightEffects() {
        // Éviter les doublons
        if (document.querySelector('.night-mode-bg-effects')) return;
        if (!document.body.classList.contains('night-mode')) return;
        
        console.log('✨ Injection des effets visuels futuristes mode nuit (autonome)');
        
        // Créer le conteneur principal
        const container = document.createElement('div');
        container.className = 'night-mode-bg-effects';
        
        // Créer la couche de fond de base
        const baseBg = document.createElement('div');
        baseBg.className = 'night-mode-base-bg';
        document.body.insertBefore(baseBg, document.body.firstChild);
        
        // Ajouter les lueurs de coins
        const glowTopLeft = document.createElement('div');
        glowTopLeft.className = 'night-corner-glow top-left';
        container.appendChild(glowTopLeft);
        
        const glowBottomRight = document.createElement('div');
        glowBottomRight.className = 'night-corner-glow bottom-right';
        container.appendChild(glowBottomRight);
        
        // Ajouter des particules flottantes
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'night-particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (8 + Math.random() * 12) + 's';
            particle.style.width = (2 + Math.random() * 4) + 'px';
            particle.style.height = particle.style.width;
            particle.style.opacity = 0.4 + Math.random() * 0.4;
            container.appendChild(particle);
        }
        
        // Ajouter quelques lignes de données
        for (let i = 0; i < 5; i++) {
            const dataLine = document.createElement('div');
            dataLine.className = 'night-data-line';
            dataLine.style.top = (15 + i * 18) + '%';
            dataLine.style.width = (80 + Math.random() * 150) + 'px';
            dataLine.style.animationDelay = (i * 1.5) + 's';
            container.appendChild(dataLine);
        }
        
        // Insérer au début du body
        document.body.insertBefore(container, document.body.firstChild);
        
        console.log('✅ Effets visuels futuristes injectés avec succès');
    }
    
    function removeNightEffects() {
        const container = document.querySelector('.night-mode-bg-effects');
        if (container) {
            container.remove();
        }
        const baseBg = document.querySelector('.night-mode-base-bg');
        if (baseBg) {
            baseBg.remove();
        }
        console.log('🧹 Effets visuels futuristes supprimés');
    }
    
    // Injecter immédiatement si le mode nuit est déjà actif
    if (document.body.classList.contains('night-mode')) {
        injectNightEffects();
    }
    
    // Écouter les changements de classe sur le body
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (document.body.classList.contains('night-mode')) {
                    injectNightEffects();
                } else {
                    removeNightEffects();
                }
            }
        });
    });
    
    observer.observe(document.body, { attributes: true });
    
    // Backup: vérifier après un court délai
    setTimeout(function() {
        if (document.body.classList.contains('night-mode')) {
            injectNightEffects();
        }
    }, 500);
    
    // Backup supplémentaire au DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (document.body.classList.contains('night-mode')) {
                injectNightEffects();
            }
        }, 100);
    });
})();
</script>
