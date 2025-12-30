<?php
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérifier que le shop_id est défini dans la session
if (!isset($_SESSION['shop_id'])) {
    error_log("Erreur: shop_id non défini dans la session pour commandes_pieces.php");
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier que le shop_id est valide
try {
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->prepare("SELECT id FROM shops WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['shop_id']]);
    if (!$stmt->fetch()) {
        error_log("Erreur: shop_id invalide ou inactif pour commandes_pieces.php");
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
} catch (Exception $e) {
    error_log("Erreur lors de la vérification du shop_id dans commandes_pieces.php: " . $e->getMessage());
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Toutes les fonctions utilitaires sont maintenant dans includes/functions.php

// Récupérer les commandes de pièces avec les informations associées
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("
        SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone,
         r.type_appareil, r.modele
         FROM commandes_pieces c 
         LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
         LEFT JOIN clients cl ON c.client_id = cl.id 
         LEFT JOIN reparations r ON c.reparation_id = r.id 
         ORDER BY c.date_creation DESC
    ");
    $commandes = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des commandes: " . $e->getMessage() . "</div>";
    $commandes = [];
}

// La fonction formatUrgence est maintenant dans includes/functions.php
?>

<!-- Inclure le header -->
<?php include_once 'includes/header.php'; ?>

<!-- Styles spécifiques pour le modal de commande -->
<link href="assets/css/modern-theme.css" rel="stylesheet">
<link href="assets/css/order-form.css" rel="stylesheet">

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<!-- Contenu principal de la page avec design amélioré -->
<div class="container-fluid py-4" id="mainContent" style="display: none;">
    <style>
        /* Variables CSS pour la palette de couleurs */
        :root {
            --primary: #3b82f6;
            --secondary: #64748b;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #ca8a04;
            --info: #4f46e5;
            --bg-light: #f1f5f9;
            --text-dark: #1e293b;
            --white: #ffffff;
            --card-border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        /* Styles généraux pour la page */
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }
        
        /* Titre de la page amélioré */
        .h3.mb-4 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem !important;
        }
        
        /* Style moderne pour les cartes */
        .card {
            border-radius: var(--card-border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: none;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary), rgba(59, 130, 246, 0.8));
            color: var(--white);
            font-weight: 600;
            border-bottom: none;
            padding: 1rem 1.25rem;
        }
        
        .card-footer {
            background-color: rgba(241, 245, 249, 0.5);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.25rem;
        }
        
        /* Style amélioré pour les badges de statut */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .status-badge::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: currentColor;
        }
        
        /* Style amélioré pour les badges de date */
        .date-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .date-badge:hover {
            transform: scale(1.03);
        }
        
        /* Style pour les boutons de filtre */
        .status-filter {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.4rem 0.75rem;
            transition: var(--transition);
        }
        
        .status-filter:hover, .status-filter.active {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Style pour la barre de recherche */
        #searchCommandes {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        #searchCommandes:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        
        /* Animation pour les boutons Google */
        .btn-google:hover {
            animation: pulse 1s;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Style pour les boutons d'action */
        .btn-group .btn {
            border-radius: 6px;
            margin: 0 2px;
            transition: var(--transition);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        /* Style pour le tableau des commandes */
        .table {
            border-radius: var(--card-border-radius);
            overflow: hidden;
            table-layout: fixed;
        }
        
        .table th {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 0.85rem 0.75rem;
            vertical-align: middle;
        }
        
        .table tr {
            transition: var(--transition);
        }
        
        .table tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        /* Largeurs spécifiques des colonnes */
        .table th:nth-child(1), .table td:nth-child(1) { width: 12%; } /* Client - réduit */
        .table th:nth-child(2), .table td:nth-child(2) { width: 8%; }  /* Date */
        .table th:nth-child(3), .table td:nth-child(3) { width: 12%; } /* Fournisseur */
        .table th:nth-child(4), .table td:nth-child(4) { width: 25%; } /* Pièce - augmenté */
        .table th:nth-child(5), .table td:nth-child(5) { width: 8%; }  /* Quantité */
        .table th:nth-child(6), .table td:nth-child(6) { width: 10%; } /* Prix */
        .table th:nth-child(7), .table td:nth-child(7) { width: 10%; } /* Statut */
        .table th:nth-child(8), .table td:nth-child(8) { width: 15%; } /* Actions */
        
        /* Gestion du débordement de texte */
        .table td:nth-child(1) {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .table td:nth-child(4) {
            word-wrap: break-word;
            white-space: normal;
            max-width: 0;
        }
        
        /* Avatar pour le client */
        .avatar-circle {
            width: 32px;
            height: 32px;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
        }
        
        /* Lien client plus visible */
        .client-info-link {
            color: var(--primary);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .client-info-link:hover {
            color: var(--info);
            text-decoration: underline !important;
        }
        
        /* Style pour les champs éditables */
        .editable-field {
            position: relative;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .editable-field:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .editable-field::after {
            content: "✏️";
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: var(--transition);
            font-size: 0.75rem;
        }
        
        .editable-field:hover::after {
            opacity: 1;
            right: -20px;
        }
        
        /* Modales améliorés */
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(45deg, var(--primary), var(--info));
            color: var(--white);
            border-bottom: none;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem 1.5rem;
        }
        
        /* Animation d'apparition des modales */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: scale(0.95);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        /* Effets des filtres */
        #status-filter-group {
            box-shadow: var(--shadow-sm);
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Style pour les boutons d'exportation et nouvelle commande */
        #export-pdf-btn, .btn-primary[data-bs-toggle="modal"] {
            background: linear-gradient(45deg, var(--success), #22c55e);
            border: none;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        #export-pdf-btn:hover, .btn-primary[data-bs-toggle="modal"]:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary[data-bs-toggle="modal"] {
            background: linear-gradient(45deg, var(--primary), var(--info));
        }
    </style>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Gestion des commandes de pièces</h1>
            
<!-- Modal pour changer le statut d'une commande -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true" data-commande-id="">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- En-tête du modal avec effet de dégradé -->
            <div class="modal-header position-relative py-3" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05));">
                <div class="position-absolute start-0 top-0 w-100 d-flex justify-content-center">
                    <div class="status-progress-bar"></div>
                </div>
                <h5 class="modal-title d-flex align-items-center" id="changeStatusModalLabel">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>
                    Changement de statut
                </h5>
                <div class="position-absolute end-0 top-0 p-3">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-0">
                <!-- En-tête de contexte -->
                <div class="bg-light p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-muted">Commande <span id="commandeIdText" class="text-dark fw-bold"></span></h6>
                        </div>
                        <div class="status-context" id="statusContext">
                            <!-- Le statut actuel sera affiché ici par JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Grille des options de statut -->
                <div class="p-4">
                    <p class="text-muted mb-4">Sélectionnez le nouveau statut pour cette commande:</p>
                    
                    <div class="row g-3 status-options-grid">
                        <!-- Option En attente -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="en_attente">
                                <div class="status-icon-wrapper bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">En attente</span>
                                    <small class="text-muted">Pas encore commandé</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Commandé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="commande">
                                <div class="status-icon-wrapper bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Commandé</span>
                                    <small class="text-muted">Commande en cours</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Reçu -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="recue">
                                <div class="status-icon-wrapper bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Reçu</span>
                                    <small class="text-muted">Pièce réceptionnée</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Utilisé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="utilise">
                                <div class="status-icon-wrapper bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Utilisé</span>
                                    <small class="text-muted">Pièce installée</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option À retourner -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="a_retourner">
                                <div class="status-icon-wrapper bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">À retourner</span>
                                    <small class="text-muted">Retour fournisseur</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Annulé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="annulee">
                                <div class="status-icon-wrapper bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Annulé</span>
                                    <small class="text-muted">Commande annulée</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Inputs cachés pour stocker les valeurs -->
                <input type="hidden" id="commandeIdInput" value="">
                <input type="hidden" id="currentStatusInput" value="">
            </div>
            
            <!-- Bouton pour activer/désactiver l'envoi de SMS -->
            <div class="p-4 border-top">
                <button id="smsToggleButtonStatus" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <i class="fas fa-ban me-2"></i>
                    NE PAS ENVOYER DE SMS AU CLIENT
                </button>
                <input type="hidden" id="sendSmsSwitchStatus" name="send_sms" value="0">
            </div>
            
            <div class="modal-footer border-top-0 d-flex justify-content-end py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>
            
            <!-- Filtres améliorés -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par statut</label>
                                <div class="btn-group w-100" role="group" id="status-filter-group">
                                    <button type="button" class="btn btn-outline-secondary status-filter active" data-status="all">
                                        <i class="fas fa-list me-1"></i> Tous
                                    </button>
                                    <button type="button" class="btn btn-outline-warning status-filter" data-status="en_attente">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </button>
                                    <button type="button" class="btn btn-outline-info status-filter" data-status="commande">
                                        <i class="fas fa-truck me-1"></i> Commandé
                                    </button>
                                    <button type="button" class="btn btn-outline-success status-filter" data-status="recue">
                                        <i class="fas fa-check me-1"></i> Reçu
                                    </button>
                        <button type="button" class="btn btn-outline-primary status-filter" data-status="utilise">
                            <i class="fas fa-check-double me-1"></i> Utilisé
                    </button>
                        <button type="button" class="btn btn-outline-secondary status-filter" data-status="a_retourner">
                            <i class="fas fa-undo me-1"></i> Retour
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par fournisseur</label>
                                                            <button id="nouveauFournisseurBouton" class="btn btn-outline-secondary dropdown-toggle w-100" data-bs-toggle="modal" data-bs-target="#nouveauFournisseurModal">
                                <i class="fas fa-filter"></i> Choisir un fournisseur
                            </button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par période</label>
                                                            <button id="nouveauDateBouton" class="btn btn-outline-secondary dropdown-toggle w-100" data-bs-toggle="modal" data-bs-target="#nouveauDateModal">
                                <i class="fas fa-calendar-alt"></i> Toutes les périodes
                            </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 shadow-sm rounded-start-3">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" id="searchCommandes" class="form-control form-control-lg bg-light border-0 shadow-sm py-2" placeholder="Rechercher une commande, un client, une pièce...">
                                    <button type="button" id="clearSearch" class="btn btn-light border-0 shadow-sm rounded-end-3 d-none">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableau des commandes -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><span id="commandesCount" class="badge bg-primary rounded-pill me-2"></span>Liste des commandes</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary d-flex align-items-center" id="selection-btn">
                                <i class="fas fa-check-square me-2"></i>
                                <span id="selection-btn-text">Sélectionner</span>
                            </button>
                            <button type="button" class="btn btn-success d-flex align-items-center" id="export-pdf-btn">
                                <i class="fas fa-file-pdf me-2"></i>
                                Exporter PDF
                            </button>
                            <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
                                <i class="fas fa-plus-circle me-2"></i>
                                Nouvelle commande
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="commandes-table-container">
                        <div id="commandesLoader" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="text-muted">Chargement des commandes...</p>
                        </div>
                    <!-- 🎨 TABLEAU MODERNE SANS BORDURES AVEC STRUCTURE HTML CORRECTE -->
                    <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <table style="width: 100%; border-collapse: collapse; margin: 0;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 50px;" class="selection-column d-none">
                                        <input type="checkbox" id="select-all-checkbox" class="form-check-input" style="transform: scale(1.2);">
                                    </th>
                                    <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #495057; font-size: 13px; border: none;">CLIENT</th>
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 100px;">DATE</th>
                                    <th id="fournisseur-header" style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 130px; cursor: pointer; user-select: none;" title="Cliquez pour trier par ordre alphabétique">
                                        FOURNISSEUR
                                        <i id="sort-icon" class="fas fa-sort ms-1" style="font-size: 0.8em; opacity: 0.6;"></i>
                                    </th>
                                    <th style="padding: 16px 12px; text-align: left; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 200px;">PIÈCE</th>
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 80px;">QTÉ</th>
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 100px;">PRIX</th>
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 110px;">STATUT</th>
                                    <th style="padding: 16px 12px; text-align: center; font-weight: 600; color: #495057; font-size: 13px; border: none; width: 120px;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="commandesTableBody">
                                <?php if (empty($commandes)): ?>
                                    <tr>
                                        <td colspan="9" style="padding: 40px 20px; text-align: center; border: none;">
                                            <div style="color: #6c757d; font-size: 16px; margin-bottom: 8px;">
                                                <i class="fas fa-shopping-cart" style="font-size: 24px; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                                                Aucune commande de pièces trouvée
                                            </div>
                                            <p style="color: #9ca3af; font-size: 13px; margin: 0;">Aucune commande de pièces en cours</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($commandes as $index => $commande): ?>
                                        <?php $bg_color = $index % 2 === 0 ? '#ffffff' : '#fafbfc'; ?>
                                        <tr data-fournisseur-id="<?= $commande['fournisseur_id'] ?>" 
                                            data-statut="<?= $commande['statut'] ?>" 
                                            data-date="<?= date('Y-m-d', strtotime($commande['date_creation'])) ?>" 
                                            data-search="<?= strtolower(htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom'] . ' ' . $commande['nom_piece'] . ' ' . $commande['fournisseur_nom'])) ?>"
                                            style="background: <?php echo $bg_color; ?>; transition: all 0.2s ease; border: none;" 
                                            onmouseover="this.style.backgroundColor='#e8f4fd'; this.style.transform='translateX(4px)'" 
                                            onmouseout="this.style.backgroundColor='<?php echo $bg_color; ?>'; this.style.transform='translateX(0)'">
                                            
                                            <!-- CASE À COCHER -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;" class="selection-column d-none">
                                                <input type="checkbox" class="form-check-input commande-checkbox" data-commande-id="<?= $commande['id'] ?>" style="transform: scale(1.2);">
                                            </td>
                                            
                                            <!-- CLIENT -->
                                            <td style="padding: 16px 20px; border: none; vertical-align: middle;">
                                                <div style="display: flex; align-items: center;">
                                                    <div style="width: 4px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 2px; margin-right: 16px;"></div>
                                                    <div style="display: flex; align-items: center;">
                                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                                            <i class="fas fa-user" style="color: white; font-size: 14px;"></i>
                                                        </div>
                                                        <div>
                                                            <a href="#" style="text-decoration: none; font-weight: 500; color: #212529; font-size: 14px;" class="client-info-link" 
                                                               onclick="showClientInfo(<?= $commande['client_id'] ?>, '<?= htmlspecialchars($commande['client_nom']) ?>', '<?= htmlspecialchars($commande['client_prenom']) ?>', '<?= htmlspecialchars($commande['telephone']) ?>')">
                                                                <?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?>
                                                            </a>
                                                            <?php if ($commande['reparation_id']): ?>
                                                                <div style="font-size: 12px; color: #6c757d; margin-top: 2px;">
                                                                    <span style="font-weight: 500;">Réparation Liée :</span>
                                                                    <a href="index.php?page=reparation&id=<?= $commande['reparation_id'] ?>" style="text-decoration: none; color: #6c757d; margin-left: 4px;">
                                                                        #<?= $commande['reparation_id'] ?> - <?= htmlspecialchars($commande['type_appareil'] . ' ' . $commande['modele']) ?>
                                                                    </a>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- DATE -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <span class="badge date-badge" 
                                                      data-light-color="<?= getDateColor(date('N', strtotime($commande['date_creation']))) ?>"
                                                      data-dark-color="<?= getDateColorDark(date('N', strtotime($commande['date_creation']))) ?>"
                                                      style="background-color: <?= getDateColor(date('N', strtotime($commande['date_creation']))) ?>; color: #333; font-weight: 600; padding: 8px 12px; border-radius: 20px; display: inline-block; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-size: 11px;"
                                                      title="<?= date('d/m/Y', strtotime($commande['date_creation'])) ?>">
                                                    <?= date('d/m', strtotime($commande['date_creation'])) ?>
                                                </span>
                                            </td>
                                            
                                            <!-- FOURNISSEUR -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <span class="badge editable-field" 
                                                      data-field="fournisseur_id" 
                                                      data-id="<?= $commande['id'] ?>" 
                                                      data-current-value="<?= $commande['fournisseur_id'] ?>" 
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#editFournisseurModal" 
                                                      style="background-color: <?= getSupplierColor($commande['fournisseur_id']) ?>; color: white; font-weight: 600; padding: 8px 12px; border-radius: 20px; display: inline-block; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; cursor: pointer;">
                                                    <?= htmlspecialchars($commande['fournisseur_nom']) ?>
                                                </span>
                                            </td>
                                            
                                            <!-- PIÈCE -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle;">
                                                <span class="editable-field" 
                                                      data-field="nom_piece" 
                                                      data-id="<?= $commande['id'] ?>" 
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#editFieldModal"
                                                      style="color: #495057; font-weight: 400; font-size: 13px; cursor: pointer; text-decoration: underline; text-decoration-style: dotted;">
                                                    <?= htmlspecialchars($commande['nom_piece']) ?>
                                                </span>
                                            </td>
                                            
                                            <!-- QUANTITÉ -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <div style="background: #f1f3f4; padding: 6px 10px; border-radius: 12px; display: inline-block;">
                                                    <span style="color: #495057; font-size: 12px; font-weight: 600;"><?= $commande['quantite'] ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- PRIX -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <span class="editable-field" 
                                                      data-field="prix_estime" 
                                                      data-id="<?= $commande['id'] ?>" 
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#editFieldModal"
                                                      style="color: #198754; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: underline; text-decoration-style: dotted;">
                                                    <?= number_format($commande['prix_estime'], 2, ',', ' ') ?> €
                                                </span>
                                            </td>
                                            
                                            <!-- STATUT -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <span class="badge <?= get_status_class($commande['statut']) ?> status-badge" 
                                                      data-id="<?= $commande['id'] ?>" 
                                                      data-status="<?= $commande['statut'] ?>" 
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#changeStatusModal" 
                                                      style="cursor: pointer; padding: 8px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                    <?= get_status_label($commande['statut']) ?>
                                                </span>
                                            </td>
                                            
                                            <!-- ACTIONS -->
                                            <td style="padding: 16px 12px; border: none; vertical-align: middle; text-align: center;">
                                                <div style="display: flex; gap: 4px; justify-content: center;">
                                                    <button style="background: #0d6efd; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; min-width: 32px;" 
                                                            onclick="editCommande(<?= $commande['id'] ?>)" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button style="background: #dc3545; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; min-width: 32px;" 
                                                            onclick="deleteCommande(<?= $commande['id'] ?>)" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <a href="https://www.google.com/search?q=<?= urlencode(htmlspecialchars($commande['fournisseur_nom']) . ' ' . htmlspecialchars($commande['code_barre'] ?: '') . ' ' . htmlspecialchars($commande['nom_piece'])) ?>" 
                                                       target="_blank" 
                                                       style="background: #ea4335; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; min-width: 32px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;" 
                                                       title="Rechercher '<?= htmlspecialchars($commande['fournisseur_nom'] . ' ' . $commande['code_barre'] . ' ' . $commande['nom_piece']) ?>' sur Google">
                                                        <i class="fab fa-google"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <span id="visibleRowsCount">0</span> commandes affichées sur <span id="totalRowsCount">0</span>
                        </div>
                        <button id="resetFilters" class="btn btn-outline-secondary btn-sm d-none">
                            <i class="fas fa-undo me-1"></i> Réinitialiser les filtres
                        </button>
        </div>
    </div>
</div>

<!-- Inclure le footer qui contient déjà le modal "Nouvelle commande de pièces" -->
<?php include_once 'includes/footer.php'; ?>

<!-- Bibliothèque jsPDF pour l'exportation PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<!-- Style spécifique pour le modal de statut -->
<style>
/* Style pour les cartes de statut */
.status-option-card {
    background-color: rgba(255, 255, 255, 0.5);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    height: 100%;
}

.status-option-card:hover {
    background-color: rgba(13, 110, 253, 0.05);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
}

.status-option-card:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);
}

.status-icon-wrapper {
    width: 40px;
    height: 40px;
    min-width: 40px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.status-option-card:hover .status-icon-wrapper {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Barre de progression en haut du modal */
.status-progress-bar {
    height: 3px;
    width: 95%;
    background: linear-gradient(90deg, #0d6efd, #83c5fd);
    border-radius: 0 0 4px 4px;
    opacity: 0.8;
}

/* Animation des cartes */
.status-options-grid .col-md-6 {
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    transform: translateY(10px);
}

/* Animation séquentielle des cartes */
.status-options-grid.animated .col-md-6 {
    opacity: 1;
    transform: translateY(0);
}

.status-options-grid.animated .col-md-6:nth-child(1) { transition-delay: 0.05s; }
.status-options-grid.animated .col-md-6:nth-child(2) { transition-delay: 0.1s; }
.status-options-grid.animated .col-md-6:nth-child(3) { transition-delay: 0.15s; }
.status-options-grid.animated .col-md-6:nth-child(4) { transition-delay: 0.2s; }
.status-options-grid.animated .col-md-6:nth-child(5) { transition-delay: 0.25s; }
.status-options-grid.animated .col-md-6:nth-child(6) { transition-delay: 0.3s; }

/* Style pour le statut actuel */
.current-status-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.current-status-badge i {
    margin-right: 0.35rem;
    font-size: 0.75rem;
}

        /* Styles adaptés pour le mode sombre */
        .dark-mode .status-option-card {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .dark-mode .status-option-card:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        /* Styles pour les inputs en mode nuit */
        .dark-mode input[type="text"],
        .dark-mode input[type="number"],
        .dark-mode input[type="email"],
        .dark-mode input[type="tel"],
        .dark-mode input[type="search"],
        .dark-mode input[type="date"],
        .dark-mode input[type="datetime-local"],
        .dark-mode select,
        .dark-mode textarea {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
        
        /* Placeholders en mode nuit */
        .dark-mode input::placeholder,
        .dark-mode textarea::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
            opacity: 1 !important;
        }
        
        /* Options des selects en mode nuit */
        .dark-mode select option {
            background-color: #2c3e50 !important;
            color: #ffffff !important;
        }
        
        /* Focus des inputs en mode nuit */
        .dark-mode input:focus,
        .dark-mode select:focus,
        .dark-mode textarea:focus {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
        }
        
        /* Labels en mode nuit */
        .dark-mode .form-label {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        /* Input groups en mode nuit */
        .dark-mode .input-group-text {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        
        /* Styles du tableau en mode nuit */
        .dark-mode .table {
            background-color: #2c3e50 !important;
            color: #ffffff !important;
        }
        
        .dark-mode .table thead th {
            background-color: #34495e !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .dark-mode .table tbody tr {
            background-color: #2c3e50 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .dark-mode .table tbody tr:hover {
            background-color: #34495e !important;
        }
        
        .dark-mode .table tbody td {
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Zone grise claire du tableau en mode nuit - amélioration */
        .dark-mode .card-footer {
            background-color: #1a252f !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        
        .dark-mode .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Amélioration des badges de date en mode nuit */
        .dark-mode .date-badge {
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Amélioration des badges de fournisseur en mode nuit */
        .dark-mode .badge {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Amélioration de la carte principale en mode nuit */
        .dark-mode .card {
            background-color: #2c3e50 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .dark-mode .card-header {
            background: linear-gradient(to right, #1e3a8a, rgba(30, 58, 138, 0.8)) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Amélioration des boutons en mode nuit */
        .dark-mode .btn-outline-primary {
            color: #60a5fa !important;
            border-color: #60a5fa !important;
        }
        
        .dark-mode .btn-outline-primary:hover {
            background-color: #60a5fa !important;
            border-color: #60a5fa !important;
            color: #ffffff !important;
        }
        
        .dark-mode .btn-outline-danger {
            color: #f87171 !important;
            border-color: #f87171 !important;
        }
        
        .dark-mode .btn-outline-danger:hover {
            background-color: #f87171 !important;
            border-color: #f87171 !important;
            color: #ffffff !important;
        }
        
        /* Texte des modals en mode nuit */
        .dark-mode .modal-body,
        .dark-mode .modal-footer {
            color: #ffffff !important;
        }
        
        /* Amélioration des inputs disabled en mode nuit */
        .dark-mode input:disabled,
        .dark-mode select:disabled {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* ===== STYLES TABLEAU MODE NUIT ===== */
        
        /* Container du tableau en mode nuit */
        .dark-mode .commandes-table-container > div {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
        }
        
        /* En-tête du tableau en mode nuit */
        .dark-mode table thead tr {
            background: rgba(51, 65, 85, 0.8) !important;
        }
        
        .dark-mode table thead th {
            color: #e2e8f0 !important;
            border-bottom: 1px solid rgba(148, 163, 184, 0.3) !important;
        }
        
        /* Corps du tableau en mode nuit */
        .dark-mode table tbody {
            background: rgba(30, 41, 59, 0.95) !important;
        }
        
        .dark-mode table tbody tr {
            background: rgba(30, 41, 59, 0.95) !important;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1) !important;
        }
        
        .dark-mode table tbody tr:hover {
            background: rgba(51, 65, 85, 0.6) !important;
        }
        
        .dark-mode table tbody td {
            color: #e2e8f0 !important;
            border: none !important;
        }
        
        /* Liens dans le tableau en mode nuit */
        .dark-mode table tbody td a {
            color: #93c5fd !important;
        }
        
        .dark-mode table tbody td a:hover {
            color: #60a5fa !important;
        }
        
        /* Message "Aucune commande" en mode nuit */
        .dark-mode table tbody td[colspan] div {
            color: #94a3b8 !important;
        }
        
        .dark-mode table tbody td[colspan] p {
            color: #64748b !important;
        }

/* Animation de chargement dans le bouton */
.status-option-card.loading {
    pointer-events: none;
}

.status-option-card.loading .status-icon-wrapper {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* Support des médias pour mobile */
@media (max-width: 767.98px) {
    .status-icon-wrapper {
    width: 36px;
    height: 36px;
        min-width: 36px;
    }
    
    .status-options-grid .col-md-6 {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    .status-option-card {
        padding: 10px !important;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>

<!-- Modal de mise à jour en lot -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- En-tête du modal -->
            <div class="modal-header position-relative py-3" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05));">
                <h5 class="modal-title d-flex align-items-center" id="bulkUpdateModalLabel">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Mise à jour en lot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="bg-light p-3 border-radius-8 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <span class="text-muted">
                            <span id="selectedCommandesCount">0</span> commande(s) sélectionnée(s)
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="bulkStatusSelect" class="form-label fw-bold text-muted">
                        <i class="fas fa-exchange-alt me-1"></i>
                        Nouveau statut
                    </label>
                    <select class="form-select form-select-lg" id="bulkStatusSelect">
                        <option value="">-- Sélectionner un statut --</option>
                        <option value="en_attente" data-icon="clock" data-color="#ffc107">En attente</option>
                        <option value="commande" data-icon="shopping-cart" data-color="#0d6efd">Commandée</option>
                        <option value="recue" data-icon="check-circle" data-color="#198754">Reçue</option>
                        <option value="installee" data-icon="tools" data-color="#20c997">Installée</option>
                        <option value="a_retourner" data-icon="undo" data-color="#dc3545">À retourner</option>
                    </select>
                </div>

                <div class="alert alert-info d-flex align-items-start">
                    <i class="fas fa-lightbulb me-2 mt-1"></i>
                    <div>
                        <strong>Information :</strong><br>
                        Cette action mettra à jour le statut de toutes les commandes sélectionnées.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-primary" id="confirmBulkUpdate">
                    <i class="fas fa-save me-1"></i>
                    Mettre à jour
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts spécifiques à la page -->
<!-- modal-commande.js est déjà chargé dans le footer -->
<script src="assets/js/commandes.js"></script>
<script src="assets/js/export-pdf.js"></script>
<script src="assets/js/client-functions.js"></script>
<script src="assets/js/reparation-selector.js"></script>

<!-- Script pour le changement de statut -->
<script>
// Fonction pour mettre à jour le statut d'une commande
function updateCommandeStatus(commandeId, newStatus) {
    // Vérifications préliminaires
    if (!commandeId) {
        console.error("Erreur: ID de commande manquant");
        showNotification('Identifiant de commande manquant', 'danger');
        return;
    }
    
    console.log("Mise à jour du statut:", { commande_id: commandeId, new_status: newStatus });
    
    // Ajouter une classe de chargement au bouton cliqué
    const clickedButton = document.querySelector(`.status-option-card[data-status="${newStatus}"]`);
    if (clickedButton) {
        clickedButton.classList.add('loading');
    }
    
    // Envoyer la requête au serveur
    fetch('ajax/update_commande_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ commande_id: commandeId, new_status: newStatus, shop_id: SHOP_ID }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch(e) {
                console.error("Erreur de parsing JSON:", e, "Texte reçu:", text);
                throw new Error("Format de réponse invalide");
            }
        });
    })
    .then(data => {
        if (data && data.success) {
            // Mettre à jour l'interface utilisateur
            updateUIAfterStatusChange(commandeId, newStatus);
            showNotification('Statut mis à jour avec succès', 'success');
            
            // Fermer le modal après un court délai
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
                if (modal) modal.hide();
            }, 300);
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
    })
    .finally(() => {
        // Retirer la classe de chargement
        if (clickedButton) {
            clickedButton.classList.remove('loading');
        }
    });
}

// Mise à jour de l'interface après changement de statut
function updateUIAfterStatusChange(commandeId, newStatus) {
    // Mettre à jour le badge de statut dans la liste
    const badge = document.querySelector(`.status-badge[data-id="${commandeId}"]`);
    if (badge) {
        badge.setAttribute('data-status', newStatus);
        badge.className = `badge ${getStatusClassJS(newStatus)} status-badge`;
        badge.textContent = getStatusLabelJS(newStatus);
    }
    
    // Mettre à jour l'attribut data-statut de la ligne de table
    const row = document.querySelector(`tr td .status-badge[data-id="${commandeId}"]`).closest('tr');
    if (row) {
        row.setAttribute('data-statut', newStatus);
    }
    
    // Appliquer les filtres pour mettre à jour l'affichage
    if (typeof filterCommandes === 'function' && window.currentStatusFilter) {
        filterCommandes(window.currentStatusFilter);
    }
}

// Fonction pour obtenir la classe CSS pour un statut
function getStatusClassJS(statut) {
    switch(statut) {
        case 'en_attente': return 'bg-warning text-dark';
        case 'commande': return 'bg-info text-white';
        case 'recue': return 'bg-success text-white';
        case 'annulee': return 'bg-danger text-white';
        case 'urgent': return 'bg-danger text-white';
        case 'utilise': return 'bg-primary text-white';
        case 'a_retourner': return 'bg-secondary text-white';
        default: return 'bg-secondary text-white';
    }
}

// Fonction pour obtenir le libellé d'un statut
function getStatusLabelJS(statut) {
    switch(statut) {
        case 'en_attente': return 'En attente';
        case 'commande': return 'Commandé';
        case 'recue': return 'Reçu';
        case 'annulee': return 'Annulé';
        case 'urgent': return 'URGENT';
        case 'utilise': return 'Utilisé';
        case 'a_retourner': return 'À retourner';
        default: return statut;
    }
}

// Fonction pour obtenir l'icône d'un statut
function getStatusIconJS(statut) {
    switch(statut) {
        case 'en_attente': return 'clock';
        case 'commande': return 'shopping-cart';
        case 'recue': return 'box';
        case 'annulee': return 'times';
        case 'urgent': return 'exclamation-triangle';
        case 'utilise': return 'check-double';
        case 'a_retourner': return 'undo';
        default: return 'question-circle';
    }
}
    
// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    // Créer la notification
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
// Ajouter au container de notifications
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
// Initialiser et afficher le toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
    
// Vibration pour feedback tactile
    if ('vibrate' in navigator) {
        navigator.vibrate([30, 20, 50]);
    }
}

// Gestion globale des erreurs JavaScript
window.addEventListener('error', function(event) {
    console.error('Erreur JavaScript détectée:', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error
    });
});

// Gestion des erreurs de promesses non gérées
window.addEventListener('unhandledrejection', function(event) {
    console.error('Promesse rejetée non gérée:', event.reason);
});

// Initialisation des événements une fois le DOM chargé
// Variables globales
const SHOP_ID = <?php echo json_encode($_SESSION['shop_id'] ?? 1); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas chargé !');
        return;
    }
    
    const statusModal = document.getElementById('changeStatusModal');
    
    if (statusModal) {
        // Variable pour éviter les ouvertures multiples
        let modalIsProcessing = false;
        
        // Variable pour stocker les données de la commande actuelle
        let currentCommandeData = { id: null, status: null };
        
        // Ajouter des gestionnaires d'événements sur tous les boutons de statut
        document.addEventListener('click', function(event) {
            const statusBadge = event.target.closest('.status-badge[data-id][data-status]');
            if (statusBadge) {
                currentCommandeData.id = statusBadge.getAttribute('data-id');
                currentCommandeData.status = statusBadge.getAttribute('data-status');
                console.log('Données de commande stockées:', currentCommandeData);
            }
        });
        
        // Gérer l'ouverture du modal
        statusModal.addEventListener('show.bs.modal', function(event) {
            // Éviter les ouvertures multiples simultanées
            if (modalIsProcessing) {
                console.log('Modal déjà en cours de traitement, ignoré');
                event.preventDefault();
                return;
            }
            
            modalIsProcessing = true;
            const button = event.relatedTarget;
            
            let commandeId = null;
            let currentStatus = null;
            
            // Essayer de récupérer les données du bouton déclencheur
            if (button && typeof button.getAttribute === 'function') {
                commandeId = button.getAttribute('data-id');
                currentStatus = button.getAttribute('data-status');
            }
            
            // Si pas de bouton ou données manquantes, utiliser les données stockées
            if (!commandeId || !currentStatus) {
                if (currentCommandeData.id && currentCommandeData.status) {
                    commandeId = currentCommandeData.id;
                    currentStatus = currentCommandeData.status;
                    console.log('Données récupérées depuis le stockage:', { commandeId, currentStatus });
                }
            }
            
            // Vérifier que les données sont présentes
            if (!commandeId || !currentStatus) {
                console.error("Impossible de récupérer les données de la commande");
                modalIsProcessing = false;
                return;
            }
            
            console.log("Modal ouvert pour commande:", { id: commandeId, status: currentStatus });
            
            // Mettre à jour les champs cachés
            const commandeIdInput = document.getElementById('commandeIdInput');
            const currentStatusInput = document.getElementById('currentStatusInput');
            const commandeIdText = document.getElementById('commandeIdText');
            
            if (commandeIdInput) commandeIdInput.value = commandeId;
            if (currentStatusInput) currentStatusInput.value = currentStatus;
            if (commandeIdText) commandeIdText.textContent = '#' + commandeId;
            
            // Afficher le statut actuel
            const statusContext = document.getElementById('statusContext');
            if (statusContext) {
                statusContext.innerHTML = `
                    <span class="current-status-badge ${getStatusClassJS(currentStatus)}">
                        <i class="fas fa-${getStatusIconJS(currentStatus)}"></i>
                        ${getStatusLabelJS(currentStatus)}
                    </span>
                `;
            }
            
            // Animer l'apparition des options
            const optionsGrid = document.querySelector('.status-options-grid');
            if (optionsGrid) {
                optionsGrid.classList.remove('animated');
                
                // Forcer un reflow pour réinitialiser l'animation
                void optionsGrid.offsetWidth;
                
                // Puis ajouter la classe pour lancer l'animation
                setTimeout(() => {
                    optionsGrid.classList.add('animated');
                }, 50);
            }
        });
        
        // Gérer la fermeture du modal
        statusModal.addEventListener('hidden.bs.modal', function(event) {
            modalIsProcessing = false;
            console.log('Modal fermé, réinitialisation du flag');
        });
        
        // Attacher les événements aux boutons de statut
        document.querySelectorAll('.status-option-card').forEach(button => {
            button.addEventListener('click', function() {
                // Éviter les clics multiples
                if (this.disabled) return;
                
                // Désactiver temporairement le bouton
                this.disabled = true;
                this.style.opacity = '0.6';
                
                const commandeIdInput = document.getElementById('commandeIdInput');
                const commandeId = commandeIdInput ? commandeIdInput.value : null;
                const newStatus = this.getAttribute('data-status');
                
                if (commandeId && newStatus) {
                    updateCommandeStatus(commandeId, newStatus);
                } else {
                    console.error('Données manquantes pour la mise à jour du statut:', { commandeId, newStatus });
                }
                
                // Réactiver le bouton après un délai
                setTimeout(() => {
                    this.disabled = false;
                    this.style.opacity = '1';
                }, 1000);
            });
        });
    }
});
</script>

<!-- Modal d'édition de commande -->
<div class="modal fade" id="editCommandeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Modifier la commande #<span id="edit_commande_id_display"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editCommandeForm">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <!-- Informations client -->
                    <div class="mb-4">
                        <label class="form-label">Client</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_client_name" readonly>
                            <input type="hidden" id="edit_client_id" name="client_id">
                            <button type="button" class="btn btn-outline-secondary" onclick="showClientInfo(edit_client_id.value)">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Informations de la commande -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur</label>
                            <select class="form-select" id="edit_fournisseur_id" name="fournisseur_id" required>
                                <option value="">Sélectionner un fournisseur</option>
                                <?php
                                try {
                                    $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                    while ($fournisseur = $stmt->fetch()) {
                                        echo "<option value='{$fournisseur['id']}'>" . 
                                             htmlspecialchars($fournisseur['nom']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Pièce</label>
                            <input type="text" class="form-control" id="edit_nom_piece" name="nom_piece" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Code barre</label>
                            <input type="text" class="form-control" id="edit_code_barre" name="code_barre">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Quantité</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decrementEditQuantity()">-</button>
                                <input type="number" class="form-control text-center" id="edit_quantite" name="quantite" min="1" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="incrementEditQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Prix estimé (€)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_prix_estime" name="prix_estime" step="0.01">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date de création</label>
                            <input type="datetime-local" class="form-control" id="edit_date_creation" name="date_creation" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Statut</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-warning flex-grow-1 btn-status-choice" data-status="en_attente">
                                    <i class="fas fa-clock me-1"></i> En attente
                                </button>
                                <button type="button" class="btn btn-outline-primary flex-grow-1 btn-status-choice" data-status="commande">
                                    <i class="fas fa-shopping-cart fa-lg"></i> Commandé
                                </button>
                                <button type="button" class="btn btn-outline-success flex-grow-1 btn-status-choice" data-status="recue">
                                    <i class="fas fa-box fa-lg"></i> Reçu
                                </button>
                                <button type="button" class="btn btn-outline-primary flex-grow-1 btn-status-choice" data-status="utilise">
                                    <i class="fas fa-check-double me-1"></i> Utilisé
                                </button>
                                <button type="button" class="btn btn-outline-secondary flex-grow-1 btn-status-choice" data-status="a_retourner">
                                    <i class="fas fa-undo me-1"></i> Retour
                                </button>
                                <button type="button" class="btn btn-outline-danger flex-grow-1 btn-status-choice" data-status="annulee">
                                    <i class="fas fa-times me-1"></i> Annulé
                                </button>
                            </div>
                            <input type="hidden" id="edit_statut" name="statut" value="en_attente">
                        </div>
                        
                        <!-- Bouton pour activer/désactiver l'envoi de SMS -->
                        <div class="col-12 mt-4">
                            <button id="smsToggleButton" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                <i class="fas fa-ban me-2"></i>
                                NE PAS ENVOYER DE SMS AU CLIENT
                            </button>
                            <input type="hidden" id="sendSmsSwitch" name="send_sms" value="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="updateCommande()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ✨ NOUVEAU MODAL FOURNISSEUR SIMPLE ET FONCTIONNEL ✨ -->
<div class="modal fade" id="nouveauFournisseurModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-filter me-2"></i>
                    Filtrer par fournisseur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action nouveau-fournisseur-btn" data-fournisseur="all">
                        <strong>🏪 Tous les fournisseurs</strong>
                    </button>
                    <?php
                    try {
                        $stmt = $shop_pdo->query("SELECT DISTINCT f.id, f.nom FROM fournisseurs f INNER JOIN commandes_pieces c ON f.id = c.fournisseur_id ORDER BY f.nom");
                        while ($fournisseur = $stmt->fetch()) {
                            echo '<button type="button" class="list-group-item list-group-item-action nouveau-fournisseur-btn" data-fournisseur="' . $fournisseur['id'] . '" data-nom="' . htmlspecialchars($fournisseur['nom']) . '">';
                            echo '🏪 ' . htmlspecialchars($fournisseur['nom']);
                            echo '</button>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✨ NOUVEAU MODAL DATE SIMPLE ET FONCTIONNEL ✨ -->
<div class="modal fade" id="nouveauDateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Filtrer par période
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="all">
                        <strong>📅 Toutes les périodes</strong>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="today">
                        📅 Aujourd'hui (<?= date('d/m/Y') ?>)
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="yesterday">
                        📅 Hier (<?= date('d/m/Y', strtotime('-1 day')) ?>)
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="this_week">
                        📅 Cette semaine
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="last_week">
                        📅 Semaine dernière
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="this_month">
                        📅 Ce mois (<?= date('F Y') ?>)
                    </button>
                    <button type="button" class="list-group-item list-group-item-action nouveau-date-btn" data-periode="last_month">
                        📅 Mois dernier (<?= date('F Y', strtotime('first day of last month')) ?>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ajoutons également du code JavaScript pour gérer les filtres -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation des nouveaux filtres...');
    
    // 📊 VARIABLES GLOBALES
    let filtreRecherche = '';
    let filtreStatut = 'all';
    let filtreFournisseur = 'all';
    let filtrePeriode = 'all';
    
    // 🔧 FONCTION UTILITAIRE POUR FERMER LES MODALS
    function fermerModalProprement(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            try {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    const newModalInstance = new bootstrap.Modal(modal);
                    newModalInstance.hide();
                }
                
                // Supprimer manuellement le backdrop et débloquer le body
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    console.log('✅ Modal', modalId, 'fermé et backdrop supprimé');
                }, 150);
            } catch (e) {
                console.log('Erreur fermeture modal:', e);
                // Force la suppression du backdrop en cas d'erreur
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }
    }
    
    // 🔍 FONCTION DE FILTRAGE PRINCIPALE
    function appliquerFiltres() {
        console.log('🔍 Application des filtres:', {
            recherche: filtreRecherche,
            statut: filtreStatut,
            fournisseur: filtreFournisseur,
            periode: filtrePeriode
        });
        
        const lignes = document.querySelectorAll('#commandesTableBody tr[data-fournisseur-id]');
        let lignesVisibles = 0;
        
        lignes.forEach(ligne => {
            let afficher = true;
            
            // FILTRE RECHERCHE
            if (filtreRecherche !== '') {
                const texteRecherche = ligne.getAttribute('data-search') || '';
                if (!texteRecherche.includes(filtreRecherche)) {
                    afficher = false;
                }
            }
            
            // FILTRE STATUT
            if (afficher && filtreStatut !== 'all') {
                const statutLigne = ligne.getAttribute('data-statut');
                if (statutLigne !== filtreStatut) {
                    afficher = false;
                }
            }
            
            // FILTRE FOURNISSEUR
            if (afficher && filtreFournisseur !== 'all') {
                const fournisseurLigne = ligne.getAttribute('data-fournisseur-id');
                if (fournisseurLigne !== filtreFournisseur) {
                    afficher = false;
                }
            }
            
            // FILTRE PÉRIODE
            if (afficher && filtrePeriode !== 'all') {
                const dateLigne = new Date(ligne.getAttribute('data-date'));
                const aujourdhui = new Date();
                aujourdhui.setHours(0, 0, 0, 0);
                
                switch(filtrePeriode) {
                    case 'today':
                        afficher = dateLigne.toDateString() === aujourdhui.toDateString();
                        break;
                    case 'yesterday':
                        const hier = new Date(aujourdhui);
                        hier.setDate(hier.getDate() - 1);
                        afficher = dateLigne.toDateString() === hier.toDateString();
                        break;
                    case 'this_week':
                        const debutSemaine = new Date(aujourdhui);
                        debutSemaine.setDate(aujourdhui.getDate() - aujourdhui.getDay() + 1);
                        const finSemaine = new Date(debutSemaine);
                        finSemaine.setDate(debutSemaine.getDate() + 6);
                        afficher = dateLigne >= debutSemaine && dateLigne <= finSemaine;
                        break;
                    case 'last_week':
                        const debutSemaineDerniere = new Date(aujourdhui);
                        debutSemaineDerniere.setDate(aujourdhui.getDate() - aujourdhui.getDay() - 6);
                        const finSemaineDerniere = new Date(debutSemaineDerniere);
                        finSemaineDerniere.setDate(debutSemaineDerniere.getDate() + 6);
                        afficher = dateLigne >= debutSemaineDerniere && dateLigne <= finSemaineDerniere;
                        break;
                    case 'this_month':
                        const debutMois = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), 1);
                        const finMois = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth() + 1, 0);
                        afficher = dateLigne >= debutMois && dateLigne <= finMois;
                        break;
                    case 'last_month':
                        const debutMoisDernier = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth() - 1, 1);
                        const finMoisDernier = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), 0);
                        afficher = dateLigne >= debutMoisDernier && dateLigne <= finMoisDernier;
                        break;
                }
            }
            
            // APPLIQUER LE RÉSULTAT
            if (afficher) {
                ligne.style.display = '';
                lignesVisibles++;
            } else {
                ligne.style.display = 'none';
            }
        });
        
        // METTRE À JOUR LES COMPTEURS
        const compteurVisible = document.getElementById('visibleRowsCount');
        const compteurTotal = document.getElementById('totalRowsCount');
        if (compteurVisible) compteurVisible.textContent = lignesVisibles;
        if (compteurTotal) compteurTotal.textContent = lignes.length;
        
        // BOUTON RESET
        const boutonReset = document.getElementById('resetFilters');
        if (boutonReset) {
            if (filtreRecherche !== '' || filtreStatut !== 'all' || filtreFournisseur !== 'all' || filtrePeriode !== 'all') {
                boutonReset.classList.remove('d-none');
            } else {
                boutonReset.classList.add('d-none');
            }
        }
        
        console.log('✅ Filtres appliqués:', lignesVisibles + '/' + lignes.length + ' lignes visibles');
    }
    
    // 🔍 RECHERCHE EN TEMPS RÉEL
    const champRecherche = document.getElementById('searchCommandes');
    if (champRecherche) {
        champRecherche.addEventListener('input', function() {
            filtreRecherche = this.value.toLowerCase().trim();
            console.log('🔍 Recherche:', filtreRecherche);
            appliquerFiltres();
        });
    }
    
    // 🏷️ FILTRES PAR STATUT
    const boutonsStatut = document.querySelectorAll('.status-filter');
    boutonsStatut.forEach(bouton => {
        bouton.addEventListener('click', function() {
            boutonsStatut.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtreStatut = this.getAttribute('data-status');
            console.log('🏷️ Statut:', filtreStatut);
            appliquerFiltres();
        });
    });
    
    // 🏪 FILTRES PAR FOURNISSEUR
    const boutonsFournisseur = document.querySelectorAll('.nouveau-fournisseur-btn');
    boutonsFournisseur.forEach(bouton => {
        bouton.addEventListener('click', function() {
            const idFournisseur = this.getAttribute('data-fournisseur');
            const nomFournisseur = this.getAttribute('data-nom') || 'Tous les fournisseurs';
            
            filtreFournisseur = idFournisseur;
            console.log('🏪 Fournisseur:', idFournisseur, nomFournisseur);
            
            // Mettre à jour le bouton
            const boutonPrincipal = document.getElementById('nouveauFournisseurBouton');
            if (boutonPrincipal) {
                if (idFournisseur === 'all') {
                    boutonPrincipal.innerHTML = '<i class="fas fa-filter"></i> Tous les fournisseurs';
                } else {
                    boutonPrincipal.innerHTML = '<i class="fas fa-filter"></i> ' + nomFournisseur;
                }
            }
            
            // Fermer le modal proprement
            fermerModalProprement('nouveauFournisseurModal');
            
            appliquerFiltres();
        });
    });
    
    // 📅 FILTRES PAR DATE
    const boutonsDate = document.querySelectorAll('.nouveau-date-btn');
    boutonsDate.forEach(bouton => {
        bouton.addEventListener('click', function() {
            const periode = this.getAttribute('data-periode');
            filtrePeriode = periode;
            console.log('📅 Période:', periode);
            
            // Mettre à jour le bouton
            const boutonPrincipal = document.getElementById('nouveauDateBouton');
            if (boutonPrincipal) {
                switch(periode) {
                    case 'all':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Toutes les périodes';
                        break;
                    case 'today':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Aujourd\'hui';
                        break;
                    case 'yesterday':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Hier';
                        break;
                    case 'this_week':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Cette semaine';
                        break;
                    case 'last_week':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Semaine dernière';
                        break;
                    case 'this_month':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Ce mois';
                        break;
                    case 'last_month':
                        boutonPrincipal.innerHTML = '<i class="fas fa-calendar-alt"></i> Mois dernier';
                        break;
                }
            }
            
            // Fermer le modal proprement
            fermerModalProprement('nouveauDateModal');
            
            appliquerFiltres();
        });
    });
    
    // 🔄 BOUTON RESET
    const boutonReset = document.getElementById('resetFilters');
    if (boutonReset) {
        boutonReset.addEventListener('click', function() {
            console.log('🔄 Réinitialisation...');
            
            // Reset recherche
            if (champRecherche) {
                champRecherche.value = '';
                filtreRecherche = '';
            }
            
            // Reset statut
            boutonsStatut.forEach(b => b.classList.remove('active'));
            const boutonTousStatuts = document.querySelector('.status-filter[data-status="all"]');
            if (boutonTousStatuts) boutonTousStatuts.classList.add('active');
            filtreStatut = 'all';
            
            // Reset fournisseur
            const boutonFournisseur = document.getElementById('nouveauFournisseurBouton');
            if (boutonFournisseur) {
                boutonFournisseur.innerHTML = '<i class="fas fa-filter"></i> Choisir un fournisseur';
            }
            filtreFournisseur = 'all';
            
            // Reset période
            const boutonPeriode = document.getElementById('nouveauDateBouton');
            if (boutonPeriode) {
                boutonPeriode.innerHTML = '<i class="fas fa-calendar-alt"></i> Toutes les périodes';
            }
            filtrePeriode = 'all';
            
            appliquerFiltres();
            this.classList.add('d-none');
            console.log('✅ Réinitialisation terminée');
        });
    }
    
    // 🚀 INITIALISATION AU CHARGEMENT
    setTimeout(() => {
        appliquerFiltres();
        console.log('✅ Filtres initialisés');
    }, 500);
});
</script>

<!-- Ajoutons le script JavaScript pour gérer le bouton SMS -->
<script>
// Initialisation du bouton toggle SMS après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initSmsToggleButton();
    
    // Autres initialisations existantes...
});

// Fonction pour initialiser le bouton toggle pour l'envoi de SMS
function initSmsToggleButton() {
    const toggleButton = document.getElementById('smsToggleButton');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    
    if (!toggleButton || !smsSwitch) {
        console.error('Éléments du bouton SMS toggle non trouvés');
        return;
    }
    
    // Définir l'état initial (0 = SMS désactivé)
    smsSwitch.value = '0';
    
    // Définir l'apparence initiale du bouton
    updateSmsButtonAppearance(toggleButton, false);
    
    // Ajouter l'écouteur d'événement click
    toggleButton.addEventListener('click', function() {
        // Inverser l'état actuel
        const currentState = smsSwitch.value === '1';
        const newState = !currentState;
        
        // Mettre à jour la valeur dans l'input hidden
        smsSwitch.value = newState ? '1' : '0';
        
        // Mettre à jour l'apparence du bouton
        updateSmsButtonAppearance(toggleButton, newState);
        
        // Vibration pour feedback tactile sur mobile
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }
        
        // Jouer un son de notification pour confirmer le changement
        playNotificationSound();
        
        console.log('État du SMS mis à jour:', newState ? 'Activé' : 'Désactivé');
    });
}

// Fonction pour mettre à jour l'apparence du bouton selon l'état
function updateSmsButtonAppearance(button, isSmsEnabled) {
    if (isSmsEnabled) {
        // SMS activé
        button.classList.remove('btn-danger');
        button.classList.add('btn-success');
        button.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
    } else {
        // SMS désactivé
        button.classList.remove('btn-success');
        button.classList.add('btn-danger');
        button.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
    }
}

// Fonction pour jouer un son de notification
function playNotificationSound() {
    try {
        const audio = new Audio('assets/sounds/click.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Impossible de jouer le son:', e));
    } catch (e) {
        console.log('Erreur lors de la lecture du son:', e);
    }
}

// Ajouter ces fonctions à l'objet global pour les rendre accessibles
window.smsToggle = {
    init: initSmsToggleButton,
    updateAppearance: updateSmsButtonAppearance,
    getSmsStatus: function() {
        return document.getElementById('sendSmsSwitch')?.value === '1';
    },
    setSmsStatus: function(status) {
        const smsSwitch = document.getElementById('sendSmsSwitch');
        const toggleButton = document.getElementById('smsToggleButton');
        if (smsSwitch && toggleButton) {
            smsSwitch.value = status ? '1' : '0';
            updateSmsButtonAppearance(toggleButton, status);
        }
    }
};

// Mise à jour de la fonction updateCommande pour inclure l'état du SMS
function updateCommande() {
    console.log("Début de la mise à jour de la commande...");
    
    // Récupérer l'ID de la commande
    const id = document.getElementById('edit_id').value;
    if (!id) {
        console.error('ID de commande manquant');
        showNotification('Erreur: ID de commande manquant', 'danger');
        return;
    }
    
    console.log('ID de la commande:', id);
    
    // Créer FormData et ajouter l'ID avec le bon nom
    const formData = new FormData(document.getElementById('editCommandeForm'));
    
    // *** CORRECTION: Ajouter l'ID avec le nom attendu par le serveur ***
    formData.delete('id'); // Supprimer l'ancien champ id si présent
    formData.set('commande_id', id); // Ajouter avec le nom attendu par le serveur
    
    // Si disponible, récupérer également l'état du SMS
    const smsSwitch = document.getElementById('sendSmsSwitch');
    if (smsSwitch) {
        formData.append('send_sms', smsSwitch.value);
        console.log('Mise à jour de la commande avec statut SMS:', smsSwitch.value === '1' ? 'Envoyer' : 'Ne pas envoyer');
    }
    
    // Log des données envoyées pour debug
    console.log("Données envoyées:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Afficher un indicateur de chargement
    const saveButton = document.querySelector('.modal-footer .btn-primary');
    const originalContent = saveButton.innerHTML;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement...';
    saveButton.disabled = true;
    
    // Envoyer les données au serveur
    fetch('ajax/update_commande.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Statut de la réponse:", response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            console.log("Réponse brute:", text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Erreur de parsing JSON:", e);
                throw new Error("Réponse invalide du serveur");
            }
        });
    })
    .then(data => {
        console.log("Données reçues:", data);
        
        // Restaurer le bouton
        saveButton.innerHTML = originalContent;
        saveButton.disabled = false;
        
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCommandeModal'));
            if (modal) modal.hide();
            
            // Afficher un message de succès
            showNotification('Commande mise à jour avec succès', 'success');
            
            // Rafraîchir la page pour afficher les modifications
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            console.error("Erreur serveur:", data.message);
            // Afficher un message d'erreur
            showNotification('Erreur: ' + (data.message || 'Erreur inconnue'), 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur complète:', error);
        
        // Restaurer le bouton
        saveButton.innerHTML = originalContent;
        saveButton.disabled = false;
        
        // Afficher un message d'erreur détaillé
        showNotification('Erreur de communication: ' + error.message, 'danger');
    });
}

// Mise à jour de la fonction pour initialiser tous les boutons toggle SMS après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les boutons SMS
    initAllSmsToggleButtons();
    
    // Autres initialisations existantes...
});

// Fonction pour initialiser tous les boutons toggle SMS sur la page
function initAllSmsToggleButtons() {
    // Liste des paires bouton/switch à initialiser
    const smsButtons = [
        { button: 'smsToggleButton', switch: 'sendSmsSwitch' },
        { button: 'smsToggleButtonStatus', switch: 'sendSmsSwitchStatus' },
        { button: 'smsToggleButtonAjout', switch: 'sendSmsSwitchAjout' }
    ];
    
    // Initialiser chaque bouton s'il existe
    smsButtons.forEach(pair => {
        const toggleButton = document.getElementById(pair.button);
        const smsSwitch = document.getElementById(pair.switch);
        
        if (toggleButton && smsSwitch) {
            console.log(`Initialisation du bouton SMS: ${pair.button}`);
            smsSwitch.value = '0';
            updateSmsButtonAppearance(toggleButton, false);
        }
    });
}
</script>



<!-- Modal Scanner de Code-Barres -->
<div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-bottom-0">
                <h5 class="modal-title d-flex align-items-center" id="barcodeScannerModalLabel">
                    <i class="fas fa-camera me-2"></i>
                    Scanner de Code-Barres
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Zone de la caméra -->
                <div class="scanner-container position-relative mb-4">
                    <div id="scanner-video-container" class="scanner-video-wrapper">
                        <video id="scanner-video" autoplay muted playsinline class="w-100 rounded"></video>
                        <canvas id="scanner-canvas" class="position-absolute top-0 start-0 w-100 h-100 rounded"></canvas>
                        
                        <!-- Overlay de visée -->
                        <div class="scanner-overlay">
                            <div class="scanner-target">
                                <div class="scanner-corners">
                                    <div class="corner corner-tl"></div>
                                    <div class="corner corner-tr"></div>
                                    <div class="corner corner-bl"></div>
                                    <div class="corner corner-br"></div>
                                </div>
                                <div class="scanner-line"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indicateur de statut -->
                    <div id="scanner-status" class="scanner-status mt-3 text-center">
                        <div class="spinner-border text-primary me-2" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <span class="status-text">Initialisation de la caméra...</span>
                    </div>
                </div>
                
                <!-- Résultat du scan -->
                <div id="scanner-result" class="d-none">
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-2 fs-4"></i>
                        <div>
                            <strong>Code-barres détecté !</strong>
                            <div class="mt-1">
                                <code id="scanned-barcode" class="fs-6"></code>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contrôles -->
                <div class="scanner-controls d-flex justify-content-center gap-2">
                    <button type="button" id="toggle-flashlight" class="btn btn-outline-secondary" title="Flash">
                        <i class="fas fa-flashlight"></i>
                    </button>
                    <button type="button" id="switch-camera" class="btn btn-outline-secondary" title="Changer de caméra">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" id="restart-scanner" class="btn btn-outline-primary" title="Redémarrer">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
                <button type="button" id="use-scanned-code" class="btn btn-primary d-none">
                    <i class="fas fa-check me-1"></i> Utiliser ce code
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le scanner de code-barres -->
<style>
/* Conteneur principal du scanner */
.scanner-container {
    max-width: 500px;
    margin: 0 auto;
}

.scanner-video-wrapper {
    position: relative;
    aspect-ratio: 4/3;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

#scanner-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#scanner-canvas {
    pointer-events: none;
}

/* Overlay de visée */
.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.scanner-target {
    position: relative;
    width: 250px;
    height: 150px;
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-radius: 8px;
}

/* Coins de visée animés */
.scanner-corners {
    position: absolute;
    inset: -2px;
}

.corner {
    position: absolute;
    width: 25px;
    height: 25px;
    border: 3px solid #00ff00;
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
}

.corner-tl {
    top: 0;
    left: 0;
    border-right: none;
    border-bottom: none;
    border-radius: 8px 0 0 0;
}

.corner-tr {
    top: 0;
    right: 0;
    border-left: none;
    border-bottom: none;
    border-radius: 0 8px 0 0;
}

.corner-bl {
    bottom: 0;
    left: 0;
    border-right: none;
    border-top: none;
    border-radius: 0 0 0 8px;
}

.corner-br {
    bottom: 0;
    right: 0;
    border-left: none;
    border-top: none;
    border-radius: 0 0 8px 0;
}

/* Ligne de scan animée */
.scanner-line {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #00ff00, transparent);
    box-shadow: 0 0 8px rgba(0, 255, 0, 0.8);
    animation: scan-line 2s linear infinite;
}

@keyframes scan-line {
    0% { transform: translateY(0); opacity: 1; }
    50% { opacity: 1; }
    100% { transform: translateY(146px); opacity: 0; }
}

/* Animation des coins */
.corner {
    animation: corner-pulse 2s ease-in-out infinite;
}

@keyframes corner-pulse {
    0%, 100% { border-color: #00ff00; box-shadow: 0 0 10px rgba(0, 255, 0, 0.5); }
    50% { border-color: #88ff88; box-shadow: 0 0 20px rgba(0, 255, 0, 0.8); }
}

/* Statut du scanner */
.scanner-status {
    font-size: 0.9rem;
    color: var(--bs-text-muted);
}

.scanner-status.success {
    color: var(--bs-success) !important;
}

.scanner-status.error {
    color: var(--bs-danger) !important;
}

/* Contrôles */
.scanner-controls {
    margin-top: 1rem;
}

.scanner-controls .btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mode sombre */
.dark-mode .scanner-target {
    border-color: rgba(255, 255, 255, 0.3);
}

.dark-mode .scanner-status {
    color: rgba(255, 255, 255, 0.7);
}

/* Animation de succès */
.scanner-success-animation {
    animation: success-flash 0.5s ease-in-out;
}

@keyframes success-flash {
    0% { background-color: transparent; }
    50% { background-color: rgba(0, 255, 0, 0.2); }
    100% { background-color: transparent; }
}

/* Responsive */
@media (max-width: 576px) {
    .scanner-target {
        width: 200px;
        height: 120px;
    }
    
    .corner {
        width: 20px;
        height: 20px;
    }
    
    .scanner-line {
        animation: scan-line-mobile 2s linear infinite;
    }
    
    @keyframes scan-line-mobile {
        0% { transform: translateY(0); opacity: 1; }
        50% { opacity: 1; }
        100% { transform: translateY(116px); opacity: 0; }
    }
}
</style>

<!-- Bibliothèque QuaggaJS pour le scanner de code-barres -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<!-- Script du scanner de code-barres -->
<script>
class BarcodeScanner {
    constructor() {
        this.isScanning = false;
        this.stream = null;
        this.currentCamera = 'environment'; // 'user' pour caméra avant, 'environment' pour arrière
        this.flashlightSupported = false;
        this.flashlightOn = false;
        this.onBarcodeDetected = null;
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        // Bouton pour ouvrir le scanner
        document.addEventListener('click', (e) => {
            console.log('Clic détecté sur:', e.target.id, e.target.className);
            
            if (e.target.id === 'scanBarcodeBtn' || e.target.closest('#scanBarcodeBtn')) {
                console.log('Bouton scanner cliqué !');
                e.preventDefault();
                this.openScanner();
            }
        });
        
        // Aussi écouter directement sur le bouton si il existe
        const scanBtn = document.getElementById('scanBarcodeBtn');
        if (scanBtn) {
            console.log('Bouton scanner trouvé dans le DOM');
            scanBtn.addEventListener('click', (e) => {
                console.log('Événement direct sur le bouton scanner');
                e.preventDefault();
                this.openScanner();
            });
        } else {
            console.log('Bouton scanner NON trouvé dans le DOM au chargement');
        }
        
        // Événements du modal
        const modal = document.getElementById('barcodeScannerModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', () => this.startScanner());
            modal.addEventListener('hidden.bs.modal', () => this.stopScanner());
        }
        
        // Contrôles
        document.getElementById('restart-scanner')?.addEventListener('click', () => this.restartScanner());
        document.getElementById('switch-camera')?.addEventListener('click', () => this.switchCamera());
        document.getElementById('toggle-flashlight')?.addEventListener('click', () => this.toggleFlashlight());
        document.getElementById('use-scanned-code')?.addEventListener('click', () => this.useScannedCode());
    }
    
    openScanner() {
        console.log('openScanner() appelée');
        const modalElement = document.getElementById('barcodeScannerModal');
        console.log('Modal scanner trouvé:', modalElement ? 'OUI' : 'NON');
        
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            console.log('Modal Bootstrap créé, tentative d\'ouverture...');
            modal.show();
        } else {
            console.error('Modal scanner non trouvé dans le DOM !');
            alert('Erreur: Modal scanner non trouvé');
        }
    }
    
    async startScanner() {
        try {
            this.updateStatus('Initialisation de la caméra...', 'loading');
            
            // Configuration Quagga
            const config = {
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner-video'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: this.currentCamera
                    }
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                frequency: 10,
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                },
                locate: true
            };
            
            await new Promise((resolve, reject) => {
                Quagga.init(config, (err) => {
                    if (err) {
                        console.error('Erreur initialisation Quagga:', err);
                        reject(err);
                    } else {
                        resolve();
                    }
                });
            });
            
            // Démarrer la détection
            Quagga.start();
            this.isScanning = true;
            
            // Écouter les détections
            Quagga.onDetected(this.onDetected.bind(this));
            
            this.updateStatus('Pointez la caméra vers un code-barres', 'scanning');
            
            // Vérifier le support du flash
            this.checkFlashlightSupport();
            
        } catch (error) {
            console.error('Erreur démarrage scanner:', error);
            this.updateStatus('Erreur: Impossible d\'accéder à la caméra', 'error');
        }
    }
    
    stopScanner() {
        if (this.isScanning) {
            Quagga.stop();
            this.isScanning = false;
        }
        
        // Réinitialiser l'interface
        document.getElementById('scanner-result').classList.add('d-none');
        document.getElementById('use-scanned-code').classList.add('d-none');
        this.updateStatus('Scanner arrêté', 'stopped');
    }
    
    onDetected(result) {
        if (!this.isScanning) return;
        
        const code = result.codeResult.code;
        console.log('Code-barres détecté:', code);
        
        // Animation de succès
        const container = document.querySelector('.scanner-video-wrapper');
        container.classList.add('scanner-success-animation');
        setTimeout(() => container.classList.remove('scanner-success-animation'), 500);
        
        // Vibration pour feedback tactile
        if ('vibrate' in navigator) {
            navigator.vibrate([100, 50, 100]);
        }
        
        // Son de notification
        this.playBeepSound();
        
        // Afficher le résultat
        document.getElementById('scanned-barcode').textContent = code;
        document.getElementById('scanner-result').classList.remove('d-none');
        document.getElementById('use-scanned-code').classList.remove('d-none');
        
        this.updateStatus('Code-barres détecté avec succès !', 'success');
        
        // Stocker le code pour utilisation
        this.lastScannedCode = code;
        
        // Arrêter temporairement le scan pour éviter les détections multiples
        setTimeout(() => {
            if (this.isScanning) {
                Quagga.start();
            }
        }, 1000);
    }
    
    useScannedCode() {
        if (this.lastScannedCode) {
            // Remplir le champ code-barres dans le modal principal
            const codeBarreField = document.getElementById('code_barre');
            if (codeBarreField) {
                codeBarreField.value = this.lastScannedCode;
                codeBarreField.dispatchEvent(new Event('input'));
            }
            
            // Fermer le modal scanner
            const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScannerModal'));
            if (modal) {
                modal.hide();
            }
            
            // Notification de succès
            this.showNotification('Code-barres ajouté avec succès !', 'success');
        }
    }
    
    async restartScanner() {
        this.stopScanner();
        await new Promise(resolve => setTimeout(resolve, 500));
        this.startScanner();
    }
    
    async switchCamera() {
        this.currentCamera = this.currentCamera === 'environment' ? 'user' : 'environment';
        await this.restartScanner();
        
        const cameraType = this.currentCamera === 'environment' ? 'arrière' : 'avant';
        this.showNotification(`Caméra ${cameraType} activée`, 'info');
    }
    
    async toggleFlashlight() {
        if (!this.flashlightSupported) {
            this.showNotification('Flash non supporté sur cet appareil', 'warning');
            return;
        }
        
        try {
            const track = this.stream?.getVideoTracks()[0];
            if (track && track.getCapabilities) {
                const capabilities = track.getCapabilities();
                if (capabilities.torch) {
                    this.flashlightOn = !this.flashlightOn;
                    await track.applyConstraints({
                        advanced: [{ torch: this.flashlightOn }]
                    });
                    
                    const button = document.getElementById('toggle-flashlight');
                    button.classList.toggle('btn-warning', this.flashlightOn);
                    button.innerHTML = this.flashlightOn ? 
                        '<i class="fas fa-lightbulb"></i>' : 
                        '<i class="fas fa-flashlight"></i>';
                }
            }
        } catch (error) {
            console.error('Erreur flash:', error);
            this.showNotification('Impossible de contrôler le flash', 'error');
        }
    }
    
    async checkFlashlightSupport() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            const track = stream.getVideoTracks()[0];
            
            if (track.getCapabilities) {
                const capabilities = track.getCapabilities();
                this.flashlightSupported = !!capabilities.torch;
                
                if (!this.flashlightSupported) {
                    document.getElementById('toggle-flashlight').style.display = 'none';
                }
            }
            
            // Arrêter le stream de test
            stream.getTracks().forEach(track => track.stop());
        } catch (error) {
            console.log('Vérification flash échouée:', error);
        }
    }
    
    updateStatus(message, type = 'info') {
        const statusElement = document.getElementById('scanner-status');
        const spinner = statusElement.querySelector('.spinner-border');
        const textElement = statusElement.querySelector('.status-text');
        
        textElement.textContent = message;
        
        // Gestion des classes CSS
        statusElement.className = 'scanner-status mt-3 text-center';
        
        switch (type) {
            case 'loading':
                statusElement.classList.add('text-primary');
                spinner.style.display = 'inline-block';
                break;
            case 'scanning':
                statusElement.classList.add('text-info');
                spinner.style.display = 'none';
                break;
            case 'success':
                statusElement.classList.add('text-success');
                spinner.style.display = 'none';
                break;
            case 'error':
                statusElement.classList.add('text-danger');
                spinner.style.display = 'none';
                break;
            default:
                statusElement.classList.add('text-muted');
                spinner.style.display = 'none';
        }
    }
    
    playBeepSound() {
        try {
            // Créer un bip audio synthétique
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'square';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (error) {
            console.log('Impossible de jouer le son:', error);
        }
    }
    
    showNotification(message, type = 'info') {
        // Réutiliser la fonction showNotification existante si elle existe
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Fonction pour mettre à jour les couleurs des dates selon le mode
function updateDateColors() {
    const isDarkMode = document.body.classList.contains('dark-mode');
    const dateBadges = document.querySelectorAll('.date-badge');
    
    dateBadges.forEach(badge => {
        const lightColor = badge.getAttribute('data-light-color');
        const darkColor = badge.getAttribute('data-dark-color');
        
        if (isDarkMode) {
            badge.style.backgroundColor = darkColor;
            badge.style.color = '#ffffff';
            badge.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
        } else {
            badge.style.backgroundColor = lightColor;
            badge.style.color = '#333';
            badge.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        }
    });
}

// Fonction de tri alphabétique pour la colonne Fournisseur
function sortTableByFournisseur() {
    const table = document.querySelector('.table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const sortIcon = document.getElementById('sort-icon');
    
    // Déterminer l'ordre de tri actuel
    const isCurrentlyAsc = sortIcon.classList.contains('fa-sort-up');
    const isCurrentlyDesc = sortIcon.classList.contains('fa-sort-down');
    
    // Définir le nouvel ordre
    let sortAsc = true; // Par défaut, tri croissant
    if (isCurrentlyAsc) {
        sortAsc = false; // Si déjà croissant, passer en décroissant
    } else if (isCurrentlyDesc) {
        sortAsc = true; // Si déjà décroissant, passer en croissant
    }
    
    // Filtrer les lignes non vides (ignorer la ligne "Aucune commande trouvée")
    const validRows = rows.filter(row => {
        const fournisseurCell = row.cells[2]; // 3ème colonne (index 2)
        return fournisseurCell && fournisseurCell.textContent.trim() !== '';
    });
    
    // Trier les lignes
    validRows.sort((a, b) => {
        const fournisseurA = a.cells[2].textContent.trim().toLowerCase();
        const fournisseurB = b.cells[2].textContent.trim().toLowerCase();
        
        if (sortAsc) {
            return fournisseurA.localeCompare(fournisseurB, 'fr', { numeric: true });
        } else {
            return fournisseurB.localeCompare(fournisseurA, 'fr', { numeric: true });
        }
    });
    
    // Mettre à jour l'icône de tri
    sortIcon.className = sortAsc ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
    sortIcon.style.opacity = '1';
    sortIcon.style.color = sortAsc ? '#28a745' : '#dc3545';
    
    // Réorganiser les lignes dans le tableau
    validRows.forEach(row => tbody.appendChild(row));
    
    // Ajouter un effet visuel temporaire
    const header = document.getElementById('fournisseur-header');
    header.style.backgroundColor = sortAsc ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)';
    header.style.transition = 'background-color 0.3s ease';
    
    // Notification
    const direction = sortAsc ? 'croissant (A→Z)' : 'décroissant (Z→A)';
    showNotification(`Tableau trié par fournisseur en ordre ${direction}`, 'success');
    
    // Réinitialiser le style après l'animation
    setTimeout(() => {
        header.style.backgroundColor = '';
        sortIcon.style.color = '';
        sortIcon.style.opacity = '0.8';
    }, 1000);
    
    // Mettre à jour le compteur de lignes visibles
    updateRowCounts();
}

// Observer pour détecter les changements de mode nuit
const themeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            updateDateColors();
        }
    });
});

// Initialiser le scanner au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation du scanner...');
    window.barcodeScanner = new BarcodeScanner();
    console.log('Scanner initialisé:', window.barcodeScanner);
    
    // Initialiser les couleurs des dates
    updateDateColors();
    
    // Ajouter l'événement de clic pour le tri des fournisseurs
    const fournisseurHeader = document.getElementById('fournisseur-header');
    if (fournisseurHeader) {
        fournisseurHeader.addEventListener('click', sortTableByFournisseur);
        console.log('Événement de tri des fournisseurs ajouté');
    }
    
    // Observer les changements de classe sur le body pour détecter le changement de mode
    themeObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Vérifier si les éléments existent
    setTimeout(() => {
        const scanBtn = document.getElementById('scanBarcodeBtn');
        const scanModal = document.getElementById('barcodeScannerModal');
        const commandModal = document.getElementById('ajouterCommandeModal');
        
        console.log('Vérification des éléments:');
        console.log('- Bouton scanner:', scanBtn ? 'EXISTE' : 'MANQUANT');
        console.log('- Modal scanner:', scanModal ? 'EXISTE' : 'MANQUANT');
        console.log('- Modal commande:', commandModal ? 'EXISTE' : 'MANQUANT');
        
        if (scanBtn) {
            console.log('Bouton scanner classes:', scanBtn.className);
            console.log('Bouton scanner parent:', scanBtn.parentElement);
        }
    }, 1000);
    
    // ===== GESTION DE LA SÉLECTION MULTIPLE =====
    
    let selectionMode = false;
    let selectedCommandes = new Set();
    
    // Bouton Sélectionner/Mettre à jour
    const selectionBtn = document.getElementById('selection-btn');
    const selectionBtnText = document.getElementById('selection-btn-text');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    // Gestion du clic sur le bouton Sélectionner/Mettre à jour
    selectionBtn.addEventListener('click', function() {
        if (!selectionMode) {
            // Activer le mode sélection
            activateSelectionMode();
        } else {
            // Ouvrir le modal de mise à jour
            if (selectedCommandes.size > 0) {
                openBulkUpdateModal();
            } else {
                showNotification('Veuillez sélectionner au moins une commande', 'warning');
            }
        }
    });
    
    // Activer le mode sélection
    function activateSelectionMode() {
        selectionMode = true;
        
        // Changer l'apparence du bouton
        selectionBtn.classList.remove('btn-outline-primary');
        selectionBtn.classList.add('btn-warning');
        selectionBtnText.textContent = 'Mettre à jour';
        selectionBtn.querySelector('i').className = 'fas fa-edit me-2';
        
        // Afficher les colonnes de sélection
        document.querySelectorAll('.selection-column').forEach(col => {
            col.classList.remove('d-none');
        });
        
        console.log('Mode sélection activé');
    }
    
    // Désactiver le mode sélection
    function deactivateSelectionMode() {
        selectionMode = false;
        selectedCommandes.clear();
        
        // Restaurer l'apparence du bouton
        selectionBtn.classList.remove('btn-warning');
        selectionBtn.classList.add('btn-outline-primary');
        selectionBtnText.textContent = 'Sélectionner';
        selectionBtn.querySelector('i').className = 'fas fa-check-square me-2';
        
        // Masquer les colonnes de sélection
        document.querySelectorAll('.selection-column').forEach(col => {
            col.classList.add('d-none');
        });
        
        // Décocher toutes les cases
        document.querySelectorAll('.commande-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        
        console.log('Mode sélection désactivé');
    }
    
    // Gestion de la case "Tout sélectionner"
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        const commandeCheckboxes = document.querySelectorAll('.commande-checkbox');
        
        commandeCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            const commandeId = checkbox.getAttribute('data-commande-id');
            
            if (isChecked) {
                selectedCommandes.add(commandeId);
            } else {
                selectedCommandes.delete(commandeId);
            }
        });
        
        updateSelectionCount();
    });
    
    // Gestion des cases individuelles
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('commande-checkbox')) {
            const commandeId = e.target.getAttribute('data-commande-id');
            
            if (e.target.checked) {
                selectedCommandes.add(commandeId);
            } else {
                selectedCommandes.delete(commandeId);
            }
            
            updateSelectionCount();
            
            // Mettre à jour la case "Tout sélectionner"
            const totalCheckboxes = document.querySelectorAll('.commande-checkbox').length;
            const checkedCheckboxes = document.querySelectorAll('.commande-checkbox:checked').length;
            
            selectAllCheckbox.checked = totalCheckboxes === checkedCheckboxes;
            selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
        }
    });
    
    // Mettre à jour le compteur de sélection
    function updateSelectionCount() {
        const count = selectedCommandes.size;
        const countElement = document.getElementById('selectedCommandesCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }
    
    // Ouvrir le modal de mise à jour en lot
    function openBulkUpdateModal() {
        updateSelectionCount();
        const modal = new bootstrap.Modal(document.getElementById('bulkUpdateModal'));
        modal.show();
    }
    
    // Gestion de la confirmation de mise à jour en lot
    document.getElementById('confirmBulkUpdate').addEventListener('click', function() {
        const newStatus = document.getElementById('bulkStatusSelect').value;
        
        if (!newStatus) {
            showNotification('Veuillez sélectionner un statut', 'warning');
            return;
        }
        
        if (selectedCommandes.size === 0) {
            showNotification('Aucune commande sélectionnée', 'warning');
            return;
        }
        
        // Désactiver le bouton pendant le traitement
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mise à jour...';
        
        // Envoyer la requête de mise à jour en lot
        performBulkUpdate(Array.from(selectedCommandes), newStatus);
    });
    
    // Effectuer la mise à jour en lot
    function performBulkUpdate(commandeIds, newStatus) {
        fetch('api/bulk_update_commandes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                commande_ids: commandeIds,
                new_status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`${commandeIds.length} commande(s) mise(s) à jour avec succès`, 'success');
                
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('bulkUpdateModal'));
                modal.hide();
                
                // Désactiver le mode sélection
                deactivateSelectionMode();
                
                // Recharger la page pour voir les modifications
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showNotification('Erreur lors de la mise à jour: ' + (data.message || 'Erreur inconnue'), 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur de connexion lors de la mise à jour', 'danger');
        })
        .finally(() => {
            // Réactiver le bouton
            const btn = document.getElementById('confirmBulkUpdate');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Mettre à jour';
        });
    }
    
    // Désactiver le mode sélection quand on ferme le modal
    document.getElementById('bulkUpdateModal').addEventListener('hidden.bs.modal', function() {
        // Réinitialiser le select
        document.getElementById('bulkStatusSelect').value = '';
    });
    
    console.log('Système de sélection multiple initialisé');
});
</script>

</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.container-fluid,
.container-fluid * {
  background: transparent !important;
}

.card,
.modal-content,
.order-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .order-card {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500);
    }, 300);
});
</script>