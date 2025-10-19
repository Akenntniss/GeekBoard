<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-modern.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil-modern');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

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

/* Particules flottantes pour le mode nuit */
.particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
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
.night-mode .daily-stat-btn {
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.night-mode .section-title {
    background: linear-gradient(45deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
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
        grid-template-columns: 1fr;
        gap: 1rem;
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
    .modern-action-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .modern-action-card {
        padding: 1.25rem;
        border-radius: 12px;
    }
    
    .modern-action-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
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
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 18px;
    padding: 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 6px 20px var(--day-shadow);
}

.status-metric-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 15px 50px var(--day-shadow);
    border-color: var(--day-primary);
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

.daily-analytics-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1.5rem;
    text-align: center;
    position: relative;
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
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px var(--day-shadow);
}

.daily-analytics-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 20px 60px var(--day-shadow);
    border-color: var(--day-primary);
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
</style>

<!-- Basculeur de thème -->
<!-- Toggle retiré - Mode automatique selon système -->

<!-- Container de particules (mode nuit) -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- 🚀 BOUTONS D'ACTIONS EN HAUT -->
    <!-- 🚀 NOUVEAUX BOUTONS D'ACTION MODERNES -->
    <div class="modern-action-grid fade-in">
        <a href="#" class="modern-action-card search-card" onclick="ouvrirRechercheModerne(); return false;">
            <div class="modern-action-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Rechercher</h3>
                <p class="modern-action-desc">Chercher clients, réparations...</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
        
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
        <div class="status-metrics-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="status-metric-card repairs-card">
                <div class="status-metric-badge">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $reparations_actives; ?></div>
                    <div class="status-metric-label">Réparations</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches" class="status-metric-card tasks-card">
                <div class="status-metric-badge">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $taches_recentes_count; ?></div>
                    <div class="status-metric-label">Tâches</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=commandes_pieces" class="status-metric-card orders-card">
                <div class="status-metric-badge">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $commandes_en_attente_count; ?></div>
                    <div class="status-metric-label">Commandes</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=reparations&urgence=1" class="status-metric-card urgent-card">
                <div class="status-metric-badge">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $reparations_en_cours; ?></div>
                    <div class="status-metric-label">Urgences</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
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
                <h4>Réparations récentes</h4>
                <span class="badge"><?php echo $reparations_recentes_count; ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($reparations_recentes)): ?>
                    <?php foreach ($reparations_recentes as $reparation): ?>
                        <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                            <div class="row-indicator reparations"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                            </div>
                            <div class="row-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?>
                                </div>
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
        </div>

        <!-- Tableau 3: Commandes récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-shopping-cart"></i>
                <h4>Commandes récentes</h4>
                <span class="badge"><?php echo count($commandes_recentes); ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($commandes_recentes)): ?>
                    <?php foreach ($commandes_recentes as $commande): ?>
                        <div class="table-row" onclick="window.location.href='index.php?page=commandes_pieces&id=<?php echo $commande['id']; ?>'">
                            <div class="row-indicator commandes"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($commande['piece_nom'] ?? $commande['reference'] ?? 'N/A'); ?></div>
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

    <!-- 📈 NOUVEAU DESIGN - STATISTIQUES DU JOUR -->
    <div class="daily-analytics-section mt-4 fade-in">
        <h3 class="daily-analytics-title">Statistiques du jour</h3>
        <div class="daily-analytics-grid">
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

</div>

<!-- Note: Le modal de statistiques est géré par le système existant via openStatsModal() -->

<script>
// ========================================
// GESTION DU THÈME
// ========================================
let currentTheme = 'day'; // Sera automatiquement détecté par initTheme()
let particlesCreated = false;

function initTheme() {
    const dashboard = document.getElementById('dashboard');
    const body = document.body;
    
    // Détecter automatiquement les préférences système
    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    currentTheme = prefersDarkMode ? 'night' : 'day';
    
    console.log('🎨 Détection automatique du thème système:', prefersDarkMode ? 'Mode sombre' : 'Mode clair');
    console.log('📱 Thème appliqué:', currentTheme);
    
    if (currentTheme === 'night') {
        dashboard.classList.add('night-mode');
        body.classList.add('night-mode');
        if (!particlesCreated) {
            createParticles();
        }
        console.log('✅ Mode nuit activé automatiquement');
        
        // Forcer les variables CSS du mode nuit
        setTimeout(() => {
            forceStatCardsNightMode();
            forceActionButtonsNightMode();
            startNightModeWatcher(); // Démarrer la surveillance
            startStyleObserver(); // Démarrer l'observateur de styles
        }, 50);
    } else {
        dashboard.classList.remove('night-mode');
        body.classList.remove('night-mode');
        // S'assurer qu'aucun élément n'a la classe night-mode
        document.querySelectorAll('.night-mode').forEach(el => {
            el.classList.remove('night-mode');
        });
        removeParticles();
        console.log('✅ Mode jour activé automatiquement');
        
        // Forcer les variables CSS du mode jour
        setTimeout(() => {
            forceStatCardsDayMode();
            stopNightModeWatcher(); // Arrêter la surveillance
            stopStyleObserver(); // Arrêter l'observateur de styles
        }, 50);
    }
}

// Fonction toggleTheme supprimée - Mode automatique uniquement

// Écouter les changements de préférences système
function setupThemeListener() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Écouter les changements
    mediaQuery.addEventListener('change', (e) => {
        console.log('🔄 Changement des préférences système détecté:', e.matches ? 'Mode sombre' : 'Mode clair');
        initTheme(); // Réappliquer le thème automatiquement
    });
    
    console.log('👂 Écoute des changements de préférences système activée');
}

// Fonction pour forcer les styles du mode jour sur les cartes de statistiques
function forceStatCardsDayMode() {
    console.log('🌞 Forçage du mode jour pour les cartes de statistiques');
    
    // Forcer les variables CSS du mode jour
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', 'rgba(255, 255, 255, 0.95)');
    root.style.setProperty('--day-text', '#1e293b');
    root.style.setProperty('--day-text-light', '#64748b');
    root.style.setProperty('--day-shadow', 'rgba(0, 0, 0, 0.1)');
    root.style.setProperty('--day-border', 'rgba(148, 163, 184, 0.2)');
    root.style.setProperty('--day-primary', '#3b82f6');
    
    // Forcer les styles sur toutes les cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.setProperty('background', 'rgba(255, 255, 255, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(148, 163, 184, 0.2)', 'important');
        card.style.setProperty('color', '#1e293b', 'important');
        card.style.setProperty('box-shadow', '0 4px 16px rgba(0, 0, 0, 0.1)', 'important');
        
        // Forcer les styles sur le contenu
        const statValue = card.querySelector('.stat-value');
        const statLabel = card.querySelector('.stat-label');
        if (statValue) {
            statValue.style.setProperty('color', '#1e293b', 'important');
        }
        if (statLabel) {
            statLabel.style.setProperty('color', '#64748b', 'important');
        }
    });
    
    console.log('✅ Styles du mode jour forcés sur', statCards.length, 'cartes');
}

// Fonction pour forcer les styles du mode nuit sur les cartes de statistiques
function forceStatCardsNightMode() {
    console.log('🌙 Forçage du mode nuit pour les cartes de statistiques');
    
    // Forcer les variables CSS du mode nuit
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', 'rgba(30, 30, 35, 0.95)');
    root.style.setProperty('--day-text', '#ffffff');
    root.style.setProperty('--day-text-light', '#b0b0b0');
    root.style.setProperty('--day-shadow', 'rgba(0, 255, 255, 0.15)');
    root.style.setProperty('--day-border', 'rgba(0, 255, 255, 0.2)');
    root.style.setProperty('--day-primary', '#00d4ff');
    
    // Forcer les styles sur toutes les cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        card.style.setProperty('color', '#ffffff', 'important');
        card.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        
        // Forcer les styles sur le contenu
        const statValue = card.querySelector('.stat-value');
        const statLabel = card.querySelector('.stat-label');
        if (statValue) {
            statValue.style.setProperty('color', '#ffffff', 'important');
        }
        if (statLabel) {
            statLabel.style.setProperty('color', '#b0b0b0', 'important');
        }
    });
    
    console.log('✅ Styles du mode nuit forcés sur', statCards.length, 'cartes');
}

// Fonction pour forcer les boutons d'action en mode nuit avec le même fond que les statistiques
function forceActionButtonsNightMode() {
    console.log('🌙 Forçage AGRESSIF des boutons d\'action en mode nuit');
    
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach((btn, index) => {
        // Supprimer toutes les classes qui pourraient interférer
        btn.classList.remove('geek-action-btn', 'futuristic-action-btn', 'action-card');
        
        // Même fond que les boutons de statistiques - FORÇAGE ULTRA AGRESSIF
        btn.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        btn.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        btn.style.setProperty('color', '#ffffff', 'important');
        btn.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        btn.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        btn.style.setProperty('border-radius', '20px', 'important');
        btn.style.setProperty('padding', '2rem', 'important');
        btn.style.setProperty('display', 'flex', 'important');
        btn.style.setProperty('align-items', 'center', 'important');
        btn.style.setProperty('gap', '1.5rem', 'important');
        btn.style.setProperty('text-decoration', 'none', 'important');
        btn.style.setProperty('transition', 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', 'important');
        
        // Ajouter un attribut pour identifier les boutons forcés
        btn.setAttribute('data-night-forced', 'true');
    });
    
    console.log('✅ Boutons d\'action ULTRA-FORCÉS en mode nuit:', actionButtons.length, 'boutons');
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
// Système intelligent de gestion des statistiques avancées
(function() {
    let pendingRequests = [];
    let systemReady = false;
    
    // Vérifier si le système est déjà prêt
    function checkSystemReady() {
        return window.advancedStats && typeof window.advancedStats.openModal === 'function';
    }
    
    // Traiter les demandes en attente
    function processPendingRequests() {
        console.log('🚀 Traitement des demandes en attente:', pendingRequests.length);
        
        while (pendingRequests.length > 0) {
            const request = pendingRequests.shift();
            console.log('📊 Ouverture du modal en attente pour:', request.statType);
            window.advancedStats.openModal(request.statType);
        }
    }
    
    // Écouter l'événement de prêt du système
    window.addEventListener('advancedStatsReady', function() {
        console.log('✅ Système de statistiques avancé prêt !');
        systemReady = true;
        processPendingRequests();
    });
    
    // Fonction principale d'ouverture des modals
    window.openStatsModal = function(statType) {
        console.log('🔄 Demande d\'ouverture du modal pour:', statType);
        
        // Vérifier si le système est prêt
        if (checkSystemReady()) {
            console.log('✅ Système disponible, ouverture immédiate');
            window.advancedStats.openModal(statType);
        } else {
            console.log('⏳ Système non prêt, ajout à la file d\'attente');
            pendingRequests.push({ statType: statType });
            
            // Timeout de sécurité au cas où l'événement ne se déclenche pas
            setTimeout(function() {
                if (!systemReady && checkSystemReady()) {
                    console.log('🔧 Système détecté par timeout, traitement des demandes');
                    systemReady = true;
                    processPendingRequests();
                }
            }, 2000);
        }
    };
    
    // Vérification initiale au cas où le système serait déjà chargé
    setTimeout(function() {
        if (checkSystemReady() && !systemReady) {
            console.log('🔧 Système déjà prêt lors de la vérification initiale');
            systemReady = true;
            processPendingRequests();
        }
    }, 100);
})();

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
    
    // Forcer les bons styles au chargement selon le thème
    setTimeout(() => {
        if (currentTheme === 'night') {
            forceStatCardsNightMode();
            forceActionButtonsNightMode();
        } else {
            forceStatCardsDayMode();
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
    
    // Gestion des touches pour les cartes
    document.querySelectorAll('.action-btn, .stat-card, .table-row, .daily-stats-card').forEach(element => {
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
        body.night-mode .action-btn, .night-mode .action-btn {
            background: rgba(30, 30, 35, 0.95) !important;
            border: 1px solid rgba(0, 255, 255, 0.2) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px) !important;
        }
        body.night-mode .action-btn:hover, .night-mode .action-btn:hover {
            background: rgba(40, 40, 45, 0.98) !important;
            box-shadow: 0 12px 40px rgba(0, 255, 255, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(0, 255, 255, 0.4) !important;
            transform: translateY(-2px) !important;
        }
        body.night-mode .action-btn .icon, .night-mode .action-btn .icon { color: #000000 !important; }
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
