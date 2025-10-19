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

        <!-- Tableau moderne des partenaires -->
        <div class="modern-table-container">
            <div class="table-header">
                <div class="table-title-section">
                    <h2 class="table-title">
                        <span class="title-icon">
                            <i class="fas fa-handshake"></i>
                        </span>
                        Liste des Partenaires
                    </h2>
                    <p class="table-subtitle">Gérez et consultez tous vos partenaires commerciaux</p>
                    </div>
                <div class="table-controls">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchPartenaire" class="search-input" placeholder="Rechercher un partenaire...">
                </div>
            </div>
        </div>

            <div class="modern-table-wrapper">
                        <?php if (!empty($partenaires)): ?>
                    <div class="table-grid" id="tablePartenaires">
                        <?php foreach ($partenaires as $index => $partenaire): ?>
                            <div class="table-row" style="animation-delay: <?php echo ($index * 0.1); ?>s">
                                <div class="row-content">
                                    <div class="partner-info">
                                        <div class="partner-avatar">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="partner-details">
                                            <h3 class="partner-name"><?php echo htmlspecialchars($partenaire['nom']); ?></h3>
                                            <p class="partner-status">Partenaire actif</p>
                                        </div>
                                    </div>
                                    
                                    <div class="balance-info">
                                        <div class="balance-label">Solde actuel</div>
                                        <div class="balance-amount <?php echo ($partenaire['solde'] < 0) ? 'negative' : 'positive'; ?>">
                                            <?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €
                                        </div>
                                    </div>
                                    
                                    <div class="transaction-info">
                                        <div class="transaction-label">Dernière transaction</div>
                                        <div class="transaction-details">
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
                                                    echo '<span class="transaction-date">' . date('d/m/Y', strtotime($derniere_transaction['date_transaction'])) . '</span>';
                                                    echo '<span class="transaction-amount">' . number_format($derniere_transaction['montant'], 2) . ' €</span>';
                                            } else {
                                                    echo '<span class="no-transaction">Aucune transaction</span>';
                                            }
                                        } catch (PDOException $e) {
                                                echo '<span class="error-transaction">Erreur</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="actions-info">
                                        <button type="button" class="action-btn view-transactions" 
                                                    data-partenaire-id="<?php echo $partenaire['id']; ?>"
                                                    data-partenaire-nom="<?php echo htmlspecialchars($partenaire['nom']); ?>">
                                            <i class="fas fa-history"></i>
                                            <span>Historique</span>
                                        </button>
                                        <button type="button" class="action-btn send-link" 
                                                data-partenaire-id="<?php echo $partenaire['id']; ?>"
                                                data-partenaire-nom="<?php echo htmlspecialchars($partenaire['nom']); ?>"
                                                data-partenaire-telephone="<?php echo htmlspecialchars($partenaire['telephone']); ?>">
                                            <i class="fas fa-link"></i>
                                            <span>Envoyer un lien</span>
                                            </button>
                                        </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                    </div>
                        <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-users"></i>
                                    </div>
                        <h3 class="empty-title">Aucun partenaire enregistré</h3>
                        <p class="empty-description">Commencez par ajouter vos premiers partenaires commerciaux</p>
                    </div>
                        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Transaction -->
<div class="modal fade" id="ajouterTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Nouvelle Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterTransactionForm" action="ajax/add_transaction_partenaire.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partenaire_id" class="form-label">
                                    <i class="fas fa-user me-1"></i> Partenaire*
                                </label>
                                <select class="form-select" id="partenaire_id" name="partenaire_id" required>
                                    <option value="">Sélectionner un partenaire</option>
                                    <?php foreach ($partenaires as $partenaire): ?>
                                        <option value="<?php echo $partenaire['id']; ?>" 
                                                data-solde="<?php echo $partenaire['solde']; ?>">
                                            <?php echo htmlspecialchars($partenaire['nom']); ?>
                                            (Solde: <?php echo number_format($partenaire['solde'] ?? 0, 2); ?> €)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="soldeActuel" class="form-text mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">
                                    <i class="fas fa-tag me-1"></i> Type de transaction*
                                </label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="typeAvance" value="AVANCE" required checked>
                                    <label class="btn btn-outline-success" for="typeAvance">
                                        <i class="fas fa-arrow-up me-1"></i> Avance
                                    </label>
                                    <input type="radio" class="btn-check" name="type" id="typeRemboursement" value="REMBOURSEMENT" required>
                                    <label class="btn btn-outline-danger" for="typeRemboursement">
                                        <i class="fas fa-arrow-down me-1"></i> Remboursement
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">
                                    <i class="fas fa-euro-sign me-1"></i> Montant*
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" id="montant" name="montant" required inputmode="decimal" pattern="[0-9]*[.,]?[0-9]*">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div id="nouveauSolde" class="form-text mt-2"></div>
                                <!-- Clavier virtuel -->
                                <div class="mt-3 virtual-keyboard">
                                    <div class="row g-2">
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">1</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">2</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">3</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">4</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">5</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">6</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">7</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">8</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">9</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">.</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-light w-100 key-btn">0</button></div>
                                        <div class="col-4"><button type="button" class="btn btn-warning w-100 key-btn-clear">C</button></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i> Description*
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                            placeholder="Détails de la transaction..." required></textarea>
                            </div>
                        </div>

                        <!-- Champ date caché -->
                        <input type="hidden" id="date_transaction" name="date_transaction" value="<?php echo date('Y-m-d H:i:s'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <div class="d-flex justify-content-between w-100">
                    <div id="transactionInfo" class="text-muted">
                        <!-- Les informations de la transaction seront affichées ici -->
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="button" id="btnEnregistrerTransaction" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Modal Historique des Transactions - Design Ultra Moderne -->
    <div class="modal fade" id="historiqueTransactionsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl" onclick="event.stopPropagation();">
            <div class="modern-history-modal" onclick="event.stopPropagation();">
                <div class="modern-history-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-history"></i>
            </div>
                        <div class="header-text">
                            <h2 class="header-title">Historique des Transactions</h2>
                            <p class="header-subtitle">
                                <span id="partenaireNom"></span>
                            </p>
                    </div>
                    </div>
                    <button type="button" class="modern-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                    </div>
                <div class="modern-history-body" onclick="event.stopPropagation();">
                    <div id="historiqueTransactions" class="modern-transactions-container" onclick="event.stopPropagation();">
                        <!-- L'historique sera chargé dynamiquement ici -->
            </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Envoyer un Lien - Design Ultra Moderne -->
    <div class="modal fade" id="envoyerLienModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
            <div class="modern-link-modal" onclick="event.stopPropagation();">
                <div class="modern-link-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-link"></i>
            </div>
                        <div class="header-text">
                            <h2 class="header-title">Envoyer un Lien</h2>
                            <p class="header-subtitle">
                                Partager par SMS - <span id="linkPartenaireNom"></span>
                            </p>
                </div>
            </div>
                    <button type="button" class="modern-close-btn" data-bs-dismiss="modal" onclick="event.stopPropagation();">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modern-link-body" onclick="event.stopPropagation();">
                    <div class="link-container">
                        <div class="link-section">
                            <label class="link-label">Lien d'accès rapide :</label>
                            <div class="link-display">
                                <input type="text" id="partnerLink" class="link-input" readonly onclick="event.stopPropagation();">
                                <button type="button" class="copy-btn" onclick="copyToClipboard(event);">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="sms-section">
                            <div class="sms-info">
                                <div class="sms-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="sms-details">
                                    <h4>Envoi automatique par SMS</h4>
                                    <p>Le lien sera envoyé automatiquement au partenaire</p>
                                    <div class="phone-display">
                                        <i class="fas fa-phone"></i>
                                        <span id="linkPartenairePhone"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="message-preview">
                                <label class="message-label">Aperçu du message SMS :</label>
                                <div class="message-content">
                                    <p>🔗 <strong><span id="previewPartenaireNom"></span></strong></p>
                                    <p>Accédez rapidement à votre espace transaction :</p>
                                    <p id="previewLink" class="preview-link"></p>
                                    <p><small>Lien sécurisé - Aucune authentification requise</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal" onclick="event.stopPropagation();">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="button" class="btn-send-sms" id="btnEnvoyerSMS" onclick="event.stopPropagation();">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer par SMS
                        </button>
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
            <div class="modern-modal-body">
                <!-- En-tête du modal avec actions -->
                <div class="modal-actions-header">
                    <button type="button" class="modern-btn modern-btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterPartenaireModal">
                        <i class="fas fa-user-plus"></i>
                        <span>Ajouter un Partenaire</span>
                    </button>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchPartenaires" class="modern-search-input" placeholder="Rechercher un partenaire...">
                    </div>
                    <button type="button" class="modern-btn modern-btn-refresh" onclick="chargerPartenaires()" title="Actualiser">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <!-- Tableau moderne des partenaires -->
                <div class="modern-partners-container">
                    <div class="modern-table-header">
                        <div class="table-col col-name">Partenaire</div>
                        <div class="table-col col-contact">Contact</div>
                        <div class="table-col col-status">Statut</div>
                        <div class="table-col col-balance">Solde</div>
                        <div class="table-col col-actions">Actions</div>
                    </div>
                    
                    <div class="modern-table-body" id="listePartenaires">
                        <!-- Les partenaires seront chargés ici par JavaScript -->
                        <div class="loading-partners">
                            <div class="loading-spinner"></div>
                            <p>Chargement des partenaires...</p>
                        </div>
                    </div>
                    
                    <!-- État vide -->
                    <div class="empty-partners-state" id="emptyPartenaires" style="display: none;">
                        <div class="empty-icon">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <h3>Aucun partenaire trouvé</h3>
                        <p>Commencez par ajouter votre premier partenaire commercial</p>
                        <button type="button" class="modern-btn modern-btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterPartenaireModal">
                            <i class="fas fa-user-plus"></i>
                            Ajouter le premier partenaire
                        </button>
                    </div>
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
                    <form id="ajouterPartenaireForm" method="POST">
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
                    <button type="button" id="btnEnregistrerPartenaire" class="btn btn-primary">Enregistrer</button>
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

    /* Modern Table Container */
    .modern-table-container {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 24px;
        padding: 0;
        border: none;
        box-shadow: 
            0 20px 60px rgba(0,0,0,0.08),
            0 0 0 1px rgba(255,255,255,0.1);
        overflow: hidden;
        position: relative;
    }

    .modern-table-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
    }

    /* Table Header */
    .table-header {
        padding: 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .table-title-section {
        flex: 1;
    }

    .table-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #2d3748;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .title-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .table-subtitle {
        color: #718096;
        margin: 0;
        font-size: 0.95rem;
    }

    /* Search Container */
    .table-controls {
        flex-shrink: 0;
    }

    .search-container {
        position: relative;
        width: 300px;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        z-index: 2;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 3rem;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-size: 0.95rem;
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 
            0 0 0 3px rgba(102, 126, 234, 0.1),
            0 4px 16px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    /* Modern Table Wrapper */
    .modern-table-wrapper {
        padding: 1rem;
    }

    .table-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Table Row */
    .table-row {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
    }

    .table-row::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, #667eea, transparent);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .table-row:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 
            0 20px 40px rgba(0,0,0,0.1),
            0 0 0 1px rgba(102, 126, 234, 0.1);
    }

    .table-row:hover::before {
        transform: scaleX(1);
    }

    .row-content {
        display: grid;
        grid-template-columns: 2fr 1fr 1.5fr 1fr;
        gap: 2rem;
        align-items: center;
    }

    /* Partner Info */
    .partner-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .partner-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        flex-shrink: 0;
    }

    .partner-details {
        flex: 1;
    }

    .partner-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 0.25rem 0;
    }

    .partner-status {
        font-size: 0.85rem;
        color: #718096;
        margin: 0;
    }

    /* Balance Info */
    .balance-info {
        text-align: center;
    }

    .balance-label {
        font-size: 0.8rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .balance-amount {
        font-size: 1.3rem;
        font-weight: 800;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        display: inline-block;
    }

    .balance-amount.positive {
        color: #38a169;
        background: linear-gradient(135deg, rgba(56, 161, 105, 0.1), rgba(56, 161, 105, 0.05));
    }

    .balance-amount.negative {
        color: #e53e3e;
        background: linear-gradient(135deg, rgba(229, 62, 62, 0.1), rgba(229, 62, 62, 0.05));
    }

    /* Transaction Info */
    .transaction-info {
        text-align: center;
    }

    .transaction-label {
        font-size: 0.8rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .transaction-details {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .transaction-date {
        font-size: 0.9rem;
        font-weight: 600;
        color: #4a5568;
    }

    .transaction-amount {
        font-size: 0.85rem;
        color: #718096;
    }

    .no-transaction {
        font-size: 0.85rem;
        color: #a0aec0;
        font-style: italic;
    }

    .error-transaction {
        font-size: 0.85rem;
        color: #e53e3e;
    }

    /* Actions */
    .actions-info {
        display: flex;
        justify-content: center;
    }

    .action-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        cursor: pointer;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        background: linear-gradient(135deg, #5a6fd8, #6b5b95);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 3rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 1rem 0;
    }

    .empty-description {
        font-size: 1rem;
        color: #718096;
        margin: 0;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .row-content {
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1.5rem;
        }
        
        .transaction-info {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .table-header {
            flex-direction: column;
            gap: 1.5rem;
            align-items: stretch;
        }
        
        .search-container {
            width: 100%;
        }
        
        .row-content {
            grid-template-columns: 1fr;
            gap: 1rem;
            text-align: center;
        }
        
        .partner-info {
            justify-content: center;
        }
        
        .table-row {
            padding: 1rem;
        }
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

    /* Modern History Modal Styles */
    .modern-history-modal {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 24px;
        box-shadow: 
            0 25px 80px rgba(0,0,0,0.15),
            0 0 0 1px rgba(255,255,255,0.1);
        overflow: hidden;
        border: none;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .modern-history-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .modern-history-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        pointer-events: none;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        z-index: 2;
        position: relative;
    }

    .header-icon {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .header-text {
        flex: 1;
    }

    .header-title {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0 0 0.5rem 0;
        color: white;
    }

    .header-subtitle {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
        font-weight: 500;
    }

    .modern-close-btn {
        background: rgba(255,255,255,0.2);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        z-index: 2;
        position: relative;
    }

    .modern-close-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
    }

    .modern-history-body {
        padding: 0;
        flex: 1;
        overflow-y: auto;
        max-height: calc(90vh - 120px);
    }

    .modern-transactions-container {
        padding: 2rem;
    }

    /* Balance Summary */
    .balance-summary {
        margin-bottom: 2rem;
    }

    .balance-card {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 20px;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }

    .balance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .balance-card.positive .balance-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .balance-card.negative .balance-icon {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .balance-icon {
        width: 70px;
        height: 70px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .balance-info {
        flex: 1;
    }

    .balance-label {
        font-size: 0.9rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .balance-amount {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1f2937;
    }

    .balance-card.positive .balance-amount {
        color: #10b981;
    }

    .balance-card.negative .balance-amount {
        color: #ef4444;
    }

    /* Modern Transactions Grid */
    .modern-transactions-grid {
        display: grid;
        gap: 1.5rem;
    }

    .transaction-card {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
    }

    .transaction-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .transaction-card.positive::before {
        background: linear-gradient(90deg, #10b981, #059669);
    }

    .transaction-card.negative::before {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }

    .transaction-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .transaction-card:hover::before {
        transform: scaleX(1);
    }

    .transaction-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .transaction-type {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .type-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .transaction-card.positive .type-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .transaction-card.negative .type-icon {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .type-info {
        flex: 1;
    }

    .type-label {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .type-date {
        font-size: 0.9rem;
        color: #6b7280;
        font-weight: 500;
    }

    .transaction-amount {
        font-size: 1.5rem;
        font-weight: 800;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        display: inline-block;
    }

    .transaction-amount.positive {
        color: #10b981;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
    }

    .transaction-amount.negative {
        color: #ef4444;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
    }

    .transaction-body {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 1rem;
    }

    .transaction-description {
        flex: 1;
        font-size: 0.95rem;
        color: #4b5563;
        line-height: 1.5;
        padding: 1rem;
        background: rgba(0,0,0,0.02);
        border-radius: 12px;
        border-left: 3px solid #e5e7eb;
    }

    .transaction-description em {
        color: #9ca3af;
        font-style: italic;
    }

    .transaction-time {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Empty State */
    .empty-transactions {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 3rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 1rem 0;
    }

    .empty-description {
        font-size: 1rem;
        color: #6b7280;
        margin: 0;
    }

    /* Error State */
    .error-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .error-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
        font-size: 3rem;
    }

    .error-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 1rem 0;
    }

    .error-description {
        font-size: 1rem;
        color: #6b7280;
        margin: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .modern-history-header {
            padding: 1.5rem;
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .modern-transactions-container {
            padding: 1rem;
        }
        
        .balance-card {
            flex-direction: column;
            text-align: center;
            padding: 1.5rem;
        }
        
        .transaction-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .transaction-amount {
            text-align: center;
            font-size: 1.3rem;
        }
        
        .transaction-body {
            flex-direction: column;
            gap: 1rem;
        }
        
        .transaction-time {
            justify-content: center;
        }
    }

    /* Modern Link Modal Styles */
    .modern-link-modal {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 24px;
        box-shadow: 
            0 25px 80px rgba(0,0,0,0.15),
            0 0 0 1px rgba(255,255,255,0.1);
        overflow: hidden;
        border: none;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .modern-link-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        padding: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .modern-link-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="linkdots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23linkdots)"/></svg>');
        pointer-events: none;
    }

    .modern-link-body {
        padding: 2rem;
        flex: 1;
        overflow-y: auto;
    }

    .link-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .link-section {
        background: linear-gradient(145deg, #f8f9fa, #ffffff);
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .link-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .link-display {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .link-input {
        flex: 1;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.9rem;
        font-family: 'Courier New', monospace;
        background: #f9fafb;
        color: #6b7280;
        transition: all 0.3s ease;
    }

    .link-input:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .copy-btn {
        padding: 1rem;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .copy-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
    }

    .sms-section {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .sms-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.05));
        padding: 1.5rem;
        border-radius: 16px;
        border-left: 4px solid #22c55e;
    }

    .sms-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
    }

    .sms-details h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 0.5rem 0;
    }

    .sms-details p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0 0 1rem 0;
    }

    .phone-display {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #22c55e;
    }

    .message-preview {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .message-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .message-content {
        background: #f9fafb;
        padding: 1.5rem;
        border-radius: 12px;
        border-left: 4px solid #4f46e5;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .message-content p {
        margin: 0 0 0.75rem 0;
        line-height: 1.5;
    }

    .message-content p:last-child {
        margin-bottom: 0;
    }

    .preview-link {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #4f46e5;
        word-break: break-all;
        background: rgba(79, 70, 229, 0.1);
        padding: 0.5rem;
        border-radius: 6px;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn-cancel {
        padding: 0.875rem 1.5rem;
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
        color: #4b5563;
    }

    .btn-send-sms {
        padding: 0.875rem 1.5rem;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .btn-send-sms:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
    }

    .btn-send-sms:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Responsive pour le modal lien */
    @media (max-width: 768px) {
        .modern-link-header {
            padding: 1.5rem;
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .modern-link-body {
            padding: 1rem;
        }
        
        .sms-info {
            flex-direction: column;
            text-align: center;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn-cancel, .btn-send-sms {
            justify-content: center;
        }
    }

    /* Styles pour les transactions en attente */
    .pending-transaction {
        border: 2px dashed #f59e0b !important;
        background: linear-gradient(145deg, #fefbf3, #fef3c7) !important;
        position: relative;
    }

    .pending-transaction::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f59e0b, #d97706);
        animation: pendingPulse 2s ease-in-out infinite;
    }

    @keyframes pendingPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    .pending-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.5rem;
        animation: badgePulse 2s ease-in-out infinite;
    }

    @keyframes badgePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .validation-actions {
        display: flex;
        gap: 0.5rem;
        margin-left: 1rem;
    }

    .btn-validate, .btn-reject {
        width: 35px;
        height: 35px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .btn-validate {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }

    .btn-validate:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-reject:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    .btn-validate:disabled, .btn-reject:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Animation pour les boutons de validation */
    .btn-validate, .btn-reject {
        animation: buttonAppear 0.5s ease-out;
    }

    @keyframes buttonAppear {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }

        /* Styles pour les transactions en attente */
        .transaction-card.pending-transaction {
            border-left: 4px solid #f59e0b;
            background: linear-gradient(145deg, #fffbeb, #fef3c7);
            animation: pendingGlow 2s ease-in-out infinite alternate;
        }

        @keyframes pendingGlow {
            from { box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2); }
            to { box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4); }
        }

        .pending-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .validation-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-validate, .btn-reject {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-validate {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-validate:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }

        .btn-validate:disabled, .btn-reject:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Styles pour le nouveau tableau moderne des partenaires */
        .partners-table-container {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
        }

        .partners-table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        .partners-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .table-search {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .table-search i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 2;
        }

        .table-search input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            transition: all 0.3s ease;
        }

        .table-search input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-refresh {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .btn-refresh:active {
            transform: translateY(0);
        }

        .btn-refresh.loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .partners-grid {
            display: grid;
            gap: 1rem;
            animation: fadeIn 0.6s ease-out;
        }

        .partner-card {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .partner-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .partner-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.15);
        }

        .partner-card:hover::before {
            opacity: 1;
        }

        .partner-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .partner-info {
            flex: 1;
        }

        .partner-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .partner-contact {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .contact-item i {
            width: 16px;
            color: #667eea;
        }

        .partner-status {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .partner-status.active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .partner-status.inactive {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .partner-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }

        .btn-partner-action {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex: 1;
            justify-content: center;
        }

        .btn-partner-edit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-partner-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-partner-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-partner-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .loading-partners {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .empty-partners {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-partners h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .empty-partners p {
            font-size: 1rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .btn-add-partner {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add-partner:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Animation pour les cartes de partenaires */
        .partner-card {
            animation: slideInUp 0.6s ease-out backwards;
        }

        .partner-card:nth-child(1) { animation-delay: 0.1s; }
        .partner-card:nth-child(2) { animation-delay: 0.2s; }
        .partner-card:nth-child(3) { animation-delay: 0.3s; }
        .partner-card:nth-child(4) { animation-delay: 0.4s; }

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

        /* Responsive */
        @media (max-width: 768px) {
            .partners-table-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .partner-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .partner-actions {
                flex-direction: column;
            }
        }

        /* Styles pour les éléments supplémentaires */
        .partner-balance {
            margin: 1rem 0;
            padding: 0.8rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-radius: 10px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .balance-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .balance-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .balance-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .error-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .error-icon {
            font-size: 3rem;
            color: #ef4444;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .error-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .error-description {
            font-size: 1rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .btn-retry {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

    /* Responsive pour les boutons de validation */
    @media (max-width: 768px) {
        .validation-actions {
            margin-left: 0;
            margin-top: 0.5rem;
            justify-content: center;
        }
        
        .transaction-amount {
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
    }
}

/* ===== STYLES MODERNES POUR LE MODAL GÉRER LES PARTENAIRES ===== */

/* Debug CSS - Très visible */
#gererPartenairesModal {
    background: red !important;
}

#gererPartenairesModal .modern-modal-body {
    padding: 0 !important;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
    border-radius: 0 0 12px 12px !important;
    overflow: hidden !important;
    margin: 0 !important;
    border: 5px solid green !important;
}

#gererPartenairesModal .modal-actions-header {
    display: flex !important;
    align-items: center !important;
    gap: 1rem !important;
    padding: 1.5rem !important;
    background: white !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.modern-btn-refresh {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    color: white;
    padding: 0.75rem;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}

.modern-btn-refresh:hover {
    transform: translateY(-2px) rotate(180deg);
    box-shadow: 0 8px 20px rgba(100, 116, 139, 0.4);
}

.modern-btn-refresh.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.search-container {
    position: relative;
    flex: 1;
    max-width: 300px;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    z-index: 10;
}

.modern-search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    background: white;
    transition: all 0.3s ease;
}

.modern-search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

#gererPartenairesModal .modern-partners-container {
    padding: 1.5rem !important;
}

#gererPartenairesModal .modern-table-header {
    display: grid !important;
    grid-template-columns: 2fr 2fr 1fr 1fr 2fr !important;
    gap: 1rem !important;
    padding: 1rem 1.5rem !important;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
    color: white !important;
    border-radius: 12px 12px 0 0 !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

#gererPartenairesModal .table-col {
    display: flex !important;
    align-items: center !important;
}

#gererPartenairesModal .modern-table-body {
    background: white !important;
    border-radius: 0 0 12px 12px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

#gererPartenairesModal .partner-row {
    display: grid !important;
    grid-template-columns: 2fr 2fr 1fr 1fr 2fr !important;
    gap: 1rem !important;
    padding: 1.5rem !important;
    border-bottom: 1px solid #f1f5f9 !important;
    transition: all 0.3s ease !important;
    animation: fadeInUp 0.5s ease forwards !important;
    opacity: 1 !important;
    transform: translateY(0) !important;
    background: yellow !important;
    border: 2px solid blue !important;
}

.partner-row:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.partner-row:last-child {
    border-bottom: none;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.partner-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.partner-name {
    font-weight: 700;
    color: #1e293b;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.partner-name i {
    color: #3b82f6;
    font-size: 1.1rem;
}

.partner-id {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.partner-contact {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #475569;
}

.contact-item i {
    width: 16px;
    text-align: center;
    color: #64748b;
}

.partner-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    width: fit-content;
}

.partner-status.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.partner-status.inactive {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.partner-balance {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
}

.balance-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.balance-amount {
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.balance-amount.positive {
    color: #10b981;
}

.balance-amount.negative {
    color: #ef4444;
}

.partner-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.action-btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.action-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.action-btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.action-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.loading-partners {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #64748b;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

.empty-partners-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
    color: #64748b;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-partners-state h3 {
    color: #475569;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.empty-partners-state p {
    margin-bottom: 2rem;
    font-size: 1rem;
}

/* États de recherche */
.no-search-results {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #64748b;
    background: white;
    border-radius: 12px;
    margin-top: 1rem;
}

.no-search-results i {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-actions-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .search-container {
        max-width: none;
    }
    
    .modern-table-header,
    .partner-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .table-col {
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .table-col:last-child {
        border-bottom: none;
    }
    
    .partner-actions {
        justify-content: flex-start;
        flex-wrap: wrap;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    const transactionForm = document.getElementById('ajouterTransactionForm');
    const btnEnregistrer = document.getElementById('btnEnregistrerTransaction');
    const montantInput = document.getElementById('montant');
        const virtualKeyboard = document.querySelector('.virtual-keyboard');
    const partenaireSelect = document.getElementById('partenaire_id');
    const typeAvance = document.getElementById('typeAvance');
    const typeRemboursement = document.getElementById('typeRemboursement');

        // Gestionnaire pour les boutons "Envoyer un lien"
        document.querySelectorAll('.send-link').forEach(button => {
        button.addEventListener('click', function() {
            const partenaireId = this.dataset.partenaireId;
            const partenaireNom = this.dataset.partenaireNom;
                const partenaireTelephone = this.dataset.partenaireTelephone;
                
                // Générer le lien simple (sans token)
                const baseUrl = window.location.origin + window.location.pathname.replace('/index.php', '');
                const partnerLink = `${baseUrl}/partner_transaction.php?pid=${partenaireId}`;
                
                // Mettre à jour les informations dans le modal
                document.getElementById('linkPartenaireNom').textContent = partenaireNom;
                document.getElementById('linkPartenairePhone').textContent = partenaireTelephone;
                document.getElementById('previewPartenaireNom').textContent = partenaireNom;
                document.getElementById('partnerLink').value = partnerLink;
                document.getElementById('previewLink').textContent = partnerLink;
                
                // Stocker les données pour l'envoi SMS
                const btnEnvoyerSMS = document.getElementById('btnEnvoyerSMS');
                btnEnvoyerSMS.setAttribute('data-partenaire-id', partenaireId);
                btnEnvoyerSMS.setAttribute('data-partenaire-nom', partenaireNom);
                btnEnvoyerSMS.setAttribute('data-partenaire-telephone', partenaireTelephone);
                btnEnvoyerSMS.setAttribute('data-link', partnerLink);
            
            // Ouvrir le modal
                const modal = new bootstrap.Modal(document.getElementById('envoyerLienModal'));
            modal.show();
        });
    });

    // Les boutons d'historique utilisent maintenant onclick="afficherHistoriqueTransactions()" directement

    // Gestionnaire du bouton Enregistrer
    if (btnEnregistrer) {
        btnEnregistrer.addEventListener('click', function() {
            console.log('Bouton Enregistrer cliqué');
            
            if (!transactionForm) {
                console.error('Le formulaire n\'existe pas');
                return;
            }
            
            // Vérifier la validité du formulaire
            if (!transactionForm.checkValidity()) {
                console.log('Formulaire invalide');
                transactionForm.reportValidity();
                return;
            }

            // Log des données du formulaire
            const formData = new FormData(transactionForm);
            console.log('Données du formulaire:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            // Désactiver le bouton pendant la soumission
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement...';

            console.log('URL de soumission:', transactionForm.action);

            // Envoyer la requête
            fetch(transactionForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status de la réponse:', response.status);
                return response.json().catch(error => {
                    console.error('Erreur parsing JSON:', error);
                    throw new Error('Erreur lors du parsing de la réponse JSON');
                });
            })
            .then(data => {
                console.log('Réponse du serveur:', data);
                if (data.success) {
                    showNotification('Transaction enregistrée avec succès', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTransactionModal'));
                    modal.hide();
                    transactionForm.reset();
                    window.location.reload();
                } else {
                    console.error('Erreur serveur:', data.message);
                    showNotification(data.message || 'Erreur lors de l\'enregistrement de la transaction', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur fetch:', error);
                showNotification('Une erreur est survenue lors de l\'enregistrement', 'danger');
            })
            .finally(() => {
                console.log('Requête terminée');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
            });
        });
    }

    // Gestion du clavier virtuel
    if (virtualKeyboard && montantInput) {
        virtualKeyboard.addEventListener('click', function(e) {
            const button = e.target.closest('.key-btn, .key-btn-clear');
            if (!button) return;
            
            e.preventDefault();
            
            if (button.classList.contains('key-btn-clear')) {
                montantInput.value = '';
            } else {
                const key = button.textContent;
                const currentValue = montantInput.value;
                
                if (key === '.' && currentValue.includes('.')) return;
                if (currentValue.includes('.') && currentValue.split('.')[1]?.length >= 2) return;
                
                montantInput.value = currentValue + key;
            }
            
            montantInput.dispatchEvent(new Event('input'));
            updateSoldeInfo();
        });
    }

    // Mise à jour des informations de solde
    function updateSoldeInfo() {
        if (!partenaireSelect || !montantInput) return;

        const soldeActuelDiv = document.getElementById('soldeActuel');
        const nouveauSoldeDiv = document.getElementById('nouveauSolde');
        const transactionInfoDiv = document.getElementById('transactionInfo');
        const selectedOption = partenaireSelect.selectedOptions[0];

        if (selectedOption && selectedOption.value) {
            const soldeActuel = parseFloat(selectedOption.dataset.solde);
            const montant = parseFloat(montantInput.value) || 0;
            const isAvance = typeAvance.checked;

            if (!isNaN(soldeActuel)) {
                soldeActuelDiv.innerHTML = `
                    <i class="fas fa-info-circle me-1"></i>
                    Solde actuel: <strong class="${soldeActuel < 0 ? 'text-danger' : 'text-success'}">
                        ${soldeActuel.toFixed(2)} €
                    </strong>`;

                if (montant > 0) {
                    const nouveauSolde = isAvance ? soldeActuel + montant : soldeActuel - montant;
                    nouveauSoldeDiv.innerHTML = `
                        <i class="fas fa-calculator me-1"></i>
                        Nouveau solde estimé: <strong class="${nouveauSolde < 0 ? 'text-danger' : 'text-success'}">
                            ${nouveauSolde.toFixed(2)} €
                        </strong>`;

                    transactionInfoDiv.innerHTML = `
                        <i class="fas fa-info-circle me-1"></i>
                        Impact: <strong class="${isAvance ? 'text-success' : 'text-danger'}">
                            ${isAvance ? '+' : '-'}${montant.toFixed(2)} €
                        </strong>`;
                }
            }
        }
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Événements pour mettre à jour les informations
    if (partenaireSelect) partenaireSelect.addEventListener('change', updateSoldeInfo);
    if (montantInput) {
        montantInput.addEventListener('input', updateSoldeInfo);
        montantInput.addEventListener('keydown', e => e.preventDefault());
    }
    if (typeAvance) typeAvance.addEventListener('change', updateSoldeInfo);
    if (typeRemboursement) typeRemboursement.addEventListener('change', updateSoldeInfo);

        // Fonction de recherche moderne
        const searchInput = document.getElementById('searchPartenaire');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const tableRows = document.querySelectorAll('.table-row');
                
                tableRows.forEach((row, index) => {
                    const partnerName = row.querySelector('.partner-name');
                    if (partnerName) {
                        const name = partnerName.textContent.toLowerCase();
                        const matches = name.includes(searchTerm);
                        
                        if (matches || searchTerm === '') {
                            row.style.display = 'block';
                            row.style.animation = `slideInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.05}s both`;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });

                // Afficher un message si aucun résultat
                const visibleRows = document.querySelectorAll('.table-row[style*="display: block"], .table-row:not([style*="display: none"])');
                const tableGrid = document.querySelector('.table-grid');
                const existingNoResults = document.querySelector('.no-results-message');
                
                if (visibleRows.length === 0 && searchTerm !== '' && tableGrid) {
                    if (!existingNoResults) {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.className = 'no-results-message empty-state';
                        noResultsDiv.innerHTML = `
                            <div class="empty-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="empty-title">Aucun partenaire trouvé</h3>
                            <p class="empty-description">Essayez avec d'autres termes de recherche</p>
                        `;
                        tableGrid.appendChild(noResultsDiv);
                    }
                } else if (existingNoResults) {
                    existingNoResults.remove();
                }
            });

            // Effet focus amélioré pour la recherche
            searchInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            searchInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        }

        // Gestionnaire pour l'ajout de partenaire
        const btnEnregistrerPartenaire = document.getElementById('btnEnregistrerPartenaire');
        const ajouterPartenaireForm = document.getElementById('ajouterPartenaireForm');

        if (btnEnregistrerPartenaire && ajouterPartenaireForm) {
            btnEnregistrerPartenaire.addEventListener('click', function() {
                console.log('Bouton Enregistrer Partenaire cliqué');
                
                // Vérifier la validité du formulaire
                if (!ajouterPartenaireForm.checkValidity()) {
                    console.log('Formulaire invalide');
                    ajouterPartenaireForm.reportValidity();
                    return;
                }

                // Désactiver le bouton pendant la soumission
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

                // Préparer les données du formulaire
                const formData = new FormData(ajouterPartenaireForm);

                console.log('Données du formulaire partenaire:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }

                // Envoyer la requête AJAX
                fetch('ajax/add_partenaire.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Status de la réponse:', response.status);
                    return response.json().catch(error => {
                        console.error('Erreur parsing JSON:', error);
                        throw new Error('Erreur lors du parsing de la réponse JSON');
                    });
                })
                .then(data => {
                    console.log('Réponse du serveur:', data);
                    if (data.success) {
                        showNotification('Partenaire ajouté avec succès !', 'success');
                        
                        // Fermer le modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterPartenaireModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Réinitialiser le formulaire
                        ajouterPartenaireForm.reset();
                        
                        // Recharger la page pour afficher le nouveau partenaire
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        console.error('Erreur serveur:', data.message);
                        showNotification(data.message || 'Erreur lors de l\'ajout du partenaire', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erreur fetch:', error);
                    showNotification('Une erreur est survenue lors de l\'enregistrement', 'danger');
                })
                .finally(() => {
                    console.log('Requête terminée');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                });
            });
        }

        // Gestionnaire pour l'envoi SMS
        const btnEnvoyerSMS = document.getElementById('btnEnvoyerSMS');
        if (btnEnvoyerSMS) {
            btnEnvoyerSMS.addEventListener('click', function(event) {
                // Empêcher la fermeture du modal
                event.preventDefault();
                event.stopPropagation();
                const partenaireId = this.getAttribute('data-partenaire-id');
                const partenaireNom = this.getAttribute('data-partenaire-nom');
                const partenaireTelephone = this.getAttribute('data-partenaire-telephone');
                const link = this.getAttribute('data-link');
                
                // Désactiver le bouton pendant l'envoi
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi en cours...';
                
                // Préparer le message SMS
                const message = `🔗 ${partenaireNom}\n\nAccédez rapidement à votre espace transaction :\n${link}\n\nLien direct - Aucune authentification requise`;
                
                // Envoyer le SMS
                fetch('ajax/send_partner_sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        partenaire_id: partenaireId,
                        telephone: partenaireTelephone,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('SMS envoyé avec succès !', 'success');
                        
                        // Fermer le modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('envoyerLienModal'));
                        if (modal) {
                            modal.hide();
                        }
                    } else {
                        console.error('Erreur envoi SMS:', data.message);
                        showNotification(data.message || 'Erreur lors de l\'envoi du SMS', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erreur fetch SMS:', error);
                    showNotification('Une erreur est survenue lors de l\'envoi du SMS', 'danger');
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer par SMS';
                });
            });
        }
    });

    // Fonctions utilitaires globales

    function copyToClipboard(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const linkInput = document.getElementById('partnerLink');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999); // Pour mobile
        
        try {
            // Méthode moderne pour copier
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(linkInput.value).then(() => {
                    showNotification('Lien copié dans le presse-papier !', 'success');
                }).catch(err => {
                    console.error('Erreur copie clipboard:', err);
                    // Fallback vers l'ancienne méthode
                    fallbackCopy();
                });
            } else {
                // Fallback pour les navigateurs plus anciens
                fallbackCopy();
            }
        } catch (err) {
            console.error('Erreur copie:', err);
            showNotification('Impossible de copier le lien', 'danger');
        }
    }

    function fallbackCopy() {
        const linkInput = document.getElementById('partnerLink');
        try {
            linkInput.focus();
            linkInput.select();
            const successful = document.execCommand('copy');
            if (successful) {
                showNotification('Lien copié dans le presse-papier !', 'success');
            } else {
                showNotification('Impossible de copier le lien', 'danger');
            }
        } catch (err) {
            console.error('Erreur fallback copy:', err);
            showNotification('Impossible de copier le lien', 'danger');
        }
    }
    
    // Fonction directe pour valider une transaction (appelée via onclick)
    window.validerTransaction = function(pendingId, action) {
        console.log('Validation transaction:', pendingId, action);
        
        const isApprove = action === 'approve';
        
        // Confirmation
        const confirmMessage = isApprove 
            ? 'Êtes-vous sûr de vouloir valider cette transaction ?' 
            : 'Êtes-vous sûr de vouloir rejeter cette transaction ?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Trouver le bouton qui a été cliqué
        const button = event.target.closest('button');
        if (button) {
            button.disabled = true;
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Envoyer la demande de validation
            fetch('ajax/validate_partner_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pending_id: pendingId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse validation:', data);
                
                if (data.success) {
                    showNotification(
                        isApprove ? 'Transaction validée avec succès !' : 'Transaction rejetée avec succès !',
                        'success'
                    );
                    
                    // Recharger l'historique après un court délai
                    setTimeout(() => {
                        const currentPartenaireNom = document.getElementById('partenaireNom').textContent;
                        console.log('Rechargement pour:', currentPartenaireNom);
                        
                        // Recharger directement l'historique
                        const partenaireId = document.getElementById('historiqueTransactionsModal').dataset.partenaireId;
                        if (partenaireId) {
                            afficherHistoriqueTransactions(partenaireId, currentPartenaireNom);
                        }
                    }, 1000);
                } else {
                    showNotification(data.message || 'Erreur lors de la validation', 'danger');
                    button.disabled = false;
                    button.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Erreur validation:', error);
                showNotification('Une erreur est survenue lors de la validation', 'danger');
                button.disabled = false;
                button.innerHTML = originalContent;
            });
        }
    }
    
    // Fonction pour afficher l'historique des transactions
    window.afficherHistoriqueTransactions = function(partenaireId, partenaireNom) {
        console.log('Affichage historique pour:', partenaireId, partenaireNom);
        
        try {
            // Mettre à jour le nom du partenaire dans le modal
            const partenaireNomEl = document.getElementById('partenaireNom');
            if (partenaireNomEl) {
                partenaireNomEl.textContent = partenaireNom;
            }
            
            // Stocker l'ID du partenaire dans le modal pour le rechargement
            const modalEl = document.getElementById('historiqueTransactionsModal');
            if (modalEl) {
                modalEl.dataset.partenaireId = partenaireId;
            }
            
            // Afficher le modal - essayer plusieurs méthodes
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                console.log('Utilisation de Bootstrap Modal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else if (typeof $ !== 'undefined') {
                console.log('Utilisation de jQuery Modal');
                $('#historiqueTransactionsModal').modal('show');
            } else {
                console.log('Affichage direct du modal');
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                
                // Ajouter backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'modal-backdrop-temp';
                document.body.appendChild(backdrop);
            }
            
            // Charger les transactions
            chargerTransactionsPartenaire(partenaireId);
            
        } catch (error) {
            console.error('Erreur afficherHistoriqueTransactions:', error);
            alert('Erreur lors de l\'ouverture du modal: ' + error.message);
        }
    }
    
    // Fonction pour charger les transactions d'un partenaire
    function chargerTransactionsPartenaire(partenaireId) {
        console.log('Chargement transactions pour partenaire:', partenaireId);
        
        const historiqueDiv = document.getElementById('historiqueTransactions');
        if (!historiqueDiv) {
            console.error('Élément historiqueTransactions non trouvé');
            return;
        }
        
        // Afficher le loading
        historiqueDiv.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Chargement de l'historique...</p>
            </div>
        `;
        
        fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${partenaireId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Données transactions reçues:', data);
                
                if (data.success) {
                    const solde = parseFloat(data.solde);
                    const soldeColor = solde >= 0 ? '#10b981' : '#ef4444';
                    const soldePrefix = solde >= 0 ? '+' : '';
                    
                    // Carte du solde
                    let soldeHtml = `
                        <div class="balance-summary-card">
                            <div class="balance-card-admin">
                                <div class="balance-label">Solde Actuel</div>
                                <div class="balance-amount" style="color: ${soldeColor}">
                                    ${soldePrefix}${Math.abs(solde).toFixed(2)} €
                                </div>
                                <div class="balance-info">
                                    ${data.pending_count > 0 ? `<div class="pending-info">${data.pending_count} transaction(s) en attente</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Transactions
                    let transactionsHtml = '';
                    if (data.transactions && data.transactions.length > 0) {
                        transactionsHtml = '<div class="transactions-grid">';
                        
                        data.transactions.forEach((transaction, index) => {
                            const date = new Date(transaction.date_transaction);
                            const dateFormatted = date.toLocaleDateString('fr-FR');
                            const timeFormatted = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                            
                            const montant = parseFloat(transaction.montant);
                            const isNegative = transaction.type === 'REMBOURSEMENT';
                            const typeClass = isNegative ? 'negative' : 'positive';
                            const typeIcon = isNegative ? 'fas fa-arrow-down' : 'fas fa-arrow-up';
                            const montantPrefix = isNegative ? '-' : '+';
                            
                            const typeLabels = {
                                'AVANCE': 'Avance',
                                'REMBOURSEMENT': 'Remboursement'
                            };
                            
                            // Vérifier si c'est une transaction en attente
                            const isPending = transaction.transaction_status === 'pending';
                            const pendingClass = isPending ? 'pending-transaction' : '';
                            const statusBadge = isPending ? '<div class="pending-badge"><i class="fas fa-clock"></i> En attente</div>' : '';
                            
                            // Boutons de validation pour les transactions en attente
                            let validationButtons = '';
                            if (isPending) {
                                validationButtons = `
                                    <div class="validation-actions" onclick="event.stopPropagation();">
                                        <button type="button" class="btn-validate" 
                                                onclick="event.stopPropagation(); validerTransaction(${transaction.pending_id}, 'approve');">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn-reject" 
                                                onclick="event.stopPropagation(); validerTransaction(${transaction.pending_id}, 'reject');">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                `;
                            }
                            
                            transactionsHtml += `
                                <div class="transaction-card ${typeClass} ${pendingClass}" style="animation-delay: ${index * 0.1}s" onclick="event.stopPropagation();">
                                    <div class="transaction-header" onclick="event.stopPropagation();">
                                        <div class="transaction-type" onclick="event.stopPropagation();">
                                            <div class="type-icon" onclick="event.stopPropagation();">
                                                <i class="${typeIcon}"></i>
                                            </div>
                                            <div class="type-info" onclick="event.stopPropagation();">
                                                <div class="type-label">${typeLabels[transaction.type] || transaction.type}</div>
                                                <div class="type-date">${dateFormatted}</div>
                                                ${statusBadge}
                                            </div>
                                        </div>
                                        <div class="transaction-amount ${typeClass}" onclick="event.stopPropagation();">
                                            ${montantPrefix}${montant.toFixed(2)} €
                                            ${validationButtons}
                                        </div>
                                    </div>
                                    <div class="transaction-body" onclick="event.stopPropagation();">
                                        <div class="transaction-description" onclick="event.stopPropagation();">
                                            ${transaction.description || '<em>Aucune description</em>'}
                                        </div>
                                        <div class="transaction-time" onclick="event.stopPropagation();">
                                            <i class="fas fa-clock"></i>
                                            ${timeFormatted}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        transactionsHtml += `</div>`;
                    } else {
                        transactionsHtml = `
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3 class="empty-title">Aucune transaction</h3>
                                <p class="empty-description">Aucune transaction enregistrée pour ce partenaire</p>
                            </div>
                        `;
                    }
                    
                    historiqueDiv.innerHTML = soldeHtml + transactionsHtml;
                } else {
                    historiqueDiv.innerHTML = `
                        <div class="error-state">
                            <div class="error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="error-title">Erreur de chargement</h3>
                            <p class="error-description">${data.message || 'Impossible de charger l\'historique des transactions'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur chargement transactions:', error);
                historiqueDiv.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">
                            <i class="fas fa-wifi-slash"></i>
                        </div>
                        <h3 class="error-title">Problème de connexion</h3>
                        <p class="error-description">Impossible de se connecter au serveur</p>
                    </div>
                `;
            });
    }
    
    // Fonction obsolète - remplacée par validerTransaction
    function addValidationHandlers() {
        console.log('addValidationHandlers appelée - utilisation de onclick directe maintenant');
    }
    
    // Fonction de test pour déboguer
    window.testModal = function() {
        console.log('Test modal...');
        try {
            const modalEl = document.getElementById('historiqueTransactionsModal');
            console.log('Modal element:', modalEl);
            console.log('Bootstrap:', typeof bootstrap);
            console.log('jQuery:', typeof $);
            
            if (modalEl) {
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                console.log('Modal affiché manuellement');
            }
        } catch (error) {
            console.error('Erreur test modal:', error);
        }
    }
    
    // Au chargement de la page, vérifier que tout est prêt
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - vérification des éléments...');
        console.log('Modal historique:', document.getElementById('historiqueTransactionsModal') ? 'TROUVÉ' : 'MANQUANT');
        console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined' ? 'OUI' : 'NON');
        console.log('Fonction afficherHistoriqueTransactions:', typeof window.afficherHistoriqueTransactions !== 'undefined' ? 'DÉFINIE' : 'MANQUANTE');
    });
    
    // Fonction pour charger la liste des partenaires - SUPPRIMÉE (restauration du tableau Bootstrap)

    // ===== FONCTIONS MODERNES POUR GÉRER LES PARTENAIRES =====
    
    // Fonction pour charger la liste des partenaires
    function chargerPartenaires() {
        console.log('Chargement des partenaires...');
        const container = document.getElementById('listePartenaires');
        const emptyState = document.getElementById('emptyPartenaires');
        const refreshBtn = document.querySelector('.modern-btn-refresh');
        
        if (!container) {
            console.error('Container listePartenaires non trouvé');
            return;
        }
        
        // Afficher le loading
        container.innerHTML = `
            <div class="loading-partners">
                <div class="loading-spinner"></div>
                <p>Chargement des partenaires...</p>
            </div>
        `;
        
        if (emptyState) {
            emptyState.style.display = 'none';
        }
        
        // Animation du bouton refresh
        if (refreshBtn) {
            refreshBtn.classList.add('loading');
        }
        
        fetch('ajax/get_partenaires.php')
            .then(response => {
                console.log('Réponse reçue:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Données partenaires:', data);
                
                if (refreshBtn) {
                    refreshBtn.classList.remove('loading');
                }
                
                if (data.success) {
                    if (data.partenaires && data.partenaires.length > 0) {
                        afficherPartenaires(data.partenaires);
                        if (emptyState) {
                            emptyState.style.display = 'none';
                        }
                    } else {
                        container.innerHTML = '';
                        if (emptyState) {
                            emptyState.style.display = 'block';
                        }
                    }
                } else {
                    container.innerHTML = `
                        <div class="no-search-results">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Erreur de chargement</h3>
                            <p>${data.message || 'Impossible de charger les partenaires'}</p>
                            <button type="button" class="modern-btn modern-btn-primary" onclick="chargerPartenaires()">
                                <i class="fas fa-redo"></i>
                                Réessayer
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des partenaires:', error);
                
                if (refreshBtn) {
                    refreshBtn.classList.remove('loading');
                }
                
                container.innerHTML = `
                    <div class="no-search-results">
                        <i class="fas fa-wifi-slash"></i>
                        <h3>Problème de connexion</h3>
                        <p>Impossible de se connecter au serveur</p>
                        <button type="button" class="modern-btn modern-btn-primary" onclick="chargerPartenaires()">
                            <i class="fas fa-redo"></i>
                            Réessayer
                        </button>
                    </div>
                `;
            });
    }
    
    // Fonction pour afficher les partenaires dans le tableau moderne
    function afficherPartenaires(partenaires) {
        console.log('Affichage de', partenaires.length, 'partenaires');
        const container = document.getElementById('listePartenaires');
        
        if (!container) {
            console.error('Container non trouvé');
            return;
        }
        
        const partnersHtml = partenaires.map((partenaire, index) => {
            return `
                <div class="partner-row" style="animation-delay: ${index * 0.1}s" data-partner-id="${partenaire.id}" data-partner-name="${partenaire.nom.toLowerCase()}">
                    <!-- Colonne Partenaire -->
                    <div class="partner-info">
                        <div class="partner-name">
                            <i class="fas fa-handshake"></i>
                            ${partenaire.nom}
                        </div>
                        <div class="partner-id">ID: ${partenaire.id}</div>
                    </div>
                    
                    <!-- Colonne Contact -->
                    <div class="partner-contact">
                        ${partenaire.email ? `
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>${partenaire.email}</span>
                            </div>
                        ` : ''}
                        ${partenaire.telephone ? `
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>${partenaire.telephone}</span>
                            </div>
                        ` : ''}
                        ${partenaire.adresse ? `
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${partenaire.adresse}</span>
                            </div>
                        ` : ''}
                        <div class="contact-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Créé le ${partenaire.date_creation_formatted}</span>
                        </div>
                    </div>
                    
                    <!-- Colonne Statut -->
                    <div class="partner-status ${partenaire.status_class}">
                        <i class="${partenaire.status_icon}"></i>
                        ${partenaire.statut_display}
                    </div>
                    
                    <!-- Colonne Solde -->
                    <div class="partner-balance">
                        <div class="balance-label">Solde</div>
                        <div class="balance-amount ${partenaire.balance_class}">
                            ${partenaire.balance_prefix}${Math.abs(parseFloat(partenaire.solde_actuel)).toFixed(2)} €
                        </div>
                    </div>
                    
                    <!-- Colonne Actions -->
                    <div class="partner-actions">
                        <button type="button" class="action-btn action-btn-primary" 
                                onclick="afficherHistoriqueTransactions(${partenaire.id}, '${partenaire.nom.replace(/'/g, '\\\'')}')"
                                title="Voir l'historique des transactions">
                            <i class="fas fa-history"></i>
                            Historique
                        </button>
                        <button type="button" class="action-btn action-btn-success" 
                                onclick="envoyerLien(${partenaire.id}, '${partenaire.nom.replace(/'/g, '\\\'')}')"
                                title="Envoyer un lien par SMS">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer un lien
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = partnersHtml;
        
        // Déclencher les animations
        setTimeout(() => {
            const rows = container.querySelectorAll('.partner-row');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.animationPlayState = 'running';
                }, index * 100);
            });
        }, 100);
    }
    
    // Fonction de recherche moderne
    function setupPartnersSearch() {
        const searchInput = document.getElementById('searchPartenaires');
        if (!searchInput) {
            console.error('Input de recherche non trouvé');
            return;
        }
        
        console.log('Configuration de la recherche des partenaires');
        
        // Supprimer les anciens event listeners
        searchInput.removeEventListener('input', handlePartnerSearch);
        
        // Ajouter le nouvel event listener
        searchInput.addEventListener('input', handlePartnerSearch);
    }
    
    // Gestionnaire de recherche
    function handlePartnerSearch(event) {
        const searchTerm = event.target.value.toLowerCase().trim();
        const partnerRows = document.querySelectorAll('.partner-row');
        const container = document.getElementById('listePartenaires');
        let visibleCount = 0;
        
        // Supprimer les anciens messages de recherche
        const existingNoResults = container.querySelector('.no-search-results');
        if (existingNoResults) {
            existingNoResults.remove();
        }
        
        partnerRows.forEach(row => {
            const partnerName = row.dataset.partnerName || '';
            const contactItems = row.querySelectorAll('.contact-item span');
            let hasMatch = partnerName.includes(searchTerm);
            
            // Rechercher aussi dans les informations de contact
            if (!hasMatch) {
                contactItems.forEach(item => {
                    if (item.textContent.toLowerCase().includes(searchTerm)) {
                        hasMatch = true;
                    }
                });
            }
            
            if (hasMatch) {
                row.style.display = 'grid';
                row.style.animation = 'fadeInUp 0.3s ease forwards';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Afficher un message si aucun résultat
        if (searchTerm && visibleCount === 0 && partnerRows.length > 0) {
            const noResultsHtml = `
                <div class="no-search-results">
                    <i class="fas fa-search"></i>
                    <h3>Aucun résultat trouvé</h3>
                    <p>Aucun partenaire ne correspond à "<strong>${searchTerm}</strong>"</p>
                    <button type="button" class="modern-btn modern-btn-primary" onclick="document.getElementById('searchPartenaires').value = ''; handlePartnerSearch({target: {value: ''}});">
                        <i class="fas fa-times"></i>
                        Effacer la recherche
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', noResultsHtml);
        }
    }
    
    // Initialisation du modal des partenaires
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initialisation du modal des partenaires...');
        
        // Charger les partenaires quand le modal s'ouvre
        const gererModal = document.getElementById('gererPartenairesModal');
        if (gererModal) {
            gererModal.addEventListener('shown.bs.modal', function() {
                console.log('Modal partenaires ouvert - chargement des données...');
                chargerPartenaires();
                setupPartnersSearch();
            });
        } else {
            console.error('Modal gererPartenairesModal non trouvé');
        }
        
        // Test de disponibilité des éléments
        console.log('Éléments trouvés:');
        console.log('- Modal:', document.getElementById('gererPartenairesModal') ? 'OUI' : 'NON');
        console.log('- Container:', document.getElementById('listePartenaires') ? 'OUI' : 'NON');
        console.log('- Search input:', document.getElementById('searchPartenaires') ? 'OUI' : 'NON');
    });
    
</script>
