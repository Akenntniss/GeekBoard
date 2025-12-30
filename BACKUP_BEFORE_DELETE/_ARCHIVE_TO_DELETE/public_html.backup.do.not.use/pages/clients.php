<?php
/**
 * Page de gestion des clients - Version COMPLÈTEMENT REFAITE (racine)
 * Cette version est calquée sur public_html/pages/clients.php pour unifier l'UI
 */

// Configuration de la pagination
$items_per_page = 20;
$current_page = max(1, intval($_GET['p'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de recherche et tri
$search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'nom';
$sort_order = ($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Validation des paramètres de tri
$allowed_sort_fields = ['id', 'nom', 'prenom', 'telephone', 'email', 'date_creation', 'nombre_reparations'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'nom';
}

try {
    $shop_pdo = getShopDBConnection();

    // Construction de la requête avec recherche
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(nom LIKE :search OR prenom LIKE :search OR telephone LIKE :search OR email LIKE :search)";
        $params['search'] = "%$search%";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Requête pour compter le total
    $count_sql = "SELECT COUNT(*) as total FROM clients $where_clause";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = (int)($count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $total_pages = $total_items > 0 ? (int)ceil($total_items / $items_per_page) : 1;

    // Requête principale avec jointure pour compter les réparations
    $sql = "SELECT c.*, COUNT(r.id) as nombre_reparations
            FROM clients c 
            LEFT JOIN reparations r ON c.id = r.client_id 
            $where_clause
            GROUP BY c.id 
            ORDER BY $sort_by $sort_order
            LIMIT :limit OFFSET :offset";

    $stmt = $shop_pdo->prepare($sql);

    // Ajouter les paramètres de pagination
    $params['limit'] = $items_per_page;
    $params['offset'] = $offset;

    // Bind des paramètres
    foreach ($params as $key => $value) {
        if ($key === 'limit' || $key === 'offset') {
            $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (Exception $e) {
    error_log("Erreur lors de la récupération des clients (racine): " . $e->getMessage());
    $clients = [];
    $total_pages = 1;
    $total_items = 0;
}

// Helpers
function getSortUrl($field) {
    global $sort_by, $sort_order, $search;
    $new_order = ($sort_by === $field && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = ['page' => 'clients', 'sort' => $field, 'order' => $new_order];
    if (!empty($search)) {
        $params['search'] = $search;
    }
    return 'index.php?' . http_build_query($params);
}

function getSortIcon($field) {
    global $sort_by, $sort_order;
    if ($sort_by !== $field) return '↕️';
    return $sort_order === 'ASC' ? '⬆️' : '⬇️';
}
?>

<style>
.clients-container { width: 100%; margin: 0; padding: 5px 20px 30px 20px; background: #f8fafc; min-height: 100vh; box-sizing: border-box; }
.page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(102,126,234,.2); }
.page-title { font-size: 2.2rem; font-weight: 700; margin: 0 0 8px 0; }
.page-subtitle { opacity: .9; margin: 0; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: #fff; padding: 22px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,.08); border-left: 4px solid #667eea; }
.stat-number { font-size: 1.8rem; font-weight: 700; color: #667eea; }
.stat-label { color: #64748b; font-size: .9rem; margin-top: 6px; }
.controls-section { background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.06); margin-bottom: 24px; border: 1px solid #f1f5f9; }
.controls-grid { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; }
.search-container { position: relative; max-width: 600px; }
.search-input { width: 100%; padding: 12px 44px 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
.search-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.12); }
.search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }
.btn { padding: 12px 20px; border: 0; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: #fff; box-shadow: 0 4px 12px rgba(102,126,234,.3); }
.table-container { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.06); border: 1px solid #f1f5f9; }
.modern-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; }
.modern-table th { background: #f8fafc; padding: 14px 12px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: .9rem; white-space: nowrap; }
.modern-table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sort-header { display: flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; }
.client-id { font-weight: 600; color: #667eea; font-size: .9rem; }
.client-name { font-weight: 600; color: #1e293b; }
.contact-group { display: flex; align-items: center; gap: 10px; }
.contact-link { color: #059669; text-decoration: none; font-weight: 500; }
.sms-btn { background: #3b82f6; color: #fff; border: 0; border-radius: 6px; padding: 6px 10px; cursor: pointer; }
.email-link { color: #7c3aed; text-decoration: none; font-weight: 500; }
.date-text { color: #64748b; font-size: .9rem; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: .8rem; font-weight: 600; }
.badge-primary { background: #dbeafe; color: #1d4ed8; }
.badge-warning { background: #fef3c7; color: #92400e; }
.action-buttons { display: flex; gap: 8px; align-items: center; }
.btn-sm { padding: 6px 12px; font-size: .85rem; border-radius: 6px; }
.btn-info { background: #0ea5e9; color: #fff; }
.btn-danger { background: #ef4444; color: #fff; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 24px; }
.pagination a, .pagination span { padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; color: #475569; }
.pagination .current { background: #667eea; color: #fff; border-color: #667eea; }
.empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
.empty-icon { font-size: 4rem; margin-bottom: 16px; opacity: .5; }

/* Modal SMS et Historique (non-Bootstrap) */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: none !important; justify-content: center; align-items: center; z-index: 1000; visibility: hidden !important; opacity: 0 !important; }
.modal-overlay.show { display: flex !important; visibility: visible !important; opacity: 1 !important; }
.modal-content { background: #fff; border-radius: 12px; padding: 0; max-width: 500px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,.3); overflow: hidden; }
.modal-header { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-close { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
.form-control { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }
.btn-secondary { background: #6b7280; color: #fff; }
.btn-success { background: #059669; color: #fff; }

.modern-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: none !important; justify-content: center; align-items: center; z-index: 9999; padding: 20px; }
.modern-modal-overlay.show { display: flex !important; }
.modern-modal-container { background: #fff; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,.25); max-width: 1000px; width: 100%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
.modern-modal-header { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: #fff; padding: 26px; display: flex; justify-content: space-between; align-items: center; }
.modern-modal-body { flex: 1; overflow-y: auto; padding: 0; background: #f8fafc; }
.historique-content { padding: 26px; display: none; }
.historique-content.loaded { display: block; }
</style>

<div class="clients-container">
    <div class="page-header">
        <h1 class="page-title">👥 Gestion des Clients</h1>
        <p class="page-subtitle">Gérez votre base client et consultez les informations détaillées</p>
    </div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-number"><?php echo number_format($total_items); ?></div><div class="stat-label">Total clients</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo count(array_filter($clients, function($c){return ($c['nombre_reparations'] ?? 0) > 0;})); ?></div><div class="stat-label">Clients actifs</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo array_sum(array_map(fn($c)=> (int)($c['nombre_reparations'] ?? 0), $clients)); ?></div><div class="stat-label">Total réparations</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo count(array_filter($clients, function($c){return (int)($c['nombre_reparations'] ?? 0) === 0;})); ?></div><div class="stat-label">Nouveaux clients</div></div>
    </div>

    <div class="controls-section">
        <div class="controls-grid">
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="clients">
                    <input type="text" class="search-input" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher un client...">
                    <span class="search-icon">🔍</span>
                </form>
            </div>
            <a href="index.php?page=ajouter_client" class="btn btn-primary">➕ Nouveau Client</a>
        </div>
    </div>

    <?php if (empty($clients)): ?>
        <div class="table-container">
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>Aucun client trouvé</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Aucun client ne correspond à votre recherche "<?php echo htmlspecialchars($search); ?>".
                    <?php else: ?>
                        Vous n'avez pas encore de clients enregistrés.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=clients" class="btn btn-primary">Voir tous les clients</a>
                <?php else: ?>
                    <a href="index.php?page=ajouter_client" class="btn btn-primary">Ajouter le premier client</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><a href="<?php echo getSortUrl('id'); ?>" class="sort-header">ID <?php echo getSortIcon('id'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('nom'); ?>" class="sort-header">Nom <?php echo getSortIcon('nom'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('prenom'); ?>" class="sort-header">Prénom <?php echo getSortIcon('prenom'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('telephone'); ?>" class="sort-header">Téléphone <?php echo getSortIcon('telephone'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('date_creation'); ?>" class="sort-header">Créé le <?php echo getSortIcon('date_creation'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('nombre_reparations'); ?>" class="sort-header">Réparations <?php echo getSortIcon('nombre_reparations'); ?></a></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><span class="client-id">#<?php echo (int)$client['id']; ?></span></td>
                            <td><span class="client-name"><?php echo htmlspecialchars($client['nom'] ?? ''); ?></span></td>
                            <td><?php echo htmlspecialchars($client['prenom'] ?? ''); ?></td>
                            <td>
                                <?php if (!empty($client['telephone'])): ?>
                                    <div class="contact-group">
                                        <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" class="contact-link">📞 <?php echo htmlspecialchars($client['telephone']); ?></a>
                                        <button type="button" class="sms-btn" onclick="openSmsModal('<?php echo (int)$client['id']; ?>','<?php echo htmlspecialchars(($client['nom'] ?? '') . ' ' . ($client['prenom'] ?? '')); ?>','<?php echo htmlspecialchars($client['telephone']); ?>')" title="Envoyer un SMS">💬</button>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#9ca3af;font-style:italic;">Non renseigné</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="date-text"><?php echo $client['date_creation'] ? date('d/m/Y', strtotime($client['date_creation'])) : '-'; ?></span></td>
                            <td>
                                <?php $nr = (int)($client['nombre_reparations'] ?? 0); ?>
                                <?php if ($nr > 0): ?>
                                    <span class="badge badge-primary"><?php echo $nr; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-info btn-sm" onclick="showClientHistory('<?php echo (int)$client['id']; ?>','<?php echo htmlspecialchars(($client['nom'] ?? '') . ' ' . ($client['prenom'] ?? '')); ?>')">📋 Historique</button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?php echo (int)$client['id']; ?>','<?php echo htmlspecialchars(($client['nom'] ?? '') . ' ' . ($client['prenom'] ?? '')); ?>')">🗑️ Supprimer</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page - 1); ?>">⬅️ Précédent</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page + 1); ?>">Suivant ➡️</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal SMS -->
<div id="smsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">💬 Envoyer un SMS</h5>
            <button type="button" class="modal-close" onclick="closeSmsModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group"><strong>Client :</strong> <span id="smsClientName"></span></div>
            <div class="form-group"><strong>Téléphone :</strong> <span id="smsClientPhone"></span></div>
            <div class="form-group">
                <label for="smsMessage" class="form-label">Message SMS</label>
                <textarea id="smsMessage" class="form-control" rows="4" placeholder="Tapez votre message ici..." maxlength="160"></textarea>
                <small style="color:#6b7280;font-size:.85rem;margin-top:5px;display:block;"><span id="charCount">0</span>/160 caractères</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSmsModal()">❌ Annuler</button>
            <button type="button" class="btn btn-success" onclick="sendSms()">📤 Envoyer SMS</button>
        </div>
    </div>
  </div>

<!-- Modal Historique Client -->
<div id="historiqueModal" class="modern-modal-overlay">
  <div class="modern-modal-container">
    <div class="modern-modal-header">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="font-size:2rem;opacity:.9;">📋</div>
        <div>
          <h2 class="modal-title" style="margin:0;font-size:1.4rem;">Historique Client</h2>
          <p id="historiqueClientName" style="margin:.25rem 0 0 0;opacity:.9;"></p>
        </div>
      </div>
      <button type="button" class="modal-close" style="background:rgba(255,255,255,.2);" onclick="closeHistoriqueModal()">×</button>
    </div>
    <div class="modern-modal-body">
      <div class="historique-content" id="historiqueContent"></div>
    </div>
    <div class="modal-footer" style="background:#fff;">
      <button type="button" class="btn btn-secondary" onclick="closeHistoriqueModal()">Fermer</button>
    </div>
  </div>
</div>

<script>
let currentSmsData = {};

document.addEventListener('DOMContentLoaded', function() {
  const input = document.querySelector('.search-input');
  if (input) {
    let t; input.addEventListener('input', function(){ clearTimeout(t); t=setTimeout(()=>this.form && this.form.submit(), 700); });
    input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); this.form && this.form.submit(); }});
  }
  initializeCharacterCounter();
});

function openSmsModal(id, name, phone){
  if(!id||!name||!phone) return false;
  currentSmsData = { id, name, phone };
  document.getElementById('smsClientName').textContent = name;
  document.getElementById('smsClientPhone').textContent = phone;
  document.getElementById('smsMessage').value='';
  updateCharacterCount();
  const m = document.getElementById('smsModal');
  m.style.display=''; m.classList.add('show'); document.body.style.overflow='hidden';
  setTimeout(()=>document.getElementById('smsMessage')?.focus(),100);
  return true;
}
function closeSmsModal(){ const m=document.getElementById('smsModal'); m.classList.remove('show'); m.style.display='none'; document.body.style.overflow=''; currentSmsData={}; }
function initializeCharacterCounter(){ const t=document.getElementById('smsMessage'); if(!t) return; t.addEventListener('input', updateCharacterCount); }
function updateCharacterCount(){ const t=document.getElementById('smsMessage'); const c=document.getElementById('charCount'); if(!t||!c) return; const n=t.value.length; c.textContent=n; c.style.color = n>140?'#ef4444':(n>120?'#f59e0b':'#6b7280'); }
function sendSms(){ const message=(document.getElementById('smsMessage').value||'').trim(); if(!message){ alert('Veuillez saisir un message SMS'); return; } if(!currentSmsData.phone){ alert('Numéro de téléphone manquant'); return; }
  const btn=document.querySelector('#smsModal .btn-success'); const text=btn.textContent; btn.textContent='⏳ Envoi...'; btn.disabled=true;
  const form=new FormData(); form.append('telephone', currentSmsData.phone); form.append('message', message); form.append('client_id', currentSmsData.id);
  fetch('ajax/send_sms.php',{ method:'POST', body:form }).then(r=>r.json()).then(d=>{ if(d.success){ alert('✅ SMS envoyé avec succès !'); closeSmsModal(); } else { alert('❌ Erreur lors de l\'envoi : '+(d.message||'Erreur inconnue')); } })
  .catch(()=>alert('❌ Erreur lors de l\'envoi du SMS')).finally(()=>{ btn.textContent=text; btn.disabled=false; }); }

function showClientHistory(id,name){ openHistoriqueModal(id,name); }
function openHistoriqueModal(id,name){ const modal=document.getElementById('historiqueModal'); const nameEl=document.getElementById('historiqueClientName'); const content=document.getElementById('historiqueContent'); nameEl.textContent=name; content.innerHTML='<div style="padding:40px;text-align:center;color:#64748b;">Chargement...</div>'; modal.classList.add('show'); document.body.style.overflow='hidden'; fetch('ajax/get_client_history.php?client_id='+id).then(r=>r.text()).then(html=>{ content.innerHTML=html; content.classList.add('loaded'); }).catch(()=>{ content.innerHTML='<div style="text-align:center;padding:40px;color:#ef4444;">Erreur de chargement</div>'; }); }
function closeHistoriqueModal(){ const modal=document.getElementById('historiqueModal'); modal.classList.remove('show'); document.body.style.overflow=''; setTimeout(()=>{ document.getElementById('historiqueContent').innerHTML=''; document.getElementById('historiqueContent').classList.remove('loaded'); }, 300); }

document.getElementById('smsModal').addEventListener('click',function(e){ if(e.target===this) closeSmsModal(); });
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ closeSmsModal(); const hm=document.getElementById('historiqueModal'); if(hm && hm.classList.contains('show')) closeHistoriqueModal(); }});
</script>


