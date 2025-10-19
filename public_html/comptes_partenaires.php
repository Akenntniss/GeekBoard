<?php
// Définir la page actuelle pour le menu
$current_page = 'comptes_partenaires';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les partenaires
$partenaires = [];
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT p.*, 
        COALESCE(s.solde_actuel, 0) as solde
        FROM partenaires p 
        LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
        WHERE p.actif = TRUE
        ORDER BY p.nom");
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des partenaires: " . $e->getMessage(), "danger");
}

// Calculer les statistiques
$total_solde_positif = 0;
$total_solde_negatif = 0;
$nombre_partenaires_actifs = 0;

foreach ($partenaires as $partenaire) {
    if ($partenaire['solde'] > 0) {
        $total_solde_positif += $partenaire['solde'];
    } else {
        $total_solde_negatif += abs($partenaire['solde']);
    }
    if ($partenaire['actif']) {
        $nombre_partenaires_actifs++;
    }
}
?>

<div class="content-wrapper" style="margin-top: 60px;">
    <!-- Hero Section avec gradient -->
    <div class="hero-section bg-gradient-primary text-white rounded-4 p-4 mb-4 position-relative overflow-hidden">
        <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 8rem;">
            <i class="fas fa-handshake"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-2 fw-bold">
                    <i class="fas fa-handshake me-3"></i>
                    Gestion des Comptes Partenaires
                </h1>
                <p class="lead mb-0 opacity-90">
                    Gérez vos relations commerciales, transactions et services partenaires en toute simplicité
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex flex-column flex-md-row gap-2">
                    <button type="button" class="btn btn-light btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#gererPartenairesModal">
                        <i class="fas fa-users-cog me-2"></i> Gérer
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#ajouterTransactionModal">
                            <i class="fas fa-plus me-2"></i> Transaction
                        </button>
                        <button type="button" class="btn btn-info btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#ajouterServiceModal">
                            <i class="fas fa-tools me-2"></i> Service
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo display_message(); ?>

    <!-- Cartes de statistiques améliorées -->
    <div class="row mb-4 g-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card-body">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $nombre_partenaires_actifs; ?></div>
                        <div class="stats-label">Partenaires Actifs</div>
                        <div class="stats-trend">
                            <i class="fas fa-chart-line text-success me-1"></i>
                            <small class="text-success">+2 ce mois</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card-body">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo number_format($total_solde_positif, 0); ?> €</div>
                        <div class="stats-label">Total Créances</div>
                        <div class="stats-trend">
                            <i class="fas fa-arrow-up text-success me-1"></i>
                            <small class="text-success">Montant à recevoir</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card-body">
                    <div class="stats-icon bg-danger">
                        <i class="fas fa-arrow-trend-down"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo number_format($total_solde_negatif, 0); ?> €</div>
                        <div class="stats-label">Total Dettes</div>
                        <div class="stats-trend">
                            <i class="fas fa-arrow-down text-danger me-1"></i>
                            <small class="text-danger">Montant à payer</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card-body">
                    <div class="stats-icon <?php echo ($total_solde_positif - $total_solde_negatif) >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number <?php echo ($total_solde_positif - $total_solde_negatif) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($total_solde_positif - $total_solde_negatif, 0); ?> €
                        </div>
                        <div class="stats-label">Balance Globale</div>
                        <div class="stats-trend">
                            <?php if (($total_solde_positif - $total_solde_negatif) >= 0): ?>
                                <i class="fas fa-check-circle text-success me-1"></i>
                                <small class="text-success">Position positive</small>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                <small class="text-warning">Position négative</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des partenaires et leurs soldes -->
    <div class="card border-0 shadow-sm modern-table-card">
        <div class="card-header bg-gradient-light border-0 py-4">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-list-ul text-primary me-2"></i>
                        Liste des Partenaires
                    </h5>
                    <small class="text-muted">Gérez et consultez tous vos partenaires commerciaux</small>
                </div>
                <div class="col-auto">
                    <div class="input-group modern-search">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="searchPartenaire" class="form-control border-start-0 ps-0" 
                               placeholder="Rechercher un partenaire..." style="box-shadow: none;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tablePartenaires">
                    <thead>
                        <tr>
                            <th>Partenaire</th>
                            <th>Solde Actuel</th>
                            <th>Dernière Transaction</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($partenaires)): ?>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building text-primary me-2"></i>
                                            <?php echo htmlspecialchars($partenaire['nom']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo ($partenaire['solde'] < 0) ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                                            <?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        try {
                                            $stmt = $shop_pdo->prepare("
                                                SELECT date_transaction, montant, type 
                                                FROM transactions_partenaires 
                                                WHERE partenaire_id = ? 
                                                ORDER BY date_transaction DESC 
                                                LIMIT 1
                                            ");
                                            $stmt->execute([$partenaire['id']]);
                                            $derniere_transaction = $stmt->fetch();
                                            
                                            if ($derniere_transaction) {
                                                echo date('d/m/Y H:i', strtotime($derniere_transaction['date_transaction']));
                                                echo ' - ';
                                                echo number_format($derniere_transaction['montant'], 2) . ' €';
                                                echo ' (' . $derniere_transaction['type'] . ')';
                                            } else {
                                                echo '<span class="text-muted">Aucune transaction</span>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<span class="text-danger">Erreur</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-transactions" 
                                                    data-partenaire-id="<?php echo $partenaire['id']; ?>"
                                                    data-partenaire-nom="<?php echo htmlspecialchars($partenaire['nom']); ?>">
                                                <i class="fas fa-history me-1"></i> Historique
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <p class="mb-0">Aucun partenaire enregistré</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Transaction - Version Moderne -->
<div class="modal fade" id="ajouterTransactionModal" tabindex="-1" aria-labelledby="ajouterTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modern-modal">
            <!-- En-tête du modal -->
            <div class="modal-header modern-modal-header">
                <div class="d-flex align-items-center">
                    <div class="modal-icon">
                        <i class="fas fa-exchange-alt"></i>
            </div>
                    <div>
                        <h5 class="modal-title mb-0" id="ajouterTransactionModalLabel">Nouvelle Transaction</h5>
                        <small class="text-white-50">Ajouter une transaction partenaire</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body modern-modal-body">
                <form id="nouvelleTransactionForm">
                    <div class="row g-4">
                        <!-- Sélection du partenaire -->
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="nouveau_partenaire_id" class="form-label-modern">
                                    <i class="fas fa-user me-2"></i>Partenaire
                                </label>
                                <select class="form-control-modern" id="nouveau_partenaire_id" name="partenaire_id" required>
                                    <option value="">Sélectionner un partenaire</option>
                                    <?php foreach ($partenaires as $partenaire): ?>
                                        <option value="<?php echo $partenaire['id']; ?>" 
                                                data-solde="<?php echo $partenaire['solde'] ?? 0; ?>">
                                            <?php echo htmlspecialchars($partenaire['nom']); ?>
                                            (<?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="nouveau_solde_actuel" class="form-help-text"></div>
                            </div>
                            </div>

                        <!-- Type de transaction -->
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <i class="fas fa-tag me-2"></i>Type de transaction
                                </label>
                                <div class="transaction-type-selector">
                                    <input type="radio" id="nouveau_type_avance" name="type" value="AVANCE" class="type-radio" checked>
                                    <label for="nouveau_type_avance" class="type-option type-avance">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Avance</span>
                                        <small>Crédit au partenaire</small>
                                    </label>
                                    
                                    <input type="radio" id="nouveau_type_remboursement" name="type" value="REMBOURSEMENT" class="type-radio">
                                    <label for="nouveau_type_remboursement" class="type-option type-remboursement">
                                        <i class="fas fa-arrow-down"></i>
                                        <span>Remboursement</span>
                                        <small>Débit du partenaire</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Montant avec clavier virtuel -->
                        <div class="col-12">
                            <div class="form-group-modern">
                                <label for="nouveau_montant" class="form-label-modern">
                                    <i class="fas fa-euro-sign me-2"></i>Montant
                                </label>
                                <div class="montant-input-group">
                                    <input type="text" class="form-control-modern montant-input" id="nouveau_montant" name="montant" 
                                           placeholder="0.00" readonly required>
                                    <span class="currency-symbol">€</span>
                                </div>
                                <div id="nouveau_solde_calcule" class="form-help-text"></div>
                                
                                <!-- Clavier virtuel moderne -->
                                <div class="virtual-keyboard-modern mt-3">
                                    <div class="keyboard-row">
                                        <button type="button" class="key-btn" data-key="1">1</button>
                                        <button type="button" class="key-btn" data-key="2">2</button>
                                        <button type="button" class="key-btn" data-key="3">3</button>
                                    </div>
                                    <div class="keyboard-row">
                                        <button type="button" class="key-btn" data-key="4">4</button>
                                        <button type="button" class="key-btn" data-key="5">5</button>
                                        <button type="button" class="key-btn" data-key="6">6</button>
                                </div>
                                    <div class="keyboard-row">
                                        <button type="button" class="key-btn" data-key="7">7</button>
                                        <button type="button" class="key-btn" data-key="8">8</button>
                                        <button type="button" class="key-btn" data-key="9">9</button>
                                    </div>
                                    <div class="keyboard-row">
                                        <button type="button" class="key-btn key-decimal" data-key=".">.</button>
                                        <button type="button" class="key-btn" data-key="0">0</button>
                                        <button type="button" class="key-btn key-clear" data-key="clear">
                                            <i class="fas fa-backspace"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group-modern">
                                <label for="nouvelle_description" class="form-label-modern">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </label>
                                <textarea class="form-control-modern" id="nouvelle_description" name="description" 
                                          rows="3" placeholder="Détails de la transaction (optionnel)..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pied du modal -->
            <div class="modal-footer modern-modal-footer">
                <div class="transaction-summary" id="transaction_summary">
                    <!-- Résumé de la transaction -->
                    </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modern btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                        </button>
                    <button type="button" class="btn-modern btn-primary" id="btn_enregistrer_nouvelle_transaction">
                        <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Service -->
<div class="modal fade" id="ajouterServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Nouveau Service
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterServiceForm" action="ajax/add_service_partenaire.php" method="POST">
                    <div class="mb-3">
                        <label for="service_partenaire_id" class="form-label">Partenaire</label>
                        <select class="form-select" id="service_partenaire_id" name="partenaire_id" required>
                            <option value="">Sélectionner un partenaire</option>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <option value="<?php echo $partenaire['id']; ?>">
                                    <?php echo htmlspecialchars($partenaire['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description_service" class="form-label">Description du service</label>
                        <textarea class="form-control" id="description_service" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="montant_service" class="form-label">Montant (€)</label>
                        <input type="number" step="0.01" class="form-control" id="montant_service" name="montant" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterServiceForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historique des Transactions -->
<div class="modal fade" id="historiqueTransactionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Historique des Transactions - <span id="partenaireNom"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historiqueTransactions" class="table-responsive">
                    <!-- L'historique sera chargé dynamiquement ici -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gérer les Partenaires -->
<div class="modal fade" id="gererPartenairesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users-cog me-2 text-primary"></i>
                    Gérer les Partenaires
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterPartenaireModal">
                        <i class="fas fa-user-plus me-1"></i> Ajouter un Partenaire
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="listePartenaires">
                            <!-- La liste des partenaires sera chargée ici dynamiquement -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Partenaire -->
<div class="modal fade" id="ajouterPartenaireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Ajouter un Partenaire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterPartenaireForm" action="ajax/add_partenaire.php" method="POST">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom*</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone">
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterPartenaireForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="20" cy="60" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="80" cy="30" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

/* Stats Cards */
.stats-card {
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    border-radius: 20px;
    padding: 0;
    border: none;
    box-shadow: 
        0 4px 25px rgba(0,0,0,0.08),
        0 0 0 1px rgba(255,255,255,0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 10px 40px rgba(0,0,0,0.12),
        0 0 0 1px rgba(255,255,255,0.1);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stats-card-body {
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.stats-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    flex-shrink: 0;
}

.stats-content {
    flex: 1;
}

.stats-number {
    font-size: 2rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stats-label {
    font-size: 0.95rem;
    color: #718096;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.stats-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Modern Table Card */
.modern-table-card {
    border-radius: 20px;
    overflow: hidden;
    border: none;
    box-shadow: 0 4px 25px rgba(0,0,0,0.08);
}

.bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modern-search {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.modern-search .input-group-text {
    border: 1px solid #e2e8f0;
}

.modern-search .form-control {
    border: 1px solid #e2e8f0;
}

.modern-search .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Table Improvements */
#tablePartenaires thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#tablePartenaires tbody tr {
    border: none;
    transition: all 0.2s ease;
}

#tablePartenaires tbody tr:hover {
    background: linear-gradient(135deg, #f8f9ff 0%, #f1f3ff 100%);
    transform: scale(1.01);
}

#tablePartenaires tbody td {
    padding: 1.2rem 1rem;
    border: none;
    vertical-align: middle;
}

/* Badge amélioré */
.badge {
    font-size: 0.8rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
}

/* Buttons améliorés */
.btn {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-group .btn {
    border-radius: 12px;
}

.btn-group .btn:not(:first-child) {
    margin-left: 0.5rem;
}

/* Virtual keyboard */
.virtual-keyboard .btn {
    font-size: 1.2rem;
    padding: 0.75rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-weight: 600;
    transition: all 0.2s ease;
}

.virtual-keyboard .key-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.virtual-keyboard .key-btn:active {
    transform: translateY(0);
}

.virtual-keyboard .key-btn-clear {
    font-weight: bold;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    border: none;
}

/* Modal amélioré */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.modal-header {
    border-radius: 20px 20px 0 0;
    border: none;
    padding: 2rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border: none;
    padding: 2rem;
    border-radius: 0 0 20px 20px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-card-body {
        padding: 1.5rem;
        gap: 1rem;
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .stats-number {
        font-size: 1.5rem;
    }
    
    .hero-section {
        text-align: center;
    }
    
    .hero-section .btn-group {
        flex-direction: column;
        width: 100%;
    }
}

/* Ajout du décalage de 57px uniquement sur les écrans larges (PC) */
@media (min-width: 992px) {
    .content-wrapper {
        margin-top: 68px !important; /* 60px + 57px */
    }
}

/* Animation d'entrée */
.stats-card {
    animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.stats-card:nth-child(1) { animation-delay: 0.1s; }
.stats-card:nth-child(2) { animation-delay: 0.2s; }
.stats-card:nth-child(3) { animation-delay: 0.3s; }
.stats-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== STYLES POUR LE NOUVEAU MODAL MODERNE ===== */

/* Modal moderne */
.modern-modal {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
}

/* En-tête moderne */
.modern-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 2rem;
    position: relative;
}

.modern-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

.modal-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.5rem;
    color: white;
    backdrop-filter: blur(10px);
}

/* Corps moderne */
.modern-modal-body {
    padding: 2.5rem;
    background: #fafbfc;
}

/* Groupes de formulaire modernes */
.form-group-modern {
    margin-bottom: 2rem;
}

.form-label-modern {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
}

.form-control-modern {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    background: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.form-control-modern:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.form-help-text {
    font-size: 0.875rem;
    color: #718096;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
}

/* Sélecteur de type de transaction */
.transaction-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.type-radio {
    display: none;
}

.type-option {
    padding: 1.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.type-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.type-option i {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.type-option span {
    font-weight: 600;
    font-size: 1rem;
}

.type-option small {
    color: #718096;
    font-size: 0.8rem;
}

.type-avance i { color: #48bb78; }
.type-remboursement i { color: #f56565; }

.type-radio:checked + .type-option {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.type-radio:checked + .type-option i,
.type-radio:checked + .type-option small {
    color: white;
}

/* Groupe de montant */
.montant-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.montant-input {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2d3748;
    padding-right: 3rem;
}

.currency-symbol {
    position: absolute;
    right: 1.25rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #667eea;
    pointer-events: none;
}

/* Clavier virtuel moderne */
.virtual-keyboard-modern {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 2px solid #f1f3f4;
}

.keyboard-row {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.keyboard-row:last-child {
    margin-bottom: 0;
}

.key-btn {
    flex: 1;
    height: 50px;
    border: none;
    border-radius: 12px;
    background: #f8f9fa;
    color: #2d3748;
    font-size: 1.25rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    user-select: none;
}

.key-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.key-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.key-clear {
    background: linear-gradient(135deg, #f56565, #e53e3e);
    color: white;
}

.key-clear:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
}

.key-decimal {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.key-decimal:hover {
    background: linear-gradient(135deg, #5a67d8, #6b46c1);
}

/* Pied du modal moderne */
.modern-modal-footer {
    background: white;
    border: none;
    padding: 2rem 2.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.transaction-summary {
    flex: 1;
    min-width: 200px;
}

.modal-actions {
    display: flex;
    gap: 1rem;
}

/* Boutons modernes */
.btn-modern {
    padding: 0.875rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    user-select: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.btn-modern:active {
    transform: translateY(0);
}

.btn-modern.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-modern.btn-secondary {
    background: #f8f9fa;
    color: #6c757d;
    border: 2px solid #e9ecef;
}

.btn-modern.btn-secondary:hover {
    background: #e9ecef;
    color: #495057;
}

/* Résumé de transaction */
.transaction-summary {
    padding: 1rem;
    background: #f8f9ff;
    border-radius: 12px;
    border: 2px solid #e8f0fe;
}

.transaction-summary.positive {
    background: #f0fff4;
    border-color: #c6f6d5;
    color: #22543d;
}

.transaction-summary.negative {
    background: #fff5f5;
    border-color: #fed7d7;
    color: #742a2a;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-modal-header {
        padding: 1.5rem;
    }
    
    .modern-modal-body {
        padding: 1.5rem;
    }
    
    .modern-modal-footer {
        padding: 1.5rem;
        flex-direction: column;
        align-items: stretch;
    }
    
    .modal-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .transaction-type-selector {
        grid-template-columns: 1fr;
    }
    
    .keyboard-row {
        gap: 0.5rem;
    }
    
    .key-btn {
        height: 45px;
        font-size: 1.1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== NOUVEAU JAVASCRIPT POUR LE MODAL MODERNE =====
    
    // Éléments du nouveau modal
    const nouvelleTransactionForm = document.getElementById('nouvelleTransactionForm');
    const btnEnregistrerNouvelle = document.getElementById('btn_enregistrer_nouvelle_transaction');
    const nouveauMontantInput = document.getElementById('nouveau_montant');
    const nouveauPartenaireSelect = document.getElementById('nouveau_partenaire_id');
    const nouveauTypeAvance = document.getElementById('nouveau_type_avance');
    const nouveauTypeRemboursement = document.getElementById('nouveau_type_remboursement');
    const virtualKeyboardModerne = document.querySelector('.virtual-keyboard-modern');
    const transactionSummary = document.getElementById('transaction_summary');
    const nouveauSoldeActuel = document.getElementById('nouveau_solde_actuel');
    const nouveauSoldeCalcule = document.getElementById('nouveau_solde_calcule');

    // ===== FONCTIONS UTILITAIRES =====
    
    // Fonction pour afficher les notifications
    function afficherNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 12px;
            border: none;
        `;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        // Auto-suppression après 4 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }
    
    // Fonction pour formater un montant
    function formaterMontant(montant) {
        return parseFloat(montant || 0).toFixed(2);
    }
    
    // Fonction pour calculer le nouveau solde
    function calculerNouveauSolde(soldeActuel, montant, typeTransaction) {
        const montantNum = parseFloat(montant) || 0;
        const soldeNum = parseFloat(soldeActuel) || 0;
        
        if (typeTransaction === 'AVANCE') {
            return soldeNum + montantNum;
        } else {
            return soldeNum - montantNum;
        }
    }
    
    // ===== GESTION DU CLAVIER VIRTUEL =====
    
    if (virtualKeyboardModerne && nouveauMontantInput) {
        virtualKeyboardModerne.addEventListener('click', function(e) {
            const keyBtn = e.target.closest('.key-btn');
            if (!keyBtn) return;
            
            e.preventDefault();
            
            const key = keyBtn.dataset.key;
            const currentValue = nouveauMontantInput.value;
            
            if (key === 'clear') {
                // Effacer le dernier caractère
                nouveauMontantInput.value = currentValue.slice(0, -1);
            } else if (key === '.') {
                // Ajouter le point décimal seulement s'il n'y en a pas déjà un
                if (!currentValue.includes('.')) {
                    nouveauMontantInput.value = currentValue + key;
                }
            } else {
                // Ajouter le chiffre
                if (currentValue.includes('.')) {
                    // Limiter à 2 décimales
                    const parts = currentValue.split('.');
                    if (parts[1] && parts[1].length >= 2) return;
                }
                nouveauMontantInput.value = currentValue + key;
            }
            
            // Déclencher la mise à jour des informations
            mettreAJourInformationsTransaction();
        });
    }
    
    // ===== MISE À JOUR DES INFORMATIONS DE TRANSACTION =====
    
    function mettreAJourInformationsTransaction() {
        if (!nouveauPartenaireSelect || !nouveauMontantInput) return;
        
        const selectedOption = nouveauPartenaireSelect.selectedOptions[0];
        const montant = nouveauMontantInput.value;
        const typeTransaction = nouveauTypeAvance.checked ? 'AVANCE' : 'REMBOURSEMENT';
        
        // Vider les informations précédentes
        nouveauSoldeActuel.innerHTML = '';
        nouveauSoldeCalcule.innerHTML = '';
        transactionSummary.innerHTML = '';
        transactionSummary.className = 'transaction-summary';
        
        if (selectedOption && selectedOption.value) {
            const nomPartenaire = selectedOption.textContent.split('(')[0].trim();
            const soldeActuel = parseFloat(selectedOption.dataset.solde);
            
            // Afficher le solde actuel
            nouveauSoldeActuel.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                Solde actuel: <strong class="${soldeActuel >= 0 ? 'text-success' : 'text-danger'}">
                    ${formaterMontant(soldeActuel)} €
                </strong>
            `;
            
            if (montant && parseFloat(montant) > 0) {
                const nouveauSolde = calculerNouveauSolde(soldeActuel, montant, typeTransaction);
                const impact = typeTransaction === 'AVANCE' ? parseFloat(montant) : -parseFloat(montant);
                
                // Afficher le nouveau solde calculé
                nouveauSoldeCalcule.innerHTML = `
                    <i class="fas fa-calculator me-2"></i>
                    Nouveau solde: <strong class="${nouveauSolde >= 0 ? 'text-success' : 'text-danger'}">
                        ${formaterMontant(nouveauSolde)} €
                    </strong>
                `;
                
                // Afficher le résumé de la transaction
                const impactClass = impact >= 0 ? 'positive' : 'negative';
                const impactIcon = impact >= 0 ? 'arrow-up' : 'arrow-down';
                const impactText = impact >= 0 ? 'Crédit' : 'Débit';
                
                transactionSummary.className = `transaction-summary ${impactClass}`;
                transactionSummary.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${nomPartenaire}</strong><br>
                            <small>${impactText} de ${formaterMontant(Math.abs(impact))} €</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-${impactIcon} fa-lg"></i>
                        </div>
                    </div>
                `;
            }
        }
    }
    
    // ===== GESTION DES ÉVÉNEMENTS =====
    
    // Changement de partenaire
    if (nouveauPartenaireSelect) {
        nouveauPartenaireSelect.addEventListener('change', mettreAJourInformationsTransaction);
    }
    
    // Changement de type de transaction
    if (nouveauTypeAvance && nouveauTypeRemboursement) {
        nouveauTypeAvance.addEventListener('change', mettreAJourInformationsTransaction);
        nouveauTypeRemboursement.addEventListener('change', mettreAJourInformationsTransaction);
    }
    
    // ===== SOUMISSION DU FORMULAIRE =====
    
    if (btnEnregistrerNouvelle && nouvelleTransactionForm) {
        btnEnregistrerNouvelle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validation des champs requis
            const partenaireId = nouveauPartenaireSelect.value;
            const montant = nouveauMontantInput.value;
            const typeTransaction = nouveauTypeAvance.checked ? 'AVANCE' : 'REMBOURSEMENT';
            const description = document.getElementById('nouvelle_description').value;
            
            if (!partenaireId) {
                afficherNotification('Veuillez sélectionner un partenaire', 'danger');
                return;
            }
            
            if (!montant || parseFloat(montant) <= 0) {
                afficherNotification('Veuillez saisir un montant valide', 'danger');
                return;
            }
            
            // Désactiver le bouton pendant l'envoi
            const boutonOriginal = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
            
            // Préparer les données
            const formData = new FormData();
            formData.append('partenaire_id', partenaireId);
            formData.append('type', typeTransaction);
            formData.append('montant', montant);
            formData.append('description', description);
            formData.append('date_transaction', new Date().toISOString().slice(0, 19).replace('T', ' '));
            
            // Envoyer la requête
            fetch('ajax/add_transaction_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    afficherNotification('Transaction enregistrée avec succès', 'success');
                    
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTransactionModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Réinitialiser le formulaire
                    nouvelleTransactionForm.reset();
                    nouveauMontantInput.value = '';
                    mettreAJourInformationsTransaction();
                    
                    // Recharger la page après un délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                } else {
                    afficherNotification(data.message || 'Erreur lors de l\'enregistrement', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement:', error);
                afficherNotification('Une erreur est survenue lors de l\'enregistrement', 'danger');
            })
            .finally(() => {
                // Réactiver le bouton
                this.disabled = false;
                this.innerHTML = boutonOriginal;
            });
        });
    }
    
    // ===== RÉINITIALISATION DU MODAL =====
    
    // Réinitialiser le modal à l'ouverture
    const ajouterTransactionModal = document.getElementById('ajouterTransactionModal');
    if (ajouterTransactionModal) {
        ajouterTransactionModal.addEventListener('shown.bs.modal', function() {
            // Réinitialiser le formulaire
            if (nouvelleTransactionForm) {
                nouvelleTransactionForm.reset();
            }
            if (nouveauMontantInput) {
                nouveauMontantInput.value = '';
            }
            if (nouveauTypeAvance) {
                nouveauTypeAvance.checked = true;
            }
            mettreAJourInformationsTransaction();
        });
    }

    // ===== GESTION DE L'HISTORIQUE DES TRANSACTIONS (CONSERVÉ) =====

    // Gestionnaire pour les boutons d'historique
    document.querySelectorAll('.view-transactions').forEach(button => {
        button.addEventListener('click', function() {
            const partenaireId = this.dataset.partenaireId;
            const partenaireNom = this.dataset.partenaireNom;
            
            // Mettre à jour le nom du partenaire dans le modal
            document.getElementById('partenaireNom').textContent = partenaireNom;
            
            // Charger l'historique des transactions
            fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const historiqueDiv = document.getElementById('historiqueTransactions');
                        let html = `
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        if (data.transactions && data.transactions.length > 0) {
                            data.transactions.forEach(transaction => {
                                const date = new Date(transaction.date_transaction).toLocaleString('fr-FR');
                                const montant = parseFloat(transaction.montant).toFixed(2);
                                const typeClass = transaction.type === 'REMBOURSEMENT' ? 'text-danger' : 'text-success';
                                const montantPrefix = transaction.type === 'REMBOURSEMENT' ? '-' : '+';
                                
                                html += `
                                    <tr>
                                        <td>${date}</td>
                                        <td>${transaction.type}</td>
                                        <td class="${typeClass}">${montantPrefix}${montant} €</td>
                                        <td>${transaction.description || ''}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            html += `
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-history fa-3x mb-3"></i>
                                            <p class="mb-0">Aucune transaction</p>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        html += `
                                </tbody>
                            </table>
                        `;
                        
                        historiqueDiv.innerHTML = html;
                        
                        // Afficher le solde actuel
                        if (data.solde !== undefined) {
                            const soldeClass = parseFloat(data.solde) >= 0 ? 'text-success' : 'text-danger';
                            historiqueDiv.insertAdjacentHTML('beforebegin', `
                                <div class="alert alert-info mb-3">
                                    <strong>Solde actuel : </strong>
                                    <span class="${soldeClass}">${parseFloat(data.solde).toFixed(2)} €</span>
                                </div>
                            `);
                        }
                    } else {
                        document.getElementById('historiqueTransactions').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Erreur lors du chargement des transactions
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('historiqueTransactions').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erreur lors du chargement des transactions
                        </div>
                    `;
                });
            
            // Ouvrir le modal
            const modal = new bootstrap.Modal(document.getElementById('historiqueTransactionsModal'));
            modal.show();
        });
    });
});
</script> 