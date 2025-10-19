<?php
header('Content-Type: application/json');
session_start();

// Forcer les sessions pour éviter les redirections
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les statistiques globales des récompenses
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(DISTINCT mv.user_mission_id) as validations_total,
            COUNT(DISTINCT CASE WHEN mv.statut = 'validee' THEN mv.user_mission_id END) as validations_approuvees,
            COUNT(DISTINCT um.user_id) as utilisateurs_actifs,
            COUNT(DISTINCT um.mission_id) as missions_avec_participants
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
    ");
    $stmt->execute();
    $stats_globales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculer les récompenses potentielles (basées sur les validations approuvées)
    $stmt = $shop_pdo->prepare("
        SELECT 
            COALESCE(SUM(m.recompense_euros), 0) as total_euros_distribues,
            COALESCE(SUM(m.recompense_points), 0) as total_points_distribues
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        WHERE mv.statut = 'validee'
    ");
    $stmt->execute();
    $recompenses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fusionner les statistiques
    $stats_globales = array_merge($stats_globales, $recompenses);
    
    // Récupérer les utilisateurs avec leurs récompenses
    $stmt = $shop_pdo->prepare("
        SELECT 
            u.id, u.full_name, u.username,
            COUNT(DISTINCT CASE WHEN mv.statut = 'validee' THEN mv.id END) as validations_approuvees,
            COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.id END) as missions_completees,
            COUNT(DISTINCT CASE WHEN um.statut = 'en_cours' THEN um.id END) as missions_en_cours,
            COALESCE(SUM(CASE WHEN mv.statut = 'validee' THEN m.recompense_euros END), 0) as total_euros,
            COALESCE(SUM(CASE WHEN mv.statut = 'validee' THEN m.recompense_points END), 0) as total_points,
            MAX(mv.date_validation) as derniere_validation
        FROM users u
        LEFT JOIN user_missions um ON u.id = um.user_id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        LEFT JOIN missions m ON um.mission_id = m.id
        WHERE u.role IN ('technicien', 'admin')
        GROUP BY u.id
        HAVING validations_approuvees > 0 OR missions_en_cours > 0
        ORDER BY total_euros DESC, total_points DESC
    ");
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les missions les plus rémunératrices
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.titre, m.recompense_euros, m.recompense_points,
            COUNT(DISTINCT um.user_id) as participants,
            COUNT(DISTINCT CASE WHEN mv.statut = 'validee' THEN mv.id END) as validations_approuvees
        FROM missions m
        LEFT JOIN user_missions um ON m.id = um.mission_id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE m.statut = 'active' AND (m.recompense_euros > 0 OR m.recompense_points > 0)
        GROUP BY m.id
        ORDER BY m.recompense_euros DESC, m.recompense_points DESC
        LIMIT 5
    ");
    $stmt->execute();
    $missions_top = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le HTML
    $html = '
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                    <h4 class="fw-bold text-success">' . number_format($stats_globales['total_euros_distribues'], 2) . ' €</h4>
                    <small class="text-muted">Total distribué</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h4 class="fw-bold text-warning">' . number_format($stats_globales['total_points_distribues']) . '</h4>
                    <small class="text-muted">Points XP distribués</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h4 class="fw-bold text-primary">' . $stats_globales['utilisateurs_actifs'] . '</h4>
                    <small class="text-muted">Utilisateurs actifs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-trophy fa-2x text-info mb-2"></i>
                    <h4 class="fw-bold text-info">' . $stats_globales['missions_avec_participants'] . '</h4>
                    <small class="text-muted">Missions avec participants</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <h5><i class="fas fa-user-friends me-2"></i>Classement des utilisateurs</h5>';
    
    if (empty($utilisateurs)) {
        $html .= '<div class="alert alert-info">Aucune récompense distribuée pour le moment</div>';
    } else {
        $html .= '<div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Utilisateur</th>
                        <th>Validations</th>
                        <th>Missions complétées</th>
                        <th>En cours</th>
                        <th>Euros gagnés</th>
                        <th>Points XP</th>
                        <th>Dernière validation</th>
                    </tr>
                </thead>
                <tbody>';
        
        $rang = 1;
        foreach ($utilisateurs as $user) {
            $badge_class = '';
            if ($rang == 1) $badge_class = 'text-warning';
            elseif ($rang == 2) $badge_class = 'text-secondary';
            elseif ($rang == 3) $badge_class = 'text-warning';
            
            $html .= '
                <tr>
                    <td>
                        <span class="fw-bold fs-5 ' . $badge_class . '">';
            
            if ($rang <= 3) {
                $icons = ['fas fa-trophy', 'fas fa-medal', 'fas fa-award'];
                $html .= '<i class="' . $icons[$rang-1] . ' me-1"></i>';
            }
            
            $html .= $rang . '</span>
                    </td>
                    <td>
                        <div>
                            <div class="fw-bold">' . htmlspecialchars($user['full_name']) . '</div>
                            <small class="text-muted">@' . htmlspecialchars($user['username']) . '</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary">' . $user['validations_approuvees'] . '</span>
                    </td>
                    <td>
                        <span class="badge bg-success">' . $user['missions_completees'] . '</span>
                    </td>
                    <td>
                        <span class="badge bg-info">' . $user['missions_en_cours'] . '</span>
                    </td>
                    <td>
                        <span class="fw-bold text-success">' . number_format($user['total_euros'], 2) . ' €</span>
                    </td>
                    <td>
                        <span class="fw-bold text-warning">' . number_format($user['total_points']) . '</span>
                    </td>
                    <td>';
            
            if ($user['derniere_validation']) {
                $html .= '<small class="text-muted">' . date('d/m/Y', strtotime($user['derniere_validation'])) . '</small>';
            } else {
                $html .= '<small class="text-muted">-</small>';
            }
            
            $html .= '</td>
                </tr>';
            
            $rang++;
        }
        
        $html .= '</tbody></table></div>';
    }
    
    $html .= '</div>';
    
    // Colonne de droite avec les missions top
    $html .= '
        <div class="col-md-4">
            <h5><i class="fas fa-chart-line me-2"></i>Missions les plus rémunératrices</h5>';
    
    if (empty($missions_top)) {
        $html .= '<div class="alert alert-info">Aucune mission rémunératrice active</div>';
    } else {
        foreach ($missions_top as $mission) {
            $html .= '
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-2">' . htmlspecialchars($mission['titre']) . '</h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-success fw-bold">' . number_format($mission['recompense_euros'], 2) . ' €</div>
                                <small class="text-muted">Récompense</small>
                            </div>
                            <div class="col-4">
                                <div class="text-warning fw-bold">' . $mission['recompense_points'] . '</div>
                                <small class="text-muted">Points XP</small>
                            </div>
                            <div class="col-4">
                                <div class="text-primary fw-bold">' . $mission['participants'] . '</div>
                                <small class="text-muted">Participants</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-check-circle me-1"></i>
                                ' . $mission['validations_approuvees'] . ' validations
                            </small>
                        </div>
                    </div>
                </div>';
        }
    }
    
    $html .= '</div></div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'stats' => $stats_globales
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_user_rewards: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des récompenses']);
}
?> 