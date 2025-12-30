<?php
// Page: ajouter_reparation-modern (version propre reproduisant l'interface originale)

// Sécurité: si accès direct au fichier, rediriger via l'index routeur principal
if (basename($_SERVER['PHP_SELF']) === 'ajouter_reparation-modern.php') {
    header('Location: ../index.php?page=ajouter_reparation-modern');
    exit();
}

// 🚨 TRAITEMENT AJAX IMMÉDIAT - AVANT TOUT AUTRE CODE
// Détection du dépassement de post_max_size
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'repair_id' => null,
        'message' => 'Erreur critique : La taille des données envoyées dépasse la limite autorisée par le serveur (' . ini_get('post_max_size') . '). La photo est probablement trop lourde malgré la compression.',
        'redirect_url' => null // Pas de redirection
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['force_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH']))) {
    // Nettoyer tout buffer existant
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Initialiser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo === null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'repair_id' => null,
            'message' => 'Erreur de connexion à la base de données',
            'redirect_url' => 'index.php?page=reparations'
        ]);
        exit;
    }
    
    // CRÉATION RÉELLE DE LA RÉPARATION
    try {
        // Récupérer et nettoyer les données du formulaire
        $client_id = cleanInput($_POST['client_id'] ?? '');
        $type_appareil = cleanInput($_POST['type_appareil'] ?? '');
        $modele = cleanInput($_POST['modele'] ?? '');
        $description_probleme = cleanInput($_POST['description_probleme'] ?? '');
        $mot_de_passe = cleanInput($_POST['mot_de_passe'] ?? '');
        $prix_reparation = cleanInput($_POST['prix_reparation'] ?? '0');
        $statut = cleanInput($_POST['statut'] ?? 'nouvelle_intervention');
        $marque = cleanInput($_POST['marque'] ?? '');
        $notes_techniques = cleanInput($_POST['notes_techniques'] ?? '');
        
        // Validation des champs obligatoires
        if (empty($client_id) || empty($type_appareil) || empty($modele) || empty($description_probleme)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'repair_id' => null,
                'message' => 'Champs obligatoires manquants: client_id, type_appareil, modele, description_probleme',
                'redirect_url' => 'index.php?page=ajouter_reparation-modern'
            ]);
            exit;
        }
        
        // Traitement de la photo si présente
        $photo_path = null;
        if (!empty($_POST['photo_appareil'])) {
            $photo_data = $_POST['photo_appareil'];
            if (strpos($photo_data, 'data:image') === 0) {
                $upload_dir = __DIR__ . '/../assets/images/reparations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $data_parts = explode(',', $photo_data);
                if (count($data_parts) == 2) {
                    $decoded_data = base64_decode($data_parts[1]);
                    if ($decoded_data !== false) {
                        $photo_name = uniqid('repair_') . '.jpg';
                        $photo_path_abs = $upload_dir . $photo_name;
                        $photo_path = 'assets/images/reparations/' . $photo_name;
                        
                        if (file_put_contents($photo_path_abs, $decoded_data) === false) {
                            $photo_path = null;
                        }
                    }
                }
            }
        }
        
        // Vérifier d'abord quelles colonnes existent dans la table
        $columns_query = $shop_pdo->query("DESCRIBE reparations");
        $existing_columns = $columns_query->fetchAll(PDO::FETCH_COLUMN);
        
        // Construire la requête dynamiquement selon les colonnes disponibles
        $base_columns = ['client_id', 'type_appareil', 'modele', 'description_probleme', 'mot_de_passe', 'prix_reparation', 'date_reception', 'statut'];
        $base_values = [$client_id, $type_appareil, $modele, $description_probleme, $mot_de_passe, $prix_reparation, date('Y-m-d H:i:s'), $statut];
        $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?'];
        
        // Ajouter les colonnes optionnelles si elles existent
        if (in_array('photo_appareil', $existing_columns)) {
            $base_columns[] = 'photo_appareil';
            $base_values[] = $photo_path;
            $placeholders[] = '?';
        }
        
        if (in_array('commande_requise', $existing_columns)) {
            $base_columns[] = 'commande_requise';
            $base_values[] = isset($_POST['commande_requise']) ? 1 : 0;
            $placeholders[] = '?';
        }
        
        if (in_array('notes_techniques', $existing_columns)) {
            $base_columns[] = 'notes_techniques';
            $base_values[] = $notes_techniques;
            $placeholders[] = '?';
        }
        
        if (in_array('marque', $existing_columns)) {
            $base_columns[] = 'marque';
            $base_values[] = $marque;
            $placeholders[] = '?';
        }
        
        // Construire la requête SQL
        $sql = "INSERT INTO reparations (" . implode(', ', $base_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($base_values);
        
        $reparation_id = $shop_pdo->lastInsertId();
        
        if ($reparation_id && $reparation_id > 0) {
            $current_domain = $_SERVER['HTTP_HOST'];
            $redirect_url = "https://" . $current_domain . "/index.php?page=imprimer_etiquette&id=" . $reparation_id;
            
            // Retourner une réponse JSON de succès
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'repair_id' => (int)$reparation_id,
                'redirect_url' => $redirect_url,
                'message' => 'Réparation créée avec succès'
            ]);
            exit;
        } else {
            // Échec de l'insertion
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'repair_id' => null,
                'message' => 'Échec de l\'insertion: lastInsertId() a retourné ' . ($reparation_id ?: 'null'),
                'redirect_url' => 'index.php?page=reparations'
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'repair_id' => null,
            'message' => 'Exception lors de la création: ' . $e->getMessage(),
            'redirect_url' => 'index.php?page=reparations'
        ]);
        exit;
    }
}

// Récupérer la liste des clients pour le formulaire
$shop_pdo = getShopDBConnection();
$clients = [];
$fournisseurs = [];

if ($shop_pdo !== null) {
    try {
        $stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone FROM clients ORDER BY nom, prenom");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
        $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $clients = [];
        $fournisseurs = [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une réparation - Version moderne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Variables CSS pour thème jour/nuit */
        :root {
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #212529;
            --border-color: #dee2e6;
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        [data-bs-theme="dark"],
        body.dark-mode {
            --bg-color: #212529;
            --card-bg: #343a40;
            --text-color: #ffffff;
            --border-color: #495057;
            --primary-color: #0dcaf0;
            --success-color: #20c997;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            background: radial-gradient(1200px 600px at 0% 0%, rgba(59,130,246,0.08), transparent 70%),
                        radial-gradient(1200px 600px at 100% 0%, rgba(6,182,212,0.06), transparent 70%),
                        var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            transition: all 0.3s ease;
        }

        .type-appareil-card, .mot-de-passe-card, .note-interne-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .type-appareil-card:hover, .mot-de-passe-card:hover, .note-interne-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .type-appareil-card.selected, .mot-de-passe-card.selected, .note-interne-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.1);
            transform: scale(1.02);
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        .client-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .client-card:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .client-card.selected {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-problem-shortcut {
            margin: 2px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .camera-container {
            max-width: 400px;
            height: 300px;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }

        #camera_feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Bouton de basculement de thème -->
    <button class="btn btn-outline-secondary theme-toggle" id="themeToggle" title="Basculer le thème">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="container-fluid p-0" style="max-width: 100vw; overflow-x: hidden;">
        <div class="row justify-content-center g-0" style="width: 100%; margin: 0 auto;">
            <div class="col-12 col-lg-10 col-xl-8 px-0" style="display: flex; flex-direction: column; align-items: center;">
                <h4 class="page-title text-center my-3">Ajouter une réparation</h4>
                
                <div class="card mb-4" style="width: 92%; max-width: 900px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-radius: 15px; margin: 0 auto;">
                    <div class="card-body">
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">Étape 1/4</div>
                        </div>
                        
                        <form id="reparationForm" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="form_submission_id" value="<?php echo uniqid('rep_'); ?>">
                            <input type="hidden" name="force_ajax" value="1">
                            
                            <!-- Étape 1: Type d'appareil -->
                            <div id="etape1" class="form-step active">
                                <h5 class="mb-3">Type d'appareil</h5>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card text-center mb-3 type-appareil-card" data-type="Informatique">
                                            <div class="card-body py-4">
                                                <i class="fas fa-laptop fa-4x mb-3"></i>
                                                <h5>Appareil informatique</h5>
                                                <p class="mb-0 text-muted">Ordinateur, téléphone, tablette...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card text-center mb-3 type-appareil-card" data-type="Trottinette">
                                            <div class="card-body py-4">
                                                <i class="fas fa-bolt fa-4x mb-3"></i>
                                                <h5>Trottinette électrique</h5>
                                                <p class="mb-0 text-muted">Tous types de trottinettes...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="type_appareil" id="type_appareil" required>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary next-step" style="min-width: 100px;" disabled>Suivant</button>
                                </div>
                            </div>
                            
                            <!-- Étape 2: Sélection du client -->
                            <div id="etape2" class="form-step">
                                <h5 class="mb-3">Recherche du client</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Rechercher un client existant</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-primary"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="recherche_client" placeholder="Nom, prénom ou téléphone...">
                                        <button class="btn btn-primary rounded-end shadow-sm" type="button" id="btn_recherche_client">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Message "aucun résultat" -->
                                <div id="no_results" class="alert alert-warning d-none my-2">
                                    Aucun client trouvé. <button type="button" class="btn btn-sm btn-outline-primary mt-1 d-block" id="btn_nouveau_client">Créer un nouveau client</button>
                                </div>
                                
                                <!-- Client sélectionné -->
                                <div id="client_selectionne" class="alert alert-info d-none mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div><strong>Client sélectionné:</strong> <span id="nom_client_selectionne"></span></div>
                                        <button type="button" class="btn-close" id="reset_client"></button>
                                    </div>
                                </div>
                                
                                <!-- Conteneur des résultats de recherche -->
                                <div id="resultats_clients" class="d-none mb-3">
                                    <div class="client-results-container">
                                        <div class="client-results-list" id="liste_clients">
                                            <!-- Les résultats seront injectés ici -->
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="client_id" id="client_id" required>
                                
                                <div class="d-flex justify-content-between flex-column flex-md-row">
                                    <button type="button" class="btn btn-secondary prev-step mb-2 mb-md-0" style="min-width: 100px;">Précédent</button>
                                    <button type="button" class="btn btn-primary next-step" id="btn_etape2_suivant" style="min-width: 100px;" disabled>Suivant</button>
                                </div>
                            </div>
                            
                            <!-- Étape 3: Informations sur l'appareil -->
                            <div id="etape3" class="form-step">
                                <h5 class="mb-3">Informations sur l'appareil</h5>
                                
                                <div class="mb-3">
                                    <label for="modele" class="form-label">Modèle de l'appareil *</label>
                                    <input type="text" class="form-control" id="modele" name="modele" required>
                                    <div class="form-text">Indiquez le nom ou référence précise de l'appareil</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">L'appareil a-t-il un mot de passe ? *</label>
                                    <div class="d-flex password-buttons-container">
                                        <div class="flex-grow-1 me-2">
                                            <div class="card text-center h-100 mot-de-passe-card" data-value="oui">
                                                <div class="card-body d-flex flex-column justify-content-center p-3">
                                                    <i class="fas fa-lock fa-2x mb-2 text-primary"></i>
                                                    <h6 class="mb-1">Oui</h6>
                                                    <p class="mb-0 text-muted small">Appareil protégé</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="card text-center h-100 mot-de-passe-card" data-value="non">
                                                <div class="card-body d-flex flex-column justify-content-center p-3">
                                                    <i class="fas fa-unlock fa-2x mb-2 text-success"></i>
                                                    <h6 class="mb-1">Non</h6>
                                                    <p class="mb-0 text-muted small">Pas de mot de passe</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="a_mot_de_passe" id="a_mot_de_passe" required>
                                </div>
                                
                                <div id="champ_mot_de_passe" class="mb-4 d-none">
                                    <label for="mot_de_passe" class="form-label">Mot de passe de l'appareil *</label>
                                    <input type="text" class="form-control" id="mot_de_passe" name="mot_de_passe">
                                    <div class="form-text">Ce mot de passe est nécessaire pour diagnostiquer l'appareil</div>
                                </div>
                                
                                <div id="confirmation_sans_mdp" class="alert alert-warning mb-4 d-none">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Attention :</strong> Sans mot de passe, nous pourrions être limités dans notre diagnostic.
                                    <div class="mt-2">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="check_responsabilite">
                                            <label class="form-check-label" for="check_responsabilite">
                                                Je confirme avoir demandé le mot de passe au client et qu'il n'en a pas. J'assume la responsabilité de cette information.
                                            </label>
                                        </div>
                                        <button type="button" class="btn btn-danger" id="btn_confirmer_sans_mdp">
                                            Je confirme sous ma responsabilité
                                        </button>
                                    </div>
                                </div>
                                
                                <hr class="my-3">
                                
                                <h5 class="mb-3">Description du problème</h5>
                                
                                <!-- Boutons de raccourci pour la description -->
                                <div class="mb-3" id="informatique_buttons" style="display: none;">
                                    <label class="form-label">Raccourcis pour appareils informatiques :</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="alimentation">Alimentation</button>
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="ecran">Ecran</button>
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="autre-info">Autre</button>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="trottinette_buttons" style="display: none;">
                                    <label class="form-label">Raccourcis pour trottinettes :</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="alimentation-trot">Alimentation</button>
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="cycle">Cycle</button>
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="electronique">Electronique</button>
                                        <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="autre-trot">Autre</button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description_probleme" class="form-label">Description détaillée du problème *</label>
                                    <textarea class="form-control" id="description_probleme" name="description_probleme" rows="4" required></textarea>
                                </div>
                                
                                <hr class="my-3">
                                
                                <h5 class="mb-3">Note interne</h5>
                                <div class="mb-4">
                                    <label class="form-label">Souhaitez-vous ajouter une information pour vos collègues ?</label>
                                    <div class="d-flex note-interne-buttons-container">
                                        <div class="flex-grow-1 me-2">
                                            <div class="card text-center h-100 note-interne-card" data-value="oui">
                                                <div class="card-body d-flex flex-column justify-content-center p-3">
                                                    <i class="fas fa-check fa-2x mb-2 text-success"></i>
                                                    <h6 class="mb-1">Oui</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="card text-center h-100 note-interne-card" data-value="non">
                                                <div class="card-body d-flex flex-column justify-content-center p-3">
                                                    <i class="fas fa-times fa-2x mb-2 text-danger"></i>
                                                    <h6 class="mb-1">Non</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="a_note_interne" id="a_note_interne" value="non">
                                </div>
                                
                                <div id="champ_note_interne" class="mb-4 d-none">
                                    <label for="notes_techniques" class="form-label">Note interne pour l'équipe *</label>
                                    <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="4"></textarea>
                                    <div class="form-text">Cette note sera visible uniquement par l'équipe, pas par le client</div>
                                </div>
                                
                                <hr class="my-3">
                                
                                <h5 class="mb-3">Photo de l'appareil</h5>
                                <div class="mb-4">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label class="form-label mb-0">Ajouter une photo de l'appareil</label>
                                            <div class="mt-2">
                                                <input type="file" id="photo_input" accept="image/*" class="form-control">
                                                <div class="form-text">Sélectionnez une photo de l'appareil</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Prévisualisation de la photo -->
                                        <div class="col-12" id="photo_preview_container" style="display: none;">
                                            <div class="mb-3">
                                                <label class="form-label">Prévisualisation :</label>
                                                <div>
                                                    <img id="photo_preview" class="photo-preview" alt="Prévisualisation">
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-sm btn-danger" id="remove_photo">
                                                            <i class="fas fa-trash me-1"></i>Supprimer
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="photo_appareil" id="photo_appareil">
                                
                                <div class="d-flex justify-content-between flex-column flex-md-row">
                                    <button type="button" class="btn btn-secondary prev-step mb-2 mb-md-0" style="min-width: 100px;">Précédent</button>
                                    <button type="button" class="btn btn-primary next-step" id="btn_etape3_suivant" style="min-width: 100px;">Suivant</button>
                                </div>
                            </div>
                            
                            <!-- Étape 4: Tarification -->
                            <div id="etape4" class="form-step">
                                <h5 class="mb-3">Tarification</h5>
                                <div class="mb-4">
                                    <label for="prix_reparation" class="form-label">Prix estimé de la réparation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" class="form-control" id="prix_reparation" name="prix_reparation" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <div class="form-text">Prix indicatif qui pourra être ajusté après diagnostic</div>
                                </div>

                                <!-- Section Commande de pièces -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Commande de pièces
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="commande_requise" name="commande_requise">
                                                <label class="form-check-label" for="commande_requise">Commande de pièces requise</label>
                                            </div>
                                        </div>

                                        <!-- Champs de commande (initialement masqués) -->
                                        <div id="commande_fields" class="d-none">
                                            <div class="mb-3">
                                                <label for="fournisseur" class="form-label">Fournisseur *</label>
                                                <select class="form-select" id="fournisseur" name="fournisseur_id">
                                                    <option value="">Sélectionner un fournisseur</option>
                                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                                        <option value="<?php echo $fournisseur['id']; ?>"><?php echo htmlspecialchars($fournisseur['nom']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="nom_piece" class="form-label">Nom du produit *</label>
                                                <input type="text" class="form-control" id="nom_piece" name="nom_piece">
                                            </div>

                                            <div class="mb-3">
                                                <label for="reference_piece" class="form-label">Référence du produit</label>
                                                <input type="text" class="form-control" id="reference_piece" name="reference_piece">
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="quantite" class="form-label">Quantité *</label>
                                                        <input type="number" class="form-control" id="quantite" name="quantite" min="1" value="1">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="prix_piece" class="form-label">Prix (€) *</label>
                                                        <div class="input-group">
                                                            <input type="number" step="0.01" class="form-control" id="prix_piece" name="prix_piece">
                                                            <span class="input-group-text">€</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Boutons de soumission -->
                                <div id="form_buttons" class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary prev-step" style="min-width: 100px;">Précédent</button>
                                    <div class="btn-group btn-group-mobile d-flex flex-column d-md-inline-flex flex-md-row" role="group">
                                        <button type="submit" name="statut" value="nouvelle_intervention" class="btn btn-primary mb-2 mb-md-0" id="btn_soumettre_reparation">
                                            <i class="fas fa-save me-2"></i>Enregistrer la réparation
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Message de confirmation caché -->
                                <div class="alert alert-info mt-3 d-none" id="submitting_message">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter un nouveau client -->
    <div class="modal fade" id="nouveauClientModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNouveauClient">
                        <div class="mb-3">
                            <label for="nouveau_nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nouveau_nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="nouveau_prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="nouveau_prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="nouveau_telephone" class="form-label">Téléphone *</label>
                            <input type="tel" class="form-control" id="nouveau_telephone" required>
                            <div class="form-text">Format : 11 chiffres (ex: 331234567890)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btn_sauvegarder_client">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'Erreur -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Erreur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="errorMessage" class="mb-3">Une erreur est survenue lors de l'enregistrement.</div>
                    
                    <div class="accordion" id="accordionDebug">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingDebug">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false" aria-controls="collapseDebug">
                                    Détails techniques (Debug)
                                </button>
                            </h2>
                            <div id="collapseDebug" class="accordion-collapse collapse" aria-labelledby="headingDebug" data-bs-parent="#accordionDebug">
                                <div class="accordion-body">
                                    <pre id="errorDebug" class="bg-light p-2 small" style="max-height: 200px; overflow-y: auto; overflow-x: auto; white-space: pre-wrap;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer et corriger</button>
                    <button type="button" class="btn btn-outline-danger" onclick="location.reload()">Recharger la page</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let currentStep = 1;
        let selectedClient = null;
        let selectedTypeAppareil = null;
        let selectedMotDePasse = null;
        let selectedNoteInterne = 'non';

        // Initialisation SÉCURISÉE
        // On encapsule chaque init dans un try-catch pour éviter qu'une erreur bloque tout le script
        // On initialise le formulaire EN PREMIER, c'est le plus critique
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Démarrage initialisation v2.1...');
            
            // 1. Initialisation de la soumission du formulaire (CRITIQUE)
            try {
                initializeFormSubmission();
                console.log('Form Submission: OK');
            } catch (e) {
                console.error('Erreur critique Form Submission:', e);
                alert('Erreur critique : Le formulaire ne peut pas être initialisé. Rechargez la page.');
            }

            // 2. Initialisation du thème
            try {
                initializeTheme();
            } catch (e) { console.error('Erreur Theme:', e); }

            // 3. Navigation
            try {
                initializeStepNavigation();
            } catch (e) { console.error('Erreur Navigation:', e); }

            // 4. Sélecteurs
            try {
                initializeTypeAppareilSelection();
            } catch (e) { console.error('Erreur Type Appareil:', e); }

            try {
                initializeClientSearch();
            } catch (e) { console.error('Erreur Client Search:', e); }

            try {
                initializePasswordSelection();
            } catch (e) { console.error('Erreur Password:', e); }

            try {
                initializeNoteInterneSelection();
            } catch (e) { console.error('Erreur Note Interne:', e); }

            try {
                initializeProblemShortcuts();
            } catch (e) { console.error('Erreur Shortcuts:', e); }

            try {
                initializePhotoUpload();
            } catch (e) { console.error('Erreur Photo Upload:', e); }

            try {
                initializeCommandeFields();
            } catch (e) { console.error('Erreur Commande Fields:', e); }
            
            // Indicateur de succès (Anti-Cache)
            const toastContainer = document.createElement('div');
            toastContainer.style.position = 'fixed';
            toastContainer.style.bottom = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            toastContainer.innerHTML = `
                <div class="toast show align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>Système prêt v2.1
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toastContainer);
            setTimeout(() => {
                toastContainer.remove();
            }, 3000);
        });

        // Gestion du thème
        function initializeTheme() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            // Vérifier le thème sauvegardé
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                body.setAttribute('data-bs-theme', 'dark');
                body.classList.add('dark-mode');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-bs-theme');
                if (currentTheme === 'dark') {
                    body.removeAttribute('data-bs-theme');
                    body.classList.remove('dark-mode');
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('theme', 'light');
                } else {
                    body.setAttribute('data-bs-theme', 'dark');
                    body.classList.add('dark-mode');
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('theme', 'dark');
                }
            });
        }

        // Navigation entre les étapes
        function initializeStepNavigation() {
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (validateCurrentStep()) {
                        goToStep(currentStep + 1);
                    }
                });
            });
            
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    goToStep(currentStep - 1);
                });
            });
        }

        function goToStep(step) {
            if (step < 1 || step > 4) return;
            
            // Masquer l'étape actuelle
            document.getElementById(`etape${currentStep}`).classList.remove('active');
            
            // Afficher la nouvelle étape
            currentStep = step;
            document.getElementById(`etape${currentStep}`).classList.add('active');
            
            // Mettre à jour la barre de progression
            const progress = (step / 4) * 100;
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = progress + '%';
            progressBar.textContent = `Étape ${step}/4`;
        }

        function validateCurrentStep() {
            switch(currentStep) {
                case 1:
                    return selectedTypeAppareil !== null;
                case 2:
                    return selectedClient !== null;
                case 3:
                    const modele = document.getElementById('modele').value.trim();
                    const description = document.getElementById('description_probleme').value.trim();
                    return modele && description && selectedMotDePasse !== null;
                case 4:
                    const prix = document.getElementById('prix_reparation').value;
                    return prix && parseFloat(prix) >= 0;
                default:
                    return true;
            }
        }

        // Sélection du type d'appareil
        function initializeTypeAppareilSelection() {
            const cards = document.querySelectorAll('.type-appareil-card');
            const nextButton = document.querySelector('#etape1 .next-step');
            
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    // Désélectionner toutes les cartes
                    cards.forEach(c => c.classList.remove('selected'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('selected');
                    selectedTypeAppareil = this.dataset.type;
                    document.getElementById('type_appareil').value = selectedTypeAppareil;
                    
                    // Activer le bouton suivant
                    nextButton.disabled = false;
                    
                    // Afficher les boutons de raccourci appropriés
                    updateProblemShortcuts();
                });
            });
        }

        // Recherche de clients
        function initializeClientSearch() {
            const searchInput = document.getElementById('recherche_client');
            const searchButton = document.getElementById('btn_recherche_client');
            const resultsContainer = document.getElementById('resultats_clients');
            const clientsList = document.getElementById('liste_clients');
            const noResults = document.getElementById('no_results');
            const clientSelectionne = document.getElementById('client_selectionne');
            const nomClientSelectionne = document.getElementById('nom_client_selectionne');
            const resetButton = document.getElementById('reset_client');
            const nextButton = document.getElementById('btn_etape2_suivant');
            const nouveauClientButton = document.getElementById('btn_nouveau_client');
            
            const clients = <?php echo json_encode(array_map(function($c){
                return [
                    'id' => (int)$c['id'],
                    'nom' => $c['nom'] ?? '',
                    'prenom' => $c['prenom'] ?? '',
                    'telephone' => $c['telephone'] ?? ''
                ];
            }, $clients)); ?>;
            
            function searchClients(query) {
                if (!query.trim()) {
                    resultsContainer.classList.add('d-none');
                    noResults.classList.add('d-none');
                    return;
                }
                
                const filtered = clients.filter(client => {
                    const fullName = `${client.nom} ${client.prenom}`.toLowerCase();
                    const telephone = client.telephone || '';
                    return fullName.includes(query.toLowerCase()) || telephone.includes(query);
                });
                
                if (filtered.length > 0) {
                    displayClients(filtered);
                    resultsContainer.classList.remove('d-none');
                    noResults.classList.add('d-none');
                } else {
                    resultsContainer.classList.add('d-none');
                    noResults.classList.remove('d-none');
                }
            }
            
            function displayClients(clientsToShow) {
                clientsList.innerHTML = '';
                clientsToShow.forEach(client => {
                    const clientCard = document.createElement('div');
                    clientCard.className = 'card client-card mb-2';
                    clientCard.innerHTML = `
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${client.nom} ${client.prenom}</strong>
                                    ${client.telephone ? `<br><small class="text-muted">${client.telephone}</small>` : ''}
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary">Sélectionner</button>
                            </div>
                        </div>
                    `;
                    
                    clientCard.addEventListener('click', function() {
                        selectClient(client);
                    });
                    
                    clientsList.appendChild(clientCard);
                });
            }
            
            function selectClient(client) {
                selectedClient = client;
                document.getElementById('client_id').value = client.id;
                nomClientSelectionne.textContent = `${client.nom} ${client.prenom}`;
                clientSelectionne.classList.remove('d-none');
                resultsContainer.classList.add('d-none');
                noResults.classList.add('d-none');
                searchInput.value = '';
                nextButton.disabled = false;
            }
            
            if (searchButton) {
                searchButton.addEventListener('click', function() {
                    searchClients(searchInput.value);
                });
            }
            
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    searchClients(this.value);
                } else if (this.value.length >= 2) {
                    searchClients(this.value);
                } else if (this.value.length === 0) {
                    resultsContainer.classList.add('d-none');
                    noResults.classList.add('d-none');
                }
            });
            
            if (resetButton) {
            resetButton.addEventListener('click', function() {
                selectedClient = null;
                document.getElementById('client_id').value = '';
                clientSelectionne.classList.add('d-none');
                resultsContainer.classList.add('d-none');
                noResults.classList.add('d-none');
                searchInput.value = '';
                nextButton.disabled = true;
            });
            }
            
            if (nouveauClientButton) {
            nouveauClientButton.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('nouveauClientModal'));
                modal.show();
            });
            }
            
            // Gestion du modal nouveau client
            document.getElementById('btn_sauvegarder_client').addEventListener('click', function() {
                const nom = document.getElementById('nouveau_nom').value.trim();
                const prenom = document.getElementById('nouveau_prenom').value.trim();
                const telephone = document.getElementById('nouveau_telephone').value.trim();
                
                if (!nom || !prenom || !telephone) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }
                
                // Envoyer la requête AJAX pour créer le client
                fetch('ajax/ajouter_client.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nom: nom,
                        prenom: prenom,
                        telephone: telephone
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newClient = {
                            id: data.client_id,
                            nom: nom,
                            prenom: prenom,
                            telephone: telephone
                        };
                        selectClient(newClient);
                        bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal')).hide();
                        document.getElementById('formNouveauClient').reset();
                    } else {
                        alert('Erreur lors de la création du client: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la création du client.');
                });
            });
        }

        // Sélection du mot de passe
        function initializePasswordSelection() {
            const cards = document.querySelectorAll('.mot-de-passe-card');
            const champMotDePasse = document.getElementById('champ_mot_de_passe');
            const confirmationSansMdp = document.getElementById('confirmation_sans_mdp');
            const checkResponsabilite = document.getElementById('check_responsabilite');
            const btnConfirmerSansMdp = document.getElementById('btn_confirmer_sans_mdp');
            
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    cards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedMotDePasse = this.dataset.value;
                    document.getElementById('a_mot_de_passe').value = selectedMotDePasse;
                    
                    if (selectedMotDePasse === 'oui') {
                        champMotDePasse.classList.remove('d-none');
                        confirmationSansMdp.classList.add('d-none');
                        document.getElementById('mot_de_passe').required = true;
                    } else {
                        champMotDePasse.classList.add('d-none');
                        confirmationSansMdp.classList.remove('d-none');
                        document.getElementById('mot_de_passe').required = false;
                        document.getElementById('mot_de_passe').value = '';
                    }
                });
            });
            
            btnConfirmerSansMdp.addEventListener('click', function() {
                if (checkResponsabilite.checked) {
                    confirmationSansMdp.classList.add('d-none');
                } else {
                    alert('Vous devez cocher la case de confirmation.');
                }
            });
        }

        // Sélection de la note interne
        function initializeNoteInterneSelection() {
            const cards = document.querySelectorAll('.note-interne-card');
            const champNoteInterne = document.getElementById('champ_note_interne');
            
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    cards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedNoteInterne = this.dataset.value;
                    document.getElementById('a_note_interne').value = selectedNoteInterne;
                    
                    if (selectedNoteInterne === 'oui') {
                        champNoteInterne.classList.remove('d-none');
                        document.getElementById('notes_techniques').required = true;
                    } else {
                        champNoteInterne.classList.add('d-none');
                        document.getElementById('notes_techniques').required = false;
                        document.getElementById('notes_techniques').value = '';
                    }
                });
            });
        }

        // Raccourcis pour la description du problème
        function initializeProblemShortcuts() {
            const buttons = document.querySelectorAll('.btn-problem-shortcut');
            const descriptionTextarea = document.getElementById('description_probleme');
            
            const problemTexts = {
                'alimentation': 'Problème d\'alimentation - L\'appareil ne s\'allume pas ou s\'éteint de manière inattendue.',
                'ecran': 'Problème d\'écran - Écran cassé, rayé, ou dysfonctionnement de l\'affichage.',
                'autre-info': 'Autre problème informatique - ',
                'alimentation-trot': 'Problème d\'alimentation - La trottinette ne se charge pas ou ne s\'allume pas.',
                'cycle': 'Problème de cycle - Dysfonctionnement du moteur ou de la transmission.',
                'electronique': 'Problème électronique - Dysfonctionnement des composants électroniques.',
                'autre-trot': 'Autre problème de trottinette - '
            };
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const problemType = this.dataset.problemType;
                    const text = problemTexts[problemType] || '';
                    descriptionTextarea.value = text;
                    descriptionTextarea.focus();
                });
            });
        }

        function updateProblemShortcuts() {
            const informatiqueButtons = document.getElementById('informatique_buttons');
            const trottinetteButtons = document.getElementById('trottinette_buttons');
            
            if (selectedTypeAppareil === 'Informatique') {
                informatiqueButtons.style.display = 'block';
                trottinetteButtons.style.display = 'none';
            } else if (selectedTypeAppareil === 'Trottinette') {
                informatiqueButtons.style.display = 'none';
                trottinetteButtons.style.display = 'block';
            } else {
                informatiqueButtons.style.display = 'none';
                trottinetteButtons.style.display = 'none';
            }
        }

        // Upload de photo avec compression
        function initializePhotoUpload() {
            const photoInput = document.getElementById('photo_input');
            const photoPreviewContainer = document.getElementById('photo_preview_container');
            const photoPreview = document.getElementById('photo_preview');
            const removePhotoButton = document.getElementById('remove_photo');
            const photoAppareilInput = document.getElementById('photo_appareil');
            
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Vérifier si c'est une image
                    if (!file.type.match('image.*')) {
                        alert('Veuillez sélectionner une image valide.');
                        return;
                    }

                    // Afficher un retour visuel pendant le traitement
                    const originalBtnText = photoInput.nextElementSibling ? photoInput.nextElementSibling.innerText : '';
                    if (photoInput.parentElement.querySelector('.form-text')) {
                        photoInput.parentElement.querySelector('.form-text').textContent = 'Compression en cours...';
                        photoInput.parentElement.querySelector('.form-text').classList.add('text-primary');
                    }

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const img = new Image();
                        img.onload = function() {
                            // Dimensions cibles
                            const maxWidth = 1200;
                            const maxHeight = 1200;
                            let width = img.width;
                            let height = img.height;

                            // Calcul du ratio pour garder les proportions
                            if (width > height) {
                                if (width > maxWidth) {
                                    height *= maxWidth / width;
                                    width = maxWidth;
                                }
                            } else {
                                if (height > maxHeight) {
                                    width *= maxHeight / height;
                                    height = maxHeight;
                                }
                            }

                            // Création du canvas pour le redimensionnement
                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            // Compression JPEG à 70% (0.7)
                            // Cela réduit considérablement la taille du base64
                            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                            
                            // Affichage et stockage
                            photoPreview.src = dataUrl;
                            photoAppareilInput.value = dataUrl;
                            photoPreviewContainer.style.display = 'block';
                            
                            // Rétablir le texte d'aide
                            if (photoInput.parentElement.querySelector('.form-text')) {
                                photoInput.parentElement.querySelector('.form-text').textContent = 'Photo ajoutée et compressée !';
                                photoInput.parentElement.querySelector('.form-text').classList.remove('text-primary');
                                photoInput.parentElement.querySelector('.form-text').classList.add('text-success');
                            }
                            
                            console.log('Image compressée avec succès');
                        };
                        img.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            removePhotoButton.addEventListener('click', function() {
                photoInput.value = '';
                photoAppareilInput.value = '';
                photoPreviewContainer.style.display = 'none';
                
                // Reset du texte d'aide
                if (photoInput.parentElement.querySelector('.form-text')) {
                    photoInput.parentElement.querySelector('.form-text').textContent = 'Sélectionnez une photo de l\'appareil';
                    photoInput.parentElement.querySelector('.form-text').classList.remove('text-success');
                    photoInput.parentElement.querySelector('.form-text').classList.remove('text-primary');
                }
            });
        }

        // Gestion des champs de commande
        function initializeCommandeFields() {
            const commandeRequise = document.getElementById('commande_requise');
            const commandeFields = document.getElementById('commande_fields');
            
            commandeRequise.addEventListener('change', function() {
                if (this.checked) {
                    commandeFields.classList.remove('d-none');
                } else {
                    commandeFields.classList.add('d-none');
                }
            });
        }

        // Soumission du formulaire
        function initializeFormSubmission() {
            const form = document.getElementById('reparationForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const submittingMessage = document.getElementById('submitting_message');
            
            // Initialisation de la modale d'erreur
            const errorModalEl = document.getElementById('errorModal');
            let errorModal;
            if (errorModalEl) {
                errorModal = new bootstrap.Modal(errorModalEl);
            }

            function showError(message, debugInfo = '') {
                if (errorModal) {
                    document.getElementById('errorMessage').innerHTML = message;
                    
                    const debugEl = document.getElementById('errorDebug');
                    if (debugInfo) {
                        // Si debugInfo est un objet, on le stringify
                        if (typeof debugInfo === 'object') {
                            try {
                                debugEl.textContent = JSON.stringify(debugInfo, null, 2);
                            } catch (e) {
                                debugEl.textContent = debugInfo;
                            }
                        } else {
                            // Limiter la longueur du debug info s'il est trop long (ex: dump HTML)
                            if (debugInfo.length > 5000) {
                                debugEl.textContent = debugInfo.substring(0, 5000) + '... (tronqué)';
                            } else {
                                debugEl.textContent = debugInfo;
                            }
                        }
                    } else {
                        debugEl.textContent = 'Aucune information technique supplémentaire.';
                    }
                    
                    errorModal.show();
                } else {
                    alert('Erreur: ' + message);
                    console.error(debugInfo);
                }
            }
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateCurrentStep()) {
                    showError('Veuillez remplir tous les champs obligatoires.');
                    return;
                }
                
                // Afficher le loading
                loadingOverlay.style.display = 'flex';
                submittingMessage.classList.remove('d-none');
                
                // Préparer les données du formulaire
                const formData = new FormData(form);
                
                // Envoyer la requête AJAX
                fetch('index.php?page=ajouter_reparation-modern', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok) {
                                // Erreur HTTP avec JSON
                                return Promise.reject({message: data.message || 'Erreur serveur', debug: data});
                            }
                            return data;
                        });
                    } else {
                        // Ce n'est pas du JSON (probablement une erreur PHP fatale qui renvoie du HTML)
                        return response.text().then(text => {
                            return Promise.reject({
                                message: 'Le serveur a renvoyé une réponse invalide (pas de JSON). Cela peut être dû à une erreur PHP.', 
                                debug: text
                            });
                        });
                    }
                })
                .then(data => {
                    loadingOverlay.style.display = 'none';
                    submittingMessage.classList.add('d-none');
                    
                    if (data.success) {
                        // Succès
                        // On peut utiliser une petite notif toast ou rediriger direct
                        // Pour l'instant on garde la redirection directe car ça marche
                        window.location.href = data.redirect_url || 'index.php?page=reparations';
                    } else {
                        // Erreur gérée par le serveur (success: false)
                        showError(data.message || 'Erreur inconnue lors de l\'enregistrement', data);
                    }
                })
                .catch(error => {
                    console.error('Erreur Catch:', error);
                    loadingOverlay.style.display = 'none';
                    submittingMessage.classList.add('d-none');
                    
                    let msg = 'Une erreur technique est survenue.';
                    let dbg = error;
                    
                    if (error.message) msg = error.message;
                    if (error.debug) dbg = error.debug;
                    
                    showError(msg, dbg);
                });
            });
        }
    </script>
</body>
</html>