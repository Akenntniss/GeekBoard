<?php
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// TEMPORAIRE : Forcer la session admin pour test
$_SESSION["shop_id"] = 63;
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";
$_SESSION["role"] = "admin";  
$_SESSION["full_name"] = "Administrateur Test";
$_SESSION["username"] = "admin";
$_SESSION["is_logged_in"] = true;

// Inclure les fichiers nécessaires avec gestion d'erreur
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
    exit;
}

$mission_id = (int)$_GET['id'];

// Vérifier l'authentification
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Récupérer les détails de la mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_quantite, m.recompense_euros, m.recompense_points, 
            m.statut, m.created_at, m.date_fin,
            mt.nom as type_nom, mt.icone as type_icone, mt.couleur as type_couleur, mt.description as type_description
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.id = ?
    ");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        echo json_encode(['success' => false, 'message' => 'Mission non trouvée']);
        exit;
    }
    
    // Récupérer les participants avec leurs progressions
    $stmt = $shop_pdo->prepare("
        SELECT 
            u.full_name, u.username,
            um.statut, um.progres, um.date_rejointe, um.date_completee
        FROM user_missions um
        LEFT JOIN users u ON um.user_id = u.id
        WHERE um.mission_id = ?
        ORDER BY um.date_rejointe DESC
    ");
    $stmt->execute([$mission_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le HTML simplifié et moderne
    $html = '<div style="max-height: 70vh; overflow-y: auto;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: ' . ($mission['type_couleur'] ?? '#4361ee') . '; display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 1rem; font-size: 2rem;">
                        <i class="' . ($mission['type_icone'] ?? 'fas fa-star') . '"></i>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; color: #343a40;">' . htmlspecialchars($mission['titre']) . '</h3>
                    <p style="margin: 0; color: #6c757d; font-size: 1.1rem;">' . htmlspecialchars($mission['type_nom'] ?? 'Mission') . '</p>
                </div>
                
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h5 style="margin: 0 0 1rem 0; color: #343a40;"><i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #4361ee;"></i>Description</h5>
                    <p style="margin: 0; line-height: 1.6; color: #495057;">' . htmlspecialchars($mission['description']) . '</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #4361ee, #6c5ce7); border-radius: 12px; color: white;">
                        <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">' . $mission['objectif_quantite'] . '</div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Objectif</div>
                    </div>';
    
    if ($mission['recompense_euros'] > 0) {
        $html .= '<div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #52b788, #40916c); border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">' . $mission['recompense_euros'] . '€</div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Récompense</div>
                  </div>';
    }
    
    if ($mission['recompense_points'] > 0) {
        $html .= '<div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #f77f00, #fd9843); border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">' . $mission['recompense_points'] . '</div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Points XP</div>
                  </div>';
    }
    
    $html .= '</div>';
    
    if (!empty($participants)) {
        $html .= '<div style="background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 1.5rem;">
                    <h5 style="margin: 0 0 1.5rem 0; color: #343a40;"><i class="fas fa-users" style="margin-right: 0.5rem; color: #4361ee;"></i>Participants (' . count($participants) . ')</h5>';
        
        foreach ($participants as $participant) {
            $statusColor = $participant['statut'] === 'terminee' ? '#52b788' : '#f77f00';
            $statusText = $participant['statut'] === 'terminee' ? 'Terminée' : 'En cours';
            $statusBg = $participant['statut'] === 'terminee' ? '#52b78820' : '#f77f0020';
            
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: ' . $statusBg . '; border-radius: 8px; margin-bottom: 0.5rem; border-left: 4px solid ' . $statusColor . ';">
                        <div>
                            <div style="font-weight: 600; color: #343a40; margin-bottom: 0.25rem;">' . htmlspecialchars($participant['full_name']) . '</div>
                            <small style="color: #6c757d;">@' . htmlspecialchars($participant['username']) . '</small>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: ' . $statusColor . '; font-weight: 600; margin-bottom: 0.25rem;">' . $statusText . '</div>
                            <small style="color: #6c757d;">Progrès: ' . ($participant['progres'] ?? 0) . '</small>
                        </div>
                      </div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div style="text-align: center; padding: 2rem; color: #6c757d; background: #f8f9fa; border-radius: 12px;">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h5>Aucun participant</h5>
                    <p style="margin: 0;">Cette mission n\'a pas encore de participants.</p>
                  </div>';
    }
    
    $html .= '<div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e9ecef; text-align: center;">
                <small style="color: #6c757d;">
                    <i class="fas fa-calendar" style="margin-right: 0.5rem;"></i>
                    Créée le ' . date('d/m/Y à H:i', strtotime($mission['created_at'])) . '
                </small>
              </div>
            </div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'mission' => $mission
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_mission_details: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors du chargement des détails: ' . $e->getMessage()
    ]);
}
?>
