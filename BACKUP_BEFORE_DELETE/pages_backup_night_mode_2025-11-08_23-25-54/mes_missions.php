<?php
// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];
$shop_pdo = getShopDBConnection();

// Traitement de l'inscription à une mission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rejoindre_mission') {
        $mission_id = (int)$_POST['mission_id'];
        
        try {
            // Vérifier si l'utilisateur n'est pas déjà inscrit
            $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
            $stmt->execute([$user_id, $mission_id]);
            
            if (!$stmt->fetch()) {
                // Inscrire l'utilisateur à la mission
                $stmt = $shop_pdo->prepare("INSERT INTO user_missions (user_id, mission_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $mission_id]);
                set_message("Vous avez rejoint la mission avec succès !", "success");
            } else {
                set_message("Vous participez déjà à cette mission.", "warning");
            }
        } catch (PDOException $e) {
            set_message("Erreur lors de l'inscription à la mission: " . $e->getMessage(), "error");
        }
    }
    
    if ($_POST['action'] === 'valider_tache') {
        $user_mission_id = (int)$_POST['user_mission_id'];
        $mission_id = (int)$_POST['mission_id'];
        $description = cleanInput($_POST['description_tache']);
        $preuve_text = cleanInput($_POST['preuve_text']);
        
        if (empty($description)) {
            set_message("La description de la tâche est obligatoire.", "error");
        } else {
            try {
                // Insérer la validation
                $stmt = $shop_pdo->prepare("
                    INSERT INTO mission_validations (user_mission_id, user_id, mission_id, description_tache, preuve_text) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_mission_id, $user_id, $mission_id, $description, $preuve_text]);
                
                // Mettre à jour la progression
                $stmt = $shop_pdo->prepare("
                    UPDATE user_missions 
                    SET progression_actuelle = progression_actuelle + 1 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$user_mission_id, $user_id]);
                
                // Vérifier si la mission est complète
                $stmt = $shop_pdo->prepare("
                    SELECT um.progression_actuelle, m.objectif_nombre 
                    FROM user_missions um 
                    JOIN missions m ON um.mission_id = m.id 
                    WHERE um.id = ?
                ");
                $stmt->execute([$user_mission_id]);
                $progress = $stmt->fetch();
                
                if ($progress && $progress['progression_actuelle'] >= $progress['objectif_nombre']) {
                    // Marquer la mission comme complète
                    $stmt = $shop_pdo->prepare("
                        UPDATE user_missions 
                        SET statut = 'complete', date_completion = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$user_mission_id]);
                    set_message("🎉 Félicitations ! Vous avez complété cette mission !", "success");
                } else {
                    set_message("Tâche validée avec succès !", "success");
                }
                
            } catch (PDOException $e) {
                set_message("Erreur lors de la validation de la tâche: " . $e->getMessage(), "error");
            }
        }
    }
    
    redirect('mes_missions');
}

// Récupération des missions disponibles (non encore rejointes)
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.*, mt.nom as type_nom, mt.icon, mt.couleur
        FROM missions m
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE m.statut = 'active' 
        AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
        AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.date_fin ASC, m.recompense_euros DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_disponibles = [];
    set_message("Erreur lors de la récupération des missions disponibles.", "error");
}

// Récupération des missions en cours
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur,
               COUNT(mv.id) as validations_count
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        GROUP BY um.id
        ORDER BY m.date_fin ASC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_en_cours = [];
    set_message("Erreur lors de la récupération des missions en cours.", "error");
}

// Récupération des missions complétées
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur,
               mr.montant_euros as gain_reel, mr.points_attribues as points_reels
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'complete'
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_completees = [];
}

// Calcul des statistiques personnelles
$stats = [
    'missions_actives' => count($missions_en_cours),
    'missions_completees' => count($missions_completees),
    'total_gains' => array_sum(array_column($missions_completees, 'gain_reel')),
    'total_points' => array_sum(array_column($missions_completees, 'points_reels'))
];
?>

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

<div class="container-fluid" id="mainContent" style="display: none;">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-trophy me-2"></i>
                                Mes Missions & Primes
                            </h1>
                            <p class="mb-0 opacity-75">Complétez vos missions pour gagner des primes et des points !</p>
                        </div>
                        <div class="col-md-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h3 mb-0"><?= $stats['missions_actives'] ?></div>
                                        <small>En cours</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h3 mb-0"><?= $stats['missions_completees'] ?></div>
                                        <small>Complétées</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h3 mb-0"><?= number_format($stats['total_gains'], 2) ?>€</div>
                                        <small>Gains totaux</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h3 mb-0"><?= $stats['total_points'] ?></div>
                                        <small>Points</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills nav-fill mb-4" id="missionsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="en-cours-tab" data-bs-toggle="pill" data-bs-target="#en-cours" type="button" role="tab">
                <i class="fas fa-tasks me-2"></i>En Cours <span class="badge bg-primary ms-1"><?= count($missions_en_cours) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="disponibles-tab" data-bs-toggle="pill" data-bs-target="#disponibles" type="button" role="tab">
                <i class="fas fa-plus-circle me-2"></i>Disponibles <span class="badge bg-success ms-1"><?= count($missions_disponibles) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completees-tab" data-bs-toggle="pill" data-bs-target="#completees" type="button" role="tab">
                <i class="fas fa-check-circle me-2"></i>Complétées <span class="badge bg-secondary ms-1"><?= count($missions_completees) ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="missionsTabContent">
        <!-- Missions en cours -->
        <div class="tab-pane fade show active" id="en-cours" role="tabpanel">
            <?php if (empty($missions_en_cours)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h4>Aucune mission en cours</h4>
                    <p class="text-muted">Consultez l'onglet "Disponibles" pour rejoindre une mission !</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($missions_en_cours as $mission): ?>
                        <div class="col-lg-6 mb-4">
                            <?php include 'components/mission_card_progress.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Missions disponibles -->
        <div class="tab-pane fade" id="disponibles" role="tabpanel">
            <?php if (empty($missions_disponibles)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>Toutes les missions rejointes !</h4>
                    <p class="text-muted">Vous participez déjà à toutes les missions disponibles.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($missions_disponibles as $mission): ?>
                        <div class="col-lg-6 mb-4">
                            <?php include 'components/mission_card_available.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Missions complétées -->
        <div class="tab-pane fade" id="completees" role="tabpanel">
            <?php if (empty($missions_completees)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-medal fa-3x text-warning mb-3"></i>
                    <h4>Aucune mission complétée</h4>
                    <p class="text-muted">Complétez vos premières missions pour voir vos récompenses ici !</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($missions_completees as $mission): ?>
                        <div class="col-lg-6 mb-4">
                            <?php include 'components/mission_card_completed.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de validation de tâche -->
<div class="modal fade" id="validateTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Valider une tâche
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="valider_tache">
                    <input type="hidden" name="user_mission_id" id="modalUserMissionId">
                    <input type="hidden" name="mission_id" id="modalMissionId">
                    
                    <div class="mb-3">
                        <label for="description_tache" class="form-label">Description de la tâche accomplie *</label>
                        <textarea class="form-control" id="description_tache" name="description_tache" rows="3" 
                                  placeholder="Décrivez précisément ce que vous avez fait..."></textarea>
                        <div class="form-text">Soyez précis : modèle de l'appareil, panne réparée, lien de l'annonce, etc.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="preuve_text" class="form-label">Preuves ou détails supplémentaires</label>
                        <textarea class="form-control" id="preuve_text" name="preuve_text" rows="2"
                                  placeholder="Numéro de série, lien vers l'annonce, référence client..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Valider la tâche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateTask(userMissionId, missionId) {
    document.getElementById('modalUserMissionId').value = userMissionId;
    document.getElementById('modalMissionId').value = missionId;
    document.getElementById('description_tache').value = '';
    document.getElementById('preuve_text').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('validateTaskModal'));
    modal.show();
}
</script>

<style>
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.mission-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.mission-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.mission-progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.nav-pills .nav-link {
    border-radius: 8px;
    padding: 12px 20px;
    margin: 0 5px;
    transition: all 0.3s ease;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.badge {
    font-size: 0.7rem;
}
</style>

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

.container-fluid,
.container-fluid * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.content-wrapper {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.card,
.modal-content {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content {
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