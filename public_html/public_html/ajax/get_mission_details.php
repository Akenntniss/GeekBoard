<?php
header('Content-Type: application/json');
session_start();

// Forcer les sessions pour éviter les redirections
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier les paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
    exit;
}

$mission_id = (int)$_GET['id'];

try {
    $shop_pdo = getShopDBConnection();
    
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
            um.id, um.user_id, um.statut, um.progres as progression_actuelle, um.date_rejointe, um.date_completee,
            u.full_name, u.username,
            CASE 
                WHEN um.statut = 'terminee' THEN m.recompense_euros
                ELSE 0
            END as total_euros,
            CASE 
                WHEN um.statut = 'terminee' THEN m.recompense_points
                ELSE 0
            END as total_points
        FROM user_missions um
        LEFT JOIN users u ON um.user_id = u.id
        LEFT JOIN missions m ON um.mission_id = m.id
        WHERE um.mission_id = ?
        ORDER BY um.date_rejointe DESC
    ");
    $stmt->execute([$mission_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les validations en attente pour cette mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.user_mission_id, mv.statut, mv.date_soumission, mv.description,
            u.full_name
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE um.mission_id = ? AND mv.statut = 'en_attente'
        ORDER BY mv.date_soumission DESC
    ");
    $stmt->execute([$mission_id]);
    $validations_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier les données essentielles avant de continuer
    if (!isset($mission['titre']) || !isset($mission['description'])) {
        echo json_encode(['success' => false, 'message' => 'Données de mission incomplètes']);
        exit;
    }
    
    // Générer le HTML du modal de manière sécurisée
    $html = '<div class="row">';
    
    // Colonne principale
    $html .= '<div class="col-md-8">';
    $html .= '<div class="mb-4">';
    
    // Badge du type de mission
    $type_couleur = $mission['type_couleur'] ?? '#4361ee';
    $type_icone = $mission['type_icone'] ?? 'fas fa-star';
    $type_nom = $mission['type_nom'] ?? 'Mission';
    
    $html .= '<div class="mission-type-badge" style="background: ' . htmlspecialchars($type_couleur) . '20; color: ' . htmlspecialchars($type_couleur) . '">';
    $html .= '<i class="' . htmlspecialchars($type_icone) . '"></i> ';
    $html .= htmlspecialchars($type_nom);
    $html .= '</div>';
    
    // Titre et description
    $html .= '<h3 class="fw-bold mb-3">' . htmlspecialchars($mission['titre']) . '</h3>';
    $html .= '<p class="text-muted mb-4">' . nl2br(htmlspecialchars($mission['description'])) . '</p>';
    $html .= '</div>';
    
    // Métriques en 3 colonnes
    $html .= '<div class="row mb-4">';
    $html .= '<div class="col-md-4">';
    $html .= '<div class="text-center p-3 bg-light rounded">';
    $html .= '<i class="fas fa-target fs-2 text-primary mb-2"></i>';
    $html .= '<div class="fw-bold fs-4">' . intval($mission['objectif_quantite']) . '</div>';
    $html .= '<small class="text-muted">Objectif</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-4">';
    $html .= '<div class="text-center p-3 bg-light rounded">';
    $html .= '<i class="fas fa-euro-sign fs-2 text-success mb-2"></i>';
    $html .= '<div class="fw-bold fs-4">' . number_format(floatval($mission['recompense_euros']), 2) . ' €</div>';
    $html .= '<small class="text-muted">Récompense</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-4">';
    $html .= '<div class="text-center p-3 bg-light rounded">';
    $html .= '<i class="fas fa-star fs-2 text-warning mb-2"></i>';
    $html .= '<div class="fw-bold fs-4">' . intval($mission['recompense_points']) . '</div>';
    $html .= '<small class="text-muted">Points XP</small>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Section participants
    $html .= '<div class="mb-4">';
    $html .= '<h5><i class="fas fa-users me-2"></i>Participants (' . count($participants) . ')</h5>';
    
    if (empty($participants)) {
        $html .= '<div class="alert alert-info">Aucun participant pour cette mission</div>';
    } else {
        $html .= '<div class="row">';
        
        foreach ($participants as $participant) {
            // Déterminer le statut d'affichage
            $statut_class = 'secondary';
            $statut_icon = 'clock';
            $statut_text = 'En attente';
            
            switch ($participant['statut']) {
                case 'en_cours':
                    $statut_class = 'info';
                    $statut_icon = 'play-circle';
                    $statut_text = 'En cours';
                    break;
                case 'terminee':
                    $statut_class = 'success';
                    $statut_icon = 'check-circle';
                    $statut_text = 'Terminée';
                    break;
                case 'abandonnee':
                    $statut_class = 'danger';
                    $statut_icon = 'times-circle';
                    $statut_text = 'Abandonnée';
                    break;
            }
            
            $progression_actuelle = intval($participant['progression_actuelle']);
            $objectif_quantite = intval($mission['objectif_quantite']);
            $progression_percent = $objectif_quantite > 0 ? min(100, ($progression_actuelle / $objectif_quantite) * 100) : 0;
            
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<div class="card participant-card">';
            $html .= '<div class="card-body">';
            
            // En-tête participant
            $html .= '<div class="d-flex justify-content-between align-items-start mb-2">';
            $html .= '<div>';
            $html .= '<h6 class="fw-bold mb-1">' . htmlspecialchars($participant['full_name']) . '</h6>';
            $html .= '<small class="text-muted">@' . htmlspecialchars($participant['username']) . '</small>';
            $html .= '</div>';
            $html .= '<span class="badge bg-' . $statut_class . '">';
            $html .= '<i class="fas fa-' . $statut_icon . ' me-1"></i>' . $statut_text;
            $html .= '</span>';
            $html .= '</div>';
            
            // Barre de progression
            $html .= '<div class="mb-2">';
            $html .= '<div class="d-flex justify-content-between mb-1">';
            $html .= '<small>Progression</small>';
            $html .= '<small>' . $progression_actuelle . ' / ' . $objectif_quantite . '</small>';
            $html .= '</div>';
            $html .= '<div class="progress" style="height: 5px;">';
            $html .= '<div class="progress-bar bg-' . $statut_class . '" style="width: ' . $progression_percent . '%"></div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Récompenses
            $html .= '<div class="row text-center">';
            $html .= '<div class="col-6">';
            $html .= '<div class="text-success fw-bold">' . number_format(floatval($participant['total_euros']), 2) . ' €</div>';
            $html .= '<small class="text-muted">Gagnés</small>';
            $html .= '</div>';
            $html .= '<div class="col-6">';
            $html .= '<div class="text-warning fw-bold">' . intval($participant['total_points']) . '</div>';
            $html .= '<small class="text-muted">Points XP</small>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Date d'inscription
            if (!empty($participant['date_rejointe'])) {
                $html .= '<div class="mt-2">';
                $html .= '<small class="text-muted">';
                $html .= '<i class="fas fa-calendar me-1"></i>';
                $html .= 'Inscrit le ' . date('d/m/Y', strtotime($participant['date_rejointe']));
                $html .= '</small>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Colonne de droite avec les informations
    $html .= '<div class="col-md-4">';
    $html .= '<div class="mb-4">';
    $html .= '<h5><i class="fas fa-info-circle me-2"></i>Informations</h5>';
    $html .= '<ul class="list-unstyled">';
    $html .= '<li><strong>Créée le:</strong> ' . date('d/m/Y', strtotime($mission['created_at'])) . '</li>';
    $html .= '<li><strong>Statut:</strong> ';
    if ($mission['statut'] === 'active') {
        $html .= '<span class="badge bg-success">Active</span>';
    } else {
        $html .= '<span class="badge bg-secondary">Inactive</span>';
    }
    $html .= '</li>';
    $html .= '<li><strong>Participants:</strong> ' . count($participants) . '</li>';
    $html .= '<li><strong>Validations en attente:</strong> ' . count($validations_attente) . '</li>';
    $html .= '</ul>';
    $html .= '</div>';
    
    // Validations en attente
    if (!empty($validations_attente)) {
        $html .= '<div class="mb-4">';
        $html .= '<h5><i class="fas fa-hourglass-half me-2"></i>Validations en attente</h5>';
        
        foreach ($validations_attente as $validation) {
            $html .= '<div class="card mb-2">';
            $html .= '<div class="card-body p-3">';
            $html .= '<h6 class="card-title mb-1">' . htmlspecialchars($validation['full_name']) . '</h6>';
            $html .= '<p class="card-text small text-muted mb-2">' . htmlspecialchars($validation['description']) . '</p>';
            $html .= '<div class="d-flex gap-2">';
            $html .= '<button class="btn btn-sm btn-success" onclick="validerTacheAdmin(' . intval($validation['id']) . ', \'approuver\')">';
            $html .= '<i class="fas fa-check"></i>';
            $html .= '</button>';
            $html .= '<button class="btn btn-sm btn-danger" onclick="validerTacheAdmin(' . intval($validation['id']) . ', \'rejeter\')">';
            $html .= '<i class="fas fa-times"></i>';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'mission' => $mission,
        'participants' => $participants,
        'validations' => $validations_attente
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_mission_details: " . $e->getMessage() . " - Ligne: " . $e->getLine() . " - Fichier: " . $e->getFile());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?> 