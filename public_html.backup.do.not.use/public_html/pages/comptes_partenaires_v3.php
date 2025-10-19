<?php
// Session and app context
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// DB connection for current shop (multi-shop safe)
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    die('Connexion base de données magasin indisponible');
}

// Filters
$search = trim($_GET['search'] ?? '');

// Stats
$stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM partenaires WHERE actif = 1");
$stmt->execute();
$nb_actifs = (int)$stmt->fetchColumn();

$stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM partenaires");
$stmt->execute();
$nb_total = (int)$stmt->fetchColumn();

$stmt = $shop_pdo->prepare("SELECT COALESCE(SUM(solde_actuel),0) FROM soldes_partenaires");
$stmt->execute();
$solde_global = (float)$stmt->fetchColumn();

// List data
$query = "
    SELECT p.id, p.nom, p.email, p.telephone, p.actif,
           COALESCE(s.solde_actuel,0) AS solde_actuel
    FROM partenaires p
    LEFT JOIN soldes_partenaires s ON s.partenaire_id = p.id
";
$params = [];
if ($search !== '') {
    $query .= " WHERE p.nom LIKE ? OR p.email LIKE ? OR p.telephone LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$query .= " ORDER BY p.nom ASC";

$stmt = $shop_pdo->prepare($query);
$stmt->execute($params);
$partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Page header & stats (reuse look of clients/commandes pages) */
.page-header { display:flex; align-items:center; justify-content:space-between; margin:14px 0 18px; }
.page-title { font-size:28px; font-weight:700; margin:0; }
.page-subtitle { color:#6c757d; margin:6px 0 0; }
.stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:12px 0 20px; }
.stat-card { background:#fff; border:1px solid #eef0f2; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
.stat-number { font-size:22px; font-weight:700; }
.stat-label { color:#6b7280; font-size:13px; margin-top:6px; }

/* Controls */
.controls-section { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:8px 0 16px; flex-wrap:wrap; }
.controls-grid { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.search-container { position:relative; }
.search-input { width:280px; max-width:60vw; border:1px solid #e5e7eb; border-radius:10px; padding:10px 34px 10px 36px; outline:none; }
.search-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.search-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:14px; }

/* Table */
.table-card { background:#fff; border:1px solid #eef0f2; border-radius:14px; overflow:hidden; }
table.modern-table { width:100%; border-collapse:separate; border-spacing:0; }
table.modern-table thead th { background:#0f172a; color:#fff; text-transform:uppercase; font-size:12px; letter-spacing:.04em; padding:12px 14px; }
table.modern-table tbody td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.modern-table tbody tr:hover { background:#f8fafc; }
.cards-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; }
@media (min-width: 768px){ .cards-grid{ grid-template-columns:repeat(3, 1fr);} }
@media (min-width: 1200px){ .cards-grid{ grid-template-columns:repeat(4, 1fr);} }
.partner-card { background:#fff; border:1px solid #eef0f2; border-radius:14px; padding:14px; box-shadow:0 1px 2px rgba(16,24,40,.04); transition:transform .15s ease, box-shadow .15s ease; }
.partner-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(16,24,40,.08); }
.partner-name { font-weight:700; font-size:16px; }
.partner-id { color:#6b7280; font-size:12px; }
.partner-contact { display:flex; flex-direction:column; gap:4px; margin-top:8px; color:#334155; font-size:13px; }
.card-row { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600; }
.badge-success { background:linear-gradient(135deg,#68d391,#38a169); color:#fff; }
.badge-danger { background:linear-gradient(135deg,#fc8181,#e53e3e); color:#fff; }
.balance-pos { color:#16a34a; font-weight:700; }
.balance-neg { color:#dc2626; font-weight:700; }
.balance-zero { color:#6b7280; font-weight:700; }

/* Buttons */
.btn { border:none; border-radius:10px; padding:9px 12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
.btn-primary { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
.btn-secondary { background:linear-gradient(135deg,#60a5fa,#3b82f6); color:#fff; }
.btn-success { background:linear-gradient(135deg,#22c55e,#86efac); color:#fff; }
.btn-ghost { background:#f1f5f9; color:#0f172a; }
.btn-sm { padding:7px 10px; border-radius:8px; font-size:12px; }
.btn-danger { background:linear-gradient(135deg,#ff6b6b,#ee5a52); color:#fff; }
.btn-icon { width:30px; height:30px; padding:0; border-radius:50%; justify-content:center; }

/* Modals (simple) */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; align-items:center; justify-content:center; z-index:1050; }
.partner-modal { background:#fff; width:min(1000px,92vw); max-height:86vh; overflow:auto; border-radius:14px; border:1px solid #eef0f2; box-shadow:0 10px 30px rgba(0,0,0,.15); }
.partner-modal-header { padding:14px 16px; border-bottom:1px solid #eef0f2; display:flex; align-items:center; justify-content:space-between; }
.partner-modal-body { padding:16px; }
.partner-modal-footer { padding:14px 16px; border-top:1px solid #eef0f2; display:flex; justify-content:flex-end; gap:8px; }
.form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-control, .form-textarea, .form-select { border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; outline:none; }
.form-control:focus, .form-textarea:focus, .form-select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.form-textarea { min-height:84px; resize:vertical; }

@media (max-width: 768px) {
  .search-input { width:200px; }
}
</style>

<div class="page-header">
  <div>
    <h1 class="page-title">🤝 Comptes partenaires</h1>
    <p class="page-subtitle">Gérer les partenaires, consulter leurs soldes et l'historique</p>
  </div>
  <div class="controls-grid">
    <button class="btn btn-primary" onclick="openModal('modal-ajout-partenaire')"><i class="fas fa-user-plus"></i>Nouveau partenaire</button>
    <button class="btn btn-success" onclick="openModal('modal-ajout-service')"><i class="fas fa-plus"></i>Ajouter service</button>
  </div>
  </div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-number"><?php echo number_format($nb_total); ?></div><div class="stat-label">Total partenaires</div></div>
  <div class="stat-card"><div class="stat-number"><?php echo number_format($nb_actifs); ?></div><div class="stat-label">Actifs</div></div>
  <div class="stat-card"><div class="stat-number"><?php echo number_format($solde_global, 2, ',', ' '); ?> €</div><div class="stat-label">Solde global</div></div>
</div>

<div class="controls-section">
  <form method="get" class="controls-grid" action="index.php">
    <input type="hidden" name="page" value="comptes_partenaires" />
    <div class="search-container">
      <input class="search-input" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher un partenaire..." />
      <span class="search-icon">🔍</span>
    </div>
    <button class="btn btn-ghost" type="submit">Rechercher</button>
  </form>
</div>

<?php if (empty($partenaires)): ?>
  <div class="table-card" style="text-align:center; padding:24px; color:#6b7280;">Aucun partenaire</div>
<?php else: ?>
  <div class="cards-grid">
    <?php foreach ($partenaires as $p): ?>
      <div class="partner-card">
        <div class="card-row">
          <div>
            <div class="partner-name"><?php echo htmlspecialchars($p['nom']); ?></div>
            <div class="partner-id">ID: <?php echo (int)$p['id']; ?></div>
          </div>
          <div>
            <?php if ((int)$p['actif'] === 1): ?>
              <span class="badge badge-success"><i class="fas fa-check-circle"></i> Actif</span>
            <?php else: ?>
              <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inactif</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="partner-contact">
          <?php if (!empty($p['email'])): ?><span><i class="far fa-envelope"></i> <?php echo htmlspecialchars($p['email']); ?></span><?php endif; ?>
          <?php if (!empty($p['telephone'])): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($p['telephone']); ?></span><?php endif; ?>
        </div>
        <div class="card-row" style="margin-top:10px;">
          <?php 
            $solde = (float)$p['solde_actuel'];
            $cls = $solde > 0 ? 'balance-pos' : ($solde < 0 ? 'balance-neg' : 'balance-zero');
            $prefix = $solde > 0 ? '+' : '';
          ?>
          <div class="<?php echo $cls; ?>"><?php echo $prefix . number_format($solde, 2, ',', ' '); ?> €</div>
        </div>
        <div class="card-actions">
          <button class="btn btn-secondary btn-sm" onclick="openHistorique(<?php echo (int)$p['id']; ?>, '<?php echo htmlspecialchars($p['nom'], ENT_QUOTES); ?>')"><i class="fas fa-history"></i> Historique</button>
          <button class="btn btn-success btn-sm" onclick="openLien(<?php echo (int)$p['id']; ?>, '<?php echo htmlspecialchars($p['nom'], ENT_QUOTES); ?>')"><i class="fas fa-paper-plane"></i> Lien</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Modal: Ajouter partenaire -->
<div id="modal-ajout-partenaire" class="modal-overlay">
  <div class="partner-modal" style="max-width:640px;">
    <div class="partner-modal-header">
      <strong><i class="fas fa-user-plus"></i> Nouveau partenaire</strong>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('modal-ajout-partenaire')">Fermer</button>
    </div>
    <div class="partner-modal-body">
      <form id="form-ajout-partenaire" onsubmit="return false;">
        <div class="form-row">
          <div class="form-group"><label>Nom *</label><input class="form-control" name="nom" required></div>
          <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email"></div>
          <div class="form-group"><label>Téléphone</label><input class="form-control" name="telephone"></div>
        </div>
        <div class="form-group"><label>Adresse</label><textarea class="form-textarea" name="adresse"></textarea></div>
      </form>
    </div>
    <div class="partner-modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-ajout-partenaire')">Annuler</button>
      <button class="btn btn-primary" onclick="ajouterPartenaire()">Enregistrer</button>
    </div>
  </div>
  </div>

<!-- Modal: Ajouter service -->
<div id="modal-ajout-service" class="modal-overlay">
  <div class="partner-modal" style="max-width:640px;">
    <div class="partner-modal-header">
      <strong><i class="fas fa-plus"></i> Ajouter un service partenaire</strong>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('modal-ajout-service')">Fermer</button>
    </div>
    <div class="partner-modal-body">
      <form id="form-ajout-service" onsubmit="return false;">
        <div class="form-row">
          <div class="form-group">
            <label>Partenaire *</label>
            <select class="form-select" name="partenaire_id" required>
              <option value="">Sélectionner...</option>
              <?php foreach ($partenaires as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Montant (€) *</label><input class="form-control" type="number" step="0.01" min="0" name="montant" required></div>
        </div>
        <div class="form-group"><label>Description *</label><textarea class="form-textarea" name="description" required></textarea></div>
      </form>
    </div>
    <div class="partner-modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-ajout-service')">Annuler</button>
      <button class="btn btn-success" onclick="ajouterService()">Enregistrer</button>
    </div>
  </div>
</div>

<!-- Modal: Historique -->
<div id="modal-historique" class="modal-overlay">
  <div class="partner-modal" style="max-width:1100px;">
    <div class="partner-modal-header">
      <strong><i class="fas fa-history"></i> Historique des transactions - <span id="hist-nom"></span></strong>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('modal-historique')">Fermer</button>
    </div>
    <div class="partner-modal-body" id="hist-content" style="min-height:160px;">
      <!-- dynamic -->
    </div>
  </div>
</div>

<!-- Modal: Envoyer lien -->
<div id="modal-lien" class="modal-overlay">
  <div class="partner-modal" style="max-width:640px;">
    <div class="partner-modal-header">
      <strong><i class="fas fa-paper-plane"></i> Envoyer un lien - <span id="lien-nom"></span></strong>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('modal-lien')">Fermer</button>
    </div>
    <div class="partner-modal-body">
      <div class="form-group"><label>Lien d'accès</label><input id="lien-input" class="form-control" readonly></div>
      <div class="form-group"><label>Numéro de téléphone</label><input id="lien-tel" class="form-control" placeholder="Ex: 0612345678"></div>
    </div>
    <div class="partner-modal-footer">
      <button class="btn btn-ghost" onclick="copyLien()"><i class="fas fa-copy"></i> Copier</button>
      <button class="btn btn-success" onclick="envoyerLienSMS()"><i class="fas fa-sms"></i> Envoyer SMS</button>
    </div>
  </div>
</div>

<script>
function openModal(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.style.display = 'flex';
  // prevent background scroll
  document.body.style.overflow = 'hidden';
}
function closeModal(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.style.display = 'none';
  document.body.style.overflow = '';
}

// Add partner
function ajouterPartenaire(){
  const form = document.getElementById('form-ajout-partenaire');
  const data = new FormData(form);
  fetch('ajax/add_partenaire.php', { method: 'POST', body: data })
    .then(r=>r.json()).then(j=>{ if(j.success){ location.reload(); } else { alert(j.message||'Erreur'); } })
    .catch(()=>alert('Erreur réseau'));
}

// Add service
function ajouterService(){
  const form = document.getElementById('form-ajout-service');
  const data = new URLSearchParams(new FormData(form));
  fetch('ajax/add_service_partenaire.php', { method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: data })
    .then(r=>r.json()).then(j=>{ if(j.success){ location.reload(); } else { alert(j.message||'Erreur'); } })
    .catch(()=>alert('Erreur réseau'));
}

// Historique
let currentPid = null, currentPname = '';
function openHistorique(id, nom){
  currentPid = id; currentPname = nom;
  document.getElementById('hist-nom').textContent = nom;
  document.getElementById('hist-content').innerHTML = '<div style="text-align:center;color:#6b7280;">Chargement...</div>';
  openModal('modal-historique');
  fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${id}`)
    .then(r=>r.json()).then(j=>{
      if(!j.success){ document.getElementById('hist-content').innerHTML = '<div style="color:#dc2626;">'+(j.message||'Erreur')+'</div>'; return; }
      renderHistorique(j);
    }).catch(()=>{ document.getElementById('hist-content').innerHTML = '<div style="color:#dc2626;">Erreur réseau</div>'; });
}

function renderHistorique(data){
  const solde = parseFloat(data.solde||0);
  const soldeCls = solde>0?'balance-pos':(solde<0?'balance-neg':'balance-zero');
  let html = `<div class="stats-row">
      <div class="stat-card"><div class="stat-number ${soldeCls}">${(solde>0?'+':'')+solde.toFixed(2)} €</div><div class="stat-label">Solde actuel</div></div>
      <div class="stat-card"><div class="stat-number">${(data.transactions||[]).length}</div><div class="stat-label">Transactions</div></div>
    </div>`;
  if(!data.transactions || !data.transactions.length){
    html += '<div style="text-align:center;color:#6b7280;padding:18px;">Aucune transaction</div>';
  } else {
    html += '<div class="table-card"><table class="modern-table"><thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Description</th><th>Statut</th></tr></thead><tbody>';
    for(const t of data.transactions){
      const typeCls = t.type==='credit' ? 'balance-pos' : 'balance-neg';
      const d = new Date(t.date_transaction);
      const isPending = t.transaction_status==='pending';
      let statusCell;
      if (isPending) {
        statusCell = `<div class="actions-group">
            <button class="btn btn-success btn-sm btn-icon" title="Valider" onclick="validerTransaction(${t.pending_id}, 'approve')"><i class=\"fas fa-check\"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" title="Rejeter" onclick="validerTransaction(${t.pending_id}, 'reject')"><i class=\"fas fa-times\"></i></button>
         </div>`;
      } else if (t.transaction_status === 'rejected') {
        const hasReason = (t.reject_reason && String(t.reject_reason).trim().length > 0);
        const reasonLink = hasReason ? `<a href="#" onclick=\"showRejectReason('${encodeURIComponent(t.reject_reason || '')}')\" style=\"color:#fff;text-decoration:underline;\">Voir</a>` : '';
        statusCell = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rejetée ' + reasonLink + '</span>';
      } else {
        statusCell = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Validée</span>';
      }
      html += `<tr>
        <td>${d.toLocaleDateString('fr-FR')}</td>
        <td><span class="${typeCls}">${t.type==='credit'?'Crédit':'Débit'}</span></td>
        <td><span class="${typeCls}">${parseFloat(t.montant).toFixed(2)} €</span></td>
        <td>${t.description||''}</td>
        <td>${statusCell}</td>
      </tr>`;
    }
    html += '</tbody></table></div>';
  }
  document.getElementById('hist-content').innerHTML = html;
}

// Lien
function openLien(id, nom){
  currentPid = id; currentPname = nom;
  document.getElementById('lien-nom').textContent = nom;
  document.getElementById('lien-input').value = `${window.location.origin}/partner_transaction.php?pid=${id}`;
  openModal('modal-lien');
}
function copyLien(){ const i=document.getElementById('lien-input'); i.select(); document.execCommand('copy'); }
function envoyerLienSMS(){
  const tel = document.getElementById('lien-tel').value.trim();
  if(!tel){ alert('Veuillez saisir un numéro'); return; }
  const lien = document.getElementById('lien-input').value;
  const body = new URLSearchParams({ partenaire_id: String(currentPid), telephone: tel, lien });
  fetch('ajax/send_partner_sms.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
    .then(r=>r.json()).then(j=>{ if(j.success){ closeModal('modal-lien'); } else { alert(j.message||'Erreur'); } })
    .catch(()=>alert('Erreur réseau'));
}

// Validate/Reject pending transaction
function validerTransaction(pendingId, action){
  if(!pendingId) return;
  let reason = '';
  if (action === 'reject') {
    reason = prompt('Motif de rejet :') || '';
  }
  const body = new URLSearchParams({ pending_id: String(pendingId), action, reason });
  console.log('Validation request:', { pendingId, action });
  
  fetch('ajax/validate_partner_transaction.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded' },
    body
  }).then(response => {
    console.log('Response status:', response.status);
    console.log('Response headers:', response.headers);
    return response.text();
  }).then(text => {
    console.log('Response text:', text);
    try {
      const j = JSON.parse(text);
      if(j && j.success){
        // reload list
        chargerTransactionsPartenaire(currentPid);
      } else {
        alert((j && j.message) || 'Erreur lors de la mise à jour');
      }
    } catch(e) {
      console.error('JSON parse error:', e);
      alert('Réponse invalide du serveur: ' + text);
    }
  }).catch(err => {
    console.error('Network error:', err);
    alert('Erreur réseau: ' + err.message);
  });
}

function showRejectReason(encoded){
  try{
    const text = decodeURIComponent(encoded).replace(/\+/g,' ');
    const container = document.createElement('div');
    container.style.whiteSpace = 'pre-wrap';
    container.textContent = text || 'Aucun motif fourni.';
    document.getElementById('hist-content').appendChild(container);
    alert(text || 'Aucun motif fourni.');
  }catch(e){
    alert('Motif non disponible');
  }
}
</script>


