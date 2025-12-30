<?php
// Auth required
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}
$user_id = $_SESSION['user_id'];

// Initialize shop and connect
if (function_exists('initializeShopSession')) {
    try { initializeShopSession(); } catch (Throwable $e) { error_log($e->getMessage()); }
}
$shop_pdo = null;
try { if (function_exists('getShopDBConnection')) { $shop_pdo = getShopDBConnection(); } } catch (Throwable $e) { error_log($e->getMessage()); }
if (!$shop_pdo && isset($_SESSION['shop_id']) && function_exists('getShopDBConnectionById')) {
    try { $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']); } catch (Throwable $e) { error_log($e->getMessage()); }
}
if ($shop_pdo) {
    try {
        $db = $shop_pdo->query("SELECT DATABASE() AS db")->fetch();
        if (($db['db'] ?? null) === 'geekboard_general' && isset($_SESSION['shop_id']) && function_exists('getShopDBConnectionById')) {
            $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
        }
    } catch (Throwable $e) { error_log($e->getMessage()); }
}
if (!$shop_pdo) { echo "<div class='alert alert-danger'>Connexion magasin indisponible.</div>"; return; }

// Handle POST actions: join_mission, validate_task, request_withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'join_mission') {
            $mission_id = (int)($_POST['mission_id'] ?? 0);
            if ($mission_id > 0) {
                $st = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
                $st->execute([$user_id, $mission_id]);
                if (!$st->fetch()) {
                    $st = $shop_pdo->prepare("INSERT INTO user_missions (user_id, mission_id, progression_actuelle, statut, date_rejointe) VALUES (?, ?, 0, 'en_cours', NOW())");
                    $st->execute([$user_id, $mission_id]);
                    set_message("Mission rejointe.", "success");
                } else {
                    set_message("Déjà inscrit.", "warning");
                }
            }
        }
        if ($_POST['action'] === 'validate_task') {
            $user_mission_id = (int)($_POST['user_mission_id'] ?? 0);
            $mission_id = (int)($_POST['mission_id'] ?? 0);
            $description = trim($_POST['description_tache'] ?? '');
            $preuve_text = trim($_POST['preuve_text'] ?? '');
            if ($user_mission_id > 0 && $mission_id > 0 && $description !== '') {
                // determine next task number
                $st = $shop_pdo->prepare("SELECT progression_actuelle FROM user_missions WHERE id = ? AND user_id = ?");
                $st->execute([$user_mission_id, $user_id]);
                $um = $st->fetch();
                $tache_numero = (int)($um['progression_actuelle'] ?? 0) + 1;
                // insert validation pending
                $st = $shop_pdo->prepare("INSERT INTO mission_validations (user_mission_id, tache_numero, description, preuve_fichier, statut, date_soumission) VALUES (?, ?, ?, NULL, 'en_attente', NOW())");
                $st->execute([$user_mission_id, $tache_numero, $description]);
                // increment progression
                $st = $shop_pdo->prepare("UPDATE user_missions SET progression_actuelle = progression_actuelle + 1 WHERE id = ? AND user_id = ?");
                $st->execute([$user_mission_id, $user_id]);
                // complete mission if reached objective
                $st = $shop_pdo->prepare("SELECT um.progression_actuelle, m.objectif_nombre FROM user_missions um JOIN missions m ON m.id = um.mission_id WHERE um.id = ?");
                $st->execute([$user_mission_id]);
                $row = $st->fetch();
                if ($row && (int)$row['progression_actuelle'] >= (int)$row['objectif_nombre']) {
                    $shop_pdo->prepare("UPDATE user_missions SET statut='complete', date_completion = NOW() WHERE id = ?")->execute([$user_mission_id]);
                }
                set_message("Tâche envoyée pour validation.", "success");
            } else {
                set_message("Description requise.", "error");
            }
        }
        if ($_POST['action'] === 'request_withdrawal') {
            $amount = (float)($_POST['amount'] ?? 0);
            if ($amount > 0) {
                $st = $shop_pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount_eur, status, created_at) VALUES (?, ?, 'pending', NOW())");
                $st->execute([$user_id, $amount]);
                set_message("Demande de retrait enregistrée.", "success");
            } else {
                set_message("Montant invalide.", "error");
            }
        }
    } catch (Throwable $e) {
        set_message("Erreur: " . $e->getMessage(), "error");
    }
    redirect('mes_mission_moderne');
}

// Stats & data
$missions_disponibles = $missions_en_cours = $missions_completees = [];
$stats = ['actives'=>0,'disponibles'=>0,'completees'=>0,'cagnotte'=>0,'xp'=>0];
try {
    $st = $shop_pdo->prepare("
        SELECT m.*, mt.nom AS type_nom, mt.icon, mt.couleur
        FROM missions m
        JOIN mission_types mt ON mt.id = m.mission_type_id
        WHERE m.statut='active' AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
          AND m.id NOT IN (SELECT mission_id FROM user_missions WHERE user_id = ?)
        ORDER BY m.priorite DESC, m.created_at DESC
    ");
    $st->execute([$user_id]);
    $missions_disponibles = $st->fetchAll();
} catch (Throwable $e) {}
try {
    $st = $shop_pdo->prepare("
        SELECT um.id, um.mission_id, um.progression_actuelle, um.statut,
               m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.date_fin,
               mt.nom AS type_nom, mt.icon, mt.couleur
        FROM user_missions um
        JOIN missions m ON m.id = um.mission_id
        LEFT JOIN mission_types mt ON mt.id = m.mission_type_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY m.date_fin IS NULL DESC, m.date_fin ASC
    ");
    $st->execute([$user_id]);
    $missions_en_cours = $st->fetchAll();
    $stats['actives'] = count($missions_en_cours);
} catch (Throwable $e) {}
try {
    $st = $shop_pdo->prepare("
        SELECT um.id, um.mission_id, um.progression_actuelle, um.statut, um.date_completion,
               m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               mt.nom AS type_nom, mt.icon, mt.couleur
        FROM user_missions um
        JOIN missions m ON m.id = um.mission_id
        LEFT JOIN mission_types mt ON mt.id = m.mission_type_id
        WHERE um.user_id = ? AND um.statut = 'complete'
        ORDER BY um.date_completion DESC
    ");
    $st->execute([$user_id]);
    $missions_completees = $st->fetchAll();
    $stats['completees'] = count($missions_completees);
} catch (Throwable $e) {}
try {
    $st = $shop_pdo->prepare("SELECT COALESCE(cagnotte,0) AS cagnotte, COALESCE(xp_total,0) AS xp FROM users WHERE id = ?");
    $st->execute([$user_id]);
    $u = $st->fetch();
    if ($u) { $stats['cagnotte'] = (float)$u['cagnotte']; $stats['xp'] = (int)$u['xp']; }
} catch (Throwable $e) {}
$stats['disponibles'] = count($missions_disponibles);
?>
<style>
.wrap{max-width:1200px;margin:0 auto;padding:20px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:12px;padding:16px}
body.night-mode .card{background:rgba(15,15,25,0.95);border-color:#0ea5e9}
.label{color:#6b7280;font-weight:600}
.val{font-size:28px;font-weight:800;color:#111827}
body.night-mode .val{color:#fff}
.tabs{display:flex;gap:8px;margin:16px 0;padding:8px;background:rgba(255,255,255,0.8);border:1px solid #e5e7eb;border-radius:12px}
body.night-mode .tabs{background:rgba(15,15,25,0.95);border-color:#0ea5e9}
.tab{flex:1;padding:10px;border:none;border-radius:10px;background:transparent;color:#6b7280;font-weight:700;cursor:pointer}
.tab.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}
.item{border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:rgba(255,255,255,0.9)}
body.night-mode .item{background:rgba(15,15,25,0.95);border-color:#0ea5e9}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;background:#e0e7ff;color:#1e40af}
body.night-mode .badge{background:#0ea5e9;color:#00121a}
.btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer}
.input,textarea{width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:10px;background:rgba(255,255,255,0.9)}
body.night-mode .input,body.night-mode textarea{background:rgba(15,15,25,0.9);border-color:#334155;color:#fff}
</style>
<div class="wrap">
  <div class="cards">
    <div class="card"><div class="label">En cours</div><div class="val"><?= (int)$stats['actives'] ?></div></div>
    <div class="card"><div class="label">Disponibles</div><div class="val"><?= (int)$stats['disponibles'] ?></div></div>
    <div class="card"><div class="label">Complétées</div><div class="val"><?= (int)$stats['completees'] ?></div></div>
    <div class="card"><div class="label">Cagnotte</div><div class="val"><?= number_format((float)$stats['cagnotte'],2) ?>€</div></div>
    <div class="card"><div class="label">XP</div><div class="val"><?= (int)$stats['xp'] ?></div></div>
  </div>

  <div class="tabs">
    <button class="tab active" onclick="switchTab('encours', this)">En cours (<?= count($missions_en_cours) ?>)</button>
    <button class="tab" onclick="switchTab('dispo', this)">Disponibles (<?= count($missions_disponibles) ?>)</button>
    <button class="tab" onclick="switchTab('done', this)">Complétées (<?= count($missions_completees) ?>)</button>
  </div>

  <div id="tab-encours">
    <?php if (empty($missions_en_cours)): ?>
      <div class="item">Aucune mission en cours.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach($missions_en_cours as $m): 
            $pc = ($m['objectif_nombre']>0)?min(100, ($m['progression_actuelle']/$m['objectif_nombre']*100)):0;
        ?>
          <div class="item">
            <div class="badge"><?= htmlspecialchars($m['type_nom'] ?? 'Mission') ?></div>
            <h3 style="margin:8px 0"><?= htmlspecialchars($m['titre']) ?></h3>
            <div style="color:#6b7280"><?= htmlspecialchars(substr($m['description'] ?? '',0,120)) ?></div>
            <div style="margin-top:8px">Progression: <?= (int)$m['progression_actuelle'] ?>/<?= (int)$m['objectif_nombre'] ?> (<?= number_format($pc,1) ?>%)</div>
            <div style="margin-top:10px">
              <form method="POST" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="action" value="validate_task"/>
                <input type="hidden" name="user_mission_id" value="<?= (int)$m['id'] ?>"/>
                <input type="hidden" name="mission_id" value="<?= (int)$m['mission_id'] ?>"/>
                <input class="input" name="description_tache" placeholder="Décrivez la tâche..." required>
                <button class="btn" type="submit"><i class="fas fa-check"></i>Valider</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="tab-dispo" style="display:none">
    <?php if (empty($missions_disponibles)): ?>
      <div class="item">Aucune mission disponible.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach($missions_disponibles as $m): ?>
          <div class="item">
            <div class="badge"><?= htmlspecialchars($m['type_nom'] ?? 'Mission') ?></div>
            <h3 style="margin:8px 0"><?= htmlspecialchars($m['titre']) ?></h3>
            <div style="color:#6b7280"><?= htmlspecialchars(substr($m['description'] ?? '',0,120)) ?></div>
            <div style="margin-top:8px">Objectif: <b><?= (int)$m['objectif_nombre'] ?></b> • €<?= number_format((float)$m['recompense_euros'],2) ?> • <?= (int)$m['recompense_points'] ?> pts</div>
            <div style="margin-top:8px">
              <form method="POST">
                <input type="hidden" name="action" value="join_mission"/>
                <input type="hidden" name="mission_id" value="<?= (int)$m['id'] ?>"/>
                <button class="btn" type="submit"><i class="fas fa-plus"></i>Rejoindre</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="tab-done" style="display:none">
    <?php if (empty($missions_completees)): ?>
      <div class="item">Aucune mission complétée.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach($missions_completees as $m): ?>
          <div class="item">
            <div class="badge"><?= htmlspecialchars($m['type_nom'] ?? 'Mission') ?></div>
            <h3 style="margin:8px 0"><?= htmlspecialchars($m['titre']) ?></h3>
            <div style="color:#6b7280">Terminée le <?= htmlspecialchars(date('d/m/Y', strtotime($m['date_completion']))) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <h2 style="margin-top:20px">Demander un retrait</h2>
  <form method="POST" class="item" style="display:flex;gap:10px;align-items:center;max-width:420px">
    <input type="hidden" name="action" value="request_withdrawal"/>
    <input class="input" type="number" step="0.01" name="amount" placeholder="Montant (€)" required>
    <button class="btn" type="submit"><i class="fas fa-paper-plane"></i>Envoyer</button>
  </form>
</div>
<script>
function switchTab(name, btn){
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.getElementById('tab-encours').style.display = (name==='encours')?'block':'none';
  document.getElementById('tab-dispo').style.display = (name==='dispo')?'block':'none';
  document.getElementById('tab-done').style.display = (name==='done')?'block':'none';
}
</script>
<?php

