<?php
// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le chemin de base
$base_path = dirname(__DIR__);

// Vérifier si le fichier existe
if (!file_exists($base_path . '/config/database.php')) {
    error_log("ERREUR: Fichier database.php introuvable à " . $base_path . '/config/database.php');
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;"><h3>Erreur de configuration</h3><p>Fichier database.php introuvable</p></div>';
    exit;
}

require_once $base_path . '/config/database.php';

// Détection simple du magasin depuis le sous-domaine
$host = $_SERVER['HTTP_HOST'] ?? '';
$subdomain = '';

if (strpos($host, '.') !== false) {
    $parts = explode('.', $host);
    if (count($parts) >= 3) {
        $subdomain = $parts[0];
    }
}

// Mapper le sous-domaine à l'ID du magasin
$shop_mapping = [
    'mkmkmk' => 1,
    'cannesphones' => 2
];

$shop_id = $shop_mapping[$subdomain] ?? 1;
$_SESSION['shop_id'] = $shop_id;

error_log("Sous-domaine détecté: " . $subdomain . ", Shop ID: " . $shop_id);

if (!isset($_GET['client_id'])) {
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;"><h3>Erreur</h3><p>ID client manquant</p></div>';
    exit;
}

$client_id = (int)$_GET['client_id'];

try {
    // Utiliser la fonction existante de connexion ou créer une connexion simple
    if (function_exists('getShopDBConnection')) {
        $pdo = getShopDBConnection();
        error_log("Utilisation de getShopDBConnection()");
    } else {
        // Connexion fallback
        $database_name = "geekboard_" . $subdomain;
        
        // Essayer avec les credentials par défaut
        try {
            $dsn = "mysql:host=localhost;dbname=" . $database_name . ";charset=utf8mb4";
            $pdo = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            // Essayer avec mot de passe
            $dsn = "mysql:host=localhost;dbname=" . $database_name . ";charset=utf8mb4";
            $pdo = new PDO($dsn, 'geekboard_user', 'BT6HzN3QSvLJ6Hf8', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }
        
        error_log("Connexion directe réussie à la base: " . $database_name);
    }
    
    // Vérifier quelles colonnes existent dans la table clients
    $columns_check = $pdo->query("SHOW COLUMNS FROM clients");
    $client_columns = [];
    while ($col = $columns_check->fetch()) {
        $client_columns[] = $col['Field'];
    }
    
    // Adapter la requête selon les colonnes disponibles
    $client_select = "id, nom, prenom";
    if (in_array('telephone', $client_columns)) $client_select .= ", telephone";
    if (in_array('email', $client_columns)) $client_select .= ", email";
    if (in_array('date_creation', $client_columns)) $client_select .= ", date_creation";
    
    $client_stmt = $pdo->prepare("SELECT $client_select FROM clients WHERE id = ?");
    $client_stmt->execute([$client_id]);
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        error_log("ERREUR: Client $client_id introuvable");
        echo '<div style="text-align: center; padding: 40px; color: #ef4444;"><h3>Client introuvable</h3><p>Aucun client trouvé avec l\'ID ' . $client_id . '</p></div>';
        exit;
    }
    
    error_log("Client trouvé: " . $client['nom'] . ' ' . $client['prenom']);
    
    // Vérifier les colonnes de la table reparations
    $rep_columns_check = $pdo->query("SHOW COLUMNS FROM reparations");
    $rep_columns = [];
    while ($col = $rep_columns_check->fetch()) {
        $rep_columns[] = $col['Field'];
    }
    
    // Adapter la requête réparations selon les colonnes disponibles
    $rep_select = "id";
    if (in_array('appareil', $rep_columns)) $rep_select .= ", appareil";
    if (in_array('device', $rep_columns)) $rep_select .= ", device as appareil";
    if (in_array('modele', $rep_columns)) $rep_select .= ", modele";
    if (in_array('model', $rep_columns)) $rep_select .= ", model as modele";
    if (in_array('probleme_declare', $rep_columns)) $rep_select .= ", probleme_declare";
    if (in_array('problem_description', $rep_columns)) $rep_select .= ", problem_description as probleme_declare";
    if (in_array('statut', $rep_columns)) $rep_select .= ", statut";
    if (in_array('status', $rep_columns)) $rep_select .= ", status as statut";
    if (in_array('date_depot', $rep_columns)) $rep_select .= ", date_depot";
    if (in_array('created_date', $rep_columns)) $rep_select .= ", created_date as date_depot";
    if (in_array('date_prevue', $rep_columns)) $rep_select .= ", date_prevue";
    if (in_array('expected_date', $rep_columns)) $rep_select .= ", expected_date as date_prevue";
    if (in_array('prix_final', $rep_columns)) $rep_select .= ", prix_final";
    if (in_array('final_price', $rep_columns)) $rep_select .= ", final_price as prix_final";
    
    error_log("Requête réparations: SELECT $rep_select FROM reparations WHERE client_id = ?");
    
    $history_stmt = $pdo->prepare("SELECT $rep_select FROM reparations WHERE client_id = ? ORDER BY id DESC");
    $history_stmt->execute([$client_id]);
    $reparations = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Nombre de réparations trouvées: " . count($reparations));
    
} catch (Exception $e) {
    error_log("ERREUR SQL: " . $e->getMessage());
    error_log("ERREUR Trace: " . $e->getTraceAsString());
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;"><h3>Erreur de base de données</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
    exit;
}

function formatStatut($statut) {
    $statuts = [
        'En attente' => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => '⏳'],
        'En cours' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => '🔧'],
        'Terminé' => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => '✅'],
        'Livré' => ['color' => '#059669', 'bg' => '#dcfce7', 'icon' => '📦']
    ];
    
    $style = $statuts[$statut] ?? ['color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => '📄'];
    
    return '<span style="background: ' . $style['bg'] . '; color: ' . $style['color'] . '; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">' . $style['icon'] . ' ' . $statut . '</span>';
}
?>

<style>
.client-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 16px;
    margin-bottom: 30px;
}

.client-summary h3 {
    margin: 0 0 15px 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.client-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.client-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    padding: 12px;
    border-radius: 8px;
}

.repair-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.clickable-repair {
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.clickable-repair:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
}

.clickable-repair::after {
    content: '👁️ Voir détails';
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.clickable-repair:hover::after {
    opacity: 1;
}

.repair-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.repair-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.repair-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
}

.detail-value {
    font-size: 0.95rem;
    color: #1e293b;
}

body.dark-mode .repair-card {
    background: #1e293b;
    border-color: #334155;
}

body.dark-mode .clickable-repair:hover {
    border-color: #4f46e5;
}

body.dark-mode .clickable-repair::after {
    background: rgba(79, 70, 229, 0.2);
    color: #a5b4fc;
}

body.dark-mode .repair-title,
body.dark-mode .detail-value {
    color: #e2e8f0;
}
</style>

<div class="client-summary">
    <h3>👤 <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></h3>
    <div class="client-info-grid">
        <?php if (!empty($client['telephone'])): ?>
        <div class="client-info-item">
            <span>📞</span>
            <span><?php echo htmlspecialchars($client['telephone']); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="client-info-item">
            <span>📅</span>
            <span>Client depuis le <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></span>
        </div>
        
        <div class="client-info-item">
            <span>🔧</span>
            <span><?php echo count($reparations); ?> réparation<?php echo count($reparations) > 1 ? 's' : ''; ?></span>
        </div>
    </div>
</div>

<h4 style="font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: 20px 0; display: flex; align-items: center; gap: 10px;">
    📋 Historique des réparations
</h4>

<?php if (empty($reparations)): ?>
    <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">🔧</div>
        <h3>Aucune réparation</h3>
        <p>Ce client n'a pas encore de réparations enregistrées.</p>
        
        <!-- DEMO: Ajouter une réparation factice pour tester -->
        <div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border: 2px dashed #0ea5e9; border-radius: 12px;">
            <h4 style="color: #0ea5e9; margin: 0 0 10px 0;">🧪 Test de fonctionnalité</h4>
            <p style="font-size: 0.9rem; color: #0369a1; margin: 0;">Une réparation de test sera ajoutée ci-dessous pour démontrer le système de clic.</p>
        </div>
    </div>
    
    <!-- DEMO: Réparation factice pour test -->
    <div class="repair-card clickable-repair" onclick="openRepairModal(1006)" data-repair-id="1006">
        <div class="repair-header">
            <div>
                <h5 class="repair-title">
                    iPhone 14 Pro - Écran cassé
                </h5>
                <div style="font-size: 0.9rem; color: #667eea; font-weight: 600;">#1006</div>
            </div>
            <div>
                <span style="background: #dbeafe; color: #3b82f6; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">🔧 En cours</span>
            </div>
        </div>
        
        <div class="repair-details">
            <div class="detail-item">
                <span class="detail-label">Date de dépôt</span>
                <span class="detail-value"><?php echo date('d/m/Y'); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Date prévue</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime('+3 days')); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Prix final</span>
                <span class="detail-value" style="font-weight: 600; color: #059669;">
                    280.00 €
                </span>
            </div>
        </div>
        
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #667eea;">
            <div class="detail-label">Problème déclaré</div>
            <div style="margin-top: 8px; font-style: italic;">
                "Écran fissuré suite à une chute, tactile ne répond plus dans la partie haute"
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($reparations as $reparation): ?>
    <div class="repair-card clickable-repair" onclick="openRepairModal(<?php echo $reparation['id']; ?>)" data-repair-id="<?php echo $reparation['id']; ?>">
        <div class="repair-header">
            <div>
                <h5 class="repair-title">
                    <?php echo htmlspecialchars($reparation['appareil'] ?? 'Appareil non spécifié'); ?>
                    <?php if (!empty($reparation['modele'])): ?>
                        - <?php echo htmlspecialchars($reparation['modele']); ?>
                    <?php endif; ?>
                </h5>
                <div style="font-size: 0.9rem; color: #667eea; font-weight: 600;">#<?php echo $reparation['id']; ?></div>
            </div>
            <div>
                <?php echo formatStatut($reparation['statut'] ?? 'En attente'); ?>
            </div>
        </div>
        
        <div class="repair-details">
            <div class="detail-item">
                <span class="detail-label">Date de dépôt</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($reparation['date_depot'])); ?></span>
            </div>
            
            <?php if (!empty($reparation['date_prevue'])): ?>
            <div class="detail-item">
                <span class="detail-label">Date prévue</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($reparation['date_prevue'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reparation['prix_final']) && $reparation['prix_final'] > 0): ?>
            <div class="detail-item">
                <span class="detail-label">Prix final</span>
                <span class="detail-value" style="font-weight: 600; color: #059669;">
                    <?php echo number_format($reparation['prix_final'], 2); ?> €
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reparation['probleme_declare'])): ?>
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #667eea;">
            <div class="detail-label">Problème déclaré</div>
            <div style="margin-top: 8px; font-style: italic;">
                "<?php echo htmlspecialchars($reparation['probleme_declare']); ?>"
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Améliorer les cartes cliquables une fois le contenu chargé
(function() {
    // Attendre un petit délai pour s'assurer que le DOM est prêt
    setTimeout(function() {
        const repairCards = document.querySelectorAll('.clickable-repair');
        
        repairCards.forEach(card => {
            // Ajouter un titre pour indiquer que c'est cliquable
            card.setAttribute('title', 'Cliquez pour voir les détails de la réparation');
            
            // Ajouter un effet de focus pour l'accessibilité
            card.setAttribute('tabindex', '0');
            
            // Gérer le clic avec Enter sur le focus
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const repairId = this.getAttribute('data-repair-id');
                    if (repairId && window.openRepairModal) {
                        window.openRepairModal(repairId);
                    }
                }
            });
        });
        
        console.log('🔧 Cartes de réparations enrichies:', repairCards.length);
    }, 100);
})();
    </script>
