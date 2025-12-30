<?php
// Sécurité: accès admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_message("Accès refusé. Vous devez être administrateur pour accéder à cette page.", "error");
    redirect('accueil');
}

$shop_pdo = getShopDBConnection();

// Statistiques et données (reprend la logique existante)
$stats_missions_actives = 0;
$stats_missions_en_cours = 0;
$stats_missions_completees = 0;
$stats_validations_en_attente = 0;
$missions = [];
$validations = [];

try { $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM missions WHERE statut = 'active'"); $stmt->execute(); $stats_missions_actives = (int)$stmt->fetchColumn(); } catch (Exception $e) { }
try { $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'en_cours'"); $stmt->execute(); $stats_missions_en_cours = (int)$stmt->fetchColumn(); } catch (Exception $e) { }
try { $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'terminee' AND MONTH(date_completee) = MONTH(NOW()) AND YEAR(date_completee) = YEAR(NOW())"); $stmt->execute(); $stats_missions_completees = (int)$stmt->fetchColumn(); } catch (Exception $e) { }
try { $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM mission_validations WHERE statut = 'en_attente'"); $stmt->execute(); $stats_validations_en_attente = (int)$stmt->fetchColumn(); } catch (Exception $e) { }

try {
    $stmt = $shop_pdo->prepare(
        "SELECT m.id, m.titre, m.description, m.objectif_quantite, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
                mt.nom as type_nom
         FROM missions m
         LEFT JOIN mission_types mt ON m.type_id = mt.id
         WHERE m.statut = 'active'
         ORDER BY m.created_at DESC"
    );
    $stmt->execute();
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

try {
    $stmt = $shop_pdo->prepare(
        "SELECT mv.id, mv.user_mission_id, mv.tache_numero, mv.statut, mv.date_soumission, mv.description,
                m.titre as mission_titre, u.full_name as user_nom
         FROM mission_validations mv
         LEFT JOIN user_missions um ON mv.user_mission_id = um.id
         LEFT JOIN missions m ON um.mission_id = m.id
         LEFT JOIN users u ON um.user_id = u.id
         WHERE mv.statut = 'en_attente'
         ORDER BY mv.date_soumission DESC"
    );
    $stmt->execute();
    $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<link href="assets/css/dashboard-new.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* Affinage visuel global de la page d'administration des missions */
.statistics-container { margin-top: 0; }
.section-title { display: flex; align-items: center; gap: .5rem; }
.section-title i { color: #2563eb; }

/* Tabs: plus contrastés et collants sur scroll */
.tabs-container { background: transparent; box-shadow: none; }
.tabs-header { 
    background: #fff; 
    border: 1px solid #e5e7eb; 
    border-radius: 12px; 
    padding: .25rem; 
    gap: .25rem;
    position: sticky; top: 84px; z-index: 10; /* sous la navbar */
}
.tab-button { 
    background: transparent; 
    color: #374151; 
    border-radius: 10px; 
    padding: .75rem 1rem; 
    transition: background .2s ease, color .2s ease, transform .15s ease;
}
.tab-button:hover { background: #f3f4f6; transform: translateY(-1px); }
.tab-button.active { background: #2563eb; color: #fff; }
.tab-button .badge { background: rgba(255,255,255,.25); font-weight: 700; }

/* Table moderne: carte blanche avec ombre douce */
.modern-table { 
    background: #fff; 
    border: 1px solid #e5e7eb; 
    border-radius: 14px; 
    box-shadow: 0 6px 16px rgba(0,0,0,.06);
}
.modern-table-columns { 
    padding: 14px 18px; 
    border-bottom: 1px solid #eef2f7; 
    color: #6b7280; 
    font-weight: 600;
}
.modern-table-row { 
    padding: 12px 18px; 
    transition: background .15s ease, box-shadow .15s ease;
}
.modern-table-row:not(:last-child) { border-bottom: 1px dashed #edf2f7; }
.modern-table-row:hover { background: #fafbff; box-shadow: inset 0 0 0 1px #e5edff; }
.modern-table-cell.primary .modern-table-text { font-weight: 600; color: #111827; }
.modern-table-cell .modern-table-subtext { color: #6b7280; }

/* Badges statut */
.badge-status { 
    display: inline-block; padding: 6px 10px; border-radius: 10px; 
    font-weight: 700; font-size: .78rem; letter-spacing: .2px; 
}
.badge-status.info { background: rgba(37,99,235,.10); color: #2563eb; }
.badge-status.gray { background: #f3f4f6; color: #4b5563; }
.modern-date-badge { background: #eef2ff; color: #3730a3; border-radius: 10px; padding: 6px 10px; display: inline-flex; }

/* Actions */
.action-cell { text-align: right; }
.action-cell a { margin-left: 8px; border-radius: 8px; padding: 6px 10px; }
.task-edit-btn { color: #2563eb; text-decoration: none; background: rgba(37,99,235,.08); }
.task-delete-btn { color: #b91c1c; text-decoration: none; background: rgba(239,68,68,.08); }
.task-edit-btn:hover { background: rgba(37,99,235,.14); }
.task-delete-btn:hover { background: rgba(239,68,68,.14); }
.task-edit-btn i, .task-delete-btn i { margin-right: 6px; }

/* Vidage élégant */
.modern-table-empty { text-align: center; padding: 48px 24px; color: #6b7280; }
.modern-table-empty i { font-size: 28px; color: #94a3b8; }
.modern-table-empty .title { font-weight: 700; color: #111827; margin-top: 6px; }
.modern-table-empty .subtitle { color: #6b7280; margin-top: 2px; }

/* Modals - nécessaires pour openModal/closeModal */
.modal {
  display: none;
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,.55);
  z-index: 2000;
}
.modal.active {
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal .modal-content {
  background: #fff;
  border-radius: 12px;
  width: 90%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 50px rgba(0,0,0,.25);
}
.modal .modal-header { background: linear-gradient(135deg,#2563eb,#6c5ce7); color:#fff; padding:1rem 1.25rem; border-radius:12px 12px 0 0; display:flex; align-items:center; justify-content:space-between; }
.modal .modal-title { font-weight:700; display:flex; align-items:center; gap:.5rem; }
.modal .modal-body { padding: 1.25rem; }
.modal .modal-footer { padding: .75rem 1.25rem 1.25rem; display:flex; justify-content:flex-end; gap:.5rem; }

/* Grille cartes missions (style proche réparations) */
.missions-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
  width: 100%;
}
.mission-card-modern {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 6px 16px rgba(0,0,0,.06);
  display: flex;
  flex-direction: column;
  transition: transform .18s ease, box-shadow .18s ease;
  cursor: pointer;
}
.mission-card-modern:hover { transform: translateY(-6px); box-shadow: 0 12px 28px rgba(37,99,235,.12); }
.mission-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .85rem 1rem; border-bottom: 1px solid #eef2f7; background: #f8fafc;
}
.mission-card-header .mission-type {
  background: #eef2ff; color: #3730a3; border-radius: 999px; padding: 4px 10px; font-weight: 700; font-size: .75rem;
}
.mission-card-header .mission-date { color: #6b7280; font-size: .8rem; }
.mission-card-body { padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .5rem; }
.mission-card-body .mission-title { font-size: 1.05rem; font-weight: 700; color: #111827; }
.mission-card-body .mission-description { color: #6b7280; line-height: 1.5; min-height: 2.2em; }
.mission-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin-top: .25rem; }
.mission-stat { background: #f9fafb; border: 1px solid #eef2f7; border-radius: 10px; padding: .6rem .7rem; display: flex; flex-direction: column; gap: 2px; }
.mission-stat i { color: #2563eb; }
.mission-stat span { color: #6b7280; font-size: .75rem; }
.mission-stat strong { color: #111827; font-size: .95rem; }
.mission-card-footer { border-top: 1px solid #eef2f7; background: #f8fafc; padding: .7rem 1rem; display: flex; justify-content: flex-end; gap: .5rem; }

@media (max-width: 520px) {
  .mission-stats-row { grid-template-columns: 1fr; }
}

/* Responsive légers */
@media (max-width: 768px) {
  .tabs-header { top: 70px; border-radius: 10px; }
  .action-cell { text-align: left; }
}
</style>

<div class="statistics-container">
    <h2 class="section-title"><i class="fas fa-trophy"></i> Administration des Missions</h2>
    <div class="statistics-grid">
        <a href="#" class="stat-card js-stat-card" data-tab="missions" style="text-decoration:none;color:inherit;">
            <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo (int)$stats_missions_actives; ?></div>
                <div class="stat-label">Missions actives</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card progress-card js-stat-card" data-tab="missions" style="text-decoration:none;color:inherit;">
            <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo (int)$stats_missions_en_cours; ?></div>
                <div class="stat-label">En cours</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card clients-card js-stat-card" data-tab="missions" style="text-decoration:none;color:inherit;">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo (int)$stats_missions_completees; ?></div>
                <div class="stat-label">Complétées (mois)</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card waiting-card js-stat-card" data-tab="validations" style="text-decoration:none;color:inherit;">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo (int)$stats_validations_en_attente; ?></div>
                <div class="stat-label">Validations en attente</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>
</div>

<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-button active" data-tab="missions"><i class="fas fa-list"></i> Missions <span class="badge bg-primary ms-2"><?php echo count($missions); ?></span></button>
        <button class="tab-button" data-tab="validations"><i class="fas fa-clipboard-check"></i> Validations <span class="badge bg-primary ms-2"><?php echo count($validations); ?></span></button>
        <button class="tab-button" data-tab="rewards"><i class="fas fa-coins"></i> Cagnotte & XP</button>
        <button class="tab-button" id="btnNewMission" type="button" style="justify-content:flex-end"><i class="fas fa-plus"></i> Nouvelle mission</button>
    </div>

    <div class="tab-content active" id="missions">
        <?php if (empty($missions)): ?>
            <div class="modern-table-empty">
                <i class="fas fa-clipboard-list"></i>
                <div class="title">Aucune mission active</div>
                <p class="subtitle">Créez votre première mission</p>
                <a href="javascript:void(0)" class="task-edit-btn" onclick="openNewMissionModal()"><i class="fas fa-plus"></i> Créer une mission</a>
            </div>
        <?php else: ?>
            <div class="missions-cards">
                <?php foreach ($missions as $mission): ?>
                <div class="mission-card-modern" data-mission-id="<?php echo (int)$mission['id']; ?>">
                    <div class="mission-card-header">
                        <span class="mission-type" title="Type de mission"><?php echo htmlspecialchars($mission['type_nom'] ?? 'Mission'); ?></span>
                        <span class="mission-date"><?php echo date('d/m/Y', strtotime($mission['created_at'] ?? 'now')); ?></span>
                    </div>
                    <div class="mission-card-body">
                        <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                        <div class="mission-description"><?php 
                            $desc = trim((string)($mission['description'] ?? ''));
                            echo htmlspecialchars(mb_strimwidth($desc, 0, 120, '…', 'UTF-8'));
                        ?></div>
                        <div class="mission-stats-row">
                            <div class="mission-stat">
                                <i class="fas fa-bullseye"></i>
                                <span>Objectif</span>
                                <strong><?php echo (int)($mission['objectif_quantite'] ?? 0); ?></strong>
                            </div>
                            <div class="mission-stat">
                                <i class="fas fa-euro-sign"></i>
                                <span>Récompense</span>
                                <strong><?php echo number_format((float)($mission['recompense_euros'] ?? 0), 2); ?> €</strong>
                            </div>
                            <div class="mission-stat">
                                <i class="fas fa-star"></i>
                                <span>Points</span>
                                <strong><?php echo (int)($mission['recompense_points'] ?? 0); ?> XP</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mission-card-footer">
                        <a href="javascript:void(0)" class="task-edit-btn" data-id="<?php echo (int)$mission['id']; ?>" onclick="editMission(<?php echo (int)$mission['id']; ?>); event.stopPropagation();"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="javascript:void(0)" class="task-delete-btn" onclick="deactivateMission(<?php echo (int)$mission['id']; ?>); event.stopPropagation();"><i class="fas fa-times"></i> Désactiver</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-content" id="validations">
        <?php if (empty($validations)): ?>
        <div class="modern-table">
            <div class="modern-table-empty">
                <i class="fas fa-clipboard-check"></i>
                <div class="title">Aucune validation en attente</div>
                <p class="subtitle">Tout est à jour</p>
            </div>
        </div>
        <?php else: ?>
        <div class="modern-table">
            <div class="modern-table-columns">
                <span style="flex:1;">Mission</span>
                <span style="width:25%; text-align:center;">Employé</span>
                <span style="width:25%; text-align:center;">Date</span>
                <span style="width:20%; text-align:right;" class="hide-sm">Actions</span>
            </div>
            <?php foreach ($validations as $validation): ?>
            <div class="modern-table-row">
                <div class="modern-table-indicator commandes"></div>
                <div class="modern-table-cell primary">
                    <span class="modern-table-text"><?php echo htmlspecialchars($validation['mission_titre']); ?></span>
                    <span class="modern-table-subtext"><?php echo htmlspecialchars($validation['description']); ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="badge-status gray"><?php echo htmlspecialchars($validation['user_nom']); ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <div class="modern-date-badge"><span><?php echo date('d/m/Y H:i', strtotime($validation['date_soumission'])); ?></span></div>
                </div>
                <div class="modern-table-cell action-cell hide-sm" style="width:20%;">
                    <a href="javascript:void(0)" class="task-edit-btn" onclick="validerTacheAdmin(<?php echo (int)$validation['id']; ?>, 'approuver')"><i class="fas fa-check"></i> Approuver</a>
                    <a href="javascript:void(0)" class="task-delete-btn" onclick="validerTacheAdmin(<?php echo (int)$validation['id']; ?>, 'rejeter')"><i class="fas fa-times"></i> Rejeter</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-content" id="rewards">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
            <h3><i class="fas fa-coins"></i> Cagnotte et Points XP</h3>
            <button class="btn btn-primary" onclick="showUserRewards()"><i class="fas fa-rotate"></i> Actualiser</button>
        </div>
        <div id="userRewardsContainer" class="modern-table-empty">
            <div class="loading-spinner"></div>
            <p class="subtitle" style="margin-top:1rem;">Chargement des données...</p>
        </div>
    </div>
</div>

<!-- Modales reprises de la page originale -->
<div class="modal" id="newMissionModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-plus"></i>Nouvelle Mission</div>
            <button class="modal-close" onclick="closeModal('newMissionModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="newMissionForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Titre de la mission</label>
                        <input type="text" class="form-control" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type de mission</label>
                        <select class="form-control" name="type_id" required>
                            <option value="">Sélectionner un type</option>
                            <option value="1">Trottinettes</option>
                            <option value="2">Smartphones</option>
                            <option value="3">LeBonCoin</option>
                            <option value="4">eBay</option>
                            <option value="5">Réparations Express</option>
                            <option value="6">Service Client</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Objectif (quantité)</label>
                        <input type="number" class="form-control" name="objectif_quantite" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Récompense (€)</label>
                        <input type="number" class="form-control" name="recompense_euros" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Points XP</label>
                        <input type="number" class="form-control" name="recompense_points" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('newMissionModal')">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="createMission()"><i class="fas fa-save"></i>Créer la Mission</button>
        </div>
    </div>
    </div>

<!-- Modal Édition Mission -->
<div class="modal" id="editMissionModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-edit"></i>Modifier la mission</div>
            <button class="modal-close" onclick="closeModal('editMissionModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="editMissionForm">
                <input type="hidden" name="id" id="editMissionId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="titre" id="editTitre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-control" name="type_id" id="editType" required>
                            <option value="">Sélectionner un type</option>
                            <option value="1">Trottinettes</option>
                            <option value="2">Smartphones</option>
                            <option value="3">LeBonCoin</option>
                            <option value="4">eBay</option>
                            <option value="5">Réparations Express</option>
                            <option value="6">Service Client</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="editDescription" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Objectif (quantité)</label>
                        <input type="number" class="form-control" name="objectif_quantite" id="editObjectif" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Récompense (€)</label>
                        <input type="number" class="form-control" name="recompense_euros" id="editEuros" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Points XP</label>
                        <input type="number" class="form-control" name="recompense_points" id="editPoints" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('editMissionModal')">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="saveMissionEdit()"><i class="fas fa-save"></i>Enregistrer</button>
        </div>
    </div>
</div>

<div class="modal" id="missionDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-info-circle"></i>Détails de la Mission</div>
            <button class="modal-close" onclick="closeModal('missionDetailsModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="missionDetailsContent"></div>
    </div>
</div>

<script>
// Onglets + cartes stats
function switchTab(tabName) {
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    const btn = document.querySelector(`.tab-button[data-tab="${tabName}"]`);
    const tab = document.getElementById(tabName);
    if (btn) btn.classList.add('active');
    if (tab) tab.classList.add('active');
    if (tabName === 'rewards') { showUserRewards(); }
}
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function(){ if (this.dataset.tab) switchTab(this.dataset.tab); });
});
document.querySelectorAll('.js-stat-card').forEach(card => {
    card.addEventListener('click', function(e){ e.preventDefault(); if (this.dataset.tab) switchTab(this.dataset.tab); });
});

// Modales
function openModal(id){ document.getElementById(id).classList.add('active'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id).classList.remove('active'); document.body.style.overflow=''; }
function openNewMissionModal(){ openModal('newMissionModal'); }
document.addEventListener('DOMContentLoaded', function(){
  // Fermer modal en cliquant hors contenu
  document.querySelectorAll('.modal').forEach(function(m){
    m.addEventListener('click', function(e){ if (e.target === m) closeModal(m.id); });
  });

  // Bouton nouvelle mission
  var newBtn = document.getElementById('btnNewMission');
  if (newBtn) newBtn.addEventListener('click', function(e){ e.preventDefault(); openNewMissionModal(); });

  // Liens Modifier sur les cartes
  document.querySelectorAll('.mission-card-footer .task-edit-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); var id = parseInt(this.getAttribute('data-id'),10); if (id) editMission(id); });
  });

  // Clic sur la carte -> afficher popup détails mission (participants + progression)
  document.querySelectorAll('.mission-card-modern').forEach(function(card){
    card.addEventListener('click', function(){
      var id = parseInt(this.getAttribute('data-mission-id'), 10);
      if (!id) return;
      fetch('ajax/get_mission_details_fixed.php?id='+id)
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (data && data.success) {
            var container = document.getElementById('missionDetailsContent');
            if (container) container.innerHTML = data.html || '<div style="padding:1rem;">Aucun détail</div>';
            openModal('missionDetailsModal');
          } else {
            alert('Erreur: '+(data && data.message ? data.message : 'chargement impossible'));
          }
        })
        .catch(function(){ alert('Erreur lors du chargement des détails'); });
    });
  });
});

// AJAX
function showMissionDetails(missionId){
    fetch(`ajax/get_mission_details_temp.php?id=${missionId}`).then(r=>r.json()).then(data=>{
        if(data.success){ document.getElementById('missionDetailsContent').innerHTML=data.html; openModal('missionDetailsModal'); }
        else alert('Erreur lors du chargement des détails');
    }).catch(()=>alert('Erreur lors du chargement des détails'));
}
function showUserRewards(){
    fetch('ajax/get_user_rewards_fixed.php').then(r=>r.json()).then(data=>{
        document.getElementById('userRewardsContainer').innerHTML = data.success ? data.html : '<div style="color:#ef4444;text-align:center;padding:2rem;">Erreur lors du chargement des données</div>';
    }).catch(()=>{ document.getElementById('userRewardsContainer').innerHTML='<div style="color:#ef4444;text-align:center;padding:2rem;">Erreur lors du chargement des données</div>'; });
}
function validerTacheAdmin(validationId, action){
    if(!confirm(`Êtes-vous sûr de vouloir ${action} cette validation ?`)) return;
    fetch('ajax/valider_mission_fixed.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({validation_id:validationId,action})})
        .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert('Erreur: '+data.message); })
        .catch(()=>alert('Erreur lors de la validation'));
}
function createMission(){
    const form=new FormData(document.getElementById('newMissionForm'));
    fetch('ajax/create_mission_fixed.php',{method:'POST',body:form}).then(r=>r.json()).then(data=>{
        if(data.success){ closeModal('newMissionModal'); location.reload(); } else alert('Erreur: '+data.message);
    }).catch(()=>alert('Erreur lors de la création de la mission'));
}
function deactivateMission(missionId){
    if(!confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) return;
    fetch('ajax/deactivate_mission_fixed.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mission_id:missionId})})
        .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert('Erreur: '+data.message); })
        .catch(()=>alert('Erreur lors de la désactivation'));
}
    function editMission(missionId){
        fetch(`ajax/get_mission_details_fixed.php?id=${missionId}`)
            .then(async (r)=>{
                const text = await r.text();
                try { return JSON.parse(text); } catch(e){ console.error('Réponse non-JSON:', text); throw new Error('Réponse invalide'); }
            })
            .then(data=>{
                if(!data || !data.success){
                    alert('Erreur: '+(data && data.message ? data.message : 'Chargement impossible'));
                    return;
                }
                const m = data.mission || {};
                document.getElementById('editMissionId').value = m.id || missionId;
                document.getElementById('editTitre').value = m.titre || '';
                document.getElementById('editDescription').value = m.description || '';
                // fallback: pas de type_id dans la réponse -> laisser vide
                if (m.type_id) document.getElementById('editType').value = m.type_id;
                document.getElementById('editObjectif').value = (m.objectif_quantite || m.nombre_taches || 1);
                document.getElementById('editEuros').value = (m.recompense_euros != null ? m.recompense_euros : 0);
                document.getElementById('editPoints').value = (m.recompense_points != null ? m.recompense_points : 0);
                openModal('editMissionModal');
            })
            .catch((err)=>{ console.error(err); alert('Erreur lors du chargement de la mission'); });
    }

    function saveMissionEdit(){
        const form = document.getElementById('editMissionForm');
        const formData = new FormData(form);
        fetch('ajax/update_mission_fixed.php', { method: 'POST', body: formData })
            .then(r=>r.json())
            .then(data=>{
                if(data.success){ closeModal('editMissionModal'); location.reload(); }
                else { alert('Erreur: '+(data.message||'Mise à jour impossible')); }
            })
            .catch(()=>alert('Erreur réseau'));
    }
</script>


