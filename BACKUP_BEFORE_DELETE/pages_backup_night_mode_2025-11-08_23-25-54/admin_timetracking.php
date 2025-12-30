<?php
/**
 * Interface administrateur améliorée pour le système de pointage
 * Version avec onglet Paramètres pour les créneaux horaires
 */

// CSS inline pour forcer laffichage correct des onglets
echo '<style>
.admin-timetracking-container {
    margin-top: 20px !important;
    position: relative !important;
    z-index: 1 !important;
}

.admin-timetracking-container .nav-tabs {
    border-bottom: 2px solid #dee2e6 !important;
    margin-bottom: 20px !important;
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.admin-timetracking-container .nav-tabs .nav-link {
    border: none !important;
    border-bottom: 3px solid transparent !important;
    background: none !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    padding: 12px 20px !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.admin-timetracking-container .nav-tabs .nav-link:hover {
    color: #ffffff !important;
    border-bottom-color: #ffffff !important;
    background: rgba(255, 255, 255, 0.1) !important;
}

.admin-timetracking-container .nav-tabs .nav-link.active {
    color: #ffffff !important;
    border-bottom-color: #ffffff !important;
    background: rgba(255, 255, 255, 0.2) !important;
}

.admin-timetracking-container .tab-content {
    min-height: 400px !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.admin-timetracking-container .tab-pane {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    position: absolute !important;
    top: -9999px !important;
    left: -9999px !important;
}

.admin-timetracking-container .tab-pane.active {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: static !important;
    top: auto !important;
    left: auto !important;
}

.admin-timetracking-container .tab-pane.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: static !important;
    top: auto !important;
    left: auto !important;
}

/* Forcer UNIQUEMENT le premier onglet à être visible au chargement */
.admin-timetracking-container .tab-pane:first-of-type:not(.manual-hidden) {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: static !important;
    top: auto !important;
    left: auto !important;
}

/* Sassurer que les onglets non-actifs sont completement caches */
.admin-timetracking-container .tab-pane:not(.active):not(.show) {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    position: absolute !important;
    top: -9999px !important;
    left: -9999px !important;
}

.admin-timetracking-container .card,
.admin-timetracking-container .alert,
.admin-timetracking-container .form-control,
.admin-timetracking-container .btn {
    opacity: 1 !important;
    visibility: visible !important;
}

.admin-timetracking-container * {
    transition: none !important;
}
</style>';

// Script pour forcer les onglets à fonctionner
echo '<script>
(function() {
    function forceTabsDisplay() {
        const container = document.querySelector(".admin-timetracking-container");
        if (!container) return;
        
        const tabs = container.querySelectorAll(".nav-link");
        const panes = container.querySelectorAll(".tab-pane");
        
        // Activer le premier onglet
        tabs.forEach((tab, i) => {
            if (i === 0) {
                tab.classList.add("active");
            } else {
                tab.classList.remove("active");
            }
        });
        
        // Afficher le premier panneau et marquer les autres comme cachés
        panes.forEach((pane, i) => {
            if (i === 0) {
                pane.classList.add("active", "show");
                pane.classList.remove("manual-hidden");
                pane.style.display = "block";
                pane.style.visibility = "visible";
                pane.style.opacity = "1";
                pane.style.position = "static";
                pane.style.top = "auto";
                pane.style.left = "auto";
                pane.setAttribute("aria-hidden", "false");
            } else {
                pane.classList.remove("active", "show");
                pane.classList.add("manual-hidden");
                pane.style.display = "none";
                pane.style.visibility = "hidden";
                pane.style.opacity = "0";
                pane.style.position = "absolute";
                pane.style.top = "-9999px";
                pane.style.left = "-9999px";
                pane.setAttribute("aria-hidden", "true");
            }
        });
        
        // Ajouter les événements avec gestion améliorée
        tabs.forEach(tab => {
            tab.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Désactiver tous les onglets et leurs contenus
                tabs.forEach(t => {
                    t.classList.remove("active");
                    t.setAttribute("aria-selected", "false");
                });
                
                panes.forEach(p => {
                    p.classList.remove("active", "show");
                    p.classList.add("manual-hidden");
                    p.style.display = "none";
                    p.style.visibility = "hidden";
                    p.style.opacity = "0";
                    p.style.position = "absolute";
                    p.style.top = "-9999px";
                    p.style.left = "-9999px";
                    p.setAttribute("aria-hidden", "true");
                });
                
                // Activer longlet clique
                this.classList.add("active");
                this.setAttribute("aria-selected", "true");
                
                // Afficher le contenu correspondant
                const target = this.getAttribute("data-bs-target");
                const pane = container.querySelector(target);
                if (pane) {
                    pane.classList.add("active", "show");
                    pane.classList.remove("manual-hidden");
                    pane.style.display = "block";
                    pane.style.visibility = "visible";
                    pane.style.opacity = "1";
                    pane.style.position = "static";
                    pane.style.top = "auto";
                    pane.style.left = "auto";
                    pane.setAttribute("aria-hidden", "false");
                    
                    // Forcer le reflow pour sassurer que les changements sont appliques
                    pane.offsetHeight;
                }
                
                console.log("Onglet active:", target);
            });
        });
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", forceTabsDisplay);
    } else {
        forceTabsDisplay();
    }
    
    setTimeout(forceTabsDisplay, 100);
    setTimeout(forceTabsDisplay, 500);
    setTimeout(forceTabsDisplay, 1000);
    setTimeout(forceTabsDisplay, 2000);
})();
</script>';

// Ce fichier est inclus via le routage GeekBoard, donc les sessions sont déjà chargées
// Mais nous devons nous assurer que les fichiers de configuration sont inclus

// Sassurer que les fichiers de configuration sont charges
if (!function_exists('getShopDBConnection')) {
    require_once BASE_PATH . '/config/database.php';
}

// Configuration de la langue française pour les dates
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'french');

// Fonction pour convertir les dates en français
function formatDateFrench($date) {
    $jours = array('dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi');
    $mois = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
    
    $timestamp = strtotime($date);
    $jour_semaine = $jours[date('w', $timestamp)];
    $jour = date('j', $timestamp);
    $mois_nom = $mois[date('n', $timestamp)];
    $annee = date('Y', $timestamp);
    
    return ucfirst($jour_semaine) . ' ' . $jour . ' ' . $mois_nom . ' ' . $annee;
}

// Vérifier les droits admin 
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-ban"></i> Accès refusé</h4>
        <p>Cette page est réservée aux administrateurs.</p>
        <p><strong>Debug:</strong> Role actuel = ' . ($_SESSION['user_role'] ?? 'non défini') . '</p>
    </div>';
    return;
}

$current_user_id = $_SESSION['user_id'];

// Obtenir la connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-database"></i> Erreur de base de données</h4>
        <p>Impossible de se connecter à la base de données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}

// Fonction pour vérifier si un pointage est dans les créneaux autorisés
function isTimeSlotValid($user_id, $clock_time, $shop_pdo) {
    $time_only = date('H:i:s', strtotime($clock_time));
    $hour = intval(date('H', strtotime($clock_time)));
    
    // Déterminer le type de créneau (matin ou après-midi)
    $slot_type = ($hour < 13) ? 'morning' : 'afternoon';
    
    // Chercher dabord les regles specifiques a lutilisateur
    $stmt = $shop_pdo->prepare("
        SELECT start_time, end_time 
        FROM time_slots 
        WHERE user_id = ? AND slot_type = ? AND is_active = TRUE
    ");
    $stmt->execute([$user_id, $slot_type]);
    $user_slot = $stmt->fetch();
    
    // Si pas de règle spécifique, utiliser la règle globale
    if (!$user_slot) {
        $stmt = $shop_pdo->prepare("
            SELECT start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NULL AND slot_type = ? AND is_active = TRUE
        ");
        $stmt->execute([$slot_type]);
        $user_slot = $stmt->fetch();
    }
    
    if ($user_slot) {
        return ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']);
    }
    
    return false; // Pas de créneau défini = non autorisé
}

// Traitement des actions admin (API endpoint)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'save_global_slots':
                // Sauvegarder les créneaux globaux
                $morning_start = $_POST['morning_start'];
                $morning_end = $_POST['morning_end'];
                $afternoon_start = $_POST['afternoon_start'];
                $afternoon_end = $_POST['afternoon_end'];
                
                // Mise à jour créneau matin global
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (NULL, 'morning', ?, ?, TRUE)
                    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
                ");
                $stmt->execute([$morning_start, $morning_end]);
                
                // Mise à jour créneau après-midi global
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (NULL, 'afternoon', ?, ?, TRUE)
                    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
                ");
                $stmt->execute([$afternoon_start, $afternoon_end]);
                
                $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés'];
                break;
                
            case 'save_user_slots':
                // Sauvegarder les créneaux spécifiques à un utilisateur
                $user_id = intval($_POST['user_id']);
                $morning_start = $_POST['user_morning_start'];
                $morning_end = $_POST['user_morning_end'];
                $afternoon_start = $_POST['user_afternoon_start'];
                $afternoon_end = $_POST['user_afternoon_end'];
                
                // Supprimer les anciens créneaux de cet utilisateur
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Ajouter les nouveaux créneaux
                if ($morning_start && $morning_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'morning', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $morning_start, $morning_end]);
                }
                
                if ($afternoon_start && $afternoon_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'afternoon', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                }
                
                $response = ['success' => true, 'message' => 'Créneaux utilisateur sauvegardés'];
                break;
                
            case 'remove_user_slots':
                // Supprimer les créneaux spécifiques d'un utilisateur (revenir aux globaux)
                $user_id = intval($_POST['user_id']);
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés, utilisation des créneaux globaux'];
                break;
                
            case 'force_clock_out':
                $user_id = intval($_POST['user_id']);
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET clock_out = NOW(), 
                        status = 'completed',
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForced clock-out by admin at ', NOW()),
                        total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0,
                        work_duration = (TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0) - IFNULL(break_duration, 0)
                    WHERE user_id = ? AND status IN ('active', 'break')
                ");
                
                if ($stmt->execute([$user_id])) {
                    $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
                } else {
                    $response = ['success' => false, 'message' => 'Erreur lors du pointage forcé'];
                }
                break;
                
            case 'approve_entry':
                $entry_id = intval($_POST['entry_id']);
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET admin_approved = TRUE, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nApproved by admin at ', NOW())
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$entry_id])) {
                    $response = ['success' => true, 'message' => 'Entrée approuvée'];
                } else {
                    $response = ['success' => false, 'message' => 'Erreur lors de l\'approbation'];
                }
                break;
                
            case 'reject_entry':
                $entry_id = intval($_POST['entry_id']);
                $reason = $_POST['reason'] ?? 'Aucune raison spécifiée';
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET admin_approved = FALSE, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nRejected by admin at ', NOW(), ' - Reason: ', ?),
                        status = 'rejected'
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$reason, $entry_id])) {
                    $response = ['success' => true, 'message' => 'Entrée rejetée'];
                } else {
                    $response = ['success' => false, 'message' => 'Erreur lors du rejet'];
                }
                break;
                
        case 'send_notification':
                $user_id = intval($_POST['user_id']);
                $message = $_POST['message'];
                $response = ['success' => true, 'message' => 'Notification simulée envoyée'];
                break;

        case 'alert_details':
            $alert_code = $_POST['alert_code'] ?? '';
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
            
            // Paramètres identiques à ceux utilisés pour le calcul des alertes
            $settings = [
                'window_days' => 14,
                'lateness_tolerance_min' => 10,
                'overtime_hours_threshold' => 8.5,
                'lunch_max_minutes' => 90,
                'short_session_hours' => 0.5,
                'no_clockout_hours' => 10,
                'early_departure_tolerance_min' => 15,
                'low_lunch_minutes' => 20,
                'night_start' => '21:00:00',
                'night_end' => '06:00:00'
            ];
            $windowStart = date('Y-m-d', strtotime('-' . (int)$settings['window_days'] . ' days'));
            
            $details = [];
            
            switch ($alert_code) {
                case 'early_departure_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               TIME(tt.clock_out) as sortie,
                               COALESCE(us_a.end_time, gs_a.end_time) as fin_prevue
                        FROM time_tracking tt
                        LEFT JOIN time_slots us_a 
                               ON us_a.user_id = tt.user_id AND us_a.slot_type = 'afternoon' AND us_a.is_active = TRUE
                        LEFT JOIN time_slots gs_a 
                               ON gs_a.user_id IS NULL AND gs_a.slot_type = 'afternoon' AND gs_a.is_active = TRUE
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.status = 'completed'
                          AND tt.user_id = ?
                          AND tt.clock_out IS NOT NULL
                    ");
                    $stmt->execute([$windowStart, $user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $tol = (int)$settings['early_departure_tolerance_min'] * 60;
                    foreach ($rows as $r) {
                        if (!empty($r['fin_prevue']) && !empty($r['sortie'])) {
                            $out = strtotime($r['date_jour'] . ' ' . $r['sortie']);
                            $scheduledEnd = strtotime($r['date_jour'] . ' ' . $r['fin_prevue']);
                            if ($out < ($scheduledEnd - $tol)) {
                                $details[] = [
                                    'date' => $r['date_jour'],
                                    'heure_sortie' => substr($r['sortie'], 0, 5),
                                    'fin_prevue' => substr($r['fin_prevue'], 0, 5)
                                ];
                            }
                        }
                    }
                    break;
                
                case 'lateness_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               MIN(TIME(tt.clock_in)) as premiere_entree,
                               COALESCE(us_m.start_time, gs_m.start_time) as debut_prevu
                        FROM time_tracking tt
                        LEFT JOIN time_slots us_m 
                               ON us_m.user_id = tt.user_id AND us_m.slot_type = 'morning' AND us_m.is_active = TRUE
                        LEFT JOIN time_slots gs_m 
                               ON gs_m.user_id IS NULL AND gs_m.slot_type = 'morning' AND gs_m.is_active = TRUE
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status IN ('completed','active','break')
                        GROUP BY DATE(tt.clock_in), debut_prevu
                    ");
                    $stmt->execute([$windowStart, $user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $tol = (int)$settings['lateness_tolerance_min'] * 60;
                    foreach ($rows as $r) {
                        if (!empty($r['debut_prevu']) && !empty($r['premiere_entree'])) {
                            $firstIn = strtotime($r['date_jour'] . ' ' . $r['premiere_entree']);
                            $scheduled = strtotime($r['date_jour'] . ' ' . $r['debut_prevu']);
                            if ($firstIn > ($scheduled + $tol)) {
                                $details[] = [
                                    'date' => $r['date_jour'],
                                    'premiere_entree' => substr($r['premiere_entree'], 0, 5),
                                    'debut_prevu' => substr($r['debut_prevu'], 0, 5)
                                ];
                            }
                        }
                    }
                    break;
                
                case 'overtime_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND COALESCE(tt.work_duration, 0) > ?
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id, (float)$settings['overtime_hours_threshold']]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'lunch_overrun_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               ROUND(COALESCE(tt.break_duration, 0) * 60, 0) as minutes_pause,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND COALESCE(tt.break_duration, 0) > ?
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id, ((int)$settings['lunch_max_minutes'])/60.0]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'short_sessions_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND COALESCE(tt.work_duration, 0) < ?
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id, (float)$settings['short_session_hours']]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'weekend_work_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie,
                               ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                               DAYNAME(tt.clock_in) as jour
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND DAYOFWEEK(tt.clock_in) IN (1,7)
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'no_clockout_long':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               TIME(tt.clock_in) as entree,
                               TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) as heures_ouvertes,
                               tt.status
                        FROM time_tracking tt
                        WHERE tt.user_id = ?
                          AND tt.status IN ('active','break')
                        ORDER BY tt.clock_in ASC
                    ");
                    $stmt->execute([$user_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'low_lunch_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               ROUND(COALESCE(tt.break_duration, 0) * 60, 0) as minutes_pause,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND COALESCE(tt.break_duration, 0) > 0
                          AND COALESCE(tt.break_duration, 0) < ?
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id, ((int)$settings['low_lunch_minutes'])/60.0]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                
                case 'night_work_frequent':
                    $stmt = $shop_pdo->prepare("
                        SELECT DATE(tt.clock_in) as date_jour,
                               TIME(tt.clock_in) as entree,
                               TIME(tt.clock_out) as sortie,
                               ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail
                        FROM time_tracking tt
                        WHERE DATE(tt.clock_in) >= ?
                          AND tt.user_id = ?
                          AND tt.status = 'completed'
                          AND (TIME(tt.clock_in) >= ? OR TIME(tt.clock_in) < ? OR (tt.clock_out IS NOT NULL AND (TIME(tt.clock_out) >= ? OR TIME(tt.clock_out) < ?)))
                        ORDER BY tt.clock_in DESC
                    ");
                    $stmt->execute([$windowStart, $user_id, $settings['night_start'], $settings['night_end'], $settings['night_start'], $settings['night_end']]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
            
            $response = ['success' => true, 'details' => $details];
            break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
    
    // Retourner la réponse en JSON pour les requêtes AJAX
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
              || (isset($_POST['action']) && in_array($_POST['action'], ['approve_entry', 'reject_entry', 'force_clock_out', 'send_notification', 'alert_details']));
    
    if ($isAjax) {
        // Nettoyer tout output buffer existant
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($response);
        exit;
    }
}

// La logique dexport sera geree plus tard apres initialisation complete

// Recuperer les donnees pour laffichage avec gestion derreurs
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Initialiser les variables avec des valeurs par défaut
$active_users = [];
$stats = [
    'total_sessions' => 0,
    'active_employees' => 0,
    'currently_working' => 0,
    'on_break' => 0,
    'avg_work_hours' => 0,
    'total_work_hours' => 0,
    'overtime_sessions' => 0,
    'pending_approvals' => 0
];
$alerts = [];
$chart_data = [];
$daily_entries = [];
$all_users = [];
$top_performers = [];
$calendar_data = [];
$pending_requests = [];
$global_slots = [];
$user_slots = [];

try {
    // Vérifier d'abord si la table time_tracking existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo '<div class="alert alert-warning">
            <h4><i class="fas fa-table"></i> Table manquante</h4>
            <p>La table <code>time_tracking</code> n\'existe pas dans cette base de données.</p>
            <p>Veuillez d\'abord créer la table de pointage pour utiliser cette fonctionnalité.</p>
        </div>';
        return;
    }

    // Vérifier si la table time_slots existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
    $stmt->execute();
    $slots_table_exists = $stmt->fetch();
    
    if (!$slots_table_exists) {
        // Créer la table time_slots si elle n'existe pas
        $sql = file_get_contents(__DIR__ . '/create_time_slots_table.sql');
        $shop_pdo->exec($sql);
    }

    // Récupérer tous les utilisateurs pour les filtres
    $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Utilisateurs actifs (pointés en ce moment)
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username, u.role,
               TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
               TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration,
               CASE 
                   WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 8 THEN 'overtime'
                   WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 6 THEN 'normal'
                   ELSE 'short'
               END as duration_status
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status IN ('active', 'break')
        ORDER BY tt.clock_in ASC
    ");
    $stmt->execute();
    $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques avancées
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(DISTINCT user_id) as active_employees,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
            SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
            COALESCE(AVG(work_duration), 0) as avg_work_hours,
            COALESCE(SUM(work_duration), 0) as total_work_hours,
            COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, clock_in, COALESCE(clock_out, NOW())) > 8 THEN 1 END) as overtime_sessions,
            COUNT(CASE WHEN admin_approved = 0 AND status = 'completed' THEN 1 END) as pending_approvals
        FROM time_tracking
        WHERE DATE(clock_in) = ?
    ");
    $stmt->execute([$filter_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }

    // Alertes pour heures supplémentaires
    $stmt = $shop_pdo->prepare("
        SELECT u.full_name, tt.clock_in, tt.user_id,
               TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) as hours_worked
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status IN ('active', 'break') 
        AND TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 8
    ");
    $stmt->execute();
    $overtime_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($overtime_alerts as $alert) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'fas fa-clock',
            'title' => 'Heures supplémentaires',
            'message' => "{$alert['full_name']} travaille depuis {$alert['hours_worked']}h",
            'code' => 'overtime_now',
            'action' => 'force_clock_out',
            'user_id' => $alert['user_id']
        ];
    }

    // Paramètres des alertes intelligentes
    $alert_settings = [
        'window_days' => 14,
        'lateness_tolerance_min' => 10,
        'lateness_threshold' => 3,
        'overtime_hours_threshold' => 8.5,
        'overtime_days_threshold' => 3,
        'lunch_max_minutes' => 90,
        'lunch_overrun_days_threshold' => 2,
        'short_session_hours' => 0.5,
        'short_session_count_threshold' => 4,
        'no_clockout_hours' => 10,
        'weekend_days_threshold' => 2,
        'early_departure_tolerance_min' => 15,
        'low_lunch_minutes' => 20,
        'night_start' => '21:00:00',
        'night_end' => '06:00:00'
    ];

    $windowStart = date('Y-m-d', strtotime('-' . (int)$alert_settings['window_days'] . ' days'));

    // 1) Retards fréquents (comparés au créneau matin s'il existe)
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, DATE(tt.clock_in) as work_date,
               MIN(TIME(tt.clock_in)) as first_in,
               COALESCE(us_m.start_time, gs_m.start_time) as morning_start
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        LEFT JOIN time_slots us_m 
               ON us_m.user_id = u.id AND us_m.slot_type = 'morning' AND us_m.is_active = TRUE
        LEFT JOIN time_slots gs_m 
               ON gs_m.user_id IS NULL AND gs_m.slot_type = 'morning' AND gs_m.is_active = TRUE
        WHERE DATE(tt.clock_in) >= ? AND tt.status IN ('completed','active','break')
        GROUP BY u.id, u.full_name, DATE(tt.clock_in), morning_start
    ");
    $stmt->execute([$windowStart]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $late_count_by_user = [];
    foreach ($rows as $r) {
        if (!empty($r['morning_start'])) {
            $firstIn = strtotime($r['work_date'] . ' ' . $r['first_in']);
            $scheduled = strtotime($r['work_date'] . ' ' . $r['morning_start']);
            $tolerance = (int)$alert_settings['lateness_tolerance_min'] * 60;
            if ($firstIn > ($scheduled + $tolerance)) {
                $late_count_by_user[$r['user_id']] = ($late_count_by_user[$r['user_id']] ?? 0) + 1;
                $late_names[$r['user_id']] = $r['full_name'];
            }
        }
    }
    foreach ($late_count_by_user as $uid => $count) {
        if ($count >= (int)$alert_settings['lateness_threshold']) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-user-clock',
                'title' => 'Retards trop fréquents',
                'message' => ($late_names[$uid] ?? 'Employe') . ": " . $count . " retards sur les " . (int)$alert_settings['window_days'] . " derniers jours",
                'code' => 'lateness_frequent',
                'user_id' => $uid
            ];
        }
    }

    // 2) Heures sup fréquentes (sessions > threshold heures)
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name,
               COUNT(*) as overtime_days
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND COALESCE(tt.work_duration, 0) > ?
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart, (float)$alert_settings['overtime_hours_threshold']]);
    $overtime_freq = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($overtime_freq as $r) {
        if ($r['overtime_days'] >= (int)$alert_settings['overtime_days_threshold']) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-hourglass-half',
                'title' => 'Heures supplémentaires fréquentes',
                'message' => $r['full_name'] . ' a dépassé ' . (float)$alert_settings['overtime_hours_threshold'] . 'h de travail sur ' . (int)$alert_settings['overtime_days_threshold'] . '+ jour(s) dans la période',
                'code' => 'overtime_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 3) Dépassement de pause déjeuner fréquent
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, COUNT(*) as lunch_overruns
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND COALESCE(tt.break_duration, 0) > ?
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart, ((int)$alert_settings['lunch_max_minutes'])/60.0]);
    $lunch_over = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lunch_over as $r) {
        if ($r['lunch_overruns'] >= (int)$alert_settings['lunch_overrun_days_threshold']) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fas fa-utensils',
                'title' => 'Pause déjeuner trop longue (fréquente)',
                'message' => $r['full_name'] . ' a dépassé ' . (int)$alert_settings['lunch_max_minutes'] . ' min de pause sur ' . (int)$alert_settings['lunch_overrun_days_threshold'] . '+ jour(s) dans la période',
                'code' => 'lunch_overrun_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 4) Trop de sessions très courtes
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, COUNT(*) as short_count
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND COALESCE(tt.work_duration, 0) < ?
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart, (float)$alert_settings['short_session_hours']]);
    $shorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($shorts as $r) {
        if ($r['short_count'] >= (int)$alert_settings['short_session_count_threshold']) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-bolt',
                'title' => 'Sessions anormalement courtes (fréquentes)',
                'message' => $r['full_name'] . ' a ' . (int)$r['short_count'] . ' session(s) < ' . (float)$alert_settings['short_session_hours'] . 'h sur la période',
                'code' => 'short_sessions_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 5) Travail le week-end fréquent
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, COUNT(*) as weekend_days
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND DAYOFWEEK(tt.clock_in) IN (1,7)
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart]);
    $weekend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($weekend as $r) {
        if ($r['weekend_days'] >= (int)$alert_settings['weekend_days_threshold']) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fas fa-calendar-week',
                'title' => 'Travail le week-end (fréquent)',
                'message' => $r['full_name'] . ' a travaillé ' . (int)$r['weekend_days'] . ' jour(s) de week-end sur la période',
                'code' => 'weekend_work_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 6) Départs trop tôt (par jour unique)
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, DATE(tt.clock_in) as work_date,
               TIME(tt.clock_out) as last_out,
               COALESCE(us_a.end_time, gs_a.end_time) as afternoon_end
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        LEFT JOIN time_slots us_a 
               ON us_a.user_id = u.id AND us_a.slot_type = 'afternoon' AND us_a.is_active = TRUE
        LEFT JOIN time_slots gs_a 
               ON gs_a.user_id IS NULL AND gs_a.slot_type = 'afternoon' AND gs_a.is_active = TRUE
        WHERE DATE(tt.clock_in) >= ? AND tt.status = 'completed' AND tt.clock_out IS NOT NULL
    ");
    $stmt->execute([$windowStart]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $daily_min_out = [];
    $afternoon_end_by_user = [];
    $early_names = [];
    foreach ($rows as $r) {
        $uid = (int)$r['user_id'];
        $date = $r['work_date'];
        $key = $uid . '|' . $date;
        if (!empty($r['last_out'])) {
            if (!isset($daily_min_out[$key]) || $r['last_out'] < $daily_min_out[$key]) {
                $daily_min_out[$key] = $r['last_out'];
            }
        }
        if (!empty($r['afternoon_end'])) {
            $afternoon_end_by_user[$uid] = $r['afternoon_end'];
        }
        $early_names[$uid] = $r['full_name'];
    }
    $early_count_by_user = [];
    $tolerance = (int)$alert_settings['early_departure_tolerance_min'] * 60;
    foreach ($daily_min_out as $key => $last_out) {
        list($uid, $date) = explode('|', $key, 2);
        $uid = (int)$uid;
        $afternoon_end = $afternoon_end_by_user[$uid] ?? null;
        if ($afternoon_end) {
            $outTs = strtotime($date . ' ' . $last_out);
            $scheduledTs = strtotime($date . ' ' . $afternoon_end);
            if ($outTs < ($scheduledTs - $tolerance)) {
                $early_count_by_user[$uid] = ($early_count_by_user[$uid] ?? 0) + 1;
            }
        }
    }
    foreach ($early_count_by_user as $uid => $count) {
        if ($count >= 2) { // seuil fixe raisonnable
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-door-open',
                'title' => 'Départs anticipés (fréquents)',
                'message' => ($early_names[$uid] ?? 'Employe') . ": " . $count . " départ(s) avant l\'heure sur la période",
                'code' => 'early_departure_frequent',
                'user_id' => $uid
            ];
        }
    }

    // 7) Pas de pointage de sortie (sessions trop longues en cours)
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name,
               TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) as hours_open
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE tt.status IN ('active','break')
    ");
    $stmt->execute();
    $open_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($open_sessions as $r) {
        if ((int)$r['hours_open'] > (int)$alert_settings['no_clockout_hours']) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fas fa-sign-out-alt',
                'title' => 'Session ouverte trop longue',
                'message' => $r['full_name'] . ' a une session ouverte depuis ' . (int)$r['hours_open'] . 'h',
                'code' => 'no_clockout_long',
                'action' => 'force_clock_out',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 8) Pause déjeuner trop courte (santé / conformité)
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name, COUNT(*) as low_lunch_days
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND COALESCE(tt.break_duration, 0) > 0
          AND COALESCE(tt.break_duration, 0) < ?
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart, ((int)$alert_settings['low_lunch_minutes'])/60.0]);
    $low_lunch = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($low_lunch as $r) {
        if ($r['low_lunch_days'] >= 3) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fas fa-coffee',
                'title' => 'Pauses déjeuner trop courtes (fréquentes)',
                'message' => $r['full_name'] . ' a pris moins de ' . (int)$alert_settings['low_lunch_minutes'] . ' min de pause sur ' . (int)$r['low_lunch_days'] . ' jour(s)',
                'code' => 'low_lunch_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // 9) Travail de nuit fréquent
    $stmt = $shop_pdo->prepare("
        SELECT u.id as user_id, u.full_name,
               COUNT(*) as night_sessions
        FROM time_tracking tt
        JOIN users u ON u.id = tt.user_id
        WHERE DATE(tt.clock_in) >= ?
          AND tt.status = 'completed'
          AND (TIME(tt.clock_in) >= ? OR TIME(tt.clock_in) < ? OR (tt.clock_out IS NOT NULL AND (TIME(tt.clock_out) >= ? OR TIME(tt.clock_out) < ?)))
        GROUP BY u.id, u.full_name
    ");
    $stmt->execute([$windowStart, $alert_settings['night_start'], $alert_settings['night_end'], $alert_settings['night_start'], $alert_settings['night_end']]);
    $night_work = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($night_work as $r) {
        if ($r['night_sessions'] >= 2) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fas fa-moon',
                'title' => 'Travail de nuit (fréquent)',
                'message' => $r['full_name'] . ' a travaillé de nuit ' . (int)$r['night_sessions'] . ' fois sur la période',
                'code' => 'night_work_frequent',
                'user_id' => $r['user_id']
            ];
        }
    }

    // Données pour les graphiques (derniers 7 jours)
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $stmt = $shop_pdo->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as employees,
                COALESCE(SUM(work_duration), 0) as total_hours,
                COUNT(*) as sessions
            FROM time_tracking 
            WHERE DATE(clock_in) = ? AND status = 'completed'
        ");
        $stmt->execute([$date]);
        $day_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $chart_data[] = [
            'date' => $date,
            'display_date' => date('d/m', strtotime($date)),
            'employees' => $day_stats['employees'] ?? 0,
            'hours' => round($day_stats['total_hours'] ?? 0, 1),
            'sessions' => $day_stats['sessions'] ?? 0
        ];
    }

    // Top performers de la semaine
    $stmt = $shop_pdo->prepare("
        SELECT u.full_name, 
               COUNT(*) as sessions,
               COALESCE(SUM(tt.work_duration), 0) as total_hours,
               COALESCE(AVG(tt.work_duration), 0) as avg_hours,
               COUNT(CASE WHEN DATE(tt.clock_in) = CURDATE() THEN 1 END) as today_sessions
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status = 'completed' 
        AND DATE(tt.clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY tt.user_id, u.full_name
        HAVING total_hours > 0
        ORDER BY total_hours DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Données pour le calendrier (mois actuel)
    $calendar_month = $_GET['calendar_month'] ?? date('Y-m');
    $calendar_user_filter = $_GET['calendar_user'] ?? '';
    
    $calendar_query = "
        SELECT tt.*, u.full_name, u.username,
               DATE(tt.clock_in) as work_date,
               TIME(tt.clock_in) as start_time,
               TIME(tt.clock_out) as end_time,
               CASE 
                   WHEN tt.status = 'completed' THEN 'completed'
                   WHEN tt.status IN ('active', 'break') THEN 'active'
                   ELSE 'other'
               END as display_status
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE DATE(tt.clock_in) LIKE ?
    ";
    
    $calendar_params = [$calendar_month . '%'];
    
    if ($calendar_user_filter) {
        $calendar_query .= " AND tt.user_id = ?";
        $calendar_params[] = $calendar_user_filter;
    }
    
    $calendar_query .= " ORDER BY tt.clock_in DESC";
    
    $stmt = $shop_pdo->prepare($calendar_query);
    $stmt->execute($calendar_params);
    $calendar_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Demandes à approuver (incluant les pointages hors créneaux)
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username,
               DATE(tt.clock_in) as work_date,
               TIME(tt.clock_in) as start_time,
               TIME(tt.clock_out) as end_time,
               TIMESTAMPDIFF(MINUTE, tt.clock_in, tt.clock_out) / 60.0 as calculated_hours,
               COALESCE(tt.auto_approved, 0) as auto_approved,
               tt.approval_reason
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status = 'completed' AND tt.admin_approved = 0
        ORDER BY tt.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les créneaux globaux
    $stmt = $shop_pdo->prepare("
        SELECT slot_type, start_time, end_time 
        FROM time_slots 
        WHERE user_id IS NULL AND is_active = TRUE
    ");
    $stmt->execute();
    $global_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($global_slots_raw as $slot) {
        $global_slots[$slot['slot_type']] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time']
        ];
    }

    // Récupérer les créneaux spécifiques par utilisateur
    $stmt = $shop_pdo->prepare("
        SELECT ts.user_id, ts.slot_type, ts.start_time, ts.end_time, u.full_name 
        FROM time_slots ts
        JOIN users u ON ts.user_id = u.id
        WHERE ts.user_id IS NOT NULL AND ts.is_active = TRUE
        ORDER BY u.full_name, ts.slot_type
    ");
    $stmt->execute();
    $user_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($user_slots_raw as $slot) {
        if (!isset($user_slots[$slot['user_id']])) {
            $user_slots[$slot['user_id']] = [
                'full_name' => $slot['full_name'],
                'slots' => []
            ];
        }
        $user_slots[$slot['user_id']]['slots'][$slot['slot_type']] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time']
        ];
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-exclamation-triangle"></i> Erreur de données</h4>
        <p>Erreur lors de la récupération des données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}
?>

<!-- CSS pour forcer une colonne unique -->
<style>
/* Reset des styles qui pourraient causer le problème de colonnes */
.admin-timetracking-container {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    float: none !important;
    clear: both !important;
}

.admin-timetracking-container * {
    box-sizing: border-box;
}

/* Variables CSS modernes */
:root {
    --primary-color: #0066cc;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --dark-color: #343a40;
    --light-color: #f8f9fa;
    --border-radius: 12px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

/* Navigation moderne */
.admin-nav {
    background: linear-gradient(135deg, var(--primary-color), #004499);
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    width: 100%;
}

.nav-tabs-custom {
    border: none;
    margin: 0;
}

.nav-tabs-custom .nav-link {
    border: none;
    color: rgba(255, 255, 255, 0.8);
    background: transparent;
    border-radius: 8px;
    margin-right: 0.5rem;
    transition: var(--transition);
    padding: 0.75rem 1.25rem;
    font-weight: 500;
}

.nav-tabs-custom .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.nav-tabs-custom .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Cards modernes */
.stats-card {
    background: white;
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    overflow: hidden;
    position: relative;
    margin-bottom: 1rem;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--success-color));
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stats-card.warning::before {
    background: linear-gradient(90deg, var(--warning-color), #e0a800);
}

.stats-card.danger::before {
    background: linear-gradient(90deg, var(--danger-color), #c82333);
}

.stats-card.info::before {
    background: linear-gradient(90deg, var(--info-color), #138496);
}

/* Cartes utilisateurs actifs */
.active-user-card {
    background: white;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--success-color);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    margin-bottom: 1rem;
}

.active-user-card:hover {
    transform: translateX(4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.break-user-card {
    border-left-color: var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

.overtime-user-card {
    border-left-color: var(--danger-color);
    background: linear-gradient(45deg, #f8d7da, #f5c6cb);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

/* Graphiques */
.chart-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

/* Alertes */
.alert-panel {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    border: none;
}

.alert-item {
    border-left: 4px solid;
    background: white;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.alert-item.warning {
    border-left-color: var(--warning-color);
    background: linear-gradient(90deg, #fff3cd, #ffeaa7);
}

.alert-item:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Calendrier */
.calendar-filters {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.calendar-entry {
    background: white;
    border-radius: 8px;
    border-left: 4px solid;
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.calendar-entry.completed {
    border-left-color: var(--success-color);
}

.calendar-entry.active {
    border-left-color: var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

.calendar-entry:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Demandes à approuver */
.approval-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.approval-item:hover {
    box-shadow: var(--box-shadow);
    transform: translateY(-2px);
}

.approval-item.out-of-hours {
    border-left: 4px solid var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

/* Paramètres */
.settings-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
}

.time-slot-config {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
}

.user-slot-item {
    background: white;
    border-radius: 8px;
    border-left: 4px solid var(--info-color);
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.user-slot-item:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Badges et status */
.duration-display {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-weight: bold;
    font-size: 1.1em;
    padding: 0.4rem 0.8rem;
    background: rgba(0, 102, 204, 0.1);
    border-radius: 6px;
    display: inline-block;
    border: 2px solid rgba(0, 102, 204, 0.2);
}

.pulse-animation {
    animation: pulse 2s infinite;
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .admin-nav {
        padding: 1rem;
    }
    
    .nav-tabs-custom .nav-link {
        margin-right: 0.25rem;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}
</style>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    
    <!-- Loader Mode Clair -->
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<!-- Container principal en une seule colonne -->
<div class="admin-timetracking-container" id="mainContent" style="display: none;">
    <div class="w-100">
        <!-- Header avec navigation -->
        <div class="admin-nav">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="text-white mb-1">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Pointage
                    </h1>
                    <p class="text-white-50 mb-0">Supervision avancée et analytics des temps de travail</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="showExportModal()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>
            
            <!-- Navigation par onglets -->
            <ul class="nav nav-tabs nav-tabs-custom" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab">
                        <i class="fas fa-broadcast-tower"></i> Temps Réel
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                        <i class="fas fa-calendar-alt"></i> Calendrier
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button" role="tab">
                        <i class="fas fa-check-double"></i> Demandes à approuver
                        <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pending_requests); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                        <i class="fas fa-bell"></i> Alertes
                        <?php if (count($alerts) > 0): ?>
                        <span class="badge bg-warning ms-1"><?php echo count($alerts); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu des onglets en pleine largeur -->
        <div class="tab-content w-100" id="adminTabsContent">
            
            <!-- Dashboard Principal -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- KPIs principaux en une ligne -->
                <div class="row w-100 mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-success mb-1"><?php echo $stats['currently_working']; ?></h3>
                                        <small class="text-muted">Actuellement au travail</small>
                                    </div>
                                    <i class="fas fa-users fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card warning">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-warning mb-1"><?php echo $stats['on_break']; ?></h3>
                                        <small class="text-muted">En pause</small>
                                    </div>
                                    <i class="fas fa-pause fa-2x text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card info">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-info mb-1"><?php echo number_format($stats['total_work_hours'], 1); ?>h</h3>
                                        <small class="text-muted">Total heures aujourd'hui</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card danger">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-danger mb-1"><?php echo $stats['pending_approvals']; ?></h3>
                                        <small class="text-muted">À approuver</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques en une ligne -->
                <div class="row w-100 mb-4">
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-line text-primary"></i> Évolution 7 derniers jours</h5>
                            <canvas id="weeklyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-doughnut text-primary"></i> Répartition équipes</h5>
                            <canvas id="teamChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top performers en une ligne -->
                <div class="row w-100">
                    <div class="col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Performers (7 jours)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_performers)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Aucune donnée disponible</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($top_performers as $index => $performer): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-pill"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($performer['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo number_format($performer['total_hours'], 1); ?>h total • <?php echo $performer['sessions']; ?> sessions</small>
                                    </div>
                                    <div>
                                        <span class="duration-display text-success"><?php echo number_format($performer['avg_hours'], 1); ?>h/jour</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistiques Rapides</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <h4 class="text-primary"><?php echo number_format($stats['avg_work_hours'], 1); ?>h</h4>
                                        <small class="text-muted">Moyenne/employé</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-warning"><?php echo $stats['overtime_sessions']; ?></h4>
                                        <small class="text-muted">Heures sup.</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo $stats['total_sessions']; ?></h4>
                                        <small class="text-muted">Sessions totales</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-info"><?php echo $stats['active_employees'] > 0 ? round(($stats['currently_working'] / $stats['active_employees']) * 100) : 0; ?>%</h4>
                                        <small class="text-muted">Taux présence</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temps réel -->
            <div class="tab-pane fade" id="live" role="tabpanel">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-satellite-dish text-success"></i> Activité en Temps Réel</h3>
                        <div>
                            <span class="badge bg-success pulse-animation">
                                <i class="fas fa-circle"></i> LIVE
                            </span>
                            <small class="text-muted ms-2">Dernière MàJ: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span></small>
                        </div>
                    </div>
                    
                    <?php if (!empty($active_users)): ?>
                    <div class="row w-100" id="activeUsersContainer">
                        <?php foreach ($active_users as $user): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="active-user-card <?php 
                                echo $user['status'] === 'break' ? 'break-user-card' : 
                                    ($user['duration_status'] === 'overtime' ? 'overtime-user-card' : ''); 
                            ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $user['status'] === 'break' ? 'warning' : 'success'; ?>">
                                                <?php echo $user['status'] === 'break' ? 'Pause' : 'Actif'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Temps écoulé</small>
                                            <span class="duration-display" id="duration-<?php echo $user['user_id']; ?>">
                                                <?php echo $user['formatted_duration']; ?>
                                            </span>
                                        </div>
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar <?php 
                                                echo $user['duration_status'] === 'overtime' ? 'bg-danger' : 
                                                    ($user['duration_status'] === 'normal' ? 'bg-success' : 'bg-info'); 
                                            ?>" 
                                                 style="width: <?php echo min(($user['current_duration'] / 8) * 100, 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            Début: <?php echo date('H:i', strtotime($user['clock_in'])); ?>
                                            <?php if ($user['duration_status'] === 'overtime'): ?>
                                            <span class="text-danger ms-2">⚠️ Heures sup.</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-outline-danger btn-sm flex-fill" 
                                                onclick="forceClockOut(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                            <i class="fas fa-sign-out-alt"></i> Sortie
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="sendNotification(<?php echo $user['user_id']; ?>)">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucun employé actuellement pointé</h4>
                        <p class="text-muted">Les employés pointés apparaîtront ici en temps réel</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendrier -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="w-100">
                    <!-- Filtres du calendrier -->
                    <div class="calendar-filters">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="calendarMonth" class="form-label">
                                    <i class="fas fa-calendar"></i> Mois
                                </label>
                                <input type="month" class="form-control" id="calendarMonth" 
                                       value="<?php echo $calendar_month; ?>" onchange="filterCalendar()">
                            </div>
                            <div class="col-md-6">
                                <label for="calendarUser" class="form-label">
                                    <i class="fas fa-user"></i> Employé
                                </label>
                                <select class="form-select" id="calendarUser" onchange="filterCalendar()">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $calendar_user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats du calendrier -->
                    <div class="row w-100">
                        <div class="col-12">
                            <div class="card stats-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-alt"></i> Historique des pointages
                                        <span class="badge bg-light text-dark ms-2"><?php echo count($calendar_data); ?> entrées</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($calendar_data)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">Aucun pointage trouvé</h4>
                                        <p class="text-muted">Aucun données pour les critères sélectionnés</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="row">
                                        <?php 
                                        $grouped_by_date = [];
                                        foreach ($calendar_data as $entry) {
                                            $grouped_by_date[$entry['work_date']][] = $entry;
                                        }
                                        ?>
                                        <?php foreach ($grouped_by_date as $date => $entries): ?>
                                        <div class="col-12 mb-4">
                                            <h6 class="text-primary border-bottom pb-2">
                                                <i class="fas fa-calendar-day"></i> 
                                                <?php echo formatDateFrench($date); ?>
                                                <span class="badge bg-secondary ms-2"><?php echo count($entries); ?> pointages</span>
                                            </h6>
                                            <?php foreach ($entries as $entry): ?>
                                            <div class="calendar-entry <?php echo $entry['display_status']; ?> p-3 mb-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                                 style="width: 40px; height: 40px;">
                                                                <?php echo strtoupper(substr($entry['full_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($entry['full_name']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo $entry['start_time']; ?> 
                                                                <?php if ($entry['end_time']): ?>
                                                                → <?php echo $entry['end_time']; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-<?php 
                                                            echo $entry['display_status'] === 'completed' ? 'success' : 
                                                                ($entry['display_status'] === 'active' ? 'warning' : 'secondary'); 
                                                        ?>">
                                                            <?php 
                                                            if ($entry['display_status'] === 'completed') echo 'Terminé';
                                                            elseif ($entry['display_status'] === 'active') echo 'En cours';
                                                            else echo ucfirst($entry['status']);
                                                            ?>
                                                        </span>
                                                        <?php if ($entry['work_duration']): ?>
                                                        <div class="duration-display mt-1">
                                                            <?php echo number_format($entry['work_duration'], 1); ?>h
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes à approuver -->
            <div class="tab-pane fade" id="approvals" role="tabpanel">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-check-double text-warning"></i> Demandes à approuver</h3>
                        <span class="badge bg-warning text-dark fs-6">
                            <?php echo count($pending_requests); ?> en attente
                        </span>
                    </div>
                    
                    <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">Toutes les demandes sont traitées</h4>
                        <p class="text-muted">Aucune demande d'approbation en attente</p>
                    </div>
                    <?php else: ?>
                    <div class="row w-100">
                        <?php foreach ($pending_requests as $request): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="approval-item p-3 <?php echo $request['approval_reason'] ? 'out-of-hours' : ''; ?>">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($request['full_name']); ?></h6>
                                            <small class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></small>
                                            <?php if ($request['approval_reason']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Hors créneaux
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning">En attente</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Date</small>
                                            <strong><?php echo date('d/m/Y', strtotime($request['work_date'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Durée</small>
                                            <strong><?php echo number_format($request['calculated_hours'], 1); ?>h</strong>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Début</small>
                                            <strong><?php echo $request['start_time']; ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fin</small>
                                            <strong><?php echo $request['end_time'] ?? 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($request['approval_reason']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Raison de la demande d'approbation</small>
                                    <div class="bg-warning bg-opacity-25 p-2 rounded" style="font-size: 0.9em;">
                                        <i class="fas fa-info-circle text-warning"></i> 
                                        <?php echo htmlspecialchars($request['approval_reason']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($request['admin_notes']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Notes</small>
                                    <div class="bg-light p-2 rounded" style="font-size: 0.9em;">
                                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success btn-sm flex-fill" 
                                            onclick="approveEntry(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                        <i class="fas fa-check"></i> Approuver
                                    </button>
                                    <button class="btn btn-danger btn-sm flex-fill" 
                                            onclick="rejectEntry(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" 
                                            onclick="viewDetails(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-cog text-primary"></i> Paramètres des créneaux horaires</h3>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Les pointages dans les créneaux sont approuvés automatiquement
                        </small>
                    </div>
                    
                    <!-- Créneaux globaux -->
                    <div class="settings-section">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-globe"></i> Créneaux globaux (par défaut)</h5>
                        </div>
                        <div class="card-body">
                            <form id="globalSlotsForm" onsubmit="saveGlobalSlots(event)">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-sun text-warning"></i> Matin</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="morning_start" 
                                                           value="<?php echo substr($global_slots['morning']['start_time'] ?? '08:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="morning_end" 
                                                           value="<?php echo substr($global_slots['morning']['end_time'] ?? '12:30:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-moon text-info"></i> Après-midi</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="afternoon_start" 
                                                           value="<?php echo substr($global_slots['afternoon']['start_time'] ?? '14:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="afternoon_end" 
                                                           value="<?php echo substr($global_slots['afternoon']['end_time'] ?? '19:00:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Sauvegarder les créneaux globaux
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Créneaux spécifiques par utilisateur -->
                    <div class="settings-section">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Créneaux spécifiques par employé</h5>
                        </div>
                        <div class="card-body">
                            <!-- Formulaire pour ajouter un nouveau créneau utilisateur -->
                            <div class="bg-light p-3 rounded mb-4">
                                <h6><i class="fas fa-plus"></i> Ajouter un créneau spécifique</h6>
                                <form id="userSlotsForm" onsubmit="saveUserSlots(event)">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Employé</label>
                                            <select class="form-select" name="user_id" required>
                                                <option value="">Sélectionner un employé</option>
                                                <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Matin début</label>
                                            <input type="time" class="form-control" name="user_morning_start">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Matin fin</label>
                                            <input type="time" class="form-control" name="user_morning_end">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">A-midi début</label>
                                            <input type="time" class="form-control" name="user_afternoon_start">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">A-midi fin</label>
                                            <input type="time" class="form-control" name="user_afternoon_end">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des créneaux spécifiques existants -->
                            <?php if (empty($user_slots)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <h6 class="text-muted">Aucun créneau spécifique configuré</h6>
                                <p class="text-muted">Tous les employés utilisent les créneaux globaux</p>
                            </div>
                            <?php else: ?>
                            <h6 class="mb-3">Créneaux spécifiques configurés</h6>
                            <?php foreach ($user_slots as $user_id => $user_data): ?>
                            <div class="user-slot-item p-3 mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h6>
                                        <div class="small text-muted">
                                            <?php if (isset($user_data['slots']['morning'])): ?>
                                            <span class="me-3">
                                                <i class="fas fa-sun text-warning"></i> 
                                                Matin: <?php echo substr($user_data['slots']['morning']['start_time'], 0, 5); ?> - 
                                                <?php echo substr($user_data['slots']['morning']['end_time'], 0, 5); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (isset($user_data['slots']['afternoon'])): ?>
                                            <span>
                                                <i class="fas fa-moon text-info"></i> 
                                                A-midi: <?php echo substr($user_data['slots']['afternoon']['start_time'], 0, 5); ?> - 
                                                <?php echo substr($user_data['slots']['afternoon']['end_time'], 0, 5); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="removeUserSlots(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informations système -->
                    <div class="settings-section">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Fonctionnement du système</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-check-circle text-success"></i> Approbation automatique</h6>
                                    <ul class="small text-muted">
                                        <li>Les pointages dans les créneaux autorisés sont approuvés automatiquement</li>
                                        <li>Les créneaux spécifiques remplacent les créneaux globaux</li>
                                        <li>La vérification se fait à l'heure de pointage</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-exclamation-triangle text-warning"></i> Demande d'approbation</h6>
                                    <ul class="small text-muted">
                                        <li>Pointages trop tôt ou trop tard</li>
                                        <li>Pointages hors créneaux définis</li>
                                        <li>Notification visible dans longlet Demandes a approuver</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertes -->
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="alert-panel w-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Alertes Actives 
                            <span class="badge bg-dark ms-2"><?php echo count($alerts); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($alerts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4 class="text-success">Aucune alerte active</h4>
                            <p class="text-muted">Tout semble fonctionner normalement</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item <?php echo $alert['type']; ?> p-3 mb-3" 
                             role="button"
                             onclick="showAlertDetails('<?php echo $alert['code'] ?? '';?>', <?php echo $alert['user_id'] ?? 'null'; ?>)">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="<?php echo $alert['icon']; ?> fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1"><?php echo $alert['title']; ?></h6>
                                        <p class="mb-0"><?php echo $alert['message']; ?></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if (isset($alert['action'])): ?>
                                    <button class="btn btn-sm btn-outline-dark" 
                                            onclick="handleAlert('<?php echo $alert['action']; ?>', <?php echo $alert['user_id'] ?? 'null'; ?>)">
                                        Résoudre
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="dismissAlert(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download text-primary"></i> Exporter les pointages
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Boutons de sélection rapide -->
                <div class="mb-4">
                    <h6><i class="fas fa-clock text-info"></i> Sélection rapide</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary" onclick="setQuickPeriod('thisWeek')">
                            <i class="fas fa-calendar-week"></i> Cette semaine
                        </button>
                        <button class="btn btn-outline-primary" onclick="setQuickPeriod('thisMonth')">
                            <i class="fas fa-calendar-alt"></i> Ce mois-ci
                        </button>
                        <button class="btn btn-outline-secondary" onclick="setQuickPeriod('lastWeek')">
                            <i class="fas fa-backward"></i> Semaine dernière
                        </button>
                        <button class="btn btn-outline-secondary" onclick="setQuickPeriod('lastMonth')">
                            <i class="fas fa-backward"></i> Mois dernier
                        </button>
                    </div>
                </div>
                
                <!-- Sélection manuelle avec calendriers -->
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar-day text-success"></i> Date de début</h6>
                        <input type="date" id="exportDateStart" class="form-control" onchange="updateExportPreview()">
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar-day text-danger"></i> Date de fin</h6>
                        <input type="date" id="exportDateEnd" class="form-control" onchange="updateExportPreview()">
                    </div>
                </div>
                
                <!-- Aperçu de la période -->
                <div class="mt-3">
                    <div class="alert alert-info" id="exportPreview">
                        <i class="fas fa-info-circle"></i> Sélectionnez une période pour voir l'aperçu
                    </div>
                </div>
                
                <!-- Options d'export -->
                <div class="mt-3">
                    <h6><i class="fas fa-cog text-warning"></i> Options d'export</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeBreaks" checked>
                        <label class="form-check-label" for="includeBreaks">
                            Inclure les détails des pauses
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeNotes" checked>
                        <label class="form-check-label" for="includeNotes">
                            Inclure les notes administrateur
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-success" onclick="executeExport()" id="exportButton" disabled>
                    <i class="fas fa-download"></i> Télécharger CSV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails Alerte -->
<div class="modal fade" id="alertDetailsModal" tabindex="-1" aria-labelledby="alertDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertDetailsLabel"><i class="fas fa-info-circle text-warning"></i> Détails de l'alerte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="alertDetailsContent">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <div>Chargement des détails...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
 </div>

<!-- Scripts Chart.js compatible -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Données pour les graphiques (depuis PHP)
const chartData = <?php echo json_encode($chart_data); ?>;

// Initialisation des graphiques après le chargement
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que Chart.js soit complètement chargé
    if (typeof Chart !== 'undefined') {
        initSimpleCharts();
    } else {
        console.log('Chart.js non disponible, graphiques désactivés');
    }
});

function initSimpleCharts() {
    try {
        // Graphique hebdomadaire
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx) {
            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.display_date),
                    datasets: [{
                        label: 'Heures travaillées',
                        data: chartData.map(d => d.hours),
                        borderColor: 'rgb(0, 102, 204)',
                        backgroundColor: 'rgba(0, 102, 204, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        }
        
        // Graphique en donut
        const teamCtx = document.getElementById('teamChart');
        if (teamCtx) {
            new Chart(teamCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Actifs', 'En pause', 'Hors ligne'],
                    datasets: [{
                        data: [
                            <?php echo $stats['currently_working']; ?>,
                            <?php echo $stats['on_break']; ?>,
                            <?php echo max(0, ($stats['active_employees'] ?? 0) - ($stats['currently_working'] ?? 0) - ($stats['on_break'] ?? 0)); ?>
                        ],
                        backgroundColor: [
                            'rgb(40, 167, 69)',
                            'rgb(255, 193, 7)',
                            'rgb(108, 117, 125)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        console.log('✅ Graphiques initialisés avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation des graphiques:', error);
    }
}

// Détails d'alertes via AJAX
async function showAlertDetails(alertCode, userId) {
    if (!alertCode || !userId) return;
    const modalEl = document.getElementById('alertDetailsModal');
    const modal = new bootstrap.Modal(modalEl);
    const content = document.getElementById('alertDetailsContent');
    content.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><div>Chargement des détails...</div></div>';
    modal.show();

    try {
        const formData = new FormData();
        formData.append('action', 'alert_details');
        formData.append('alert_code', alertCode);
        formData.append('user_id', userId);
        
        const resp = await fetch('/alert_details.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await resp.json();
        if (!data.success) throw new Error(data.message || 'Erreur inconnue');

        const details = data.details || [];
        if (details.length === 0) {
            content.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Aucun détail disponible pour cette alerte.</div>';
            return;
        }

        // Titre dynamique simple
        const titleMap = {
            'early_departure_frequent': 'Departs anticipes',
            'lateness_frequent': 'Retards frequents',
            'overtime_frequent': 'Heures sup frequentes',
            'lunch_overrun_frequent': 'Pause dejeuner trop longue',
            'short_sessions_frequent': 'Sessions tres courtes',
            'weekend_work_frequent': 'Travail le weekend',
            'no_clockout_long': 'Sessions ouvertes trop longues',
            'low_lunch_frequent': 'Pauses dejeuner trop courtes',
            'night_work_frequent': 'Travail de nuit'
        };
        const titleEl = document.getElementById('alertDetailsLabel');
        if (titleEl) {
            titleEl.innerText = (titleMap[alertCode] || "Details de l'alerte") + ' - ' + details.length + ' ligne' + (details.length > 1 ? 's' : '');
        }

        function formatInfo(code, row) {
            switch (code) {
                case 'early_departure_frequent':
                    return `Sortie ${row.heure_sortie || ''} • Fin prevue ${row.fin_prevue || ''}`;
                case 'lateness_frequent':
                    return `Premiere entree ${row.premiere_entree || ''} • Debut prevu ${row.debut_prevu || ''}`;
                case 'overtime_frequent':
                    return `Entree ${row.entree || ''} • Sortie ${row.sortie || ''} • ${row.heures_travail || ''} h`;
                case 'lunch_overrun_frequent':
                    return `Pause ${row.minutes_pause || ''} min • ${row.entree || ''}-${row.sortie || ''}`;
                case 'short_sessions_frequent':
                    return `Duree ${row.heures_travail || ''} h • ${row.entree || ''}-${row.sortie || ''}`;
                case 'weekend_work_frequent':
                    return `${row.jour || ''} • ${row.entree || ''}-${row.sortie || ''} • ${row.heures_travail || ''} h`;
                case 'no_clockout_long':
                    return `Entree ${row.entree || ''} • Ouverte ${row.heures_ouvertes || ''} h • ${row.status || ''}`;
                case 'low_lunch_frequent':
                    return `Pause ${row.minutes_pause || ''} min • ${row.entree || ''}-${row.sortie || ''}`;
                case 'night_work_frequent':
                    return `${row.entree || ''}-${row.sortie || ''} • ${row.heures_travail || ''} h`;
                default:
                    return Object.keys(row).map(k => `${k}: ${row[k]}`).join(' • ');
            }
        }

        let html = '<div class="table-responsive">';
        html += '<table class="table table-sm"><thead><tr><th style="width:160px">Date</th><th>Infos</th></tr></thead><tbody>';
        html += details.map(r => `<tr><td>${r.date || r.date_jour || ''}</td><td>${formatInfo(alertCode, r)}</td></tr>`).join('');
        html += '</tbody></table></div>';
        content.innerHTML = html;
    } catch (e) {
        content.innerHTML = `<div class="alert alert-danger"><i class=\"fas fa-exclamation-triangle\"></i> Erreur: ${e.message}</div>`;
    }
}

// Fonctions pour le calendrier
function filterCalendar() {
    const month = document.getElementById('calendarMonth').value;
    const user = document.getElementById('calendarUser').value;
    
    const url = new URL(window.location);
    url.searchParams.set('calendar_month', month);
    if (user) {
        url.searchParams.set('calendar_user', user);
    } else {
        url.searchParams.delete('calendar_user');
    }
    
    window.location.href = url.toString();
}

// Fonctions pour les paramètres
function saveGlobalSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_global_slots');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

function saveUserSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_user_slots');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

function removeUserSlots(userId, userName) {
    if (confirm(`Supprimer les créneaux spécifiques de ${userName} ? L'employé utilisera les créneaux globaux.`)) {
        const formData = new FormData();
        formData.append('action', 'remove_user_slots');
        formData.append('user_id', userId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

// Fonctions pour les approbations
function approveEntry(entryId, userName) {
    if (confirm(`Approuver le pointage de ${userName} ?`)) {
        const formData = new FormData();
        formData.append('action', 'approve_entry');
        formData.append('entry_id', entryId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            return response.text().then(text => {
                console.log('Response text:', text.substring(0, 500) + (text.length > 500 ? '...' : ''));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Parsing JSON failed:', e);
                    throw new Error('Response is not valid JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed data:', data);
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

function rejectEntry(entryId, userName) {
    const reason = prompt(`Raison du rejet pour ${userName}:`);
    if (reason !== null) {
        const formData = new FormData();
        formData.append('action', 'reject_entry');
        formData.append('entry_id', entryId);
        formData.append('reason', reason);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

function viewDetails(entryId) {
    // Fonction pour voir les détails (à implémenter selon les besoins)
    alert('Fonctionnalité de détails à venir - ID: ' + entryId);
}

// Fonctions globales existantes
function refreshDashboard() {
    location.reload();
}

// Nouvelles fonctions pour le modal dexport
function showExportModal() {
    // Initialiser les dates par defaut
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setMonth(today.getMonth() - 1);
    
    document.getElementById('exportDateStart').value = lastMonth.toISOString().split('T')[0];
    document.getElementById('exportDateEnd').value = today.toISOString().split('T')[0];
    
    updateExportPreview();
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

function setQuickPeriod(period) {
    const today = new Date();
    let startDate, endDate;
    
    switch(period) {
        case 'thisWeek':
            // Debut de la semaine (lundi)
            startDate = new Date(today);
            startDate.setDate(today.getDate() - today.getDay() + 1);
            endDate = new Date(today);
            break;
            
        case 'thisMonth':
            // Debut du mois
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today);
            break;
            
        case 'lastWeek':
            // Semaine derniere
            endDate = new Date(today);
            endDate.setDate(today.getDate() - today.getDay());
            startDate = new Date(endDate);
            startDate.setDate(endDate.getDate() - 6);
            break;
            
        case 'lastMonth':
            // Mois dernier
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
    }
    
    document.getElementById('exportDateStart').value = startDate.toISOString().split('T')[0];
    document.getElementById('exportDateEnd').value = endDate.toISOString().split('T')[0];
    
    updateExportPreview();
}

function updateExportPreview() {
    const startDate = document.getElementById('exportDateStart').value;
    const endDate = document.getElementById('exportDateEnd').value;
    const preview = document.getElementById('exportPreview');
    const exportButton = document.getElementById('exportButton');
    
    if (!startDate || !endDate) {
        preview.innerHTML = '<i class="fas fa-info-circle"></i> Selectionnez une periode pour voir lapercu';
        preview.className = 'alert alert-info';
        exportButton.disabled = true;
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        preview.innerHTML = '<i class="fas fa-exclamation-triangle"></i> La date de debut doit etre anterieure a la date de fin';
        preview.className = 'alert alert-warning';
        exportButton.disabled = true;
        return;
    }
    
    // Calculer le nombre de jours
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    // Formater les dates en francais
    const startFormatted = formatDateForPreview(start);
    const endFormatted = formatDateForPreview(end);
    
    preview.innerHTML = `<i class="fas fa-check-circle"></i> Export du <strong>${startFormatted}</strong> au <strong>${endFormatted}</strong> (${diffDays} jour${diffDays > 1 ? 's' : ''})`;
    preview.className = 'alert alert-success';
    exportButton.disabled = false;
}

function formatDateForPreview(date) {
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('fr-FR', options);
}

function executeExport() {
    const startDate = document.getElementById('exportDateStart').value;
    const endDate = document.getElementById('exportDateEnd').value;
    
    if (!startDate || !endDate) {
        alert('Veuillez selectionner une periode');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('La date de debut doit etre anterieure a la date de fin');
        return;
    }
    
    // Construire lURL dexport vers le fichier dedie
    const exportUrl = '/export_csv.php?date_start=' + encodeURIComponent(startDate) + '&date_end=' + encodeURIComponent(endDate);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    modal.hide();
    
    // Lancer lexport
    window.open(exportUrl, '_blank');
}

function forceClockOut(userId, userName) {
    if (confirm(`Forcer le pointage de sortie de ${userName} ?`)) {
        const formData = new FormData();
        formData.append('action', 'force_clock_out');
        formData.append('user_id', userId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

function sendNotification(userId) {
    const message = prompt('Message à envoyer:');
    if (message) {
        const formData = new FormData();
        formData.append('action', 'send_notification');
        formData.append('user_id', userId);
        formData.append('message', message);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        });
    }
}

function handleAlert(action, userId) {
    if (action === 'force_clock_out' && userId) {
        forceClockOut(userId, 'cet employé');
    }
}

function dismissAlert(button) {
    button.closest('.alert-item').style.display = 'none';
}

// Auto-refresh toutes les 60 secondes pour longlet temps reel
setInterval(() => {
    if (document.getElementById('live-tab') && document.getElementById('live-tab').classList.contains('active')) {
        location.reload();
    }
}, 60000);
</script>

</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

/* Texte du loader mode clair */
.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.admin-timetracking-container,
.admin-timetracking-container * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.content-wrapper {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.card,
.modal-content,
.stats-card,
.chart-container {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .stats-card,
.dark-mode .chart-container {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script>

