<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Démarrer la session pour la détection du shop
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Détecter le shop_id depuis le sous-domaine si pas encore défini
if (!isset($_SESSION['shop_id'])) {
    require_once __DIR__ . '/../config/subdomain_config.php';
}

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Initialiser les variables
$resultats = [];
$recherche_effectuee = false;
$message_erreur = '';
$reparation_id = '';
$client_email = '';

// Vérifier que nous avons une connexion valide
if ($shop_pdo === null) {
    $message_erreur = "Service temporairement indisponible. Veuillez réessayer plus tard.";
}

// Vérifier si un ID est fourni dans l'URL - Déplacé en haut pour traitement prioritaire
if (isset($_GET['id']) && !empty($_GET['id']) && $shop_pdo !== null) {
    $reparation_id = cleanInput($_GET['id']);
    $recherche_effectuee = true;
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                   s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                   (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                   (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            LEFT JOIN statuts s ON r.statut = s.code
            LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reparation_id]);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($resultats)) {
            $message_erreur = "Aucune réparation trouvée avec cet identifiant.";
        }
    } catch (PDOException $e) {
        $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
    }
}
// Traiter le formulaire de recherche uniquement si aucun ID n'est présent dans l'URL
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $shop_pdo !== null) {
    $recherche_effectuee = true;
    
    if (isset($_POST['reparation_id']) && !empty($_POST['reparation_id'])) {
        // Recherche par ID de réparation
        $reparation_id = cleanInput($_POST['reparation_id']);
        
        try {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reparation_id]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée avec cet identifiant.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } elseif (isset($_POST['client_email']) && !empty($_POST['client_email'])) {
        // Recherche par email du client
        $client_email = cleanInput($_POST['client_email']);
        
        try {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE c.email = ?
                ORDER BY r.date_reception DESC
            ");
            $stmt->execute([$client_email]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée pour cette adresse email.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez saisir un identifiant de réparation ou une adresse email.";
    }
}

// Fonction pour obtenir le dernier log de réparation
function getDernierLog($reparation_id) {
    global $shop_pdo;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT rl.*, e.full_name as employe_nom 
            FROM reparation_logs rl
            LEFT JOIN employes e ON rl.employe_id = e.id
            WHERE rl.reparation_id = ?
            ORDER BY rl.date_action DESC
            LIMIT 1
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Fonction pour obtenir les photos d'une réparation
function getPhotosReparation($reparation_id) {
    global $shop_pdo;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM photos_reparation
            WHERE reparation_id = ?
            ORDER BY date_upload DESC
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fonction pour obtenir l'historique des statuts
function getHistoriqueStatuts($reparation_id) {
    global $shop_pdo;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT rl.*, e.full_name as employe_nom 
            FROM reparation_logs rl
            LEFT JOIN employes e ON rl.employe_id = e.id
            WHERE rl.reparation_id = ? AND rl.action_type = 'changement_statut'
            ORDER BY rl.date_action DESC
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fonction pour obtenir les devis associés à une réparation
function getDevisReparation($reparation_id) {
    global $shop_pdo;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT d.*, 
                   e.nom as employe_nom, e.prenom as employe_prenom,
                   (SELECT COUNT(*) FROM devis_solutions WHERE devis_id = d.id) as nb_solutions
            FROM devis d
            LEFT JOIN employes e ON d.employe_id = e.id
            WHERE d.reparation_id = ?
            ORDER BY d.date_creation DESC
        ");
        $stmt->execute([$reparation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de réparation - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css for animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --gray-100: #f3f4f6;
            --gray-800: #1f2937;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1200px;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .search-section {
            background: var(--gray-100);
            padding: 2rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-envoye { background: var(--primary-color); color: white; }
        .status-accepte { background: var(--success-color); color: white; }
        .status-refuse { background: var(--danger-color); color: white; }
        .status-expire { background: var(--gray-800); color: white; }
        .status-pending { background: var(--warning-color); color: white; }

        .info-card {
            background: var(--gray-100);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid var(--primary-color);
        }

        .repair-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .repair-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), #6366f1);
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-point {
            position: absolute;
            left: -40px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            top: 5px;
        }

        .timeline-content {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .photos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .photo-item {
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .photo-item:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .devis-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .devis-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .devis-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border-color: var(--primary-color);
        }

        .devis-info h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .devis-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .devis-status.status-brouillon {
            background-color: #6c757d;
            color: white;
        }

        .devis-status.status-envoye {
            background-color: var(--primary-color);
            color: white;
        }

        .devis-status.status-accepte {
            background-color: var(--success-color);
            color: white;
        }

        .devis-status.status-refuse {
            background-color: var(--danger-color);
            color: white;
        }

        .devis-status.status-expire {
            background-color: var(--warning-color);
            color: white;
        }

        .devis-price {
            font-weight: 700;
            color: var(--success-color);
            font-size: 1rem;
        }

        .btn {
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .price-highlight {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .header-section {
                padding: 1.5rem 1rem;
            }
            
            .search-section {
                padding: 1.5rem;
            }
            
            .price-highlight {
                font-size: 1.5rem;
            }
            
            .photos-container {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 10px;
            }
            
            .devis-card {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container fade-in">
            
            <!-- En-tête de la page -->
            <div class="header-section">
                <div class="text-center">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-tools me-3"></i>
                        Suivi de réparation
                    </h1>
                    <p class="mb-0 opacity-90">
                        Consultez l'état d'avancement de votre réparation en temps réel
                    </p>
                </div>
            </div>

            <!-- Section de recherche -->
            <div class="search-section">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reparation_id" class="form-label fw-semibold">
                                <i class="fas fa-hashtag me-2 text-primary"></i>Numéro de réparation
                            </label>
                            <input type="text" class="form-control" id="reparation_id" name="reparation_id" 
                                   placeholder="Ex: 999" value="<?php echo htmlspecialchars($reparation_id); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="client_email" class="form-label fw-semibold">
                                <i class="fas fa-envelope me-2 text-primary"></i>OU votre adresse email
                            </label>
                            <input type="email" class="form-control" id="client_email" name="client_email" 
                                   placeholder="Ex: client@exemple.com" value="<?php echo htmlspecialchars($client_email); ?>">
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-search me-2"></i>Rechercher ma réparation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        
            <!-- Contenu principal -->
            <div class="p-4">
                <?php if ($recherche_effectuee): ?>
                    <?php if (!empty($message_erreur)): ?>
                        <div class="alert alert-warning border-0 rounded-3 shadow-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $message_erreur; ?>
                        </div>
                    <?php elseif (!empty($resultats)): ?>
                        <div class="text-center mb-4">
                            <h3 class="text-primary mb-2">
                                <i class="fas fa-clipboard-list me-2"></i>
                                <?php echo count($resultats) > 1 ? 'Vos réparations' : 'Votre réparation'; ?>
                            </h3>
                            <p class="text-muted">Détails et progression de votre demande</p>
                        </div>
                        
                        <?php foreach ($resultats as $reparation): ?>
                            <div class="repair-card">
                                <!-- En-tête de la réparation -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="text-primary mb-0">
                                        <i class="fas fa-wrench me-2"></i>
                                        Réparation #<?php echo $reparation['id']; ?>
                                    </h4>
                                    <?php
                                    $couleur = !empty($reparation['statut_couleur']) ? $reparation['statut_couleur'] : '#6c757d';
                                    $statut_nom = !empty($reparation['statut_nom']) ? $reparation['statut_nom'] : $reparation['statut'];
                                    ?>
                                    <span class="status-badge" style="background-color: <?php echo $couleur; ?>; color: #fff;">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?php echo htmlspecialchars($statut_nom); ?>
                                    </span>
                                </div>
                                <!-- Informations générales -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h5><i class="fas fa-mobile-alt text-primary me-2"></i>Appareil concerné</h5>
                                            <p class="mb-1">
                                                <strong>
                                                    <?php 
                                                    $appareil_parts = array_filter([
                                                        $reparation['type_appareil'] ?? '',
                                                        $reparation['marque'] ?? '',
                                                        $reparation['modele'] ?? ''
                                                    ]);
                                                    echo htmlspecialchars(implode(' ', $appareil_parts)); 
                                                    ?>
                                                </strong>
                                            </p>
                                            <p class="mb-1">Problème: <?php echo htmlspecialchars($reparation['description_probleme'] ?? ''); ?></p>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar me-1"></i>
                                                Reçu le <?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h5><i class="fas fa-euro-sign text-success me-2"></i>Tarification</h5>
                                            <?php if (!empty($reparation['prix_reparation'])): ?>
                                                <div class="price-highlight">
                                                    <?php echo number_format($reparation['prix_reparation'], 2, ',', ' '); ?> €
                                                </div>
                                                <small class="text-muted">Prix estimé TTC</small>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Prix en cours d'évaluation</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- Progression de la réparation -->
                                <div class="mt-4">
                                    <h4><i class="fas fa-tasks text-primary me-2"></i>Progression</h4>
                                    <div class="timeline">
                                        <?php 
                                        $historique = getHistoriqueStatuts($reparation['id']);
                                        foreach ($historique as $log): 
                                        ?>
                                        <div class="timeline-item">
                                            <div class="timeline-point"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <strong class="text-primary">
                                                        <?php echo date('d/m/Y à H:i', strtotime($log['date_action'])); ?>
                                                    </strong>
                                                </div>
                                                <div class="timeline-text">
                                                    <span class="badge bg-secondary me-2"><?php echo $log['statut_avant'] ? htmlspecialchars($log['statut_avant']) : 'Initial'; ?></span>
                                                    <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                </div>
                                                <?php if (!empty($log['details'])): ?>
                                                <div class="mt-2 text-muted small">
                                                    <?php echo nl2br(htmlspecialchars($log['details'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($historique)): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-point"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <strong class="text-primary">
                                                        <?php echo date('d/m/Y à H:i', strtotime($reparation['date_reception'])); ?>
                                                    </strong>
                                                </div>
                                                <div class="timeline-text">
                                                    <i class="fas fa-box-open me-2 text-success"></i>
                                                    Réception de l'appareil
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                    
                                <?php 
                                $photos = getPhotosReparation($reparation['id']);
                                if (!empty($photos)): 
                                ?>
                                <div class="mt-4">
                                    <h4><i class="fas fa-camera text-primary me-2"></i>Photos</h4>
                                    <div class="photos-container">
                                        <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" data-bs-toggle="modal" data-bs-target="#photoModal<?php echo $reparation['id']; ?>" data-index="<?php echo $index; ?>">
                                            <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="Photo <?php echo $index + 1; ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                    
                                    <!-- Modal pour les photos -->
                                    <div class="modal fade" id="photoModal<?php echo $reparation['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Photos de la réparation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img id="modalImage<?php echo $reparation['id']; ?>" src="" class="img-fluid" alt="Photo agrandie">
                                                    <p id="photoDescription<?php echo $reparation['id']; ?>" class="mt-3"></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                <?php 
                                $devis = getDevisReparation($reparation['id']);
                                if (!empty($devis)): 
                                ?>
                                <div class="mt-4">
                                    <h4><i class="fas fa-file-invoice text-primary me-2"></i>Devis associés</h4>
                                    <div class="devis-container">
                                        <?php foreach ($devis as $devis_item): ?>
                                        <div class="devis-card">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="devis-info flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-file-invoice me-1"></i>
                                                        Devis <?php echo htmlspecialchars($devis_item['numero_devis'] ?? ''); ?>
                                                    </h6>
                                                    <p class="mb-2 text-muted">
                                                        <?php echo htmlspecialchars($devis_item['titre'] ?? ''); ?>
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="devis-status status-<?php echo $devis_item['statut']; ?>">
                                                            <?php
                                                            $status_labels = [
                                                                'brouillon' => 'Brouillon',
                                                                'envoye' => 'Envoyé',
                                                                'accepte' => 'Accepté',
                                                                'refuse' => 'Refusé',
                                                                'expire' => 'Expiré'
                                                            ];
                                                            echo $status_labels[$devis_item['statut']] ?? $devis_item['statut'];
                                                            ?>
                                                        </span>
                                                        <span class="devis-price">
                                                            <?php echo number_format($devis_item['total_ttc'], 2, ',', ' '); ?> €
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?php echo date('d/m/Y', strtotime($devis_item['date_creation'])); ?>
                                                        </small>
                                                        <?php if (!empty($devis_item['lien_securise'])): ?>
                                                        <a href="../pages/devis_client.php?lien=<?php echo htmlspecialchars($devis_item['lien_securise']); ?>" 
                                                           class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-external-link-alt me-1"></i>
                                                            Consulter le devis
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($devis_item['statut'] == 'envoye'): ?>
                                                    <small class="text-info d-block mt-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        En attente de votre réponse
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            
            <!-- Footer -->
            <div class="search-section text-center">
                <p class="text-muted mb-2">
                    <i class="fas fa-phone me-2"></i>
                    Pour toute question concernant votre réparation, n'hésitez pas à nous contacter directement.
                </p>
                <p class="text-muted mb-0">
                    &copy; <?php echo date('Y'); ?> GeekBoard - Tous droits réservés
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des animations AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
        
        // Animation des éléments de la timeline
        const timelineItems = document.querySelectorAll('.timeline-item');
        timelineItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.2}s`;
        });
        
        // Gestion des modals de photos avec animations fluides
        <?php foreach ($resultats as $reparation): ?>
        const photoItems<?php echo $reparation['id']; ?> = document.querySelectorAll('[data-bs-target="#photoModal<?php echo $reparation['id']; ?>"]');
        const modalImage<?php echo $reparation['id']; ?> = document.getElementById('modalImage<?php echo $reparation['id']; ?>');
        const photoDescription<?php echo $reparation['id']; ?> = document.getElementById('photoDescription<?php echo $reparation['id']; ?>');
        
        if (photoItems<?php echo $reparation['id']; ?>.length) {
            const photos<?php echo $reparation['id']; ?> = <?php echo json_encode(getPhotosReparation($reparation['id'])); ?>;
            
            photoItems<?php echo $reparation['id']; ?>.forEach(item => {
                item.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    const photo = photos<?php echo $reparation['id']; ?>[index];
                    
                    modalImage<?php echo $reparation['id']; ?>.style.opacity = '0';
                    modalImage<?php echo $reparation['id']; ?>.src = photo.url;
                    
                    modalImage<?php echo $reparation['id']; ?>.onload = function() {
                        this.style.transition = 'opacity 0.3s ease-in-out';
                        this.style.opacity = '1';
                    };
                    
                    photoDescription<?php echo $reparation['id']; ?>.textContent = photo.description || 'Aucune description disponible';
                });
            });
        }
        <?php endforeach; ?>
        
        // Animation des cartes de réparation
        const repairCards = document.querySelectorAll('.repair-card');
        repairCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.2}s`;
        });
        
        // Validation du formulaire avec animation
        document.querySelector('form').addEventListener('submit', function(e) {
            const repairId = document.getElementById('reparation_id').value.trim();
            const clientEmail = document.getElementById('client_email').value.trim();
            
            if (!repairId && !clientEmail) {
                e.preventDefault();
                
                const inputs = [document.getElementById('reparation_id'), document.getElementById('client_email')];
                inputs.forEach(input => {
                    input.style.transition = 'transform 0.15s ease-in-out';
                    input.style.transform = 'translateX(10px)';
                    setTimeout(() => {
                        input.style.transform = 'translateX(-10px)';
                        setTimeout(() => {
                            input.style.transform = 'translateX(0)';
                        }, 150);
                    }, 150);
                });
                
                alert('Veuillez entrer un numéro de réparation ou une adresse email.');
            }
        });
        
        // Effet de parallaxe sur le header
        const pageHeader = document.querySelector('.page-header');
        if (pageHeader) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                pageHeader.style.transform = `translateY(${scrolled * 0.3}px)`;
            });
        }
    });
    </script>
</body>
</html> 