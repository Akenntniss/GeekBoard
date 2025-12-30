<?php
// Admin required
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_message("Accès refusé. Vous devez être administrateur.", "error");
    redirect('accueil');
}

// Initialize shop session and connect
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

// Handle POST actions: create_mission, approve_validation, reject_validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'create_mission') {
            $titre = trim($_POST['titre'] ?? '');
            $type_id = (int)($_POST['type_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $objectif = max(1, (int)($_POST['objectif_nombre'] ?? 1));
            $recomp_eur = (float)($_POST['recompense_euros'] ?? 0);
            $recomp_pts = (int)($_POST['recompense_points'] ?? 0);
            $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
            if ($titre && $type_id > 0) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, recompense_euros, recompense_points, date_fin, statut, created_at, updated_at, actif)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW(), 1)
                ");
                $stmt->execute([$titre, $description, $type_id, $objectif, $recomp_eur, $recomp_pts, $date_fin]);
                set_message("Mission créée.", "success");
            } else {
                set_message("Titre et type requis.", "error");
            }
        }
        if ($_POST['action'] === 'approve_validation' || $_POST['action'] === 'reject_validation') {
            $validation_id = (int)($_POST['validation_id'] ?? 0);
            if ($validation_id > 0) {
                $new_status = $_POST['action'] === 'approve_validation' ? 'validee' : 'rejectee';
                // Load validation with mission details
                $stmt = $shop_pdo->prepare("
                    SELECT mv.*, um.user_id, m.id AS mission_id, m.recompense_euros, m.recompense_points, m.objectif_nombre
                    FROM mission_validations mv
                    JOIN user_missions um ON um.id = mv.user_mission_id
                    JOIN missions m ON m.id = um.mission_id
                    WHERE mv.id = ?
                ");
                $stmt->execute([$validation_id]);
                $val = $stmt->fetch();
                if ($val) {
                    $stmt = $shop_pdo->prepare("UPDATE mission_validations SET statut = ?, date_traitement = NOW(), admin_id = ? WHERE id = ?");
                    $stmt->execute([$new_status, $_SESSION['user_id'] ?? null, $validation_id]);
                    if ($new_status === 'validee') {
                        // per-task credit
                        $per_eur = ($val['recompense_euros'] > 0 && $val['objectif_nombre'] > 0) ? ($val['recompense_euros'] / $val['objectif_nombre']) : 0;
                        $per_pts = ($val['recompense_points'] > 0 && $val['objectif_nombre'] > 0) ? (int)round($val['recompense_points'] / $val['objectif_nombre']) : 0;
                        // wallet transaction
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO wallet_transactions (user_id, amount_eur, points, type, status, meta, created_at)
                            VALUES (?, ?, ?, 'mission_gain', 'confirmed', ?, NOW())
                        ");
                        $meta = json_encode(['validation_id'=>$validation_id,'mission_id'=>$val['mission_id']]);
                        $stmt->execute([$val['user_id'], $per_eur, $per_pts, $meta]);
                        // Optional: update users.cagnotte if exists
                        try { $shop_pdo->exec("UPDATE users SET cagnotte = COALESCE(cagnotte,0) + " . (float)$per_eur . " WHERE id = " . (int)$val['user_id']); } catch (Throwable $e) {}
                    }
                    set_message("Validation mise à jour.", "success");
                } else {
                    set_message("Validation introuvable.", "error");
                }
            }
        }
    } catch (Throwable $e) {
        set_message("Erreur: " . $e->getMessage(), "error");
    }
    redirect('admin_mission_moderne');
}

// Stats
$stats = ['missions_actives'=>0,'en_cours'=>0,'validations_en_attente'=>0,'cagnottes'=>0];
try { $stats['missions_actives'] = (int)$shop_pdo->query("SELECT COUNT(*) FROM missions WHERE statut='active'")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['en_cours'] = (int)$shop_pdo->query("SELECT COUNT(*) FROM user_missions WHERE statut='en_cours'")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['validations_en_attente'] = (int)$shop_pdo->query("SELECT COUNT(*) FROM mission_validations WHERE statut='en_attente'")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['cagnottes'] = (float)$shop_pdo->query("SELECT COALESCE(SUM(cagnotte),0) FROM users")->fetchColumn(); } catch (Throwable $e) {}

// Data lists
$missions = [];
$validations = [];
try {
    $stmt = $shop_pdo->query("
        SELECT m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
               mt.nom AS type_nom, mt.icon AS type_icone, mt.couleur AS type_couleur,
               COUNT(DISTINCT um.id) AS nb_participants
        FROM missions m
        LEFT JOIN mission_types mt ON mt.id = m.mission_type_id
        LEFT JOIN user_missions um ON um.mission_id = m.id
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $missions = $stmt->fetchAll();
} catch (Throwable $e) {}
try {
    $stmt = $shop_pdo->query("
        SELECT mv.id, mv.user_mission_id, mv.tache_numero, mv.statut, mv.date_soumission, u.full_name AS user_nom,
               m.titre AS mission_titre, m.recompense_euros, m.recompense_points, m.objectif_nombre
        FROM mission_validations mv
        JOIN user_missions um ON um.id = mv.user_mission_id
        JOIN missions m ON m.id = um.mission_id
        LEFT JOIN users u ON u.id = um.user_id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.date_soumission DESC
    ");
    $validations = $stmt->fetchAll();
} catch (Throwable $e) {}
?>
<style>
.admin-wrap{max-width:1200px;margin:0 auto;padding:20px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:12px;padding:16px}
body.night-mode .card{background:rgba(15,15,25,0.95);border-color:#0ea5e9}
.title{font-weight:800;font-size:22px;margin:0 0 8px;color:#1e293b}
body.night-mode .title{color:#fff}
.label{color:#6b7280;font-weight:600}
.val{font-size:28px;font-weight:800;color:#111827}
body.night-mode .val{color:#fff}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px}
.btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer}
.btn-sec{background:#6b7280}
.input,textarea,select{width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:10px;background:rgba(255,255,255,0.9)}
body.night-mode .input,body.night-mode textarea,body.night-mode select{background:rgba(15,15,25,0.9);border-color:#334155;color:#fff}
.item{border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:rgba(255,255,255,0.9)}
body.night-mode .item{background:rgba(15,15,25,0.95);border-color:#0ea5e9}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;background:#e0e7ff;color:#1e40af}
body.night-mode .badge{background:#0ea5e9;color:#00121a}
</style>
<div class="admin-wrap">
  <div class="cards">
    <div class="card"><div class="label">Missions actives</div><div class="val"><?= (int)$stats['missions_actives'] ?></div></div>
    <div class="card"><div class="label">Missions en cours</div><div class="val"><?= (int)$stats['en_cours'] ?></div></div>
    <div class="card"><div class="label">Validations en attente</div><div class="val"><?= (int)$stats['validations_en_attente'] ?></div></div>
    <div class="card"><div class="label">Cagnottes (total)</div><div class="val"><?= number_format((float)$stats['cagnottes'],2) ?>€</div></div>
  </div>

  <h2 class="title" style="margin-top:20px">Créer une mission</h2>
  <form method="POST" class="item" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
    <input type="hidden" name="action" value="create_mission"/>
    <div><div class="label">Titre</div><input class="input" name="titre" required></div>
    <div><div class="label">Type</div>
      <select class="input" name="type_id" required>
        <?php
          try{
            $types=$shop_pdo->query("SELECT id, nom FROM mission_types WHERE actif=1 ORDER BY nom")->fetchAll();
            foreach($types as $t){ echo '<option value="'.$t['id'].'">'.htmlspecialchars($t['nom']).'</option>'; }
          }catch(Throwable $e){}
        ?>
      </select>
    </div>
    <div><div class="label">Objectif (nb tâches)</div><input class="input" type="number" name="objectif_nombre" min="1" value="1" required></div>
    <div><div class="label">Récompense (€)</div><input class="input" type="number" step="0.01" name="recompense_euros" value="0"></div>
    <div><div class="label">Points</div><input class="input" type="number" name="recompense_points" value="0"></div>
    <div><div class="label">Date fin</div><input class="input" type="date" name="date_fin"></div>
    <div style="grid-column:1/-1"><div class="label">Description</div><textarea class="input" name="description" rows="3"></textarea></div>
    <div style="grid-column:1/-1"><button class="btn" type="submit"><i class="fas fa-plus"></i>Créer</button></div>
  </form>

  <h2 class="title" style="margin-top:20px">Missions</h2>
  <div class="grid">
    <?php foreach($missions as $m): ?>
      <div class="item">
        <div class="badge"><?= htmlspecialchars($m['type_nom'] ?? 'Mission') ?></div>
        <h3 style="margin:8px 0"><?= htmlspecialchars($m['titre']) ?></h3>
        <div style="color:#6b7280"><?= htmlspecialchars(substr($m['description'] ?? '',0,120)) ?></div>
        <div style="margin-top:8px">Objectif: <b><?= (int)$m['objectif_nombre'] ?></b> • €<?= number_format((float)$m['recompense_euros'],2) ?> • <?= (int)$m['recompense_points'] ?> pts</div>
        <div style="margin-top:8px;color:#6b7280">Participants: <?= (int)$m['nb_participants'] ?> • Statut: <?= htmlspecialchars($m['statut']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="title" style="margin-top:20px">Validations en attente</h2>
  <div class="grid">
    <?php foreach($validations as $v): 
      $per_eur = ($v['recompense_euros']>0 && $v['objectif_nombre']>0)?($v['recompense_euros']/$v['objectif_nombre']):0;
      $per_pts = ($v['recompense_points']>0 && $v['objectif_nombre']>0)?(int)round($v['recompense_points']/$v['objectif_nombre']):0;
    ?>
      <div class="item">
        <div class="badge">#<?= (int)$v['id'] ?></div>
        <div style="margin-top:6px"><b><?= htmlspecialchars($v['mission_titre']) ?></b></div>
        <div style="color:#6b7280;margin-top:4px">Employé: <?= htmlspecialchars($v['user_nom'] ?? 'N/A') ?></div>
        <div style="color:#6b7280;margin-top:4px">Tâche #<?= (int)$v['tache_numero'] ?> • <?= date('d/m/Y H:i', strtotime($v['date_soumission'])) ?></div>
        <div style="margin-top:6px">Crédit si approuvé: €<?= number_format($per_eur,2) ?> + <?= (int)$per_pts ?> pts</div>
        <div style="display:flex;gap:8px;margin-top:10px">
          <form method="POST">
            <input type="hidden" name="action" value="approve_validation"/>
            <input type="hidden" name="validation_id" value="<?= (int)$v['id'] ?>"/>
            <button class="btn" type="submit"><i class="fas fa-check"></i>Approuver</button>
          </form>
          <form method="POST">
            <input type="hidden" name="action" value="reject_validation"/>
            <input type="hidden" name="validation_id" value="<?= (int)$v['id'] ?>"/>
            <button class="btn btn-sec" type="submit"><i class="fas fa-times"></i>Rejeter</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php

