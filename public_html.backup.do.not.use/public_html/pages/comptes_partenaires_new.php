<?php
// Inclure la configuration de session
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    die('Erreur de connexion à la base de données du magasin');
}

// Récupérer les statistiques
$stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM partenaires WHERE actif = 1");
$stmt->execute();
$nombre_partenaires_actifs = $stmt->fetchColumn();

$stmt = $shop_pdo->prepare("SELECT SUM(solde_actuel) as total FROM soldes_partenaires");
$stmt->execute();
$solde_total = $stmt->fetchColumn() ?: 0;

// Récupérer tous les partenaires pour le tableau
$stmt = $shop_pdo->prepare("
    SELECT 
        p.id,
        p.nom,
        p.email,
        p.telephone,
        p.actif,
        COALESCE(s.solde_actuel, 0) as solde_actuel
    FROM partenaires p
    LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
    ORDER BY p.nom ASC
");
$stmt->execute();
$partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Partenaires - <?php echo $_SESSION['shop_name'] ?? 'GeekBoard'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-action {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .balance-positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .balance-negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .balance-zero {
            color: #6c757d;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-handshake me-3"></i>Gestion des Partenaires</h1>
                    <p class="mb-0">Gérez vos relations commerciales, transactions et services partenaires</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-light btn-lg me-2" data-bs-toggle="modal" data-bs-target="#gererPartenairesModal">
                        <i class="fas fa-users-cog me-2"></i>Gérer
                    </button>
                    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#ajouterTransactionModal">
                        <i class="fas fa-plus me-2"></i>Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo $nombre_partenaires_actifs; ?></div>
                    <div>Partenaires Actifs</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo number_format($solde_total, 2); ?> €</div>
                    <div>Solde Global</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo count($partenaires); ?></div>
                    <div>Total Partenaires</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo date('m/Y'); ?></div>
                    <div>Période</div>
                </div>
            </div>
        </div>

        <!-- Tableau des Partenaires -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="fas fa-list me-2"></i>Liste des Partenaires</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterPartenaireModal">
                    <i class="fas fa-user-plus me-2"></i>Nouveau Partenaire
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Partenaire</th>
                            <th>Contact</th>
                            <th>Statut</th>
                            <th>Solde</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partenaires as $partenaire): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($partenaire['nom']); ?></strong>
                                <br><small class="text-muted">ID: <?php echo $partenaire['id']; ?></small>
                            </td>
                            <td>
                                <?php if ($partenaire['email']): ?>
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($partenaire['email']); ?><br>
                                <?php endif; ?>
                                <?php if ($partenaire['telephone']): ?>
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($partenaire['telephone']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($partenaire['actif']): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle me-1"></i>Actif
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times-circle me-1"></i>Inactif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $solde = (float)$partenaire['solde_actuel'];
                                $class = $solde > 0 ? 'balance-positive' : ($solde < 0 ? 'balance-negative' : 'balance-zero');
                                $prefix = $solde > 0 ? '+' : '';
                                ?>
                                <span class="<?php echo $class; ?>">
                                    <?php echo $prefix . number_format($solde, 2); ?> €
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action" 
                                        onclick="afficherHistoriqueTransactions(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-history"></i> Historique
                                </button>
                                <button class="btn btn-sm btn-success btn-action" 
                                        onclick="envoyerLien(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-paper-plane"></i> Envoyer un lien
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partenaires as $partenaire): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($partenaire['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($partenaire['email'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($partenaire['telephone'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if ($partenaire['actif']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-1" 
                                                onclick="afficherHistoriqueTransactions(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-history"></i> Historique
                                        </button>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="envoyerLien(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-link"></i> Envoyer un lien
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                <form id="ajouterPartenaireForm" method="POST" action="ajax/add_partenaire.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom du partenaire *</label>
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
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="actif" name="actif" checked>
                            <label class="form-check-label" for="actif">
                                Partenaire actif
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="ajouterPartenaire()">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Transaction -->
    <div class="modal fade" id="ajouterTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2 text-success"></i>
                        Nouvelle Transaction
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="ajouterTransactionForm" method="POST" action="ajax/add_transaction_partenaires.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="partenaire_id" class="form-label">Partenaire *</label>
                            <select class="form-control" id="partenaire_id" name="partenaire_id" required>
                                <option value="">Sélectionner un partenaire</option>
                                <?php foreach ($partenaires as $partenaire): ?>
                                    <option value="<?php echo $partenaire['id']; ?>">
                                        <?php echo htmlspecialchars($partenaire['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type de transaction *</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Sélectionner le type</option>
                                <option value="debit">Débit (ce que le partenaire nous doit)</option>
                                <option value="credit">Crédit (ce que nous devons au partenaire)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="montant" class="form-label">Montant (€) *</label>
                            <input type="number" class="form-control" id="montant" name="montant" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" onclick="ajouterTransaction()">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Historique des Transactions -->
    <div class="modal fade" id="historiqueTransactionsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl" onclick="event.stopPropagation();">
            <div class="modal-content" onclick="event.stopPropagation();">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2 text-primary"></i>
                        Historique des Transactions - <span id="partenaireNom"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="historiqueTransactions">
                        <!-- Contenu chargé dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Envoyer un Lien -->
    <div class="modal fade" id="envoyerLienModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog" onclick="event.stopPropagation();">
            <div class="modal-content" onclick="event.stopPropagation();">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2 text-success"></i>
                        Envoyer un lien - <span id="partenaireNomLien"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Lien d'accès partenaire :</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="lienPartenaire" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copierLien()">
                                <i class="fas fa-copy"></i> Copier
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="numeroTelephone" class="form-label">Numéro de téléphone :</label>
                        <input type="tel" class="form-control" id="numeroTelephone" placeholder="Ex: 06 12 34 56 78">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-success" onclick="envoyerSMS()">
                        <i class="fas fa-sms"></i> Envoyer par SMS
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales
        let currentPartenaireId = null;
        let currentPartenaireNom = '';

        // Fonction pour ajouter un partenaire
        function ajouterPartenaire() {
            const form = document.getElementById('ajouterPartenaireForm');
            const formData = new FormData(form);
            
            fetch('ajax/add_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Partenaire ajouté avec succès !');
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        // Fonction pour ajouter une transaction
        function ajouterTransaction() {
            const form = document.getElementById('ajouterTransactionForm');
            const formData = new FormData(form);
            
            fetch('ajax/add_transaction_partenaires.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction ajoutée avec succès !');
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        // Fonction pour afficher l'historique des transactions
        function afficherHistoriqueTransactions(partenaireId, partenaireNom) {
            currentPartenaireId = partenaireId;
            currentPartenaireNom = partenaireNom;
            
            document.getElementById('partenaireNom').textContent = partenaireNom;
            
            const modal = new bootstrap.Modal(document.getElementById('historiqueTransactionsModal'));
            modal.show();
            
            // Charger les transactions
            chargerTransactionsPartenaire(partenaireId);
        }

        // Fonction pour charger les transactions d'un partenaire
        function chargerTransactionsPartenaire(partenaireId) {
            const historiqueDiv = document.getElementById('historiqueTransactions');
            historiqueDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Chargement...</p></div>';
            
            fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        afficherTransactions(data);
                    } else {
                        historiqueDiv.innerHTML = '<div class="alert alert-danger">Erreur de chargement : ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    historiqueDiv.innerHTML = '<div class="alert alert-danger">Erreur de connexion</div>';
                });
        }

        // Fonction pour afficher les transactions
        function afficherTransactions(data) {
            const historiqueDiv = document.getElementById('historiqueTransactions');
            const solde = parseFloat(data.solde);
            const soldeClass = solde >= 0 ? 'text-success' : 'text-danger';
            const soldePrefix = solde >= 0 ? '+' : '';
            
            let html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Solde Actuel</h5>
                                <h3 class="${soldeClass}">${soldePrefix}${Math.abs(solde).toFixed(2)} €</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Transactions</h5>
                                <h3 class="text-primary">${data.transactions.length}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.transactions && data.transactions.length > 0) {
                html += '<div class="table-responsive"><table class="table table-striped"><thead class="table-dark"><tr><th>Date</th><th>Type</th><th>Montant</th><th>Description</th><th>Statut</th></tr></thead><tbody>';
                
                data.transactions.forEach(transaction => {
                    const typeClass = transaction.type === 'credit' ? 'text-success' : 'text-danger';
                    const typeIcon = transaction.type === 'credit' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                    const statusClass = transaction.transaction_status === 'pending' ? 'warning' : 'success';
                    const statusText = transaction.transaction_status === 'pending' ? 'En attente' : 'Validée';
                    
                    html += `
                        <tr>
                            <td>${new Date(transaction.date_transaction).toLocaleDateString('fr-FR')}</td>
                            <td><span class="${typeClass}"><i class="${typeIcon} me-1"></i>${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</span></td>
                            <td class="${typeClass}">${parseFloat(transaction.montant).toFixed(2)} €</td>
                            <td>${transaction.description}</td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                html += '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune transaction trouvée</div>';
            }
            
            historiqueDiv.innerHTML = html;
        }

        // Fonction pour envoyer un lien
        function envoyerLien(partenaireId, partenaireNom) {
            currentPartenaireId = partenaireId;
            currentPartenaireNom = partenaireNom;
            
            document.getElementById('partenaireNomLien').textContent = partenaireNom;
            document.getElementById('lienPartenaire').value = `${window.location.origin}/partner_transaction.php?pid=${partenaireId}`;
            
            const modal = new bootstrap.Modal(document.getElementById('envoyerLienModal'));
            modal.show();
        }

        // Fonction pour copier le lien
        function copierLien() {
            const lienInput = document.getElementById('lienPartenaire');
            lienInput.select();
            document.execCommand('copy');
            alert('Lien copié dans le presse-papiers !');
        }

        // Fonction pour envoyer un SMS
        function envoyerSMS() {
            const telephone = document.getElementById('numeroTelephone').value;
            if (!telephone.trim()) {
                alert('Veuillez saisir un numéro de téléphone');
                return;
            }
            
            const lien = document.getElementById('lienPartenaire').value;
            
            fetch('ajax/send_partner_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `partenaire_id=${currentPartenaireId}&telephone=${encodeURIComponent(telephone)}&lien=${encodeURIComponent(lien)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('SMS envoyé avec succès !');
                    bootstrap.Modal.getInstance(document.getElementById('envoyerLienModal')).hide();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'envoi du SMS');
            });
        }
    </script>
</body>
</html>

