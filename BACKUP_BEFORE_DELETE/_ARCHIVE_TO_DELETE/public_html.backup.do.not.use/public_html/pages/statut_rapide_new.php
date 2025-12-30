<?php
// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Vérifier si l'utilisateur est déjà attribué à cette réparation
$user_id = $_SESSION['user_id'];
$est_attribue = false;

try {
    // Vérifier dans la table reparation_attributions
    $stmt = $shop_pdo->prepare("
        SELECT ra.id 
        FROM reparation_attributions ra
        WHERE ra.reparation_id = ? AND ra.employe_id = ? AND ra.date_fin IS NULL
    ");
    $stmt->execute([$reparation_id, $user_id]);
    $attribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attribution) {
        $est_attribue = true;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de l'attribution: " . $e->getMessage());
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_notes':
            $notes_techniques = clean_input($_POST['notes_techniques'] ?? '');
            try {
                $stmt = $shop_pdo->prepare("UPDATE reparations SET notes_techniques = ? WHERE id = ?");
                $stmt->execute([$notes_techniques, $reparation_id]);
                set_message("Notes techniques mises à jour avec succès!", "success");
                redirect("index.php?page=statut_rapide&id=" . $reparation_id);
            } catch (PDOException $e) {
                set_message("Erreur lors de la mise à jour des notes techniques: " . $e->getMessage(), "danger");
            }
            break;
            
        case 'restitue':
            try {
                $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
                $stmt->execute([$reparation_id]);
                $ancien_statut = $stmt->fetchColumn();
                
                $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = 'restitue', statut_categorie = 5, date_modification = NOW() WHERE id = ?");
                $stmt->execute([$reparation_id]);
                
                set_message("Réparation marquée comme restituée avec succès!", "success");
                redirect("reparations");
            } catch (PDOException $e) {
                set_message("Erreur lors du changement de statut: " . $e->getMessage(), "danger");
            }
            break;
            
        case 'gardiennage':
            try {
                $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
                $stmt->execute([$reparation_id]);
                $ancien_statut = $stmt->fetchColumn();
                
                $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = 'en_gardiennage', statut_categorie = 3, date_modification = NOW() WHERE id = ?");
                $stmt->execute([$reparation_id]);
                
                set_message("Appareil mis en gardiennage avec succès!", "success");
                redirect("index.php?page=statut_rapide&id=" . $reparation_id);
            } catch (PDOException $e) {
                set_message("Erreur lors du changement de statut: " . $e->getMessage(), "danger");
            }
            break;
    }
}

// Récupérer les photos de la réparation
$photos = [];
try {
    $stmt = $shop_pdo->prepare("SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC");
    $stmt->execute([$reparation_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des photos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut Rapide - Réparation #<?php echo $reparation_id; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .status-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></svg>');
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-2px);
        }

        .repair-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge {
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
        }

        .info-section {
            padding: 30px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4ff 100%);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .info-card.client { border-left-color: #667eea; }
        .info-card.device { border-left-color: #11998e; }
        .info-card.price { border-left-color: #f093fb; cursor: pointer; }

        .info-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .info-item i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
        }

        .price-display {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            text-align: center;
            margin: 15px 0;
        }

        .actions-section {
            padding: 30px;
        }

        .actions-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .actions-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-btn:hover::before {
            transform: scaleX(1);
        }

        .action-btn:hover {
            border-color: #667eea;
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .action-icon.notes { background: var(--primary-gradient); }
        .action-icon.price { background: var(--warning-gradient); }
        .action-icon.photos { background: var(--info-gradient); }
        .action-icon.start { background: var(--success-gradient); animation: pulse 2s infinite; }
        .action-icon.stop { background: var(--danger-gradient); animation: pulse 2s infinite; }
        .action-icon.order { background: var(--primary-gradient); }
        .action-icon.return { background: var(--success-gradient); }
        .action-icon.sms { background: var(--info-gradient); }
        .action-icon.storage { background: var(--warning-gradient); }
        .action-icon.quote { background: var(--danger-gradient); }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .action-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .action-description {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .repair-title {
                font-size: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .action-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Carte principale -->
        <div class="status-card">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-content">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="index.php?page=reparations" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="repair-title">
                            <i class="fas fa-tools"></i>
                            Réparation #<?php echo $reparation_id; ?>
                        </h1>
                    </div>
                    <div class="status-badge">
                        <?php 
                            $statusClass = 'primary';
                            if (strpos(strtolower($reparation['statut']), 'termin') !== false) {
                                $statusClass = 'success';
                            } elseif (strpos(strtolower($reparation['statut']), 'attente') !== false) {
                                $statusClass = 'warning';
                            } elseif (strpos(strtolower($reparation['statut']), 'cours') !== false) {
                                $statusClass = 'info';
                            } elseif (strpos(strtolower($reparation['statut']), 'annul') !== false) {
                                $statusClass = 'danger';
                            }
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($reparation['statut']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <div class="info-grid">
                    <!-- Carte Client -->
                    <div class="info-card client">
                        <h3><i class="fas fa-user"></i> Client</h3>
                        <div class="info-item">
                            <i class="fas fa-signature"></i>
                            <span><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                        </div>
                    </div>

                    <!-- Carte Appareil -->
                    <div class="info-card device">
                        <h3><i class="fas fa-laptop"></i> <?php echo htmlspecialchars($reparation['type_appareil']); ?></h3>
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <span><strong>Modèle:</strong> <?php echo htmlspecialchars($reparation['modele']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-sticky-note"></i>
                            <span><strong>Note interne:</strong> 
                                <?php echo (!empty($reparation['notes_techniques']) && trim($reparation['notes_techniques']) !== '') ? 
                                    '<span class="text-success"><i class="fas fa-check-circle"></i> Oui</span>' : 
                                    '<span class="text-danger"><i class="fas fa-times-circle"></i> Non</span>'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><strong>Problème:</strong> <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?></span>
                        </div>
                        <?php if (!empty($reparation['mot_de_passe'])): ?>
                        <div class="info-item">
                            <i class="fas fa-key"></i>
                            <span><strong>Mot de passe:</strong> <?php echo htmlspecialchars($reparation['mot_de_passe']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Carte Prix -->
                    <div class="info-card price" onclick="openPriceModal()" id="priceCard">
                        <h3><i class="fas fa-euro-sign"></i> Prix</h3>
                        <div class="price-display" id="priceDisplay">
                            <?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 0, '', ' ') . ' €' : 'Non défini'; ?>
                        </div>
                        <small style="color: #718096; text-align: center; display: block;">Cliquer pour modifier</small>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="actions-section">
                <div class="actions-title">
                    <h2>Actions Rapides</h2>
                    <p class="text-muted">Choisissez l'action à effectuer</p>
                </div>

                <div class="actions-grid">
                    <!-- Notes Internes -->
                    <div class="action-btn" onclick="openNotesModal()">
                        <div class="action-icon notes">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <h3 class="action-title">Notes Internes</h3>
                        <p class="action-description">Afficher et modifier les notes techniques internes</p>
                    </div>

                    <!-- Prix -->
                    <div class="action-btn" onclick="openPriceModal()">
                        <div class="action-icon price">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h3 class="action-title">Prix</h3>
                        <p class="action-description">Modifier le prix de la réparation</p>
                    </div>

                    <!-- Photos -->
                    <div class="action-btn" onclick="openPhotosModal()">
                        <div class="action-icon photos">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3 class="action-title">Photos</h3>
                        <p class="action-description">Ajouter et afficher les photos de la réparation</p>
                    </div>

                    <!-- Démarrer/Arrêter -->
                    <?php if (!$est_attribue): ?>
                    <div class="action-btn" onclick="startRepair()">
                        <div class="action-icon start">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <h3 class="action-title">Démarrer</h3>
                        <p class="action-description">Commencer la réparation</p>
                    </div>
                    <?php else: ?>
                    <div class="action-btn" onclick="stopRepair()">
                        <div class="action-icon stop">
                            <i class="fas fa-stop-circle"></i>
                        </div>
                        <h3 class="action-title">Arrêter</h3>
                        <p class="action-description">Terminer la réparation</p>
                    </div>
                    <?php endif; ?>

                    <!-- Commander pièce -->
                    <div class="action-btn" onclick="openOrderModal()">
                        <div class="action-icon order">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="action-title">Commander pièce</h3>
                        <p class="action-description">Commander une pièce détachée</p>
                    </div>

                    <!-- Restitué -->
                    <div class="action-btn" onclick="markAsReturned()">
                        <div class="action-icon return">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="action-title">Restitué</h3>
                        <p class="action-description">Marquer la réparation comme restituée</p>
                    </div>

                    <!-- Envoyer SMS -->
                    <div class="action-btn" onclick="openSmsModal()">
                        <div class="action-icon sms">
                            <i class="fas fa-sms"></i>
                        </div>
                        <h3 class="action-title">Envoyer SMS</h3>
                        <p class="action-description">Envoyer un message au client</p>
                    </div>

                    <!-- Gardiennage -->
                    <div class="action-btn" onclick="markAsStorage()">
                        <div class="action-icon storage">
                            <i class="fas fa-archive"></i>
                        </div>
                        <h3 class="action-title">Gardiennage</h3>
                        <p class="action-description">Placer l'appareil en gardiennage</p>
                    </div>

                    <!-- Envoyer devis -->
                    <div class="action-btn" onclick="openQuoteModal()">
                        <div class="action-icon quote">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3 class="action-title">Envoyer devis</h3>
                        <p class="action-description">Créer et envoyer un devis au client</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    
    <!-- Modal Notes Techniques -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sticky-note me-2"></i>Notes Techniques
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_notes">
                        <div class="mb-3">
                            <label for="notes_techniques" class="form-label">Notes internes (visibles uniquement par les techniciens) :</label>
                            <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="8" placeholder="Saisissez vos notes techniques ici..."><?php echo html_entity_decode($reparation['notes_techniques'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Prix -->
    <div class="modal fade" id="priceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-euro-sign me-2"></i>Modifier le prix
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div id="currentPrice" class="display-4 text-primary"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2) : '0.00'; ?> €</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="row g-2">
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="1">1</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="2">2</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="3">3</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="4">4</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="5">5</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="6">6</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="7">7</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="8">8</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="9">9</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 numpad-btn" data-value="0">0</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 numpad-btn" data-value=".">.</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-danger w-100" onclick="clearPrice()">C</button></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="backspacePrice()">
                                <i class="fas fa-backspace"></i>
                            </button>
                            <button type="button" class="btn btn-success w-100" onclick="savePrice()" style="height: 150px;">
                                <i class="fas fa-check fa-2x"></i><br>Valider
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Photos -->
    <div class="modal fade" id="photosModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>Photos de la réparation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Ajouter une photo</h6>
                            <div class="mb-3">
                                <input type="file" class="form-control" id="photoInput" accept="image/*" multiple>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="uploadPhotos()">
                                <i class="fas fa-upload me-1"></i>Télécharger
                            </button>
                        </div>
                        <div class="col-md-8">
                            <h6>Photos existantes</h6>
                            <div class="row" id="photosGrid">
                                <?php foreach ($photos as $photo): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <img src="<?php echo htmlspecialchars($photo['chemin_fichier']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($photo['date_upload'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($photos)): ?>
                                <div class="col-12">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-images fa-3x mb-3"></i>
                                        <p>Aucune photo disponible</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal SMS -->
    <div class="modal fade" id="smsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sms me-2"></i>Envoyer SMS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom'] . ' - ' . $reparation['client_telephone']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="smsMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="smsMessage" rows="4" placeholder="Tapez votre message ici..."></textarea>
                        <div class="form-text">
                            <span id="charCount">0</span>/160 caractères
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="sendSms()">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Commande Pièce -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-cart me-2"></i>Commander une pièce
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        <div class="mb-3">
                            <label for="partName" class="form-label">Nom de la pièce *</label>
                            <input type="text" class="form-control" id="partName" required>
                        </div>
                        <div class="mb-3">
                            <label for="partQuantity" class="form-label">Quantité *</label>
                            <input type="number" class="form-control" id="partQuantity" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="partPrice" class="form-label">Prix estimé (€)</label>
                            <input type="number" class="form-control" id="partPrice" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="partNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="partNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitOrder()">
                        <i class="fas fa-save me-1"></i>Commander
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inclure le modal de devis depuis reparations.php -->
    <?php include BASE_PATH . '/components/modals/devis_modal_clean.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour le modal de devis (même que reparations.php) -->
    <script src="assets/js/devis-clean.js"></script>

    <script>
        let currentPrice = <?php echo !empty($reparation['prix_reparation']) ? $reparation['prix_reparation'] : 0; ?>;
        const reparationId = <?php echo $reparation_id; ?>;
        
        // Données de la réparation pour le modal de commande
        const reparationData = {
            id: <?php echo $reparation_id; ?>,
            type_appareil: <?php echo json_encode($reparation['type_appareil']); ?>,
            modele: <?php echo json_encode($reparation['modele']); ?>,
            description_probleme: <?php echo json_encode($reparation['description_probleme']); ?>,
            notes_techniques: <?php echo json_encode($reparation['notes_techniques'] ?? ''); ?>,
            client_id: <?php echo $reparation['client_id']; ?>,
            client_nom: <?php echo json_encode($reparation['client_nom']); ?>,
            client_prenom: <?php echo json_encode($reparation['client_prenom']); ?>,
            client_telephone: <?php echo json_encode($reparation['client_telephone']); ?>
        };

        // Fonctions pour les boutons d'action
        function openNotesModal() {
            const modal = new bootstrap.Modal(document.getElementById('notesModal'));
            modal.show();
        }

        function openPriceModal() {
            const modal = new bootstrap.Modal(document.getElementById('priceModal'));
            modal.show();
        }

        function openPhotosModal() {
            const modal = new bootstrap.Modal(document.getElementById('photosModal'));
            modal.show();
        }

        function openSmsModal() {
            const modal = new bootstrap.Modal(document.getElementById('smsModal'));
            modal.show();
        }

        function openOrderModal() {
            const modal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
            modal.show();
        }

        function openQuoteModal() {
            // Utiliser la fonction du modal devis clean
            if (typeof window.ouvrirDevisClean === 'function') {
                window.ouvrirDevisClean(reparationId);
            } else {
                // Fallback - ouvrir directement le modal
                const modal = new bootstrap.Modal(document.getElementById('devisModalClean'));
                const reparationIdField = document.getElementById('devis_reparation_id');
                if (reparationIdField) {
                    reparationIdField.value = reparationId;
                }
                modal.show();
            }
        }

        function startRepair() {
            if (confirm('Êtes-vous sûr de vouloir démarrer cette réparation ?')) {
                // Rediriger vers l'action de démarrage
                window.location.href = 'actions/start_repair.php?id=' + reparationId;
            }
        }

        function stopRepair() {
            if (confirm('Êtes-vous sûr de vouloir arrêter cette réparation ?')) {
                // Rediriger vers l'action d'arrêt
                window.location.href = 'actions/stop_repair.php?id=' + reparationId;
            }
        }

        function markAsReturned() {
            if (confirm('Êtes-vous sûr de vouloir marquer cette réparation comme restituée ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'restitue';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function markAsStorage() {
            if (confirm('Êtes-vous sûr de vouloir placer cet appareil en gardiennage ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'gardiennage';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Gestion du clavier numérique pour le prix
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.numpad-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const currentPriceElement = document.getElementById('currentPrice');
                    let priceText = currentPriceElement.textContent.replace(' €', '').replace(',', '.');
                    
                    if (priceText === '0.00') {
                        priceText = '';
                    }
                    
                    priceText += value;
                    
                    // Limiter à 2 décimales
                    if (priceText.includes('.')) {
                        const parts = priceText.split('.');
                        if (parts[1] && parts[1].length > 2) {
                            return;
                        }
                    }
                    
                    currentPrice = parseFloat(priceText) || 0;
                    currentPriceElement.textContent = priceText + ' €';
                });
            });

            // Gestion du compteur de caractères pour SMS
            const smsMessage = document.getElementById('smsMessage');
            if (smsMessage) {
                smsMessage.addEventListener('input', function() {
                    const count = this.value.length;
                    document.getElementById('charCount').textContent = count;
                    
                    if (count > 160) {
                        document.getElementById('charCount').style.color = 'red';
                    } else {
                        document.getElementById('charCount').style.color = '';
                    }
                });
            }
        });

        function clearPrice() {
            currentPrice = 0;
            document.getElementById('currentPrice').textContent = '0.00 €';
        }

        function backspacePrice() {
            const currentPriceElement = document.getElementById('currentPrice');
            let priceText = currentPriceElement.textContent.replace(' €', '');
            priceText = priceText.slice(0, -1);
            
            if (priceText === '' || priceText === '0') {
                priceText = '0.00';
                currentPrice = 0;
            } else {
                currentPrice = parseFloat(priceText) || 0;
            }
            
            currentPriceElement.textContent = priceText + ' €';
        }

        function savePrice() {
            // Envoyer le nouveau prix au serveur
            fetch('ajax/update_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reparation_id: reparationId,
                    price: currentPrice
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    document.getElementById('priceDisplay').textContent = currentPrice.toLocaleString('fr-FR') + ' €';
                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('priceModal')).hide();
                    // Afficher un message de succès
                    alert('Prix mis à jour avec succès !');
                    // Recharger la page pour voir les changements
                    location.reload();
                } else {
                    alert('Erreur lors de la mise à jour du prix : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du prix');
            });
        }

        function sendSms() {
            const message = document.getElementById('smsMessage').value;
            if (!message.trim()) {
                alert('Veuillez saisir un message');
                return;
            }
            
            // Envoyer le SMS
            fetch('ajax/send_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reparation_id: reparationId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('SMS envoyé avec succès !');
                    bootstrap.Modal.getInstance(document.getElementById('smsModal')).hide();
                    document.getElementById('smsMessage').value = '';
                } else {
                    alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du SMS');
            });
        }

        function submitOrder() {
            const partName = document.getElementById('partName').value;
            const partQuantity = document.getElementById('partQuantity').value;
            const partPrice = document.getElementById('partPrice').value;
            const partNotes = document.getElementById('partNotes').value;
            
            if (!partName || !partQuantity) {
                alert('Veuillez remplir les champs obligatoires');
                return;
            }
            
            // Envoyer la commande
            fetch('ajax/create_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reparation_id: reparationId,
                    part_name: partName,
                    quantity: partQuantity,
                    price: partPrice,
                    notes: partNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Commande créée avec succès !');
                    bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
                    document.getElementById('orderForm').reset();
                } else {
                    alert('Erreur lors de la création de la commande : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création de la commande');
            });
        }

        function uploadPhotos() {
            const fileInput = document.getElementById('photoInput');
            const files = fileInput.files;
            
            if (files.length === 0) {
                alert('Veuillez sélectionner au moins une photo');
                return;
            }
            
            const formData = new FormData();
            formData.append('reparation_id', reparationId);
            
            for (let i = 0; i < files.length; i++) {
                formData.append('photos[]', files[i]);
            }
            
            // Envoyer les photos
            fetch('ajax/upload_photos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Photos téléchargées avec succès !');
                    location.reload(); // Recharger pour voir les nouvelles photos
                } else {
                    alert('Erreur lors du téléchargement : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du téléchargement des photos');
            });
        }
    </script>
</body>
</html>
